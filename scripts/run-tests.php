<?php

$php = [PHP_BINARY];
if (PHP_OS_FAMILY === 'Windows' && ! extension_loaded('pdo_sqlite')) {
    $php[] = '-dextension=pdo_sqlite';
    $php[] = '-dextension=sqlite3';
}
$command = array_merge($php, [dirname(__DIR__).'/vendor/bin/phpunit'], array_slice($argv, 1));
$process = proc_open($command, [STDIN, STDOUT, STDERR], $pipes, dirname(__DIR__));
exit(is_resource($process) ? proc_close($process) : 1);
