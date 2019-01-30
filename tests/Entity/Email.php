<?php

namespace Webchain\ScimFilterToDqb\Tests\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="email")
 *
 * @ORM\Entity
 */
class Email
{
    /**
     * @var string
     *
     * @ORM\Column(name="value", type="string", nullable=false)
     */
    private $value;
    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", nullable=false)
     */
    private $type;
    /**
     * @var bool
     *
     * @ORM\Column(name="primary", type="boolean", nullable=false)
     */
    private $primary;

    /**
     * @ORM\ManyToOne(targetEntity="User", inversedBy="emails")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
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
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param string $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

    /**
     * @return string
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
        $this->type = $type;
    }

    /**
     * @return bool
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
        $this->primary = $primary;
    }

    /**
     * @return mixed
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param mixed $user
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
