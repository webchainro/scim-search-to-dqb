<?php

namespace Webchain\ScimFilterToDqb\Tests\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="meta")
 *
 * @ORM\Entity
 */
class Name
{
    /**
     * @var string
     *
     * @ORM\Column(name="family_name", type="string", nullable=false)
     */
    protected $familyName;
    /**
     * @var string
     *
     * @ORM\Column(name="given_name", type="string", nullable=false)
     */
    protected $givenName;
    /**
     * @var User
     * @ORM\OneToOne(targetEntity="User", mappedBy="name")
     */
    private $user;
    /**
     * @var int
     * @ORM\Id
     * @ORM\Column(name="id", type="int", nullable=false)
     */
    private $id;

    /**
     * @return string
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
        $this->familyName = $familyName;
    }

    /**
     * @return string
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
        $this->givenName = $givenName;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param User $user
     */
    public function setUser($user)
    {
        $this->user = $user;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }
}
