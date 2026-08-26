<?php

declare(strict_types=1);

namespace Clue\GraphComposer\Composer;

use JMS\Composer\DependencyAnalyzer;
use JMS\Composer\Graph\DependencyGraph;
use JMS\Composer\Graph\PackageNode;

class LevelAwareDependencyAnalyzer extends DependencyAnalyzer
{
    /**
     * @param array $composerJsonData
     * @param array $composerLockData
     * @param string $dir
     *
     * @throws \RuntimeException
     * @return \JMS\Composer\Graph\DependencyGraph
     */
    public function analyzeComposerData($composerJsonData, $composerLockData = null, $dir = null)
    {
        $graph = parent::analyzeComposerData($composerJsonData, $composerLockData, $dir);

        $this->calculateLevels($graph, $graph->getRootPackage()->getName());

        return $graph;
    }

    /**
     * Provision all package's minimum possible level.
     */
    protected function calculateLevels(DependencyGraph $graph, string $name, int $packageLevel = 0, array & $stack = []): void
    {
        $package = $graph->getPackage($name);

        // Package is not available, skip.
        if ( ! $package instanceof PackageNode) {
            return;
        }

        // Package has circular dependency and was already visited in this stack.
        if (\in_array($package->getName(), $stack, true)) {
            return;
        }

        // Ensure the lowest possible level is set as attribute of the package node.
        $level = $package->hasAttribute('level')
            ? (int) $package->getAttribute('level')
            : null;
        if (null === $level) {
            $package->setAttribute('level', (string) $packageLevel);
        } else {
            $package->setAttribute('level', (string) \min($packageLevel, $level));
        }

        ++$packageLevel;
        $isDevDependency = $package->hasAttribute('isDevDependency')
            ? (bool) $package->getAttribute('isDevDependency')
            : false;

        \array_push($stack, $package->getName());
        foreach ($package->getOutEdges() as $requires) {
            $destPackage = $requires->getDestPackage();
            $destPackage->setAttribute('isDevDependency', (string) ($isDevDependency || $requires->isDevDependency()));

            $this->calculateLevels($graph, $destPackage->getName(), $packageLevel, $stack);
        }
        \array_pop($stack);
    }
}
