<?php

use Symfony\Component\Process\Process;

// Isolated browser test server. Never uses the application's .env database.
$root = dirname(__DIR__);
require $root.'/vendor/autoload.php';

$connection = getenv('E2E_DB_CONNECTION') ?: 'sqlite';
$database = $connection === 'sqlite' ? $root.'/storage/framework/testing/e2e.sqlite' : getenv('E2E_DB_DATABASE');
if ($connection !== 'sqlite' && (! is_string($database) || ! str_ends_with($database, '_e2e'))) {
    fwrite(STDERR, "E2E_DB_DATABASE deve terminar em _e2e.\n");
    exit(1);
}
if ($connection === 'sqlite' && ! file_exists($database)) {
    if (! is_dir(dirname($database))) {
        mkdir(dirname($database), 0770, true);
    }
    touch($database);
}
$overrides = [
    'APP_ENV' => 'testing', 'APP_DEBUG' => 'true', 'APP_URL' => 'http://127.0.0.1:8010',
    'APP_KEY' => 'base64:'.base64_encode(random_bytes(32)),
    'DB_CONNECTION' => $connection, 'DB_DATABASE' => $database, 'DB_URL' => '',
    'SESSION_DRIVER' => 'database', 'SESSION_SECURE_COOKIE' => 'false',
    'CACHE_STORE' => 'database', 'QUEUE_CONNECTION' => 'sync', 'MAIL_MAILER' => 'array',
    'E2E_CAPTURE_MAIL' => 'true',
    'SECURITY_CSP_ENABLED' => 'true',
];
$environment = array_merge(getenv(), $overrides);
$php = [PHP_BINARY];
if ($connection === 'sqlite' && ! extension_loaded('pdo_sqlite') && PHP_OS_FAMILY === 'Windows') {
    $php[] = '-dextension=pdo_sqlite';
    $php[] = '-dextension=sqlite3';
}
foreach ([['artisan', 'migrate:fresh', '--seed', '--force']] as $command) {
    $process = new Process(array_merge($php, $command), $root, $environment);
    $process->setTimeout(90);
    $process->mustRun(fn ($type, $data) => fwrite($type === 'err' ? STDERR : STDOUT, $data));
}
if (in_array('--prepare', $argv, true)) {
    // Only isolated test overrides, never the inherited environment or application secrets.
    echo '__E2E_CONFIG__'.json_encode(['php' => $php, 'env' => $overrides], JSON_THROW_ON_ERROR).PHP_EOL;
    exit(0);
}
// Direct PHP server keeps the test-only SQLite extension active in the serving process.
$server = new Process(array_merge($php, ['-S', '127.0.0.1:8010', '-t', '.', $root.'/vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php']), $root.'/public', $environment);
$server->setTimeout(null);
$server->run(function ($type, $data) use ($root): void {
    file_put_contents($root.'/storage/logs/e2e-server.log', $data, FILE_APPEND);
    if (str_contains($data, 'PHP Fatal') || str_contains($data, 'PHP Warning')) {
        fwrite(STDERR, $data);
    }
});
exit($server->getExitCode() ?? 1);
