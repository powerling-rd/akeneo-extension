<?php

namespace Pim\Bundle\PowerlingBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Inject the extension settings in the configuration controller.
 *
 * @author    Arnaud Lejosne <a.lejosne@powerling.com>
 * @copyright 2019 Powerling (https://powerling.com)
*/
class OroConfigCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        $configManagerDefinition = $container->findDefinition('oro_config.global');
        $settings = $configManagerDefinition->getArguments()[1];

        $diExtensionName = 'pim_powerling';
        $bundleSettings = $settings[$diExtensionName];

        $configControllerDefinition = $container->findDefinition('oro_config.controller.configuration');
        $arguments = $configControllerDefinition->getArguments();
        $options = $arguments[3];

        foreach ($bundleSettings as $name => $value) {
            $options[] = [
                'section' => $diExtensionName,
                'name'    => $name,
            ];
        }
        $arguments[3] = $options;
        $configControllerDefinition->setArguments($arguments);
    }
}
