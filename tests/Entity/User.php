<?php

namespace Webchain\ScimFilterToDqb\Tests\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="user")
 *
 * @ORM\Entity
 */
class User
{
    /**
     * @var Meta
     *
     * @ORM\OneToOne(targetEntity="Meta", inversedBy="cart")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false)
     */
    private $meta;
    /**
     * @var Name
     *
     * @ORM\OneToOne(targetEntity="Name", inversedBy="user")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false)
     */
    private $name;
    /**
     * @var string
     *
     * @ORM\Column(name="username", type="string", nullable=false)
     */
    private $userName;
    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", nullable=false)
     */
    private $title;
    /**
     * @var string
     *
     * @ORM\Column(name="user_type", type="string", nullable=false)
     */
    private $userType;
    /**
     * @var Email[]
     * @ORM\OneToMany(targetEntity="Email", mappedBy="user")
     */
    private $emails = [];
    /**
     * @var PhoneNumber[]
     * @ORM\OneToMany(targetEntity="PhoneNumber", mappedBy="user")
     */
    private $phoneNumbers = [];
    /**
     * @var InstantMessaging[]
     * @ORM\OneToMany(targetEntity="InstantMessaging", mappedBy="user")
     */
    private $ims = [];
    /**
     * @var int
     * @ORM\Id
     * @ORM\Column(name="id", type="int", nullable=false)
     */
    private $id;

    /**
     * @return Meta
     */
    public function getMeta()
    {
        return $this->meta;
    }

    /**
     * @param Meta $meta
     */
    public function setMeta($meta)
    {
        $this->meta = $meta;
    }

    /**
     * @return Name
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param Name $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getUserName()
    {
        return $this->userName;
    }

    /**
     * @param string $userName
     */
    public function setUserName($userName)
    {
        $this->userName = $userName;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * @return string
     */
    public function getUserType()
    {
        return $this->userType;
    }

    /**
     * @param string $userType
     */
    public function setUserType($userType)
    {
        $this->userType = $userType;
    }

    /**
     * @return Email[]
     */
    public function getEmails()
    {
        return $this->emails;
    }

    /**
     * @param Email[] $emails
     */
    public function setEmails($emails)
    {
        $this->emails = $emails;
    }

    /**
     * @return PhoneNumber[]
     */
    public function getPhoneNumbers()
    {
        return $this->phoneNumbers;
    }

    /**
     * @param PhoneNumber[] $phoneNumbers
     */
    public function setPhoneNumbers($phoneNumbers)
    {
        $this->phoneNumbers = $phoneNumbers;
    }

    /**
     * @return InstantMessaging[]
     */
    public function getIms(): array
    {
        return $this->ims;
    }

    /**
     * @param InstantMessaging[] $ims
     */
    public function setIms(array $ims): void
    {
        $this->ims = $ims;
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
