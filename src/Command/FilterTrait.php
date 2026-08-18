<?php

namespace Clue\GraphComposer\Command;

use Clue\GraphComposer\Graph\Filter;
use JMS\Composer\Graph\DependencyEdge;
use JMS\Composer\Graph\DependencyGraph;
use JMS\Composer\Graph\PackageNode;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

trait FilterTrait
{
    private function createFilter(InputInterface $input, OutputInterface $output): callable
    {
        $filters = (array)$input->getOption('filter');
        $level = \max(0, (int)$input->getOption('level'));
        $withDevPackages = (bool)$input->getOption('dev');
        $strict = (bool)$input->getOption('strict-filter') && !empty($filters);

        $filter = Filter::createFilter($filters, $level, $withDevPackages, $strict);
        if (!$output->isVeryVerbose()) {
            return $filter;
        }

        return static function (DependencyGraph $graph, PackageNode $package, ?DependencyEdge $requires) use ($filter, $output): bool {
            $use = $filter($graph, $package, $requires);

            if (!$use) {
                $output->writeln(
                    \sprintf('* Filtered %s "%s".', $requires instanceof DependencyEdge ? 'edge' : 'package', $package->getName()), OutputInterface::VERBOSITY_VERY_VERBOSE
                );
            }

            return $use;
        };
    }
}
