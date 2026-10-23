import { expect, test } from '@playwright/test';

async function login(page) {
    await page.goto('/login');
    await page.getByLabel('E-mail', { exact: true }).fill('demo@editorcultura.test');
    await page.getByLabel('Senha', { exact: true }).fill('password');
    await page.getByRole('button', { name: /entrar/i }).click();
    await expect(page).toHaveURL(/\/admin$/);
    await page.goto('/gestao-conteudo');
    return page.locator('meta[name="csrf-token"]').getAttribute('content');
}

async function section(page, title) {
    await page.getByLabel('Nova seção', { exact: true }).fill(title);
    await page.getByRole('button', { name: 'Criar seção', exact: true }).click();
    const panel = page.locator('[data-section-id]').filter({ has: page.locator('summary').filter({ hasText: title }) });
    await expect(panel).toHaveCount(1);
    return { id: await panel.getAttribute('data-section-id'), panel };
}

async function createPage(page, csrf, title, sectionId, html) {
    const response = await page.request.post('/gestao-conteudo', {
        form: { _token: csrf, pagina_titulo: title, pagina_tipo_layout: 'interna', ...(sectionId ? { secao_id: sectionId } : {}) },
    });
    expect(response.ok()).toBeTruthy();
    const id = response.url().match(/gestao-conteudo\/(\d+)\/edit$/)[1];
    if (html) {
        const published = await page.request.post(`/gestao-conteudo/${id}/publicar-editor`, {
            headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
            data: { base_version: 1, project_data: { pages: [{ component: { tagName: 'main' } }] }, html, css: '' },
        });
        expect(published.ok(), await published.text()).toBeTruthy();
    }
    return id;
}

test('seções permitem adicionar e ordenar páginas sem recarregar o painel', async ({ page }, testInfo) => {
    const csrf = await login(page);
    await page.evaluate(() => { window.sectionsSidebar = document.querySelector('.main-sidebar'); });
    const { id, panel } = await section(page, `Organização ${testInfo.project.name} ${Date.now()}`);
    expect(await page.evaluate(() => window.sectionsSidebar === document.querySelector('.main-sidebar'))).toBe(true);
    const first = await createPage(page, csrf, `Primeira ${id}`, id);
    const second = await createPage(page, csrf, `Segunda ${id}`, id);
    await page.reload();
    await panel.locator('summary').click();
    const list = panel.locator('[data-section-order-list]');
    await expect(list.locator('li').first()).toHaveAttribute('data-section-page', first);
    await list.locator('li').last().getByRole('button', { name: /para cima/ }).click();
    await panel.getByRole('button', { name: 'Salvar ordem', exact: true }).click();
    await expect(page.locator('.toast').filter({ hasText: 'Ordem salva' })).toBeVisible();
    await panel.locator('summary').click();
    await expect(panel.locator('[data-section-page]').first()).toHaveAttribute('data-section-page', second);
    await page.screenshot({ path: testInfo.outputPath('section-manager.png'), fullPage: true });
    await panel.getByRole('button', { name: 'Adicionar página nesta seção' }).click();
    await expect(page.locator('#newPageSection')).toHaveValue(id);
    await expect(page.locator('#newPageType')).toHaveValue('interna');
    await page.locator('#newPageTitle').fill(`Terceira ${id}`);
    await page.getByRole('button', { name: 'Criar e abrir editor' }).click();
    await expect(page.locator('#visualEditorApp')).toHaveAttribute('data-editor-ready', 'true');
});

test('toques e cards navegam pela seção offline preservando shell e controles', async ({ page, context }, testInfo) => {
    test.setTimeout(120_000);
    const csrf = await login(page);
    const { id } = await section(page, `Leitura ${testInfo.project.name} ${Date.now()}`);
    const first = await createPage(page, csrf, `Leitura A ${id}`, id,
        '<main><h1>Primeira leitura</h1><button type="button" id="localControl">Controle local</button><details><summary>Mais informações</summary><p>Detalhes da leitura</p></details></main>');
    const second = await createPage(page, csrf, `Leitura B ${id}`, id,
        '<main><h1>Segunda leitura</h1><video controls width="200" height="120"></video><section data-cultura-carousel="1"><div data-cultura-slide="1">Slide um</div><div data-cultura-slide="1">Slide dois</div><button data-carousel-direction="1">Mudar slide</button></section></main>');
    const landing = await createPage(page, csrf, `Menu de leitura ${id}`, null,
        `<main><article data-cultura-navigation="1" data-section-id="${id}"><h1>Abrir leitura</h1></article><article data-cultura-navigation="1" data-section-id="99999999"><h2>Seção indisponível</h2></article></main>`);
    const status = await page.request.get('/totens/monitoramento/tempo-real');
    const totem = (await status.json()).totens[0];
    expect((await page.request.post('/pairing', { form: { totem_id: totem.id, _token: csrf } })).ok()).toBeTruthy();
    await page.goto(`/totem/exibir/${landing}`);
    await expect(page.locator('#playerSyncStatus')).toContainText('disponível offline', { timeout: 90_000 });
    const fullscreen = testInfo.project.name === 'chromium-desktop' && await page.evaluate(() => document.fullscreenEnabled);
    if (fullscreen) {
        await page.locator('#totemFullscreen').click();
        await expect.poll(() => page.evaluate(() => Boolean(document.fullscreenElement))).toBe(true);
    }
    await page.evaluate(() => { window.playerShell = document.getElementById('totemPublicoViewport'); window.playerStage = document.getElementById('totemStage'); });
    await expect(page.getByRole('link', { name: 'Seção indisponível' })).toHaveAttribute('aria-disabled', 'true');
    await context.setOffline(true);
    await page.getByRole('link', { name: 'Abrir leitura' }).click();
    await expect(page).toHaveURL(new RegExp(`/totem/exibir/${first}$`));
    await expect(page.locator('.totem-sequence-control').first()).not.toBeVisible();
    await page.getByRole('button', { name: 'Controle local' }).click();
    await expect(page).toHaveURL(new RegExp(`/totem/exibir/${first}$`));
    await page.locator('summary').click();
    await expect(page.locator('details')).toHaveAttribute('open', '');
    const display = page.locator('#totemDisplayArea');
    const box = await display.boundingBox();
    const tapDisplay = (ratio) => testInfo.project.use.hasTouch
        ? display.tap({ position: { x: box.width * ratio, y: box.height * .8 } })
        : display.click({ position: { x: box.width * ratio, y: box.height * .8 } });
    await tapDisplay(.9);
    await expect(page).toHaveURL(new RegExp(`/totem/exibir/${second}$`));
    await page.getByRole('button', { name: 'Mudar slide' }).click();
    await expect(page.getByText('Slide dois', { exact: true })).toBeVisible();
    await expect(page).toHaveURL(new RegExp(`/totem/exibir/${second}$`));
    await page.locator('video').click({ position: { x: 20, y: 20 } });
    await expect(page).toHaveURL(new RegExp(`/totem/exibir/${second}$`));
    await tapDisplay(.9);
    await expect(page).toHaveURL(new RegExp(`/totem/exibir/${first}$`));
    await page.goBack();
    await expect(page.locator('#canvasTotem')).toContainText('Segunda leitura');
    await expect(page).toHaveURL(new RegExp(`/totem/exibir/${second}$`));
    await tapDisplay(.1);
    await expect(page).toHaveURL(new RegExp(`/totem/exibir/${first}$`));
    expect(await page.evaluate(() => window.playerShell === document.getElementById('totemPublicoViewport') && window.playerStage === document.getElementById('totemStage'))).toBe(true);
    if (fullscreen) {
        expect(await page.evaluate(() => Boolean(document.fullscreenElement))).toBe(true);
        await expect(page.locator('#totemFullscreen')).toHaveAttribute('aria-label', 'Sair da tela cheia');
    }
    await page.screenshot({ path: testInfo.outputPath('section-player.png'), fullPage: true });
    await context.setOffline(false);
});

test('editor associa texto a uma seção e preserva o destino ao salvar', async ({ page }, testInfo) => {
    const csrf = await login(page);
    const { id } = await section(page, `Destino ${testInfo.project.name} ${Date.now()}`);
    const editable = await createPage(page, csrf, `Entrada visual ${id}`, null);
    await page.goto(`/gestao-conteudo/${editable}/edit`);
    await expect(page.locator('#visualEditorApp')).toHaveAttribute('data-editor-ready', 'true');
    await page.frameLocator('.gjs-frame').locator('h1').first().click({ position: { x: 50, y: 15 } });
    if (await page.locator('#togglePropertiesButton').getAttribute('aria-pressed') !== 'true') await page.locator('#togglePropertiesButton').click();
    await page.getByRole('tab', { name: 'Conteúdo', exact: true }).click();
    await page.locator('#navigationActionField').selectOption('section');
    await page.locator('#sectionDestinationField').selectOption(id);
    const save = page.waitForResponse((response) => response.request().method() === 'PUT' && response.url().endsWith('/editor'));
    await page.getByRole('button', { name: /salvar rascunho/i }).click();
    expect((await save).ok()).toBeTruthy();
    await page.reload();
    await expect(page.locator('#visualEditorApp')).toHaveAttribute('data-editor-ready', 'true');
    await expect(page.frameLocator('.gjs-frame').locator('h1').first()).toHaveAttribute('data-section-id', id);
});
