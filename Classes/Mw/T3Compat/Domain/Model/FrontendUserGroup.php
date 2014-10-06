<?php
namespace Mw\T3Compat\Domain\Model;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Mw.T3Compat".           *
 *                                                                        *
 *                                                                        */

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;

/**
 * @Flow\Entity
 */
class FrontendUserGroup
{



    /**
     * @var string
     */
    protected $title;


    /**
     * @var string
     */
    protected $description;


    /**
     * @var FrontendUserGroup
     * @ORM\ManyToOne(inversedBy="subgroups")
     */
    protected $parent;

    /**
     * @var Collection<FrontendUserGroup>
     * @ORM\OneToMany(mappedBy="parent")
     */
    protected $subgroups;



    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }



    /**
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
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
     * @return Collection<FrontendUserGroup>
     */
    public function getSubgroup()
    {
        return $this->subgroups;
    }



    /**
     * @param Collection $subgroups
     */
    public function setSubgroup(Collection $subgroups)
    {
        $this->subgroups = $subgroups;
    }



    public function addSubgroup(FrontendUserGroup $subgroup)
    {
        $this->subgroups->add($subgroup);
    }



    public function removeSubgroup(FrontendUserGroup $subgroup)
    {
        $this->subgroups->removeElement($subgroup);
    }



}