import { expect, test } from '@playwright/test';
import { readFile } from 'node:fs/promises';

// This mailbox exists only in the isolated testing environment; no application route exposes it.
async function accessLinkFor(email) {
    let url;
    await expect.poll(async () => {
        const messages = (await readFile(new URL('../../storage/framework/testing/mailbox.jsonl', import.meta.url), 'utf8'))
            .trim().split('\n').filter(Boolean).map((line) => JSON.parse(line));
        const text = messages.filter((message) => message.to.includes(email)).at(-1)?.text || '';
        url = text.match(/https?:\/\/[^\s)<>]+\/password\/reset\/[^\s)<>]+/u)?.[0];
        return Boolean(url);
    }).toBe(true);
    return url;
}

async function definePassword(page, email, password) {
    await page.goto(await accessLinkFor(email));
    await page.getByLabel('Nova senha', { exact: true }).fill(password);
    await page.getByLabel('Confirmar nova senha', { exact: true }).fill(password);
    await page.getByRole('button', { name: 'Definir senha', exact: true }).click();
    await expect(page).toHaveURL(/\/login$/);
    await login(page, email, password);
    await expect(page).toHaveURL(/\/admin$/);
}

async function login(page, email = 'demo@editorcultura.test', password = 'password') {
    await page.goto('/login');
    await page.getByLabel('E-mail', { exact: true }).fill(email);
    await page.getByLabel('Senha', { exact: true }).fill(password);
    await page.getByRole('button', { name: /entrar/i }).click();
}

test('navegação parcial preserva shell, filtros e histórico', async ({ page }) => {
    await login(page);
    await expect(page).toHaveURL(/\/admin$/);
    await page.waitForFunction(() => Boolean(window.AdminNavigation));
    await page.evaluate(() => {
        window.originalSidebar = document.querySelector('.main-sidebar');
        window.originalHeader = document.querySelector('.main-header');
    });
    // Programmatic click also exercises narrow layouts where the drawer starts closed.
    await page.locator('.main-sidebar a[href$="/gestao-conteudo"]').evaluate((link) => link.click());
    await expect(page.locator('#pageSearch')).toBeVisible();
    await page.locator('#pageSearch').fill('termo-inexistente-ux');
    await expect(page.locator('#noPageResults')).toBeVisible();
    await page.locator('.main-sidebar a[href$="/midias"]').evaluate((link) => link.click());
    await expect(page.getByRole('heading', { name: 'Biblioteca de mídia', exact: true })).toBeVisible();
    await page.goBack();
    await expect(page.locator('#pageSearch')).toBeVisible();
    await page.locator('.main-sidebar a[href$="/relatorios"]').evaluate((link) => link.click());
    await expect(page.locator('#lineChart')).toBeVisible();
    await page.locator('.main-sidebar a.nav-link[href$="/admin"]').evaluate((link) => link.click());
    await expect(page.locator('#adminOverview')).toBeVisible();
    await page.locator('#btnAtualizarDashboard').click();
    await expect(page.locator('#adminMainContent')).not.toHaveAttribute('aria-busy', 'true');
    expect(await page.evaluate(() => window.originalSidebar === document.querySelector('.main-sidebar') && window.originalHeader === document.querySelector('.main-header'))).toBe(true);
});

test('convite, recuperação por link e confirmação de perfil sem reload', async ({ page, browser }, testInfo) => {
    test.setTimeout(90_000);
    const email = `ux-${testInfo.project.name}-${Date.now()}@example.test`;
    const adminEmail = `admin-${email}`;
    const adminPassword = 'senha-admin-ux-segura';
    await login(page);
    await expect(page).toHaveURL(/\/admin$/);
    // Use a separate actor per profile so tests do not share the security rate limit.
    const csrf = await page.locator('meta[name="csrf-token"]').getAttribute('content');
    const actor = await page.request.post('/usuarios', {
        headers: { Accept: 'application/json' },
        form: { _token: csrf, name: 'Admin UX', email: adminEmail, data_nascimento: '1990-01-02', perfil: 'admin' },
    });
    expect(actor.ok()).toBeTruthy();
    expect((await actor.json()).temporary_password).toBeUndefined();
    await page.request.post('/logout', { form: { _token: csrf } });
    await definePassword(page, adminEmail, adminPassword);
    await page.locator('.main-sidebar a[href$="/usuarios"]').evaluate((link) => link.click());
    await page.getByLabel('Nome', { exact: true }).first().fill('Usuário UX');
    await page.locator('#new-user-email').fill(email);
    await page.getByRole('button', { name: 'Criar usuário', exact: true }).click();
    await expect(page.locator('.toast').filter({ hasText: 'Usuário criado. O convite' })).toBeVisible();
    await expect(page.getByRole('dialog', { name: 'Senha temporária', exact: true })).not.toBeVisible();
    let card = page.locator(`[data-user-email="${email}"]`);
    await expect(card).toBeVisible();
    await expect(card).toContainText('Convite pendente');
    const context = await browser.newContext({ baseURL: testInfo.project.use.baseURL });
    const userPage = await context.newPage();
    try {
        await definePassword(userPage, email, 'senha-anterior-ux');
        await page.reload();
        card = page.locator(`[data-user-email="${email}"]`);
        await card.getByRole('button', { name: 'Enviar link', exact: true }).click();
        const dialog = page.getByRole('dialog', { name: 'Enviar link de acesso?' });
        await expect(dialog).toBeVisible();
        await expect(dialog.getByRole('button', { name: 'Enviar link', exact: true })).toBeDisabled();
        await dialog.getByLabel('Digite o e-mail do usuário para confirmar').fill(email);
        await dialog.getByLabel('Sua senha atual').fill('incorrect-password');
        await dialog.getByRole('button', { name: 'Enviar link', exact: true }).click();
        await expect(page.locator('.toast.bg-danger').last()).toBeVisible();
        await card.getByRole('button', { name: 'Enviar link', exact: true }).click();
        await dialog.getByLabel('Digite o e-mail do usuário para confirmar').fill(email);
        await dialog.getByLabel('Sua senha atual').fill(adminPassword);
        const resetResponse = page.waitForResponse((response) => response.url().endsWith('/resetar-senha') && response.ok());
        await dialog.getByRole('button', { name: 'Enviar link', exact: true }).click();
        expect((await (await resetResponse).json()).temporary_password).toBeUndefined();
        await expect(page.locator('.toast').filter({ hasText: 'Link enviado por e-mail' })).toBeVisible();
        await expect(page.locator('body')).not.toContainText(await accessLinkFor(email));
        await userPage.goto('/admin');
        await expect(userPage).toHaveURL(/\/admin$/);
        await definePassword(userPage, email, 'senha-segura-para-ux');
        // Open profile without replacing the document.
        await userPage.locator('a[href$="/minha-conta"]').evaluate((link) => link.click());
        await expect(userPage.locator('#profile-name')).toBeVisible();
        await userPage.evaluate(() => { window.originalSidebar = document.querySelector('.main-sidebar'); });
        await userPage.locator('#profile-name').fill('Perfil UX alterado');
        await userPage.locator('#profile-password').fill('senha-segura-para-ux');
        await userPage.getByRole('button', { name: 'Salvar perfil', exact: true }).click();
        const confirmation = userPage.getByRole('dialog', { name: 'Salvar perfil?' });
        await expect(confirmation).toBeVisible();
        await confirmation.getByRole('button', { name: 'Cancelar', exact: true }).click();
        await expect(userPage.locator('#profile-name')).toHaveValue('Perfil UX alterado');
        await userPage.getByRole('button', { name: 'Salvar perfil', exact: true }).click();
        await confirmation.getByRole('button', { name: 'Confirmar', exact: true }).click();
        await expect(userPage.locator('.editor-user-summary strong')).toHaveText('Perfil UX alterado');
        expect(await userPage.evaluate(() => window.originalSidebar === document.querySelector('.main-sidebar'))).toBe(true);

        card = page.locator(`[data-user-email="${email}"]`);
        await card.getByRole('button', { name: 'Revogar todas as sessões', exact: true }).click();
        const revoke = page.getByRole('dialog', { name: 'Revogar todas as sessões?' });
        await expect(revoke.getByRole('button', { name: 'Revogar sessões', exact: true })).toBeDisabled();
        await revoke.getByLabel('Digite o e-mail do usuário para confirmar').fill(email);
        await revoke.getByLabel('Sua senha atual').fill(adminPassword);
        await revoke.getByRole('button', { name: 'Revogar sessões', exact: true }).click();
        await expect(page.locator('.toast').filter({ hasText: 'Sessões do usuário revogadas' })).toBeVisible();
        await userPage.goto('/admin');
        await expect(userPage).toHaveURL(/\/login$/);
    } finally { await context.close(); }
});
