<?php

namespace Clue\GraphComposer\Graph;

use JMS\Composer\Graph\DependencyEdge;
use JMS\Composer\Graph\DependencyGraph;
use JMS\Composer\Graph\PackageNode;

class Filter
{
    /**
     * Create a filter function considering the input options.
     *
     * If the `callable` returns `true`, the package will be used for the graph.
     *
     * @return callable
     */
    public static function createFilter(array $filters, int $level = 0, bool $withDevPackages = true, bool $strict = false): callable
    {
        return function (DependencyGraph $graph, PackageNode $package, ?DependencyEdge $requires) use ($filters, $level, $withDevPackages, $strict): bool {
            // Filter the dependency edges only.
            if ($requires instanceof DependencyEdge) {
                return (!$requires->isDevDependency() || $withDevPackages)
                    && (!$strict || static::matchFilter($graph, $requires->getDestPackage(), $filters))
                    && (
                        static::matchFilter($graph, $package, $filters)
                        || static::matchLevel($graph, $package, $level)
                    );
            } else {
                $use = static::matchFilter($graph, $package, $filters)
                    || static::matchLevel($graph, $package, $level);
            }

            return $use;
        };
    }

    protected static function matchFilter(DependencyGraph $graph, PackageNode $package, array $filters): bool
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

    protected static function matchLevel(DependencyGraph $graph, PackageNode $package, int $level): bool
    {
        // Filter the package level (unsupported).
        if (0 < $level && $package->hasAttribute('level')) {
            // TODO: Use correct attribute (or inject as attribute).
            return $level < $package->getAttribute('level');
        }

        return false;
    }
}
