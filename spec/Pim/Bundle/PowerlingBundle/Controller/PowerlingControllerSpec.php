<?php

namespace spec\Pim\Bundle\PowerlingBundle\Controller;

use PhpSpec\ObjectBehavior;
use Pim\Bundle\PowerlingBundle\Api\WebApiRepositoryInterface;

/**
 * @author    Arnaud Lejosne <a.lejosne@powerling.com>
 * @copyright 2019 Powerling (https://powerling.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ClientFactorySpec extends ObjectBehavior
{
    function let(WebApiRepositoryInterface $apiRepository)
    {
        $this->beConstructedWith($apiRepository);
    }
}
