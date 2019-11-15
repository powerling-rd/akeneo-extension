<?php

namespace Pim\Bundle\PowerlingBundle\DependencyInjection;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * @author    Arnaud Lejosne <a.lejosne@powerling.com>
 * @copyright 2019 Powerling (https://powerling.com)
*/
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();

        $rootNode = $treeBuilder->root('powerling');

        $rootNode->children()
            ->scalarNode('api_key')->end()
            ->scalarNode('attributes')->end()
            ->end();

        $rootNode->end();

        SettingsBuilder::append(
            $rootNode,
            [
                'api_key' => ['value' => null],
                'attributes' => ['value' => null],
            ]
        );

        return $treeBuilder;
    }
}
