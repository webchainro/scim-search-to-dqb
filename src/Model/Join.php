<?php

namespace Webchain\ScimFilterToDqb\Model;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;

class Join
{
    /** @var string */
    private $alias;
    /** @var ClassMetadata */
    private $classMetadata;
    /** @var Join[]  */
    private $joinedWith = [];

    public function __construct(string $alias, ClassMetadata $classMetadata)
    {
        $this->alias = $alias;
        $this->classMetadata = $classMetadata;
    }

    /**
     * @return string
     */
    public function getAlias(): string
    {
        return $this->alias;
    }

    /**
     * @return ClassMetadata
     */
    public function getClassMetadata(): ClassMetadata
    {
        return $this->classMetadata;
    }

    public function joinWith(string $columnName, Join $join)
    {
        $this->joinedWith[$columnName] = $join;
    }

    public function hasColumnJoined(string $columnName): bool
    {
        return isset($this->joinedWith[$columnName]);
    }

    public function getJoinedWithByColumn($columnName):? Join
    {
        if (!$this->hasColumnJoined($columnName)) {
            return null;
        }

        return $this->joinedWith[$columnName];
    }
}
