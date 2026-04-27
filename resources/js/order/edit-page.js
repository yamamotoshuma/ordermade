const orderRowSelector = '[data-order-row]';

const getRows = (rowsContainer) => Array.from(rowsContainer.querySelectorAll(orderRowSelector));

const getNextBattingOrder = (rowsContainer) => {
    const maxOrder = getRows(rowsContainer).reduce((max, row) => {
        const input = row.querySelector('[data-order-batting-order]');
        const value = Number.parseInt(input?.value ?? '', 10);

        if (Number.isNaN(value)) {
            return max;
        }

        return Math.max(max, value);
    }, 0);

    return maxOrder + 1;
};

const setText = (element, text) => {
    if (element) {
        element.textContent = text;
    }
};

const updateRowTitles = (row, index) => {
    const battingOrderInput = row.querySelector('[data-order-batting-order]');
    const playerSelect = row.querySelector('[data-order-user-id]');
    const playerNameInput = row.querySelector('[data-order-user-name]');
    const battingOrder = (battingOrderInput?.value ?? '').trim();
    const selectedPlayerName = playerSelect?.selectedOptions?.[0]?.textContent?.trim() ?? '';
    const manualPlayerName = (playerNameInput?.value ?? '').trim();
    const playerName = selectedPlayerName && playerSelect?.value ? selectedPlayerName : manualPlayerName;
    const title = battingOrder ? `${battingOrder}番` : `${index + 1}行目`;

    setText(row.querySelector('[data-order-row-title]'), title);
    setText(row.querySelector('[data-order-row-player]'), playerName || '未入力');
};

const syncRankings = (rowsContainer) => {
    const counters = {};

    getRows(rowsContainer).forEach((row, index) => {
        const battingOrderInput = row.querySelector('[data-order-batting-order]');
        const rankingInput = row.querySelector('[data-order-ranking-input]');
        const rankingLabel = row.querySelector('[data-order-ranking-label]');
        const battingOrder = (battingOrderInput?.value ?? '').trim();
        let ranking = 1;

        if (battingOrder !== '') {
            counters[battingOrder] = (counters[battingOrder] ?? 0) + 1;
            ranking = counters[battingOrder];
        }

        if (rankingInput) {
            rankingInput.value = ranking;
        }

        setText(rankingLabel, ranking > 1 ? `控え順 ${ranking}` : '先発');
        updateRowTitles(row, index);
    });
};

const bindRow = (row, rowsContainer) => {
    row.querySelectorAll('input, select').forEach((field) => {
        field.addEventListener('input', () => syncRankings(rowsContainer));
        field.addEventListener('change', () => syncRankings(rowsContainer));
    });
};

const appendRow = (template, rowsContainer) => {
    const fragment = template.content.cloneNode(true);
    const row = fragment.querySelector(orderRowSelector);

    if (! row) {
        return;
    }

    const battingOrderInput = row.querySelector('[data-order-batting-order]');

    if (battingOrderInput) {
        battingOrderInput.value = getNextBattingOrder(rowsContainer);
    }

    bindRow(row, rowsContainer);
    rowsContainer.appendChild(fragment);
    syncRankings(rowsContainer);
    row.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
};

export function initOrderEditPage() {
    const page = document.querySelector('[data-order-edit-page]');

    if (! page || page.dataset.orderEditInitialized === 'true') {
        return;
    }

    const rowsContainer = page.querySelector('[data-order-rows]');
    const template = page.querySelector('[data-order-row-template]');

    if (! rowsContainer || ! template) {
        return;
    }

    page.dataset.orderEditInitialized = 'true';

    getRows(rowsContainer).forEach((row) => bindRow(row, rowsContainer));
    page.querySelectorAll('[data-order-add-row]').forEach((button) => {
        button.addEventListener('click', () => appendRow(template, rowsContainer));
    });
    syncRankings(rowsContainer);
}
