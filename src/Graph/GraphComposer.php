<?php

namespace Clue\GraphComposer\Graph;

use Clue\GraphComposer\Composer\LevelAwareDependencyAnalyzer;
use Fhaculty\Graph\Graph;
use Fhaculty\Graph\Attribute\AttributeAware;
use Fhaculty\Graph\Attribute\AttributeBagNamespaced;
use Graphp\GraphViz\GraphViz;

class GraphComposer
{
    private $layoutVertex = array(
        'fillcolor' => '#eeeeee',
        'style' => 'filled, rounded',
        'shape' => 'box',
        'fontcolor' => '#314B5F'
    );

    private $layoutVertexRoot = array(
        'style' => 'filled, rounded, bold'
    );

    private $layoutEdge = array(
        'fontcolor' => '#767676',
        'fontsize' => 10,
        'color' => '#1A2833'
    );

    private $layoutEdgeDev = array(
        'style' => 'dashed'
    );

    private $dependencyGraph;

    /**
     * @var GraphViz
     */
    private $graphviz;

    public function __construct(string $dir, ?GraphViz $graphviz = null)
    {
        if ($graphviz === null) {
            $graphviz = new GraphViz();
            $graphviz->setFormat('svg');
        }

        // Use custom implementation to support levels:
        $analyzer = new LevelAwareDependencyAnalyzer();

        $this->dependencyGraph = $analyzer->analyze($dir);
        $this->graphviz = $graphviz;
    }

    /**
     * @return Graph
     */
    public function createGraph(?callable $filter = null)
    {
        $graph = new Graph();

        foreach ($this->dependencyGraph->getPackages() as $package) {
            if (\is_callable($filter) && !$filter($this->dependencyGraph, $package, null)) {
                continue;
            }

            $name = $package->getName();
            $start = $graph->createVertex($name, true);

            $label = $name;
            if ($package->getVersion() !== null) {
                $label .= ': ' . $package->getVersion();
            }

            $this->setLayout($start, array('label' => $label) + $this->layoutVertex);

            foreach ($package->getOutEdges() as $requires) {
                if (\is_callable($filter) && !$filter($this->dependencyGraph, $package, $requires)) {
                    continue;
                }

                $targetName = $requires->getDestPackage()->getName();
                $target = $graph->createVertex($targetName, true);

                $label = $requires->getVersionConstraint();

                $edge = $start->createEdgeTo($target);
                $this->setLayout($edge, array('label' => $label) + $this->layoutEdge);

                if ($requires->isDevDependency()) {
                    $this->setLayout($edge, $this->layoutEdgeDev);
                }
            }
        }

        $root = $graph->getVertex($this->dependencyGraph->getRootPackage()->getName());
        $this->setLayout($root, $this->layoutVertexRoot);

        if (\is_callable($filter)) {
            $unassignedVertices = $graph->getVertices()
                ->getVerticesMatch(static function ($vertex): bool {
                    return 0 === \count($vertex->getEdges());
                });

            foreach ($unassignedVertices as $vertex) {
                $graph->removeVertex($vertex);
            }
        }

        return $graph;
    }

    /**
     * @return AttributeAware
     */
    private function setLayout(AttributeAware $entity, array $layout)
    {
        $bag = new AttributeBagNamespaced($entity->getAttributeBag(), 'graphviz.');
        $bag->setAttributes($layout);

        return $entity;
    }

    /**
     * @return void
     */
    public function displayGraph(?callable $filter = null)
    {
        $graph = $this->createGraph($filter);

        $this->graphviz->display($graph);
    }

    /**
     * @return string
     */
    public function getImagePath(?callable $filter = null)
    {
        $graph = $this->createGraph($filter);

        return $this->graphviz->createImageFile($graph);
    }

    /**
     * @return static
     */
    public function setFormat($format)
    {
        $this->graphviz->setFormat($format);

        return $this;
    }
}
