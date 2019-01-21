<?php

namespace Webchain\ScimFilterToDql;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Composite;
use Doctrine\ORM\QueryBuilder;
use Tmilos\ScimFilterParser\Ast\AttributePath;
use Tmilos\ScimFilterParser\Ast\ComparisonExpression;
use Tmilos\ScimFilterParser\Ast\Conjunction;
use Tmilos\ScimFilterParser\Ast\Disjunction;
use Tmilos\ScimFilterParser\Ast\Negation;
use Tmilos\ScimFilterParser\Ast\Node;
use Tmilos\ScimFilterParser\Ast\ValuePath;
use Tmilos\ScimFilterParser\Parser as StringParser;

class Parser
{
    const PRIMARY_ENTITY_ALIAS = 'sftdp';
    const JOINS_ALIAS_SUFFIX = 'sftdj';
    /** @var EntityManagerInterface */
    private $entityManager;
    /** @var StringParser */
    private $stringParser;
    /** @var string */
    private $primaryEntityClass;
    /** @var QueryBuilder */
    private $queryBuilder;
    /** @var int  */
    private $joinsCounter = 0;

    private $builderParameters = [];

    public function __construct(EntityManagerInterface $entityManager, string $primaryEntityClass)
    {
        $this->entityManager = $entityManager;
        $this->stringParser = new StringParser();
        $this->primaryEntityClass = $primaryEntityClass;
    }

    /**
     * @param string $filterString See https://ldapwiki.com/wiki/SCIM%20Filtering
     * @return QueryBuilder
     */
    public function fromScimToQueryBuilder(string $filterString): QueryBuilder
    {
        $this->queryBuilder = $this->entityManager->createQueryBuilder();
        $node = $this->stringParser->parse($filterString);

        $this->queryBuilder
            ->select(self::PRIMARY_ENTITY_ALIAS)
            ->from($this->primaryEntityClass, self::PRIMARY_ENTITY_ALIAS);
        $predicates = $this->buildPredicatesRecursively($node);
        $this->queryBuilder
            ->where($predicates)
            ->setParameters($this->builderParameters);

        return clone $this->queryBuilder;
    }

    private function buildPredicatesRecursively(Node $node, $negation = false, $currentAlias = self::PRIMARY_ENTITY_ALIAS)
    {
        if ($node instanceof Negation) {
            return $this->buildPredicatesRecursively($node, true, $currentAlias);
        }

        if($node instanceof Conjunction) {
            return $this->fromConjunctionToComposite($node, $negation, $currentAlias);
        }

        if($node instanceof Disjunction) {
            return $this->fromDisjunctionToComposite($node, $negation, $currentAlias);
        }

        if ($node instanceof ValuePath) {
            return $this->fromValuePathToCondition($node, $negation, $currentAlias);
        }

        if ($node instanceof ComparisonExpression) {
            return $this->fromComparisonExpressionToCondition($node, $negation, $currentAlias);
        }

        throw new \InvalidArgumentException('Node type not recognized');
    }

    /**
     * @param Conjunction $conjunction
     * @param $negation
     * @param $currentAlias
     * @return mixed
     */
    private function fromConjunctionToComposite(Conjunction $conjunction, bool $negation, string $currentAlias): Composite
    {
        $arguments = [];
        foreach ($conjunction->getFactors() as $factor) {
            $arguments[] = $this->buildPredicatesRecursively($factor, $negation, $currentAlias);
        }

        $methodName = 'andX';
        if ($negation) {
            $methodName = 'orX';
        }

        return call_user_func_array([$this->queryBuilder->expr(), $methodName], $arguments);
    }

    /**
     * @param Disjunction $disjunction
     * @param $negation
     * @param $currentAlias
     */
    private function fromDisjunctionToComposite(Disjunction $disjunction, bool $negation, string $currentAlias): Composite
    {
        $arguments = [];
        foreach ($disjunction->getTerms() as $term) {
            $arguments[] = $this->buildPredicatesRecursively($term, $negation, $currentAlias);
        }

        $methodName = 'orX';
        if ($negation) {
            $methodName = 'andX';
        }

        return call_user_func_array([$this->queryBuilder->expr(), $methodName], $arguments);
    }

    /**
     * @param ValuePath $node
     * @param $negation
     * @param $currentAlias
     */
    private function fromValuePathToCondition(ValuePath $node, bool $negation, string $currentAlias)
    {
        $array = $node->dump();
        /** @var AttributePath $attributePath */
        $attributePath = $array['ValuePath'][0]['AttributePath'];
        $attributesCount = count($attributePath->attributeNames);

        if ($attributesCount === 1) {
            $nextAlias = self::JOINS_ALIAS_SUFFIX . $this->joinsCounter;
            $this->queryBuilder->leftJoin($currentAlias . '.' . $attributePath->attributeNames[0], $nextAlias);
            $this->joinsCounter++;

            return $this->buildPredicatesRecursively($array['ValuePath'][1], $negation, $nextAlias);
        }

        foreach ($attributePath->attributeNames as $key => $attributeName) {
            $nextAlias = self::JOINS_ALIAS_SUFFIX . $this->joinsCounter;
            $this->queryBuilder->leftJoin($currentAlias . '.' . $attributeName, $nextAlias);

            $this->joinsCounter++;
            $currentAlias = $nextAlias;
        }

        return $this->buildPredicatesRecursively($array['ValuePath'][1], $negation, $nextAlias);
    }

    /**
     * @param Node $node
     * @param $negation
     * @param $currentAlias
     */
    private function fromComparisonExpressionToCondition(ComparisonExpression $node, bool $negation, string $currentAlias)
    {
        $attributesCount = count($node->attributePath->attributeNames);
        if ($attributesCount === 1) {
            $attributeName = $currentAlias . '.' . $node->attributePath->attributeNames[0];
            return $this->buildDqlCondition($node, $attributeName, $negation);
        }

        $lastIndex = $attributesCount - 1;
        foreach ($node->attributePath->attributeNames as $key => $attributeName) {
            if ($key === $lastIndex) {
                continue;
            }

            $nextAlias = self::JOINS_ALIAS_SUFFIX . $this->joinsCounter;
            $this->queryBuilder->leftJoin($currentAlias . '.' . $attributeName, $nextAlias);

            $this->joinsCounter++;
            $currentAlias = $nextAlias;
        }

        $attributeName = $currentAlias . '.' . $node->attributePath->attributeNames[$lastIndex];

        return $this->buildDqlCondition($node, $attributeName, $negation);
    }

    private function buildDqlCondition(ComparisonExpression $comparisonExpression, string $attributeName, bool $negation)
    {
        $condition = null;
        $nextParameterIndex = count($this->builderParameters) + 1;
        $compareValue = $comparisonExpression->compareValue;
        $expression = $this->queryBuilder->expr();

        switch ($comparisonExpression->operator) {
            case 'eq':
                if ($negation) {
                    $condition = $expression->eq($attributeName, "?$nextParameterIndex");
                } else {
                    $condition = $expression->neq($attributeName, "?$nextParameterIndex");
                }

                $this->builderParameters[$nextParameterIndex] = $compareValue;
                break;
            case 'co':
                if ($negation) {
                    $condition = $expression->notLike($attributeName, $expression->literal('%' . $compareValue .'%'));
                } else {
                    $condition = $expression->like($attributeName, $expression->literal('%' . $compareValue .'%'));
                }
                break;
            case 'sw':
                if ($negation) {
                    $condition = $expression->notLike($attributeName, $expression->literal($compareValue .'%'));
                } else {
                    $condition = $expression->like($attributeName, $expression->literal($compareValue .'%'));
                }
                break;
            case 'pr':
                if ($negation) {
                    $condition = $expression->isNull($attributeName);
                } else {
                    $condition = $expression->isNotNull($attributeName);
                }
                break;
            case 'gt':
                if ($negation) {
                    $condition = $expression->lte($attributeName, "?$nextParameterIndex");
                } else {
                    $condition = $expression->gt($attributeName, "?$nextParameterIndex");
                }
                $this->builderParameters[$nextParameterIndex] = $compareValue;
                break;
            case 'ge':
                if ($negation) {
                    $condition = $expression->lt($attributeName, "?$nextParameterIndex");
                } else {
                    $condition = $expression->gte($attributeName, "?$nextParameterIndex");
                }
                $this->builderParameters[$nextParameterIndex] = $compareValue;
                break;
            case 'lt':
                if ($negation) {
                    $condition = $expression->gte($attributeName, "?$nextParameterIndex");
                } else {
                    $condition = $expression->lt($attributeName, "?$nextParameterIndex");
                }
                $this->builderParameters[$nextParameterIndex] = $compareValue;
                break;
            case 'le':
                if ($negation) {
                    $condition = $expression->gt($attributeName, "?$nextParameterIndex");
                } else {
                    $condition = $expression->lte($attributeName, "?$nextParameterIndex");
                }
                $this->builderParameters[$nextParameterIndex] = $compareValue;
                break;
            default:
                throw new \InvalidArgumentException('Operator ' . $comparisonExpression->operator . ' not recognized by SCIM. See https://ldapwiki.com/wiki/SCIM%20Filtering');
        }

        return $condition;
    }
}
