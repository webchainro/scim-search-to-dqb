<?php

namespace WO2\API\Identity\Model\SCIM;

use Assert\Assertion;

class Email
{
    private $value;
    private $type;
    private $primary;
    
    /**
     * @return string|null
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param string $value
     */
    public function setValue($value)
    {
        Assertion::string($value);
        $this->value = $value;
    }

    /**
     * @return string|null
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType($type)
    {
        Assertion::string($type);
        $this->type = $type;
    }

    /**
     * @return bool|null
     */
    public function isPrimary()
    {
        return $this->primary;
    }

    /**
     * @param bool $primary
     */
    public function setPrimary($primary)
    {
        Assertion::boolean($primary);
        $this->primary = $primary;
    }
}
