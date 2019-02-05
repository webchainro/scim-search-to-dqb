<?php

namespace Webchain\ScimFilterToDqb\ValueObject;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;

class Join
{
    /** @var string */
    private $id;
    /** @var string */
    private $alias;
    /** @var ClassMetadata */
    private $classMetadata;

    public function __construct(string $id, string $alias, ClassMetadata $classMetadata)
    {
        $this->id = $id;
        $this->alias = $alias;
        $this->classMetadata = $classMetadata;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
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
}
