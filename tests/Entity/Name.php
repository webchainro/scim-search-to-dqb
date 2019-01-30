<?php

namespace WO2\API\Identity\Model\SCIM;

use Assert\Assertion;

class FullName
{
    protected $familyName;
    protected $givenName;

    /**
     * @return string|null
     */
    public function getFamilyName()
    {
        return $this->familyName;
    }

    /**
     * @param string $familyName
     */
    public function setFamilyName($familyName)
    {
        Assertion::string($familyName);
        $this->familyName = $familyName;
    }

    /**
     * @return string|null
     */
    public function getGivenName()
    {
        return $this->givenName;
    }

    /**
     * @param string $givenName
     */
    public function setGivenName($givenName)
    {
        Assertion::string($givenName);
        $this->givenName = $givenName;
    }
}
