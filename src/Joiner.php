<?php

namespace Webchain\ScimFilterToDqb;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Webchain\ScimFilterToDqb\Model\Join;

class Joiner
{
    const PRIMARY_ENTITY_ALIAS = 'sftdp';
    const JOINS_ALIAS_SUFFIX = 'sftdj';
    /** @var EntityManagerInterface */
    private $entityManager;
    /** @var QueryBuilder */
    private $queryBuilder;
    /** @var Join[]  */
    private $mapByDepth = [];
    /** @var Join[]  */
    private $mapByAlias = [];

    public function __construct(EntityManagerInterface $entityManager, string $primaryEntityClass)
    {
        $this->entityManager = $entityManager;
        $join = new Join(
            self::PRIMARY_ENTITY_ALIAS,
            $entityManager->getMetadataFactory()->getMetadataFor($primaryEntityClass)
        );

        $this->mapByDepth[self::PRIMARY_ENTITY_ALIAS] = $join;
        $this->mapByAlias[self::PRIMARY_ENTITY_ALIAS] = $join;
    }

    public function detectNextAlias(string $currentAlias, $columnName, int $depth = 0): string
    {
        $id = $currentAlias . '.' . $columnName . $depth;
        if (isset($this->mapByDepth[$id])) {
            return $this->mapByDepth[$id]->getAlias();
        }

        $currentJoin = $this->mapByAlias[$currentAlias];

        $nextAlias = self::JOINS_ALIAS_SUFFIX . count($this->mapByDepth);
        $classMetadata = $currentJoin->getClassMetadata();
        $this->queryBuilder->leftJoin($currentAlias . '.' . $columnName, $nextAlias);
        $targetEntity = $classMetadata->getAssociationTargetClass($columnName);
        $targetClassMetadata = $this->entityManager->getMetadataFactory()->getMetadataFor($targetEntity);
        $join = new Join($nextAlias, $targetClassMetadata);
        $this->mapByDepth[$id] = $join;
        $this->mapByAlias[$nextAlias] = $join;
        $currentJoin->joinWith($columnName, $join);

        return $nextAlias;
    }

    public function hasAliasJoined(string $alias): bool
    {
        return isset($this->mapByAlias[$alias]);
    }

    public function getJoinByAlias(string $alias): Join
    {
        return $this->mapByAlias[$alias];
    }

    /**
     * @param QueryBuilder $queryBuilder
     */
    public function setQueryBuilder(QueryBuilder $queryBuilder): void
    {
        $this->queryBuilder = $queryBuilder;

        $this->mapByDepth = [self::PRIMARY_ENTITY_ALIAS => $this->mapByDepth[self::PRIMARY_ENTITY_ALIAS]];
        $this->mapByAlias = [self::PRIMARY_ENTITY_ALIAS => $this->mapByAlias[self::PRIMARY_ENTITY_ALIAS]];
    }
}
