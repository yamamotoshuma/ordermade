import { expect, test } from '@playwright/test';

const fixture = {
    email: 'visual-regression@example.com',
    password: 'password',
    gameId: 990001,
};

test.describe('試合詳細画面のビジュアルリグレッション', () => {
    test('打撃成績セクションとページ全体が崩れていない', async ({ page }) => {
        await page.goto('/_testing/visual-regression/seed');
        await expect(page.locator('body')).toContainText('ok');

        await page.goto('/login');

        await page.getByLabel('メールアドレス').fill(fixture.email);
        await page.getByLabel('パスワード').fill(fixture.password);
        await page.getByRole('button', { name: 'ログイン' }).click();

        await page.goto(`/game/${fixture.gameId}`);
        await expect(page.getByRole('heading', { name: '試合詳細' })).toBeVisible();
        await expect(page.getByRole('heading', { name: '打撃成績' })).toBeVisible();

        const battingHeading = page.getByRole('heading', { name: '打撃成績' });
        const battingCard = battingHeading.locator('xpath=ancestor::div[contains(@class,"bg-white") and contains(@class,"rounded-lg")][1]');
        const battingTable = battingCard.locator('table').first();

        for (const header of ['打順', '守備位置', '選手名', '打数', '安打', '打点', '打率', '盗塁']) {
            await expect(battingTable).toContainText(header);
        }

        const inningHeaders = await battingTable.locator('thead th').evaluateAll((headers) =>
            headers.slice(8).map((header) => (header.textContent || '').trim()),
        );
        expect(inningHeaders).toContain('3');
        expect(inningHeaders).toContain('');

        await expect(battingCard).toHaveScreenshot('game-show-batting-card.png', {
            animations: 'disabled',
            caret: 'hide',
        });

        await expect(page).toHaveScreenshot('game-show-full-page.png', {
            animations: 'disabled',
            caret: 'hide',
            fullPage: true,
        });
    });
});
