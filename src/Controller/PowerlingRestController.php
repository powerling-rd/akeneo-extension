<?php

namespace Pim\Bundle\PowerlingBundle\Controller;

use Pim\Bundle\PowerlingBundle\Api\WebApiRepositoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * @author    Arnaud Lejosne <a.lejosne@powerling.com>
 * @copyright 2019 Powerling (https://powerling.com)
*/
class PowerlingRestController
{
    /** @var WebApiRepositoryInterface */
    private $apiRepository;

    public function __construct(WebApiRepositoryInterface $apiRepository)
    {
        $this->apiRepository = $apiRepository;
    }

    public function fetchPowerlingLangAssociations(): JsonResponse
    {
        $langAssociations = $this->apiRepository->getLangAssociations();

        return new JsonResponse($langAssociations);
    }
}
