export function mountQuestionnaire({ deviceId, signal, onStatus = () => {} }) {
    const dialog = document.getElementById('visitorQuestionnaire');
    if (!dialog || !deviceId) return () => {};
    const form = document.getElementById('questionnaireForm');
    const status = document.getElementById('questionnaireStatus');
    const send = form.querySelector('[type="submit"]');
    let pending = false;
    let idle;
    const listen = (target, event, handler) => target.addEventListener(event, handler, { signal });
    const close = () => { dialog.close(); form.reset(); status.textContent = ''; clearTimeout(idle); };
    const resetIdle = () => {
        clearTimeout(idle);
        idle = setTimeout(close, 120_000);
    };
    const database = () => new Promise((resolve, reject) => {
        const request = indexedDB.open('totemsync-questionnaire', 1);
        request.onupgradeneeded = () => request.result.createObjectStore('responses', { keyPath: 'uuid' });
        request.onsuccess = () => resolve(request.result);
        request.onerror = () => reject(request.error);
    });
    const write = async (action) => {
        const db = await database();
        try {
            await new Promise((resolve, reject) => {
                const tx = db.transaction('responses', 'readwrite');
                action(tx.objectStore('responses'));
                tx.oncomplete = resolve;
                tx.onerror = () => reject(tx.error);
                tx.onabort = () => reject(tx.error);
            });
        } finally { db.close(); }
    };
    const flush = async () => {
        if (pending || !navigator.onLine || signal.aborted) return;
        pending = true;
        try {
            const db = await database();
            const rows = await new Promise((resolve, reject) => {
                const request = db.transaction('responses').objectStore('responses').getAll();
                request.onsuccess = () => resolve(request.result);
                request.onerror = () => reject(request.error);
            });
            db.close();
            for (const row of rows.filter((value) => value.device_id === deviceId)) {
                const response = await fetch(dialog.dataset.endpoint, {
                    method: 'POST', credentials: 'same-origin', signal,
                    headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
                    body: JSON.stringify(row),
                });
                if (!response.ok) return;
                if ((await response.json()).acknowledged === row.uuid) {
                    await write((store) => store.delete(row.uuid));
                    onStatus('Avaliação enviada. Obrigado por participar!');
                }
            }
        } catch { /* A resposta permanece no dispositivo até a próxima tentativa. */ }
        finally { pending = false; }
    };
    listen(document.getElementById('openQuestionnaire'), 'click', () => {
        form.reset(); status.textContent = ''; dialog.showModal(); resetIdle();
    });
    listen(document.getElementById('skipQuestionnaire'), 'click', close);
    listen(document.getElementById('closeQuestionnaire'), 'click', close);
    listen(dialog, 'cancel', close);
    listen(dialog, 'input', resetIdle);
    listen(dialog, 'pointerdown', resetIdle);
    listen(form, 'submit', async (event) => {
        event.preventDefault();
        if (send.disabled) return;
        const data = new FormData(form);
        const answers = Object.fromEntries(['q1', 'q2', 'q3', 'q4', 'q5'].filter((key) => data.has(key)).map((key) => [key, data.get(key)]));
        if (!Object.keys(answers).length) { status.textContent = 'Escolha pelo menos uma resposta ou use Pular e voltar.'; return; }
        send.disabled = true;
        try {
            await write((store) => store.put({
                uuid: crypto.randomUUID(), device_id: deviceId, version: dialog.dataset.version,
                occurred_at: new Date().toISOString(), participate: true, answers,
            }));
            form.reset();
            status.textContent = 'Obrigado! Avaliação guardada neste dispositivo para envio assim que houver conexão.';
            await flush();
        } catch { status.textContent = 'Não foi possível guardar a avaliação. Tente novamente ou use Pular e voltar.'; }
        finally { send.disabled = false; }
    });
    listen(window, 'online', flush);
    const interval = setInterval(flush, 30_000);
    void flush();
    return () => { clearInterval(interval); close(); };
}
