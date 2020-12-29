<?php

declare(strict_types=1);

namespace Keboola\GoogleDriveExtractor\Configuration;

use Keboola\Component\Config\BaseConfigDefinition;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

class ConfigDefinition extends BaseConfigDefinition
{
    protected function getParametersDefinition(): ArrayNodeDefinition
    {
        $parametersNode = parent::getParametersDefinition();
        $parametersNode
            ->children()
                ->arrayNode('sheets')
                    ->isRequired()
                    ->prototype('array')
                        ->children()
                            ->integerNode('id')
                                ->isRequired()
                                ->min(0)
                            ->end()
                            ->scalarNode('fileId')
                                ->isRequired()
                                ->cannotBeEmpty()
                            ->end()
                            ->scalarNode('fileTitle')
                                ->isRequired()
                                ->cannotBeEmpty()
                            ->end()
                            ->scalarNode('sheetId')
                                ->isRequired()
                                ->cannotBeEmpty()
                            ->end()
                            ->scalarNode('sheetTitle')
                                ->isRequired()
                                ->cannotBeEmpty()
                            ->end()
                            ->scalarNode('outputTable')
                                ->isRequired()
                                ->cannotBeEmpty()
                            ->end()
                            ->arrayNode('header')
                                ->addDefaultsIfNotSet()
                                ->children()
                                    ->integerNode('rows')
                                        ->defaultValue(1)
                                    ->end()
                                    ->arrayNode('columns')
                                        ->prototype('scalar')
                                        ->end()
                                    ->end()
                                    ->arrayNode('transpose')
                                        ->children()
                                            ->integerNode('row')
                                            ->end()
                                            ->scalarNode('name')
                                            ->end()
                                        ->end()
                                    ->end()
                                    ->booleanNode('sanitize')
                                    ->end()
                                ->end()
                            ->end()
                            ->arrayNode('transform')
                                ->children()
                                    ->arrayNode('transpose')
                                        ->children()
                                            ->scalarNode('from')
                                            ->end()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $parametersNode;
    }
}
