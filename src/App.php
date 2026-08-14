<?php

namespace Clue\GraphComposer;

use Symfony\Component\Console\Application as BaseApplication;

class App extends BaseApplication
{
    public function __construct()
    {
        parent::__construct('clue/graph-composer', '@dev');

        $this->add(new Command\Show());
        $this->add(new Command\Export());
    }

    public function getVersion(): string
    {
        // Since PHP-Scoper relies on COMPOSER_ROOT_VERSION the version parsed by PackageVersions, we rely on Box
        // placeholders in order to get the right version for the PHAR.
        if (\str_starts_with(__FILE__, 'phar:')) {
            return '@git_version_placeholder@';
        }

        // Fallback to default if not running as PHAR.
        return parent::getVersion();
    }
}
