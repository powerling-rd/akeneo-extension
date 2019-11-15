<?php

namespace Pim\Bundle\PowerlingBundle\Api;

use Pim\Bundle\PowerlingBundle\Project\ProjectInterface;

/**
 * Calls to Powerling php API
 *
 * @author    Arnaud Lejosne <a.lejosne@powerling.com>
 * @copyright 2019 Powerling (https://powerling.com)
*/
interface WebApiRepositoryInterface
{
    /**
     * @param ProjectInterface $project
     * @return array
     */
    public function sendProjectDocuments(ProjectInterface $project);

    /**
     * @param array $data
     *
     * @return array
     */
    public function createProject(array $data);

    /**
     * @param string $projectCode
     */
    public function getDocuments($projectCode);
}
