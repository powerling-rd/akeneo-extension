<?php

namespace Pim\Bundle\PowerlingBundle\Project\Model;

/**
 * Powerling Project.
 * These features could be added to base library later on.
 *
 * @author    Arnaud Lejosne <a.lejosne@powerling.com>
 * @copyright 2019 Powerling (https://powerling.com)
*/
interface ProjectInterface
{
    /**
     * Get project's documents statuses
     *
     * @return int[]
     */
    public function getDocumentsStatuses();

    /**
     * Get project's status
     *
     * @return int[]
     */
    public function getStatus();
}
