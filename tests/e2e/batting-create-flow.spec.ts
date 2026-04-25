import { expect, test, type Page } from '@playwright/test';

const fixture = {
    email: 'e2e-batting@example.com',
    password: 'password',
    rbiGameId: 991101,
    duplicateGameId: 991102,
    runnerGameId: 991103,
    duplicateFirstOrderId: '991211',
};

const login = async (page: Page) => {
    await page.goto('/login');
    await page.getByLabel('メールアドレス').fill(fixture.email);
    await page.getByLabel('パスワード').fill(fixture.password);
    await page.getByRole('button', { name: 'ログイン' }).click();
};

const seedFixture = async (page: Page) => {
    await page.goto('/_testing/batting-flow/seed');
    await expect(page.locator('body')).toContainText('ok');
};

const openCreateScreen = async (page: Page, gameId: number) => {
    await page.goto(`/batting/${gameId}/create`);
};

const fillSimpleSingle = async (page: Page) => {
    await page.getByRole('button', { name: '安打' }).click();
    await page.getByRole('button', { name: '左', exact: true }).click();
    await page.getByRole('button', { name: '0', exact: true }).click();
};

const submitBatting = async (page: Page) => {
    await page.getByRole('button', { name: '登録する' }).click();
};

const expectWarningAlert = async (page: Page, text: string) => {
    await expect(page.locator('#batting-conflict-alert')).toContainText(text);
};

test.describe('打撃登録の主要フロー', () => {
    test.beforeEach(async ({ page }) => {
        await seedFixture(page);
        await login(page);
    });

    test('打点警告の確認後もそのまま登録できる', async ({ page }) => {
        await openCreateScreen(page, fixture.rbiGameId);
        await fillSimpleSingle(page);
        await submitBatting(page);

        await expectWarningAlert(page, '打点を確認してください。');
        await expectWarningAlert(page, '満塁');

        await page.getByRole('button', { name: 'このまま登録する' }).click();

        await expect(page.locator('#latest-batting-card')).toContainText('四番四郎');
        await expect(page.locator('#latest-batting-card')).toContainText('左安打');
        await expect(page.locator('body')).not.toContainText('user id フィールドは');
        await expect(page.locator('body')).not.toContainText('user name フィールドは');
    });

    test('打点警告をキャンセルした場合は登録されず、再入力すると再度警告される', async ({ page }) => {
        await openCreateScreen(page, fixture.rbiGameId);
        await fillSimpleSingle(page);
        await submitBatting(page);

        await expectWarningAlert(page, '打点を確認してください。');
        await page.getByRole('link', { name: 'やめる' }).click();

        await expect(page.locator('#batting-conflict-alert')).toHaveCount(0);
        await expect(page.locator('#latest-batting-card')).toHaveCount(0);

        await fillSimpleSingle(page);
        await submitBatting(page);
        await expectWarningAlert(page, '打点を確認してください。');
    });

    test('重複警告の確認後に同一イニングの2打席目を追加できる', async ({ page }) => {
        await openCreateScreen(page, fixture.duplicateGameId);

        await page.locator('#batterSelect').selectOption(fixture.duplicateFirstOrderId);
        await fillSimpleSingle(page);
        await submitBatting(page);

        await expectWarningAlert(page, '同じ打者・同じイニング');
        await page.getByRole('button', { name: 'このまま追加する' }).click();

        await expect(page.locator('#latest-batting-card')).toContainText('1回 2打席目');
        await expect(page.locator('#latest-batting-card')).toContainText('一番太郎');
        await expect(page.locator('#latest-batting-card')).toContainText('左安打');
    });

    test('重盗と取り消しで走者状態が更新される', async ({ page }) => {
        await openCreateScreen(page, fixture.runnerGameId);

        const offensePanel = page.locator('#offense-state-panel');
        await expect(offensePanel).toContainText('一塁');
        await expect(offensePanel).toContainText('二番次郎');
        await expect(offensePanel).toContainText('二塁');
        await expect(offensePanel).toContainText('一番太郎');

        await page.getByRole('button', { name: '走者操作', exact: true }).click();

        const firstBaseRunnerCard = page.locator('#runner-sheet section').filter({ hasText: '一塁走者' }).first();
        await firstBaseRunnerCard.getByRole('button', { name: '重盗' }).click();

        await expect(offensePanel).toContainText('三塁');
        await expect(offensePanel).toContainText('一番太郎');

        await page.getByRole('button', { name: '走者操作', exact: true }).click();
        page.once('dialog', (dialog) => dialog.accept());
        await page.getByRole('button', { name: '直前の走者操作を取り消す' }).click();

        await expect(offensePanel).toContainText('二番次郎');
        await expect(offensePanel).toContainText('一番太郎');
        await expect(offensePanel).not.toContainText('三塁走者 一番太郎');
    });
});
