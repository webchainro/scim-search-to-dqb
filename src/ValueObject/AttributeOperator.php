<?php

namespace Webchain\ScimFilterToDqb\ValueObject;

use Assert\Assertion;

/**
 * @see https://tools.ietf.org/html/rfc7644#section-3.4.2.2
 *
 * Class AttributeOperator
 * @package Webchain\ScimFilterToDqb\ValueObject
 */
class AttributeOperator
{
    const EQUAL = 'eq';
    const NOT_EQUAL = 'ne';
    const CONSTAINS = 'co';
    const STARTS_WITH = 'sw';
    const ENDS_WITH = 'ew';
    const PRESENT = 'pr';
    const GREATER_THAN = 'gt';
    const GREATER_THAN_OR_EQUAL = 'ge';
    const LESS_THAN = 'lt';
    const LESS_THAN_OR_EQUAL = 'le';

    /** @var string */
    private $operator;

    public function __construct(string $operator)
    {
        Assertion::choice($operator, $this->getAvailableOperators(), 'Operator is not a SCIM attribute operator');

        $this->operator = $operator;
    }

    /**
     * @return string
     */
    public function getOperator(): string
    {
        return $this->operator;
    }

    public function getAvailableOperators(): array
    {
        return [
            self::EQUAL,
            self::NOT_EQUAL,
            self::CONSTAINS,
            self::STARTS_WITH,
            self::ENDS_WITH,
            self::PRESENT,
            self::GREATER_THAN,
            self::GREATER_THAN_OR_EQUAL,
            self::LESS_THAN,
            self::LESS_THAN_OR_EQUAL
        ];
    }

    public function __toString()
    {
        return $this->getOperator();
    }
}
