<?php

// explicitly give VERSION via ENV or ask git for current version
$version = \getenv('VERSION');
if ($version === false) {
    $version = \ltrim(\exec('git describe --always --dirty', result_code: $code), 'v');

    if ($code !== 0) {
        \fwrite(\STDERR, 'Error: Unable to get version info from git. Try passing VERSION via ENV' . \PHP_EOL);

        exit(1);
    }
}

// use first argument as output file or use "graph-composer-{version}.phar"
$output = isset($argv[1])
    ? $argv[1]
    : ('graph-composer-' . $version . '.phar');

\passthru(\implode(' && ', [
    'mkdir build/ || true',
    \sprintf('php %s/vendor/bin/box compile', \dirname(__DIR__)),
    \sprintf('mv build/graph-composer.phar build/%s', \escapeshellarg($output)),
    \sprintf('php build/%s --version', \escapeshellarg($output)),
]), $code);
exit($code);
