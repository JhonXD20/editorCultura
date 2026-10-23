<?php

// Restores only into a newly created, randomly named database. Never targets the application database.
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;

require dirname(__DIR__).'/vendor/autoload.php';
$app = require dirname(__DIR__).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$dump = realpath($argv[1] ?? '');
$client = $argv[2] ?? 'mysql';
if (! $dump || ! is_file($dump)) {
    fwrite(STDERR, "Uso: php scripts/verify-backup.php CAMINHO_DUMP [CAMINHO_MYSQL]\n");
    exit(1);
}
$sql = file_get_contents($dump);
if (preg_match('/^\s*(?:USE\s|CREATE\s+DATABASE\s)/mi', $sql)) {
    throw new RuntimeException('Dump com troca/criação de banco não é permitido neste verificador.');
}
preg_match_all('/^CREATE TABLE `([^`]+)`/m', $sql, $matches);
$tables = $matches[1];
if ($tables === []) {
    throw new RuntimeException('Dump sem tabelas.');
}
$connection = config('database.connections.mysql');
$database = 'totemsync_restore_'.bin2hex(random_bytes(8));
config(['database.connections.backup_restore' => array_replace($connection, ['database' => $database, 'url' => null])]);
$created = false;
try {
    DB::connection('mysql')->statement("CREATE DATABASE `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $created = true;
    $process = new Process([
        $client, '--host='.$connection['host'], '--port='.$connection['port'],
        '--user='.$connection['username'], '--default-character-set=utf8mb4', '--binary-mode', $database,
    ], null, ['MYSQL_PWD' => $connection['password']]);
    $process->setInput(fopen($dump, 'rb'));
    $process->setTimeout(300);
    $process->mustRun();
    $restored = DB::connection('backup_restore');
    $total = 0;
    foreach ($tables as $table) {
        if (! preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
            throw new RuntimeException('Nome de tabela inesperado no dump.');
        }
        $check = $restored->select("CHECK TABLE `{$table}`");
        if (($check[0]->Msg_text ?? '') !== 'OK') {
            throw new RuntimeException("Falha de integridade na tabela {$table}.");
        }
        $count = $restored->table($table)->count();
        if (str_contains($sql, 'INSERT INTO `'.$table.'`') && $count === 0) {
            throw new RuntimeException("Registros ausentes na tabela {$table}.");
        }
        $total += $count;
    }
    echo count($tables)." tabelas restauradas e verificadas; {$total} registros.\n";
    echo 'SHA-256 do dump: '.hash_file('sha256', $dump)."\n";
} finally {
    DB::purge('backup_restore');
    if ($created && preg_match('/^totemsync_restore_[a-f0-9]{16}$/', $database)) {
        DB::connection('mysql')->statement("DROP DATABASE `{$database}`");
        echo "Banco temporário de restauração removido; banco original preservado.\n";
    }
}
