<?php

namespace Webchain\ScimFilterToDqb;

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
use Webchain\ScimFilterToDqb\ValueObject\AttributeOperator;

class Parser
{
    /** @var EntityManagerInterface */
    private $entityManager;
    /** @var StringParser */
    private $stringParser;
    /** @var string */
    private $primaryEntityClass;
    /** @var QueryBuilder */
    private $queryBuilder;
    /** @var Joiner  */
    private $joiner;

    private $builderParameters = [];

    public function __construct(EntityManagerInterface $entityManager, string $primaryEntityClass, StringParser $stringParser = null)
    {
        $this->entityManager = $entityManager;
        $this->stringParser = $stringParser ?? new StringParser();
        $this->primaryEntityClass = $primaryEntityClass;
        $this->joiner = new Joiner($this->entityManager, $primaryEntityClass);
    }

    /**
     * @param string $filterString See https://ldapwiki.com/wiki/SCIM%20Filtering
     * @return QueryBuilder
     */
    public function fromScimToQueryBuilder(string $filterString): QueryBuilder
    {
        $this->queryBuilder = $this->entityManager->createQueryBuilder();
        $this->joiner->setQueryBuilder($this->queryBuilder);
        $node = $this->stringParser->parse($filterString);

        $this->queryBuilder
            ->select(Joiner::PRIMARY_ENTITY_ALIAS)
            ->from($this->primaryEntityClass, Joiner::PRIMARY_ENTITY_ALIAS);
        $predicates = $this->buildPredicatesRecursively($node);
        $this->queryBuilder
            ->where($predicates)
            ->setParameters($this->builderParameters);

        return clone $this->queryBuilder;
    }

    private function buildPredicatesRecursively(Node $node, $negation = false, $currentAlias = Joiner::PRIMARY_ENTITY_ALIAS, int $depth = 0)
    {
        if ($node instanceof Negation) {
            return $this->buildPredicatesRecursively($node->getFilter(), true, $currentAlias, $depth + 1);
        }

        if($node instanceof Conjunction) {
            return $this->fromConjunctionToComposite($node, $negation, $currentAlias, $depth);
        }

        if($node instanceof Disjunction) {
            return $this->fromDisjunctionToComposite($node, $negation, $currentAlias, $depth);
        }

        if ($node instanceof ValuePath) {
            return $this->fromValuePathToCondition($node, $negation, $currentAlias, $depth);
        }

        if ($node instanceof ComparisonExpression) {
            return $this->fromComparisonExpressionToCondition($node, $negation, $currentAlias, $depth);
        }

        throw new \InvalidArgumentException('Node type not recognized');
    }

    /**
     * @param Conjunction $conjunction
     * @param $negation
     * @param $currentAlias
     * @return mixed
     */
    private function fromConjunctionToComposite(Conjunction $conjunction, bool $negation, string $currentAlias, int $depth): Composite
    {
        $arguments = [];
        foreach ($conjunction->getFactors() as $factor) {
            $arguments[] = $this->buildPredicatesRecursively($factor, $negation, $currentAlias, $depth + 1);
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
    private function fromDisjunctionToComposite(Disjunction $disjunction, bool $negation, string $currentAlias, int $depth): Composite
    {
        $arguments = [];
        foreach ($disjunction->getTerms() as $term) {
            $arguments[] = $this->buildPredicatesRecursively($term, $negation, $currentAlias, $depth + 1);
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
    private function fromValuePathToCondition(ValuePath $node, bool $negation, string $currentAlias, int $depth)
    {
        $attributePath = $node->getAttributePath();
        $attributesCount = count($attributePath->attributeNames);

        if ($attributesCount === 1) {
            $nextAlias = $this->joiner->detectNextAlias($currentAlias, $attributePath->attributeNames[0], $depth);

            return $this->buildPredicatesRecursively($node->getFilter(), $negation, $nextAlias, $depth + 1);
        }

        foreach ($attributePath->attributeNames as $key => $attributeName) {
            $nextAlias = $this->joiner->detectNextAlias($currentAlias, $attributeName, $depth);
            $currentAlias = $nextAlias;
        }

        return $this->buildPredicatesRecursively($node->getFilter(), $negation, $nextAlias, $depth + 1);
    }

    /**
     * @param Node $node
     * @param $negation
     * @param $currentAlias
     */
    private function fromComparisonExpressionToCondition(ComparisonExpression $node, bool $negation, string $currentAlias, int $depth)
    {
        $attributesCount = count($node->attributePath->attributeNames);
        if ($attributesCount === 1) {
            return $this->buildDqlCondition($node, $currentAlias, $node->attributePath->attributeNames[0], $negation, $depth);
        }

        $lastIndex = $attributesCount - 1;
        foreach ($node->attributePath->attributeNames as $key => $attributeName) {
            if ($key === $lastIndex) {
                continue;
            }

            $nextAlias = $this->joiner->detectNextAlias($currentAlias, $attributeName, $depth);

            $currentAlias = $nextAlias;
        }

        $columnName = $node->attributePath->attributeNames[$lastIndex];

        return $this->buildDqlCondition($node, $currentAlias, $columnName, $negation, $depth);
    }

    private function buildDqlCondition(ComparisonExpression $comparisonExpression, string $alias, string $columnsName, bool $negation, int $depth)
    {
        $condition = null;
        $nextParameterIndex = count($this->builderParameters) + 1;
        $compareValue = $comparisonExpression->compareValue;

        $classMetadata = $this->joiner->getJoinByAlias($alias)->getClassMetadata();
        if ($classMetadata->isCollectionValuedAssociation($columnsName)) {
            $nextAlias = $this->joiner->detectNextAlias($alias, $columnsName, $depth);
            $attributePath = new AttributePath();
            /**
             * If the specified attribute in a filter expression is a multi-valued
             * attribute, the filter matches if any of the values of the specified
             * attribute match the specified criterion; e.g., if a User has multiple
             * "emails" values, only one has to match for the entire User to match.
             * For complex attributes, a fully qualified sub-attribute MUST be
             * specified using standard attribute notation, see https://tools.ietf.org/html/rfc7644#page-22
             */
            $attributePath->add('value');
            $newExpression = new ComparisonExpression($attributePath, $comparisonExpression->operator, $compareValue);

            return $this->buildDqlCondition($newExpression, $nextAlias, 'value', $negation, $depth + 1);
        }

        $x = $alias . '.' . $columnsName;

        $expression = $this->queryBuilder->expr();

        switch ($comparisonExpression->operator) {
            case AttributeOperator::EQUAL:
                if ($negation) {
                    $condition = $expression->neq($x, "?$nextParameterIndex");
                } else {
                    $condition = $expression->eq($x, "?$nextParameterIndex");
                }

                $this->builderParameters[$nextParameterIndex] = $compareValue;
                break;
            case AttributeOperator::NOT_EQUAL:
                if ($negation) {
                    $condition = $expression->eq($x, "?$nextParameterIndex");
                } else {
                    $condition = $expression->neq($x, "?$nextParameterIndex");
                }

                $this->builderParameters[$nextParameterIndex] = $compareValue;
                break;
            case AttributeOperator::CONSTAINS:
                if ($negation) {
                    $condition = $expression->notLike($x, $expression->literal('%' . $compareValue .'%'));
                } else {
                    $condition = $expression->like($x, $expression->literal('%' . $compareValue .'%'));
                }
                break;
            case AttributeOperator::STARTS_WITH:
                if ($negation) {
                    $condition = $expression->notLike($x, $expression->literal($compareValue .'%'));
                } else {
                    $condition = $expression->like($x, $expression->literal($compareValue .'%'));
                }
                break;
            case AttributeOperator::PRESENT:
                if ($negation) {
                    $condition = $expression->isNull($x);
                } else {
                    $condition = $expression->isNotNull($x);
                }
                break;
            case AttributeOperator::GREATER_THAN:
                if ($negation) {
                    $condition = $expression->lte($x, "?$nextParameterIndex");
                } else {
                    $condition = $expression->gt($x, "?$nextParameterIndex");
                }
                $this->builderParameters[$nextParameterIndex] = $compareValue;
                break;
            case AttributeOperator::GREATER_THAN_OR_EQUAL:
                if ($negation) {
                    $condition = $expression->lt($x, "?$nextParameterIndex");
                } else {
                    $condition = $expression->gte($x, "?$nextParameterIndex");
                }
                $this->builderParameters[$nextParameterIndex] = $compareValue;
                break;
            case AttributeOperator::LESS_THAN:
                if ($negation) {
                    $condition = $expression->gte($x, "?$nextParameterIndex");
                } else {
                    $condition = $expression->lt($x, "?$nextParameterIndex");
                }
                $this->builderParameters[$nextParameterIndex] = $compareValue;
                break;
            case AttributeOperator::LESS_THAN_OR_EQUAL:
                if ($negation) {
                    $condition = $expression->gt($x, "?$nextParameterIndex");
                } else {
                    $condition = $expression->lte($x, "?$nextParameterIndex");
                }
                $this->builderParameters[$nextParameterIndex] = $compareValue;
                break;
            default:
                throw new \InvalidArgumentException('Operator ' . $comparisonExpression->operator . ' not recognized by SCIM. See https://tools.ietf.org/html/rfc7644#page-17');
        }

        return $condition;
    }
}
