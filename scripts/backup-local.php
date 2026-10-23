<?php

use Illuminate\Contracts\Console\Kernel;
use Symfony\Component\Process\Process;

require dirname(__DIR__).'/vendor/autoload.php';
$app = require dirname(__DIR__).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (! $app->environment('local') || config('database.default') !== 'mysql' || empty($argv[1]) || ! is_file($argv[1])) {
    fwrite(STDERR, "Use apenas no MySQL local: php scripts/backup-local.php CAMINHO_MYSQLDUMP\n");
    exit(1);
}
$connection = config('database.connections.mysql');
$directory = storage_path('app/backups/local-'.date('Ymd-His').'-'.bin2hex(random_bytes(3)));
mkdir($directory, 0700, true);
$target = $directory.'/database.sql';
$stream = fopen($target.'.partial', 'xb');
$process = new Process([
    $argv[1], '--single-transaction', '--quick', '--no-tablespaces', '--set-gtid-purged=OFF',
    '--host='.$connection['host'], '--port='.$connection['port'], '--user='.$connection['username'],
    $connection['database'],
], dirname(__DIR__), ['MYSQL_PWD' => $connection['password']]);
$process->setTimeout(120);
$process->run(function ($type, $data) use ($stream): void {
    if ($type === Process::OUT) {
        fwrite($stream, $data);
    }
});
fclose($stream);
if (! $process->isSuccessful()) {
    fwrite(STDERR, "Falha no backup local. Nenhuma migração deve ser aplicada.\n");
    exit(1);
}
rename($target.'.partial', $target);
echo json_encode(['backup' => $target, 'sha256' => hash_file('sha256', $target), 'bytes' => filesize($target)], JSON_UNESCAPED_SLASHES).PHP_EOL;
