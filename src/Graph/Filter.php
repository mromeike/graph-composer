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
        return function (DependencyGraph $graph, PackageNode $package, ?DependencyEdge $requires) use ($filters, $level, $withDevPackages, $strict): bool
        {
            $isDevDependency = $package->hasAttribute('isDevDependency')
                && (bool) $package->getAttribute('isDevDependency');

            // Filter the dependency edges only.
            if ($requires instanceof DependencyEdge) {
                $isDevDependency = $requires->getDestPackage()
                        ->hasAttribute('isDevDependency')
                    && (bool)$requires->getDestPackage()
                        ->getAttribute('isDevDependency');

                $use = (!$isDevDependency || $withDevPackages)
                    && (!$strict || static::matchFilter($graph, $requires->getDestPackage(), $filters))
                    && (
                        static::matchLevel($graph, $package, $level)
                        || static::matchFilter($graph, $package, $filters)
                    );
            } else {
                // Always keep the root package.
                if ($graph->getRootPackage()->getName() === $package->getName()) {
                    return true;
                }

                $use = (!$isDevDependency || $withDevPackages)
                    && (
                        static::matchLevel($graph, $package, $level)
                        || static::matchFilter($graph, $package, $filters)
                    );
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

        return empty($filters);
    }

    protected static function matchLevel(DependencyGraph $graph, PackageNode $package, int $level): bool
    {
        if (0 === $level) {
            return true;
        }

        // Filter the package level (unsupported).
        if ($package->hasAttribute('level')) {
            return $level > (int) $package->getAttribute('level');
        }

        return false;
    }
}
