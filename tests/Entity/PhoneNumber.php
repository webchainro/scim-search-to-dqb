<?php

namespace WO2\API\Identity\Model\SCIM;

use Assert\Assertion;

class PhoneNumber
{
    const TYPE_MOBILE = 'mobile';
    const TYPE_HOME = 'home';
    const TYPE_WORK = 'work';
    private $type;
    private $value;

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
        Assertion::choice($type, [self::TYPE_HOME, self::TYPE_MOBILE, self::TYPE_WORK]);
        $this->type = $type;
    }

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
}
