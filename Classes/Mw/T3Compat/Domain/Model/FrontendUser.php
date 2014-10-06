<?php
namespace Mw\T3Compat\Domain\Model;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Mw.T3Compat".           *
 *                                                                        *
 * (C) 2014 Martin Helmich <m.helmich@mittwald.de>                        *
 *          Mittwald CM Service GmbH & Co. KG                             *
 *                                                                        */


use Doctrine\ORM\Mapping as ORM;
use Mw\T3Compat\Exception\IncompatibilityException;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Security\Account;
use TYPO3\Party\Domain\Model\Person;


/**
 * @Flow\Entity
 */
class FrontendUser
{



    /**
     * @var Account
     * @ORM\OneToOne
     */
    protected $account;


    /**
     * @var string
     */
    protected $address;


    /**
     * @var string
     */
    protected $zip;


    /**
     * @var string
     */
    protected $city;


    /**
     * @var string
     */
    protected $country;


    /**
     * @var string
     */
    protected $www;


    /**
     * @var string
     */
    protected $company;


    /**
     * @var string
     */
    protected $image;


    /**
     * @var string
     */
    protected $telephone;


    /**
     * @var string
     */
    protected $fax;



    static public function fromAccount(Account $account)
    {
        $user          = new FrontendUser();
        $user->account = $account;
    }



    public function getUsername()
    {
        return $this->account->getAccountIdentifier();
    }



    public function setUsername($username)
    {
        $this->account->setAccountIdentifier($username);
    }



    public function setName($name)
    {
        $parts = explode(' ', $name);

        if (preg_match(',(Herr|Frau|Mr|Ms|Prof|Dr|Ph.D|Dipl),', $parts[0]))
        {
            $this->setTitle(array_shift($parts));
        }

        if (count($parts) === 2)
        {
            $this->setFirstName($parts[0]);
            $this->setLastName($parts[1]);
        }
        else if (count($parts) === 3)
        {
            $this->setFirstName($parts[0]);
            $this->setMiddleName($parts[1]);
            $this->setLastName($parts[2]);
        }
        else
        {
            throw new IncompatibilityException('Don\'t know what to do with name "' . $name . '".');
        }
    }



    /**
     * @return Person
     */
    private function getPerson()
    {
        if ($this->account->getParty() instanceof Person)
        {
            return $this->account->getParty();
        }
        return NULL;
    }



    public function getName()
    {
        return $this->getPerson() ? $this->getPerson()->getName()->getFullName() : NULL;
    }



    public function setFirstName($firstName)
    {
        if ($this->getPerson())
        {
            $this->getPerson()->getName()->setFirstName($firstName);
        }
    }



    public function getFirstName()
    {
        return $this->getPerson() ? $this->getPerson()->getName()->getFirstName() : NULL;
    }



    public function setMiddleName($middleName)
    {
        if ($this->getPerson())
        {
            $this->getPerson()->getName()->setMiddleName($middleName);
        }
    }



    public function getMiddleName()
    {
        return $this->getPerson() ? $this->getPerson()->getName()->getMiddleName() : NULL;
    }



    public function setLastName($lastName)
    {
        if ($this->getPerson())
        {
            $this->getPerson()->getName()->setLastName($lastName);
        }
    }



    public function getLastName()
    {
        return $this->getPerson() ? $this->getPerson()->getName()->getLastName() : NULL;
    }



    public function setTitle($title)
    {
        if ($this->getPerson())
        {
            $this->getPerson()->getName()->setTitle($title);
        }
    }



    /**
     * @param string $address
     */
    public function setAddress($address)
    {
        $this->address = $address;
    }



    /**
     * @param string $telephone
     */
    public function setTelephone($telephone)
    {
        $this->telephone = $telephone;
    }



    public function getTitle()
    {
        return $this->getPerson() ? $this->getPerson()->getName()->getTitle() : NULL;
    }



    public function getAddress()
    {
        return $this->address;
    }



    public function getTelephone()
    {
        return $this->telephone;
    }



    /**
     * @return string
     */
    public function getCity()
    {
        return $this->city;
    }



    /**
     * @param string $city
     */
    public function setCity($city)
    {
        $this->city = $city;
    }



    /**
     * @return string
     */
    public function getCompany()
    {
        return $this->company;
    }



    /**
     * @param string $company
     */
    public function setCompany($company)
    {
        $this->company = $company;
    }



    /**
     * @return string
     */
    public function getCountry()
    {
        return $this->country;
    }



    /**
     * @param string $country
     */
    public function setCountry($country)
    {
        $this->country = $country;
    }



    /**
     * @return string
     */
    public function getFax()
    {
        return $this->fax;
    }



    /**
     * @param string $fax
     */
    public function setFax($fax)
    {
        $this->fax = $fax;
    }



    /**
     * @return string
     */
    public function getImage()
    {
        return $this->image;
    }



    /**
     * @param string $image
     */
    public function setImage($image)
    {
        $this->image = $image;
    }



    /**
     * @return string
     */
    public function getWww()
    {
        return $this->www;
    }



    /**
     * @param string $www
     */
    public function setWww($www)
    {
        $this->www = $www;
    }



    /**
     * @return string
     */
    public function getZip()
    {
        return $this->zip;
    }



    /**
     * @param string $zip
     */
    public function setZip($zip)
    {
        $this->zip = $zip;
    }



}