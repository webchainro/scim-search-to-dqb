<?php

namespace WO2\API\Identity\Model\SCIM;

use Assert\Assertion;

class Meta
{
    private $created;
    private $location;

    /**
     * @return \DateTime|null
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * @param \DateTime|null $created
     */
    public function setCreated($created)
    {
        Assertion::nullOrIsInstanceOf($created, \DateTime::class);
        $this->created = $created;
    }

    /**
     * @return null|string
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * @param string $location
     */
    public function setLocation($location)
    {
        Assertion::string($location);
        $this->location = $location;
    }
}
