<?php

namespace Pim\Bundle\PowerlingBundle\Project;

/**
 * PIM Project entity interface
 *
 * @author    Arnaud Lejosne <a.lejosne@powerling.com>
 * @copyright 2019 Powerling (https://powerling.com)
*/
interface ProjectInterface
{
    /**
     * @return int
     */
    public function getId();

    /**
     * @param int $id
     */
    public function setId($id);

    /**
     * @return string
     */
    public function getCode();

    /**
     * @param string $code
     */
    public function setCode($code);

    /**
     * @return string
     */
    public function getName();

    /**
     * @param string $name
     */
    public function setName($name);

    /**
     * @return string
     */
    public function getLangAssociationId();

    /**
     * @param string $langsId
     */
    public function setLangAssociationId($langsId);

    /**
     * @return \DateTime
     */
    public function getUpdatedAt();

    public function setUpdatedAt();

    /**
     * @return string
     */
    public function getUsername();

    /**
     * @param string $username
     */
    public function setUsername($username);

    /**
     * @return array
     */
    public function getDocuments();

    /**
     * @param array $documents
     */
    public function setDocuments($documents);

    /**
     * @param string[] $document
     */
    public function addDocument($document);
}
