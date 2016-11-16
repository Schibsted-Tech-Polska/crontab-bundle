<?php

namespace Stp\CrontabBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Dependency injection
 */
class Configuration implements ConfigurationInterface
{
    /**
     * Get config tree builder
     *
     * @return TreeBuilder
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('stp_crontab');

        $rootNode->children()
            // logger's name
            ->scalarNode('logger_name')
                ->defaultValue('monolog.logger.command')
            ->end()
            // limit of processes with the same command line /the same type/
            ->integerNode('processes_limit')
                ->defaultValue(1)
            ->end()
            // worker sleep - lower value saves your CPU load, higher value gives you more frequent output from processes
            ->integerNode('worker_sleep')
                ->defaultValue(5)
            ->end()
            // worker step - SHOULDN'T be changed for anything other than default value /60/,
            // because we try to mimics original 60 seconds crontab's granularity
            ->integerNode('worker_step')
                ->defaultValue(60)
            ->end()
        ->end()
        ;

        return $treeBuilder;
    }
}
