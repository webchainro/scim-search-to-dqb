<?php

namespace Webchain\ScimFilterToDqb;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
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
use Webchain\ScimFilterToDqb\Model\SearchRequest;
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

    public function __construct(EntityManagerInterface $entityManager, string $primaryEntityClass, StringParser $stringParser = null)
    {
        $this->entityManager = $entityManager;
        $this->stringParser = $stringParser ?? new StringParser();
        $this->primaryEntityClass = $primaryEntityClass;
        $this->joiner = new Joiner($this->entityManager, $primaryEntityClass);
    }

    public function createQueryBuilder(SearchRequest $searchRequest): QueryBuilder
    {
        $this->queryBuilder = $this->entityManager->createQueryBuilder();
        $this->joiner->setQueryBuilder($this->queryBuilder);
        $this->queryBuilder
            ->select(Joiner::PRIMARY_ENTITY_ALIAS)
            ->from($this->primaryEntityClass, Joiner::PRIMARY_ENTITY_ALIAS);

        if ($searchRequest->hasFilter()) {
            $node = $this->stringParser->parse($searchRequest->getFilter());
            $filterBuilder = new FilterBuilder($this->joiner);
            $filterBuilder->addFiltersOnQueryBuilder($this->queryBuilder, $node);
        }

        if ($searchRequest->hasSortBy()) {
            $this->addSortToQueryBuilder($searchRequest);
        }

        if ($searchRequest->hasCount()) {
            $this->queryBuilder->setMaxResults($searchRequest->getCount());
            $this->queryBuilder->setFirstResult($searchRequest->getStartIndex() - 1);
        }

        return clone $this->queryBuilder;
    }

    /**
     * @deprecated See createQueryBuilder
     *
     * @param string $filterString See https://ldapwiki.com/wiki/SCIM%20Filtering
     * @return QueryBuilder
     */
    public function fromScimToQueryBuilder(string $filterString): QueryBuilder
    {
        $this->queryBuilder = $this->entityManager->createQueryBuilder();
        $this->joiner->setQueryBuilder($this->queryBuilder);
        $this->queryBuilder
            ->select(Joiner::PRIMARY_ENTITY_ALIAS)
            ->from($this->primaryEntityClass, Joiner::PRIMARY_ENTITY_ALIAS);

        $node = $this->stringParser->parse($filterString);
        $filterBuilder = new FilterBuilder($this->joiner);
        $filterBuilder->addFiltersOnQueryBuilder($this->queryBuilder, $node);

        return clone $this->queryBuilder;
    }

    /**
     * @param SearchRequest $searchRequest
     */
    private function addSortToQueryBuilder(SearchRequest $searchRequest): void
    {
        $currentAlias = Joiner::PRIMARY_ENTITY_ALIAS;
        $attributePath = $searchRequest->getSortBy();
        $attributesCount = count($attributePath->attributeNames);
        $depth = 0;
        $attribute = '';
        foreach ($attributePath->attributeNames as $key => $attributeName) {
            if ($key + 1 === $attributesCount) {
                $attribute = $attributePath->attributeNames[$key];
                continue;
            }

            $nextAlias = $this->joiner->detectNextAlias($currentAlias, $attributeName, $depth);
            $currentAlias = $nextAlias;
            $depth++;
        }

        $orderMap = [
            SearchRequest::SORT_ASCENDING => 'ASC',
            SearchRequest::SORT_DESCENDING => 'DESC'
        ];

        $this->queryBuilder->orderBy($currentAlias . '.' . $attribute, $orderMap[$searchRequest->getSortOrder()]);
    }
}
