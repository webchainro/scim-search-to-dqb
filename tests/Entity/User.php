<?php

namespace WO2\API\Identity\Model\SCIM;

use Assert\Assertion;

class User
{
    const GENDER_FEMALE = 'female';
    const GENDER_MALE = 'male';

    private $meta;
    private $name;
    private $userName;
    private $emails = [];
    private $addresses = [];
    private $phoneNumbers = [];
    private $groups = [];
    private $nifId;
    private $id;
    private $birthDate;
    private $gender;

    /**
     * @return Meta|null
     */
    public function getMeta()
    {
        return $this->meta;
    }

    /**
     * @param Meta $meta
     */
    public function setMeta(Meta $meta)
    {
        $this->meta = $meta;
    }

    /**
     * @return FullName|null
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param FullName $name
     */
    public function setName(FullName $name)
    {
        $this->name = $name;
    }

    /**
     * @return string|null
     */
    public function getUserName()
    {
        return $this->userName;
    }

    /**
     * @param string|null $userName
     */
    public function setUserName($userName)
    {
        Assertion::nullOrString($userName);
        $this->userName = $userName;
    }

    /**
     * @param Address $address
     */
    public function addAddress(Address $address)
    {
        $this->addresses[] = $address;
    }

    /**
     * @return Address[]
     */
    public function getAddresses()
    {
        return $this->addresses;
    }

    /**
     * @param Address[] $addresses
     */
    public function setAddresses(array $addresses)
    {
        Assertion::allIsInstanceOf($addresses, Address::class);
        $this->addresses = $addresses;
    }

    /**
     * @param Email $email
     */
    public function addEmail(Email $email)
    {
        $this->emails[] = $email;
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
    public function setEmails(array $emails)
    {
        Assertion::allIsInstanceOf($emails, Email::class);
        $this->emails = $emails;
    }

    /**
     * @param PhoneNumber $phoneNumber
     */
    public function addPhoneNumber(PhoneNumber $phoneNumber)
    {
        $this->phoneNumbers[] = $phoneNumber;
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
    public function setPhoneNumbers(array $phoneNumbers)
    {
        Assertion::allIsInstanceOf($phoneNumbers, PhoneNumber::class);
        $this->phoneNumbers = $phoneNumbers;
    }

    /**
     * @param Membership $group
     */
    public function addGroup(Membership $group)
    {
        $this->groups[] = $group;
    }

    /**
     * @return Membership[]
     */
    public function getGroups()
    {
        return $this->groups;
    }

    /**
     * @param Membership[] $groups
     */
    public function setGroups(array $groups)
    {
        Assertion::allIsInstanceOf($groups, Membership::class);
        $this->groups = $groups;
    }

    /**
     * @return int
     */
    public function getNifId()
    {
        return $this->nifId;
    }

    /**
     * @param int|null $nifId
     */
    public function setNifId($nifId)
    {
        Assertion::nullOrInteger($nifId);
        $this->nifId = $nifId;
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
        Assertion::greaterThan($id, 0);
        $this->id = $id;
    }

    /**
     * @return \DateTime|null
     */
    public function getBirthDate()
    {
        return $this->birthDate;
    }

    /**
     * @param \DateTime|null $birthDate
     */
    public function setBirthDate($birthDate)
    {
        Assertion::nullOrIsInstanceOf($birthDate, \DateTime::class);
        $this->birthDate = $birthDate;
    }

    /**
     * @return string|null
     */
    public function getGender()
    {
        return $this->gender;
    }

    /**
     * @param string|null $gender
     */
    public function setGender($gender)
    {
        Assertion::nullOrChoice($gender, [self::GENDER_FEMALE, self::GENDER_MALE]);
        $this->gender = $gender;
    }
}
