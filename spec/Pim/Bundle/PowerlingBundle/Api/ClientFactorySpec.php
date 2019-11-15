<?php

namespace spec\Pim\Bundle\PowerlingBundle\Api;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use PhpSpec\ObjectBehavior;
use Powerling\HttpClient\HttpClient;

/**
 * @author    Arnaud Lejosne <a.lejosne@powerling.com>
 * @copyright 2019 Powerling (https://powerling.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ClientFactorySpec extends ObjectBehavior
{
    function let(ConfigManager $configManager)
    {
        $configManager->get('pim_powerling.api_key')->willReturn('fookey');
        $this->beConstructedWith($configManager);
    }

    function it_can_create_http_client()
    {
        $this->createHttpClient([])->shouldBeAnInstanceOf(HttpClient::class);
    }
}
