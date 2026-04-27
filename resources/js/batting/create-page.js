import { initOffenseStatePanel } from './offense-state-panel';

export function initBattingCreatePage() {
    const form = document.getElementById('batting-create-form');

    if (!form) {
        return;
    }

    const loading = document.getElementById('loading');
    const batterSelect = document.getElementById('batterSelect');
    const userIdInput = document.getElementById('userId');
    const userNameInput = document.getElementById('userName');
    const result1Select = document.getElementById('resultId1');
    const result2Select = document.getElementById('resultId2');
    const result3Select = document.getElementById('resultId3');
    const resultSelectorRoot = document.getElementById('batting-result-selector');
    const inningInput = document.getElementById('inning');
    const inningDecrementButton = document.querySelector('[data-role="inning-decrement"]');
    const inningIncrementButton = document.querySelector('[data-role="inning-increment"]');
    const inningStatus = document.querySelector('[data-role="inning-status"]');
    const metaSummary = document.querySelector('[data-role="batting-meta-summary"]');
    const metaPanel = document.getElementById('batting-meta-panel');
    const stickySubmitSummary = document.querySelector('[data-role="sticky-submit-summary"]');
    const stickyInningChip = document.querySelector('[data-role="sticky-inning-chip"]');
    const stickyOutCountChip = document.querySelector('[data-role="sticky-out-count-chip"]');
    const stickyBatterChip = document.querySelector('[data-role="sticky-batter-chip"]');
    const submitButton = document.querySelector('[data-role="batting-submit-button"]');
    const createConfig = JSON.parse(form.dataset.createConfig || '{}');
    let isSubmitting = false;

    const clearMessages = () => {
        document.querySelectorAll('.x-input-error').forEach((error) => {
            error.innerHTML = '';
        });

        const message = document.querySelector('.x-message');
        if (message) {
            message.innerHTML = '';
        }
    };

    const showLoading = () => {
        if (!loading) {
            return;
        }

        loading.style.opacity = '1';
        loading.style.visibility = 'visible';
        loading.style.pointerEvents = 'auto';
        clearMessages();
    };

    const hideLoading = () => {
        if (!loading) {
            return;
        }

        loading.style.opacity = '0';
        loading.style.visibility = 'hidden';
        loading.style.pointerEvents = 'none';
    };

    window.showLoading = showLoading;
    window.hideLoading = hideLoading;

    const resetSubmitState = () => {
        isSubmitting = false;
        hideLoading();

        if (!submitButton) {
            return;
        }

        submitButton.disabled = false;
        submitButton.textContent = '登録する';
    };

    const getSelectedText = (select) => {
        if (!select || !select.value || select.selectedIndex < 0) {
            return '';
        }

        return select.options[select.selectedIndex].text.trim();
    };

    const getCurrentOutCount = () => {
        if (!inningInput?.value) {
            return 0;
        }

        if (String(createConfig.currentStateInning || '') === String(inningInput.value)) {
            return Number(createConfig.currentOutCount || 0);
        }

        return Number((createConfig.inningOutCounts || {})[inningInput.value] || 0);
    };

    const updateMetaSummary = () => {
        const selectedOption = batterSelect && batterSelect.selectedIndex >= 0
            ? batterSelect.options[batterSelect.selectedIndex]
            : null;
        const batterName = selectedOption && selectedOption.value
            ? selectedOption.text.trim()
            : '未選択';
        const inning = inningInput?.value || '未設定';

        if (metaSummary) {
            metaSummary.textContent = `${inning}回 / ${batterName}`;
        }

        if (stickySubmitSummary) {
            const resultName = getSelectedText(result1Select);
            const directionName = getSelectedText(result2Select);
            const rbiName = getSelectedText(result3Select);
            const resultSummary = resultName
                ? `${directionName && directionName !== '空欄' ? directionName : ''}${resultName}`
                : '結果未選択';

            stickySubmitSummary.textContent = `${resultSummary} / 打点 ${rbiName || '未設定'}`;
        }

        if (stickyInningChip) {
            stickyInningChip.textContent = `${inning}回`;
        }

        if (stickyOutCountChip) {
            const outCount = getCurrentOutCount();
            stickyOutCountChip.textContent = `${outCount}アウト`;
            stickyOutCountChip.className = 'rounded-full px-2.5 py-1 text-xs font-black';
            stickyOutCountChip.classList.add(
                outCount >= 3 ? 'bg-amber-100' : 'bg-slate-100',
                outCount >= 3 ? 'text-amber-800' : 'text-slate-700',
            );
        }

        if (stickyBatterChip) {
            stickyBatterChip.textContent = `次: ${batterName}`;
        }
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

    const updateInningStatus = () => {
        if (!inningStatus || !inningInput) {
            return;
        }

        const outCount = getCurrentOutCount();
        inningStatus.className = 'mt-2 text-sm';

        if (!inningInput.value) {
            inningStatus.textContent = '';
            inningStatus.classList.add('text-slate-500');
            return;
        }

        if (outCount >= 3) {
            inningStatus.textContent = `${inningInput.value}回はすでに${outCount}アウト入力されています。続けて登録する場合は確認が出ます。`;
            inningStatus.classList.add('font-semibold', 'text-amber-700');
            return;
        }

        inningStatus.textContent = '';
        inningStatus.classList.add('text-slate-500');
    };

    const scrollToResultSelector = () => {
        resultSelectorRoot?.scrollIntoView({
            behavior: 'smooth',
            block: 'start',
        });
    };

    const validateBeforeSubmit = () => {
        syncBatterInputs();

        if (!userIdInput?.value && !userNameInput?.value) {
            window.alert('打者を選択してください。');
            batterSelect?.focus();
            syncMetaPanelState();
            return false;
        }

        if (!inningInput?.value || Number(inningInput.value) < 1) {
            window.alert('イニングを入力してください。');
            inningInput?.focus();
            return false;
        }

        if (!result1Select?.value) {
            window.alert('結果を選択してください。');
            scrollToResultSelector();
            return false;
        }

        if (!result2Select?.value) {
            window.alert('打球方向を選択してください。');
            scrollToResultSelector();
            return false;
        }

        if (!result3Select?.value) {
            window.alert('打点を選択してください。');
            scrollToResultSelector();
            return false;
        }

        return true;
    };

    const lockSubmitButton = () => {
        if (!submitButton) {
            return;
        }

        submitButton.disabled = true;
        submitButton.textContent = '登録中...';
    };

    hideLoading();
    resetSubmitState();
    window.addEventListener('pageshow', resetSubmitState);

    batterSelect?.addEventListener('change', () => {
        clearMessages();
        syncBatterInputs();
        updateMetaSummary();
        syncMetaPanelState();
    });

    [result1Select, result2Select, result3Select].forEach((select) => {
        select?.addEventListener('change', updateMetaSummary);
    });

    inningInput?.addEventListener('input', updateMetaSummary);
    inningInput?.addEventListener('change', updateMetaSummary);
    inningInput?.addEventListener('input', updateInningStatus);
    inningInput?.addEventListener('change', updateInningStatus);

    inningDecrementButton?.addEventListener('click', () => moveInning(-1));
    inningIncrementButton?.addEventListener('click', () => moveInning(1));

    form.addEventListener('submit', (event) => {
        if (isSubmitting) {
            event.preventDefault();
            return;
        }

        if (!validateBeforeSubmit()) {
            hideLoading();
            event.preventDefault();
            return;
        }

        const outCount = getCurrentOutCount();
        if (outCount >= 3) {
            const confirmed = window.confirm(`${inningInput.value}回にはすでに${outCount}アウト入力されています。本当に登録しますか？`);

            if (!confirmed) {
                resetSubmitState();
                event.preventDefault();
                return;
            }
        }

        isSubmitting = true;
        lockSubmitButton();
        showLoading();
    });

    submitButton?.addEventListener('click', () => {
        syncBatterInputs();

        if (typeof form.requestSubmit === 'function') {
            form.requestSubmit();
            return;
        }

        const submitEvent = new Event('submit', { bubbles: true, cancelable: true });
        if (form.dispatchEvent(submitEvent)) {
            form.submit();
        }
    });

    syncBatterInputs();
    updateMetaSummary();
    syncMetaPanelState();
    updateInningStatus();

    [result1Select, result2Select, result3Select].forEach((select) => {
        select?.dispatchEvent(new Event('change', { bubbles: true }));
    });

    initOffenseStatePanel({ showLoading, hideLoading });
}
