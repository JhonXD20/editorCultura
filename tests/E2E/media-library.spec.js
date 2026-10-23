import { expect, test } from '@playwright/test';

test('imagem enviada aparece no card da biblioteca no mesmo domínio', async ({ page }, testInfo) => {
    await page.goto('/login');
    await page.getByLabel('E-mail', { exact: true }).fill('demo@editorcultura.test');
    await page.getByLabel('Senha', { exact: true }).fill('password');
    await page.getByRole('button', { name: /entrar/i }).click();
    await expect(page).toHaveURL(/\/admin$/);
    const image = await page.screenshot({ type: 'png' });
    const name = `card-${testInfo.project.name}-${Date.now()}.png`;
    await page.goto('/midias');
    await page.evaluate(() => { window.shellMarker = document.querySelector('.main-sidebar'); });
    await page.getByLabel('Arquivo de mídia').setInputFiles({ name, mimeType: 'image/png', buffer: image });
    await page.getByRole('button', { name: 'Enviar arquivo', exact: true }).click();
    const thumbnail = page.getByRole('img', { name, exact: true });
    await expect(thumbnail).toBeVisible();
    await expect(thumbnail).toHaveAttribute('src', /^\/storage\/media\/images\//);
    await expect.poll(() => thumbnail.evaluate((element) => element.complete && element.naturalWidth > 0)).toBe(true);
    const source = await thumbnail.getAttribute('src');
    const response = await page.request.get(source);
    expect(response.ok()).toBeTruthy();
    expect(response.headers()['content-type']).toContain('image/png');
    await thumbnail.locator('..').getByRole('button', { name: 'Excluir', exact: true }).click();
    await expect(page.getByRole('dialog', { name: 'Excluir mídia?' })).toBeVisible();
    await page.screenshot({ path: testInfo.outputPath('confirmacao-midias.png') });
    await expect(thumbnail).toBeVisible();
    await page.getByRole('button', { name: 'Cancelar', exact: true }).click();
    await expect(thumbnail).toBeVisible();
    // A rejected deletion must preserve the card and allow a later retry.
    await page.route('**/midias/*', (route) => route.request().method() === 'POST'
        ? route.fulfill({ status: 422, contentType: 'application/json', body: JSON.stringify({ message: 'O arquivo está em uso e não pode ser excluído.' }) })
        : route.continue());
    await thumbnail.locator('..').getByRole('button', { name: 'Excluir', exact: true }).click();
    await page.getByRole('button', { name: 'Excluir arquivo', exact: true }).click();
    await expect(page.locator('.toast').filter({ hasText: 'O arquivo está em uso' })).toBeVisible();
    await expect(thumbnail).toBeVisible();
    await page.unroute('**/midias/*');
    await thumbnail.locator('..').getByRole('button', { name: 'Excluir', exact: true }).click();
    await page.getByRole('button', { name: 'Excluir arquivo', exact: true }).click();
    await expect(thumbnail).toHaveCount(0);
    expect(await page.evaluate(() => window.shellMarker === document.querySelector('.main-sidebar'))).toBe(true);
});
