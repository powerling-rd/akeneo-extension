<?php

namespace Pim\Bundle\PowerlingBundle\Entity;

use Pim\Bundle\PowerlingBundle\Project\ProjectInterface;

/**
 * Project entity
 *
 * @author    Arnaud Lejosne <a.lejosne@powerling.com>
 * @copyright 2019 Powerling (https://powerling.com)
*/
class Project implements ProjectInterface
{
    /** @var int */
    private $id;

    /** @var string */
    private $code;

    /** @var string */
    private $name;

    /** @var \DateTime */
    private $updatedAt;

    /** @var string */
    private $username;

    /** @var string */
    private $langAssociationId;

    /** @var array */
    private $documents;

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getCode()
    {
        return $this->code;
    }

    public function setCode($code)
    {
        $this->code = $code;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt()
    {
        $this->updatedAt = new \DateTime();
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function setUsername($username)
    {
        $this->username = $username;
    }

    public function getLangAssociationId()
    {
        return $this->langAssociationId;
    }

    public function setLangAssociationId($langAssociation)
    {
        $this->langAssociationId = $langAssociation;
    }

    public function getDocuments()
    {
        return $this->documents;
    }

    public function setDocuments($documents)
    {
        $this->documents = $documents;
    }

    public function addDocument($document)
    {
        $this->documents[] = $document;
    }
}
