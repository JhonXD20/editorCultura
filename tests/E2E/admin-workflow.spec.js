import { expect, test } from '@playwright/test';

test('administrador entra e abre o gerenciador e o editor GrapesJS', async ({ page }, testInfo) => {
    await page.goto('/login');
    await page.getByLabel(/e-mail/i).fill('demo@editorcultura.test');
    await page.getByLabel('Senha', { exact: true }).fill('password');
    await page.getByRole('button', { name: /entrar/i }).click();
    await expect(page).toHaveURL(/\/admin$/);
    await page.goto('/gestao-conteudo');
    await expect(page.getByRole('heading', { name: /páginas criadas/i })).toBeVisible();
    await page.locator('[data-page-row]').filter({ hasText: 'Tocantins: caminhos de memória' })
        .getByRole('link', { name: /editar/i }).click();
    await expect(page.locator('#gjs')).toBeVisible();
    await expect(page.locator('#visualEditorApp')).toHaveAttribute('data-editor-ready', 'true');
    await expect(page.locator('.preloader')).toHaveCSS('height', '0px');
    await expect(page.locator('.preloader strong')).toBeHidden();
    await expect(page.frameLocator('.gjs-frame').locator('body')).toContainText('Tocantins: caminhos de memória');
    await expect(page.frameLocator('.gjs-frame').locator('.ts-page')).toHaveCSS('background-color', 'rgb(246, 242, 233)');
    await expect(page.frameLocator('.gjs-frame').locator('[data-section-id]')).toHaveCount(2);
    await expect(page.getByRole('button', { name: /publicar/i })).toBeInViewport();
    await expect(page.getByRole('button', { name: /salvar rascunho/i })).toBeInViewport();
    await page.screenshot({ path: testInfo.outputPath('editor.png'), fullPage: true });
});

test('pareamento por cookie permite navegar offline após sincronizar', async ({ page, context }, testInfo) => {
    test.setTimeout(90_000);
    await page.goto('/login');
    await page.getByLabel('E-mail', { exact: true }).fill('demo@editorcultura.test');
    await page.getByLabel('Senha', { exact: true }).fill('password');
    await page.getByRole('button', { name: /entrar/i }).click();
    await expect(page).toHaveURL(/\/admin$/);
    const csrf = await page.locator('meta[name="csrf-token"]').getAttribute('content');
    const status = await page.request.get('/totens/monitoramento/tempo-real');
    const totem = (await status.json()).totens[0];
    const paired = await page.request.post('/pairing', { form: { totem_id: totem.id, _token: csrf } });
    expect(paired.ok()).toBeTruthy();
    const manifest = await (await page.request.get('/device/manifest')).json();
    await page.goto(paired.url());
    await expect(page.locator('#playerSyncStatus')).toContainText('disponível offline', { timeout: 60_000 });
    await context.setOffline(true);
    await page.locator('#canvasTotem [data-section-id]').first().click();
    await expect(page.locator('#canvasTotem h1')).toHaveText('A Luta pela Emancipação');
    await expect(page.locator('.ts-page')).toHaveCSS('overflow-y', 'hidden');
    await expect(page.locator('.ts-prompt')).toBeInViewport();
    for (const content of manifest.pages) {
        await page.goto(`/totem/exibir/${content.id}`);
        await expect(page.locator('.ts-page')).toBeVisible();
        const overflow = await page.locator('.ts-page').evaluate((root) => {
            const frame = root.getBoundingClientRect();
            return [root, ...root.querySelectorAll('*')].flatMap((element) => {
                const rect = element.getBoundingClientRect();
                if (!rect.width || !rect.height) return [];
                const outside = rect.left < frame.left - 1 || rect.top < frame.top - 1
                    || rect.right > frame.right + 1 || rect.bottom > frame.bottom + 1;
                const clipped = element.clientHeight > 0 && (element.scrollHeight > element.clientHeight + 1
                    || element.scrollWidth > element.clientWidth + 1);
                return outside || clipped ? [{ element: element.tagName, class: element.className, outside, clipped }] : [];
            });
        });
        expect(overflow, `Conteúdo ${content.id} deve caber inteiro, sem rolagem nem cortes`).toEqual([]);
        if (content.id === 1 || content.id === manifest.totem.home_page_id) {
            await page.screenshot({ path: testInfo.outputPath(`tela-sem-rolagem-${content.id}.png`) });
        }
    }
    await page.goto('/totem/exibir/2');
    await expect(page.locator('#canvasTotem')).toBeVisible();
    await expect(page.locator('#canvasTotem')).toContainText('história');
    await context.setOffline(false);
});

test('editor altera texto, salva e recebe notificação AdminLTE', async ({ page }, testInfo) => {
    await page.goto('/login');
    await page.getByLabel('E-mail', { exact: true }).fill('demo@editorcultura.test');
    await page.getByLabel('Senha', { exact: true }).fill('password');
    await page.getByRole('button', { name: /entrar/i }).click();
    await expect(page).toHaveURL(/\/admin$/);
    const csrf = await page.locator('meta[name="csrf-token"]').getAttribute('content');
    const created = await page.request.post('/gestao-conteudo', {
        form: { pagina_titulo: `Edição ${testInfo.project.name}`, pagina_tipo_layout: 'interna', _token: csrf },
    });
    expect(created.ok()).toBeTruthy();
    await page.goto(created.url());
    await expect(page.locator('#visualEditorApp')).toHaveAttribute('data-editor-ready', 'true');
    await expect(page.locator('.preloader')).toHaveCSS('height', '0px');
    await expect(page.locator('.preloader strong')).toBeHidden();
    await page.frameLocator('.gjs-frame').locator('h1').first().click({ position: { x: 50, y: 15 } });
    if (await page.locator('#togglePropertiesButton').getAttribute('aria-pressed') !== 'true') {
        await page.locator('#togglePropertiesButton').click();
    }
    await page.getByRole('tab', { name: 'Conteúdo', exact: true }).click();
    await page.locator('#elementTextField').fill(`Texto alterado ${testInfo.project.name}`);
    const saved = page.waitForResponse((response) => response.request().method() === 'PUT' && response.url().endsWith('/editor'));
    await page.getByRole('button', { name: /salvar rascunho/i }).click();
    expect((await saved).ok()).toBeTruthy();
    await expect(page.locator('.toast').filter({ hasText: 'Rascunho salvo' })).toBeVisible();
    await page.reload();
    await expect(page.locator('#visualEditorApp')).toHaveAttribute('data-editor-ready', 'true');
    await expect(page.frameLocator('.gjs-frame').locator('h1').first()).toHaveText(`Texto alterado ${testInfo.project.name}`);
});

test('dispositivo sem credencial não acessa conteúdo publicado', async ({ page }) => {
    const response = await page.goto('/totem/exibir/1');
    expect(response.status()).toBe(401);
    await expect(page.getByRole('heading', { name: /não pareado/i })).toBeVisible();
});
