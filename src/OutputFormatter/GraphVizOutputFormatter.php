<?php

namespace DependencyTracker\OutputFormatter;

use DependencyTracker\ClassLayerMap;
use DependencyTracker\DependencyResult;
use DependencyTracker\Event\Visitor\FoundDependencyEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class GraphVizOutputFormatter implements OutputFormatterInterface
{
    protected $eventDispatcher;

    public function getName()
    {
        return 'graphviz';
    }

    public function finish(DependencyResult $dependencyResult)
    {
        $layersDependOnLayers = [];

        foreach ($dependencyResult->getDependencies() as $dependency) {

            $layersA = $dependencyResult->getLayersByClassName($dependency->getClassA());
            $layersB = $dependencyResult->getLayersByClassName($dependency->getClassB());

            if (empty($layersB)) {
                continue;
            }

            foreach ($layersA as $layerA) {

                if (!isset($layersDependOnLayers[$layerA])) {
                    $layersDependOnLayers[$layerA] = [];
                }

                $layersDependOnLayers[$layerA] = array_values(
                    array_unique(array_merge($layersB, $layersDependOnLayers[$layerA]))
                );
            }
        }

        // refactor to multiple methods

        $graph = new \Fhaculty\Graph\Graph();
        $vertices = [];

        // create a vertice for every layer
        foreach ($layersDependOnLayers as $layer => $layersDependOn) {

            if (!isset($vertices[$layer])) {
                $vertices[$layer] = $graph->createVertex($layer);
            }

            foreach($layersDependOn as $layerDependOn) {
                if (!isset($vertices[$layerDependOn])) {
                    $vertices[$layerDependOn] = $graph->createVertex($layerDependOn);
                }
            }

        }

        // createEdges
        foreach ($layersDependOnLayers as $layer => $layersDependOn) {
            foreach ($layersDependOn as $layerDependOn) {
                $vertices[$layer]->createEdgeTo($vertices[$layerDependOn]);
            }
        }

        $graphviz = new \Graphp\GraphViz\GraphViz();
        $graphviz->display($graph);
    }

}