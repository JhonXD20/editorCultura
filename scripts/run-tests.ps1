$ErrorActionPreference = 'Stop'

$phpBinary = (Get-Command php).Source
$loadedModules = @(& $phpBinary -m)
$phpArguments = @()

if ($loadedModules -notcontains 'pdo_sqlite' -or $loadedModules -notcontains 'sqlite3') {
    $extensionDirectory = & $phpBinary -r "echo ini_get('extension_dir');"
    $sqliteExtension = Join-Path $extensionDirectory 'php_pdo_sqlite.dll'
    $sqlite3Extension = Join-Path $extensionDirectory 'php_sqlite3.dll'

    if (-not (Test-Path -LiteralPath $sqliteExtension) -or -not (Test-Path -LiteralPath $sqlite3Extension)) {
        throw 'As extensões pdo_sqlite e sqlite3 não foram encontradas na instalação do PHP.'
    }

    $phpArguments += "-dextension_dir=$extensionDirectory"
    $phpArguments += '-dextension=pdo_sqlite'
    $phpArguments += '-dextension=sqlite3'
}

& $phpBinary @phpArguments 'vendor/bin/phpunit' @args

exit $LASTEXITCODE
