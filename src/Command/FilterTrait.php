<?php

namespace Clue\GraphComposer\Command;

use JMS\Composer\Graph\DependencyEdge;
use JMS\Composer\Graph\DependencyGraph;
use JMS\Composer\Graph\PackageNode;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

trait FilterTrait
{
    /**
     * Create a filter function considering the input options.
     *
     * If the `callable` returns `true`, the package will be used for the graph.
     */
    protected function createFilter(InputInterface $input, OutputInterface $output): callable
    {
        $filters = (array)$input->getOption('filter');
        $level = \max(0, (int)$input->getOption('level'));
        $withDevPackages = (bool)$input->getOption('dev');

        return function (DependencyGraph $graph, PackageNode $package, ?DependencyEdge $requires) use ($filters, $level, $withDevPackages, $output): bool {
            // Filter the dependency edges only.
            if ($requires instanceof DependencyEdge) {
                return (!$requires->isDevDependency() || $withDevPackages)
                    && (
                        $this->matchFilter($graph, $package, $filters)
                        || $this->matchLevel($graph, $package, $level)
                    );
            } else {
                $use = $this->matchFilter($graph, $package, $filters)
                    || $this->matchLevel($graph, $package, $level);
            }

            if (!$use) {
                $output->writeln(
                    \sprintf('* Filtered %s "%s".', $requires instanceof DependencyEdge ? 'edge' : 'package', $package->getName()), OutputInterface::VERBOSITY_VERY_VERBOSE
                );
            }

            return $use;
        };
    }

    protected function matchFilter(DependencyGraph $graph, PackageNode $package, array $filters): bool
    {
        $packageName = $package->getName();

        // Always keep the root package.
        if ($graph->getRootPackage()->getName() === $packageName) {
            return true;
        }

        // Filter the package name pattern.
        foreach ($filters as $filter) {
            if (\fnmatch($filter, $packageName, \FNM_PATHNAME | \FNM_CASEFOLD)) {
                return true;
            }
        }

        return false;
    }

    protected function matchLevel(DependencyGraph $graph, PackageNode $package, int $level): bool
    {
        // Filter the package level (unsupported).
        if (0 < $level && $package->hasAttribute('level')) {
            // TODO: Use correct attribute (or inject as attribute).
            return $level < $package->getAttribute('level');
        }

        return false;
    }
}
