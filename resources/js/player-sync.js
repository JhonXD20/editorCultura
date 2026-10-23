export async function workerMessage(worker, message) {
    return new Promise((resolve, reject) => {
        const channel = new MessageChannel();
        const timeout = setTimeout(() => reject(new Error('A sincronização demorou mais que o esperado.')), 240_000);
        channel.port1.onmessage = ({ data }) => {
            clearTimeout(timeout);
            if (data.error) reject(new Error(data.error));
            else resolve(data);
        };
        worker.postMessage(message, [channel.port2]);
    });
}

export function createPlayerSync({ manifestUrl, currentVersion, onUpdate, onRevoked, onStatus = () => {} }) {
    let pending = false;
    const registration = 'serviceWorker' in navigator
        ? navigator.serviceWorker.register('/sw.js').then(() => navigator.serviceWorker.ready)
        : Promise.resolve(null);

    return async () => {
        if (pending || !manifestUrl) return;
        pending = true;
        try {
            const response = await fetch(manifestUrl, { credentials: 'same-origin', cache: 'no-store', headers: { Accept: 'application/json' } });
            const registered = await registration;
            const worker = registered?.active;
            if (response.status === 401) {
                if (worker) await workerMessage(worker, { type: 'REVOKE' });
                onRevoked();
                return;
            }
            if (!response.ok) throw new Error('Servidor indisponível.');
            const manifest = await response.json();
            if (!worker) throw new Error('Este navegador precisa de HTTPS para permitir o modo offline.');
            onStatus('Sincronizando conteúdo…');
            await workerMessage(worker, { type: 'SYNC_RELEASE', manifest });
            onStatus('Conteúdo sincronizado · disponível offline');
            if (manifest.version !== currentVersion) onUpdate(manifest);
        } catch (error) {
            onStatus(navigator.onLine ? error.message : 'Offline · exibindo a última versão disponível');
        } finally {
            pending = false;
        }
    };
}
