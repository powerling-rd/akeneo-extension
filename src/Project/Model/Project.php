<?php

namespace Pim\Bundle\PowerlingBundle\Project\Model;

/**
 * Powerling Project.
 * These features could be added to base library later on.
 *
 * @author    Arnaud Lejosne <a.lejosne@powerling.com>
 * @copyright 2019 Powerling (https://powerling.com)
*/
class Project implements ProjectInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDocumentsStatuses()
    {
       # return $this->getProperty('documents_statuses');
    }

    public function getStatus()
    {

    }
}
