export function initBattingEditPage() {
    const form = document.getElementById('batting-edit-form');

    if (!form) {
        return;
    }

    const updateButton = document.querySelector('[data-role="batting-update-button"]');
    const stickySummary = document.querySelector('[data-role="edit-sticky-summary"]');
    const metaSummary = document.querySelector('[data-role="edit-meta-summary"]');
    const metaPanel = document.getElementById('batting-edit-meta-panel');
    const batterSelect = document.getElementById('batterSelect');
    const userIdInput = document.getElementById('userId');
    const userNameInput = document.getElementById('userName');
    const resultSelect = document.getElementById('resultId1');
    const directionSelect = document.getElementById('resultId2');
    const rbiSelect = document.getElementById('resultId3');
    const inningInput = document.getElementById('inning');
    const inningDecrementButton = document.querySelector('[data-role="inning-decrement"]');
    const inningIncrementButton = document.querySelector('[data-role="inning-increment"]');
    let isSubmitting = false;

    const getSelectedText = (select) => {
        if (!select || !select.value || select.selectedIndex < 0) {
            return '';
        }

        return select.options[select.selectedIndex].text.trim();
    };

    const getBatterText = () => {
        if (!batterSelect || !batterSelect.value || batterSelect.selectedIndex < 0) {
            return '未選択';
        }

        return batterSelect.options[batterSelect.selectedIndex].text.trim();
    };

    const syncBatterInputs = () => {
        if (!batterSelect || !userIdInput || !userNameInput) {
            return;
        }

        const selectedOption = batterSelect.options[batterSelect.selectedIndex];
        userIdInput.value = selectedOption?.dataset.userId || '';
        userNameInput.value = selectedOption?.dataset.userName || '';
    };

    const syncMetaPanelState = () => {
        if (!metaPanel || !batterSelect) {
            return;
        }

        metaPanel.classList.toggle('ring-2', batterSelect.value === '');
        metaPanel.classList.toggle('ring-amber-300', batterSelect.value === '');
    };

    const moveInning = (delta) => {
        if (!inningInput) {
            return;
        }

        const currentValue = Number(inningInput.value || 1);
        inningInput.value = String(Math.max(1, currentValue + delta));
        inningInput.dispatchEvent(new Event('input', { bubbles: true }));
        inningInput.dispatchEvent(new Event('change', { bubbles: true }));
    };

    const updateSummary = () => {
        const inning = inningInput?.value || '未設定';
        const batter = getBatterText();
        const resultName = getSelectedText(resultSelect);
        const directionName = getSelectedText(directionSelect);
        const rbiName = getSelectedText(rbiSelect);
        const resultSummary = resultName
            ? `${directionName && directionName !== '空欄' ? directionName : ''}${resultName}`
            : '結果未設定';

        if (metaSummary) {
            metaSummary.textContent = `${inning}回 / ${batter}`;
        }

        if (stickySummary) {
            stickySummary.textContent = `${inning}回 / ${batter} / ${resultSummary} / 打点 ${rbiName || '未設定'}`;
        }
    };

    [resultSelect, directionSelect, rbiSelect, inningInput].forEach((select) => {
        select?.addEventListener('change', updateSummary);
        select?.addEventListener('input', updateSummary);
    });

    batterSelect?.addEventListener('change', () => {
        syncBatterInputs();
        syncMetaPanelState();
        updateSummary();
    });

    inningDecrementButton?.addEventListener('click', () => moveInning(-1));
    inningIncrementButton?.addEventListener('click', () => moveInning(1));

    form.addEventListener('submit', (event) => {
        if (isSubmitting) {
            event.preventDefault();
            return;
        }

        syncBatterInputs();
        isSubmitting = true;

        if (updateButton) {
            updateButton.disabled = true;
            updateButton.textContent = '更新中...';
        }
    });

    syncBatterInputs();
    syncMetaPanelState();
    updateSummary();
}
