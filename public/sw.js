const META = 'totemsync-state-v2';
const STATE_URL = '/_totemsync/active-release';
const PREFIX = 'totemsync-release-';
let syncing;

async function activeState() {
    const response = await (await caches.open(META)).match(STATE_URL);
    return response ? response.json() : null;
}

async function clearPlayer() {
    for (const key of await caches.keys()) {
        if (key.startsWith('totemsync-')) await caches.delete(key);
    }
}

function localUrl(value, base) {
    try {
        const url = new URL(value.replaceAll('&amp;', '&'), base);
        return url.origin === self.location.origin ? url.href : null;
    } catch { return null; }
}

async function synchronize(manifest) {
    const previous = await activeState();
    const name = `${PREFIX}${manifest.totem.id}-${manifest.version}`;
    if (previous?.cache === name) return { version: manifest.version };
    const staged = await caches.open(name);
    const queue = [...manifest.pages.map((page) => page.url), ...manifest.assets];
    const visited = new Set();
    try {
        for (let index = 0; index < queue.length; index += 1) {
            const url = localUrl(queue[index], self.location.origin);
            if (!url || visited.has(url)) continue;
            visited.add(url);
            const response = await fetch(url, { credentials: 'same-origin', cache: 'no-store' });
            if (!response.ok || response.redirected) throw new Error(`Recurso indisponível: ${url}`);
            const type = response.headers.get('content-type') || '';
            if (type.includes('text/html') || type.includes('text/css') || type.includes('javascript')) {
                const source = await response.clone().text();
                const patterns = type.includes('text/html')
                    ? [/(?:src|href|poster)=["']([^"'#]+)["']/gi]
                    : type.includes('text/css') ? [/url\(\s*["']?([^)'"\s]+)["']?\s*\)/gi] : [];
                // Only static dependencies, never page links, authentication or admin routes.
                for (const pattern of patterns) {
                    for (const match of source.matchAll(pattern)) {
                        const dependency = localUrl(match[1], url);
                        if (dependency && /\/(?:build\/assets|storage\/media|vendor|totem\/(?:img|assets|imagens-conteudo))\//.test(new URL(dependency).pathname)) queue.push(dependency);
                    }
                }
                if (type.includes('javascript')) {
                    for (const match of source.matchAll(/(?:from\s*|import\s*|import\s*\()["'](\.\/[^"']+)["']/g)) {
                        const dependency = localUrl(match[1], url);
                        if (dependency) queue.push(dependency);
                    }
                }
            }
            await staged.put(url, response);
        }
        // A publication that changed while downloading is retried without changing the active cache.
        const finalResponse = await fetch('/device/manifest', { credentials: 'same-origin', cache: 'no-store' });
        if (!finalResponse.ok || (await finalResponse.json()).version !== manifest.version) throw new Error('Publicação alterada durante a sincronização.');
        await (await caches.open(META)).put(STATE_URL, new Response(JSON.stringify({ cache: name, version: manifest.version, previous: previous?.cache })));
        for (const key of await caches.keys()) {
            if (key.startsWith(PREFIX) && key !== name && key !== previous?.cache) await caches.delete(key);
        }
        return { version: manifest.version };
    } catch (error) {
        await caches.delete(name);
        throw error;
    }
}

self.addEventListener('install', (event) => event.waitUntil(self.skipWaiting()));
self.addEventListener('activate', (event) => event.waitUntil(self.clients.claim()));
self.addEventListener('message', (event) => {
    if (event.data?.type === 'REVOKE') {
        event.waitUntil(clearPlayer().then(() => event.ports[0]?.postMessage({ cleared: true })));
    }
    if (event.data?.type !== 'SYNC_RELEASE') return;
    if (!syncing) syncing = synchronize(event.data.manifest).finally(() => { syncing = null; });
    event.waitUntil(syncing.then((result) => event.ports[0]?.postMessage(result))
        .catch((error) => event.ports[0]?.postMessage({ error: error.message })));
});

self.addEventListener('fetch', (event) => {
    const url = new URL(event.request.url);
    if (event.request.method !== 'GET' || url.origin !== self.location.origin) return;
    const playerPage = url.pathname.startsWith('/totem/exibir/');
    const staticAsset = /\/(?:build\/assets|storage\/media|vendor|totem\/(?:img|assets|imagens-conteudo))\//.test(url.pathname);
    if (!playerPage && !staticAsset) return;
    event.respondWith((async () => {
        const active = await activeState();
        if (active) {
            const cached = await (await caches.open(active.cache)).match(event.request, { ignoreSearch: false });
            if (cached) return cached;
        }
        return fetch(event.request);
    })());
});
