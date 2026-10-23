import { spawn } from 'node:child_process';
import { appendFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import path from 'node:path';
import { createServer } from 'node:net';

// A direct child can be stopped on Windows without relying on shell process groups.
export default async function setup() {
    if (process.env.PLAYWRIGHT_BASE_URL) return;
    await new Promise((resolve, reject) => {
        const probe = createServer();
        probe.once('error', reject);
        probe.listen(8010, '127.0.0.1', () => probe.close((error) => error ? reject(error) : resolve()));
    });
    const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
    const config = await new Promise((resolve, reject) => {
        const prepare = spawn('php', ['scripts/e2e-server.php', '--prepare'], { cwd: root, windowsHide: true, stdio: ['ignore', 'pipe', 'inherit'], timeout: 120_000 });
        let output = '';
        prepare.stdout.setEncoding('utf8');
        prepare.stdout.on('data', (chunk) => { output += chunk; });
        prepare.on('error', reject);
        prepare.on('exit', (code) => {
            const marker = output.split(/\r?\n/).find((line) => line.startsWith('__E2E_CONFIG__'));
            if (code !== 0 || !marker) { reject(new Error('Não foi possível preparar o ambiente E2E isolado.')); return; }
            resolve(JSON.parse(marker.slice('__E2E_CONFIG__'.length)));
        });
    });
    const server = spawn(config.php[0], [...config.php.slice(1), '-S', '127.0.0.1:8010', '-t', '.', path.join(root, 'vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php')], {
        cwd: path.join(root, 'public'), env: { ...process.env, ...config.env }, windowsHide: true,
        stdio: ['ignore', 'ignore', 'pipe'],
    });
    server.stderr.on('data', (chunk) => {
        appendFileSync(path.join(root, 'storage/logs/e2e-server.log'), chunk);
        if (/PHP (Fatal|Warning)/.test(String(chunk))) process.stderr.write(chunk);
    });
    let error;
    server.on('error', (value) => { error = value; });
    const exited = new Promise((resolve) => server.once('exit', resolve));
    const stop = async () => {
        if (server.exitCode !== null || server.signalCode !== null) return;
        server.kill();
        let timeout;
        try {
            await Promise.race([exited, new Promise((_, reject) => {
                timeout = setTimeout(() => reject(new Error('O servidor E2E não encerrou.')), 5000);
            })]);
        } finally { clearTimeout(timeout); }
    };
    try {
        const deadline = Date.now() + 120_000;
        while (Date.now() < deadline) {
            if (error || server.exitCode !== null) throw error || new Error('O servidor E2E encerrou antes de iniciar.');
            try {
                if ((await fetch('http://127.0.0.1:8010/login', { signal: AbortSignal.timeout(1000) })).ok) return stop;
            } catch { /* Aguarda somente o processo isolado criado acima. */ }
            await new Promise((resolve) => setTimeout(resolve, 100));
        }
        throw new Error('O servidor E2E não ficou disponível.');
    } catch (failure) { await stop(); throw failure; }
}
