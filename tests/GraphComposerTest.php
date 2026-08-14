<?php

use Clue\GraphComposer\Command\Export;
use Clue\GraphComposer\Command\FilterTrait;
use Clue\GraphComposer\Graph\GraphComposer;
use Fhaculty\Graph\Graph;
use Graphp\GraphViz\GraphViz;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

class GraphVizMockDisplay extends GraphViz
{
    public $called = 0;
    public function display(Graph $graph)
    {
        ++$this->called;
    }
}

class GraphVizMockCreateImageFile extends GraphViz
{
    public $called = 0;
    public function createImageFile(Graph $graph)
    {
        return 'test' . ++$this->called . '.png';
    }
}

class GraphVizMockSetFormat extends GraphViz
{
    public $called = null;
    public function setFormat($format)
    {
        $this->called = $format;
    }
}

class PackageFilterFunctionFactory
{
    use FilterTrait;

    public function getFilter(array $params, Command $cmd): callable
    {
        return $this->createFilter(
            new ArrayInput($params, $cmd->getDefinition()),
            new NullOutput(),
        );
    }
}

class GraphComposerTest extends TestCase
{
    public function testCreateGraph()
    {
        $dir = __DIR__ . '/../';

        $graphComposer = new GraphComposer($dir);
        $graph = $graphComposer->createGraph();

        $this->assertInstanceOf('Fhaculty\Graph\Graph', $graph);
        $this->assertTrue(count($graph->getVertices()) > 0);
    }

    public function testFilterGraph()
    {
        $dir = __DIR__ . '/../';

        $filter = (new PackageFilterFunctionFactory)
            ->getFilter([
                '--filter' => 'clue/graph-composer',
                '--no-dev' => true,
            ], new Export);

        $graphComposer = new GraphComposer($dir);
        $graph = $graphComposer->createGraph($filter);

        $this->assertInstanceOf('Fhaculty\Graph\Graph', $graph);
        $this->assertTrue(count($graph->getVertices()) > 0);
        // if not filtered, symfony/filesystem would be available and phpunit/phpunit is dev
        $this->assertFalse($graph->hasVertex('symfony/string'));
        $this->assertFalse($graph->hasVertex('phpunit/phpunit'));
        // root package is always available
        $this->assertTrue($graph->hasVertex('clue/graph-composer'));
    }

    public function testFilterDevGraph()
    {
        $dir = __DIR__ . '/../';

        $filter = (new PackageFilterFunctionFactory)
            ->getFilter([
                '--filter' => 'clue/graph-composer',
                '--dev' => true,
            ], new Export);

        $graphComposer = new GraphComposer($dir);
        $graph = $graphComposer->createGraph($filter);

        $this->assertInstanceOf('Fhaculty\Graph\Graph', $graph);
        $this->assertTrue(count($graph->getVertices()) > 0);
        // if not filtered, symfony/filesystem would be available and phpunit/phpunit is dev
        $this->assertFalse($graph->hasVertex('symfony/string'));
        $this->assertTrue($graph->hasVertex('phpunit/phpunit'));
        // root package is always available
        $this->assertTrue($graph->hasVertex('clue/graph-composer'));
    }

    public function testDisplayGraphCallsDisplayGraphViz()
    {
        $dir = __DIR__ . '/../';

        // mocking with PHP 7.4 reports error with legacy PHPUnit, create manual mock classes instead
        $graphviz = new GraphVizMockDisplay();

        $graphComposer = new GraphComposer($dir, $graphviz);
        $graphComposer->displayGraph();

        $this->assertEquals(1, $graphviz->called);
    }

    public function testGetImagePathWillCreateTemporaryImageFileViaGraphViz()
    {
        $dir = __DIR__ . '/../';

        // mocking with PHP 7.4 reports error with legacy PHPUnit, create manual mock classes instead
        $graphviz = new GraphVizMockCreateImageFile();

        $graphComposer = new GraphComposer($dir, $graphviz);
        $ret = $graphComposer->getImagePath();

        $this->assertEquals('test1.png', $ret);
    }

    public function testSetFormatWillSetFormatOnGraphViz()
    {
        $dir = __DIR__ . '/../';

        // mocking with PHP 7.4 reports error with legacy PHPUnit, create manual mock classes instead
        $graphviz = new GraphVizMockSetFormat();

        $graphComposer = new GraphComposer($dir, $graphviz);
        $ret = $graphComposer->setFormat('gif');

        $this->assertEquals($graphComposer, $ret);
        $this->assertEquals('gif', $graphviz->called);
    }
}
