<?php

namespace M6Web\Bundle\AmqpBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\NodeBuilder;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode    = $treeBuilder->root('m6_web_amqp');
        $rootNode
            ->children()
                ->booleanNode('debug')->defaultValue('%kernel.debug%')->end()
                ->booleanNode('event_dispatcher')->defaultTrue()->end()
            ->end();

        $this->addConnections($rootNode);
        $this->addProducers($rootNode);
        $this->addConsumers($rootNode);

        return $treeBuilder;
    }

    protected function addConnections(ArrayNodeDefinition $node)
    {
        $node
            ->children()
                ->arrayNode('connections')
                    ->useAttributeAsKey('key')
                    ->canBeUnset()
                    ->prototype('array')
                        ->children()
                            ->scalarNode('class')->defaultValue('%m6_web_amqp.connection.class%')->end()
                            ->scalarNode('host')->defaultValue('localhost')->end()
                            ->scalarNode('port')->defaultValue(5672)->end()
                            ->scalarNode('timeout')->defaultValue(10)->end()
                            ->scalarNode('login')->defaultValue('guest')->end()
                            ->scalarNode('password')->defaultValue('guest')->end()
                            ->scalarNode('vhost')->defaultValue('/')->end()
                            ->booleanNode('lazy')->defaultFalse()->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    protected function addProducers(ArrayNodeDefinition $node)
    {
        $node
            ->children()
                ->arrayNode('producers')
                    ->canBeUnset()
                    ->useAttributeAsKey('key')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('class')->defaultValue('%m6_web_amqp.producer.class%')->end()
                            ->scalarNode('connection')->defaultValue('default')->end()
                            ->append($this->exchangeOptions())
                            ->arrayNode('queue_options')
                                ->addDefaultsIfNotSet()
                                ->children()
                                    // base info
                                    ->scalarNode('name')->end()

                                    // flags
                                    ->booleanNode('passive')->defaultFalse()->end()
                                    ->booleanNode('durable')->defaultTrue()->end()
                                    ->booleanNode('auto_delete')->defaultFalse()->end()

                                    // args
                                    ->arrayNode('arguments')
                                        ->prototype('scalar')->end()
                                        ->defaultValue(array())
                                        ->normalizeKeys(false)
                                    ->end()

                                    // binding
                                    ->arrayNode('routing_keys')
                                        ->prototype('scalar')->end()
                                        ->defaultValue(array())
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    protected function addConsumers(ArrayNodeDefinition $node)
    {
        $node
            ->children()
                ->arrayNode('consumers')
                    ->canBeUnset()
                    ->useAttributeAsKey('key')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('class')->defaultValue('%m6_web_amqp.consumer.class%')->end()
                            ->scalarNode('connection')->defaultValue('default')->end()
                            ->scalarNode('callback')->info('service id that implements CallbackInterface and to be called when consume() message called w/o parameters')->end()
                            ->append($this->exchangeOptions())
                            ->arrayNode('queue_options')
                                ->children()
                                    // base
                                    ->scalarNode('name')->isRequired()->end()

                                    // flags
                                    ->booleanNode('passive')->defaultFalse()->end()
                                    ->booleanNode('durable')->defaultTrue()->end()
                                    ->booleanNode('exclusive')->defaultFalse()->end()
                                    ->booleanNode('auto_delete')->defaultFalse()->end()

                                    // args
                                    ->arrayNode('arguments')
                                        ->prototype('scalar')->end()
                                        ->defaultValue(array())
                                        ->normalizeKeys(false)
                                    ->end()

                                    // binding
                                    ->arrayNode('routing_keys')
                                        ->prototype('scalar')->end()
                                        ->defaultValue(array())
                                    ->end()
                                ->end()
                            ->end()

                            ->arrayNode('qos_options')
                                ->addDefaultsIfNotSet()
                                ->children()
                                    ->integerNode('prefetch_size')->defaultValue(0)->end()
                                    ->integerNode('prefetch_count')->defaultValue(0)->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    private function exchangeOptions()
    {
        $builder = new NodeBuilder();
        return $builder
            ->arrayNode('exchange_options')
                ->children()
                    // base info
                    ->scalarNode('name')->isRequired()->end()
                    ->scalarNode('type')
                        ->info('Set the type of the exchange. If exchange already exist - you can skip it. Otherwise behavior is unpredictable')
                    ->end()

                    // flags
                    ->booleanNode('passive')->defaultFalse()->end()
                    ->booleanNode('durable')->defaultTrue()->end()
                    ->booleanNode('auto_delete')->defaultFalse()->end()

                    // args
                    ->arrayNode('arguments')
                        ->prototype('scalar')->end()
                        ->defaultValue(array())
                        ->normalizeKeys(false)
                    ->end()

                    // binding
                    ->arrayNode('routing_keys')
                        ->prototype('scalar')->end()
                        ->defaultValue(array())
                    ->end()

                    // default message attributes
                    ->arrayNode('publish_attributes')
                        ->prototype('scalar')->end()
                        ->defaultValue(array())
                    ->end()
                ->end();
            //last end is missed here intentionally because arrayNode doesn't have an actual parent
    }
}
