<?php

namespace App\MediaBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link https://symfony.com/doc/current/bundles/configuration.html}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('site_media');

        // Symfony 6+ requires this to use getRootNode instead of root()
        $rootNode = $treeBuilder->getRootNode();

        // Define your configuration parameters here, for example:
        // $rootNode
        //     ->children()
        //         ->scalarNode('example')->defaultValue('default_value')->end()
        //     ->end();

        return $treeBuilder;
    }
}
