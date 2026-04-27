export function initOffenseStatePanel(options = {}) {
    const panel = document.getElementById('offense-state-panel');
    const sheet = document.getElementById('runner-sheet');
    const backdrop = document.getElementById('runner-sheet-backdrop');
    const runnerEventForm = document.getElementById('runner-event-form');
    const runnerUndoForm = document.getElementById('runner-undo-form');

    if (!panel || !sheet || !backdrop || !runnerEventForm || !runnerUndoForm) {
        return;
    }

    const showLoading = options.showLoading || (() => {});
    const hideLoading = options.hideLoading || (() => {});
    const openButtons = Array.from(document.querySelectorAll('[data-role="runner-sheet-open"]'));
    const closeButtons = Array.from(document.querySelectorAll('[data-role="runner-sheet-close"]'));
    const runnerActionButtons = Array.from(document.querySelectorAll('[data-role="runner-action-submit"]'));
    const runnerUndoButton = document.querySelector('[data-role="runner-undo-submit"]');
    const manualRunnerSubmitButton = document.querySelector('[data-role="manual-runner-submit"]');
    const manualRunnerSelect = document.getElementById('manualRunnerSelect');
    const manualRunnerTargetBase = document.getElementById('manualRunnerTargetBase');
    const manualRunnerOrderId = document.getElementById('manualRunnerOrderId');
    const manualRunnerUserId = document.getElementById('manualRunnerUserId');
    const manualRunnerUserName = document.getElementById('manualRunnerUserName');
    const manualRunnerDisplayName = document.getElementById('manualRunnerDisplayName');
    const runnerEventAction = document.getElementById('runnerEventAction');
    const runnerEventBase = document.getElementById('runnerEventBase');
    const runnerEventTargetBase = document.getElementById('runnerEventTargetBase');
    const runnerEventOrderId = document.getElementById('runnerEventOrderId');
    const runnerEventUserId = document.getElementById('runnerEventUserId');
    const runnerEventUserName = document.getElementById('runnerEventUserName');
    const runnerEventDisplayName = document.getElementById('runnerEventDisplayName');
    const manualActionName = manualRunnerSubmitButton?.dataset.manualAction || '';

    const submitRunnerEvent = (payload) => {
        runnerEventAction.value = payload.action || '';
        runnerEventBase.value = payload.base || '';
        runnerEventTargetBase.value = payload.targetBase || '';
        runnerEventOrderId.value = payload.orderId || '';
        runnerEventUserId.value = payload.userId || '';
        runnerEventUserName.value = payload.userName || '';
        runnerEventDisplayName.value = payload.displayName || '';
        showLoading();
        runnerEventForm.submit();
    };

    const openSheet = () => {
        sheet.classList.remove('hidden');
        backdrop.classList.remove('hidden');
        document.body.classList.add('runner-sheet-open');
    };

    const closeSheet = () => {
        sheet.classList.add('hidden');
        backdrop.classList.add('hidden');
        document.body.classList.remove('runner-sheet-open');
    };

    openButtons.forEach((button) => button.addEventListener('click', openSheet));
    closeButtons.forEach((button) => button.addEventListener('click', closeSheet));
    backdrop.addEventListener('click', closeSheet);

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeSheet();
        }
    });

    runnerActionButtons.forEach((button) => {
        button.addEventListener('click', () => {
            submitRunnerEvent({
                action: button.dataset.action,
                base: button.dataset.base,
            });
        });
    });

    runnerUndoButton?.addEventListener('click', () => {
        const confirmed = window.confirm('直前の走者操作を取り消しますか？');

        if (!confirmed) {
            hideLoading();
            return;
        }

        showLoading();
        runnerUndoForm.submit();
    });

    manualRunnerSelect?.addEventListener('change', () => {
        const selectedOption = manualRunnerSelect.options[manualRunnerSelect.selectedIndex];
        manualRunnerOrderId.value = selectedOption?.dataset.orderId || '';
        manualRunnerUserId.value = selectedOption?.dataset.userId || '';
        manualRunnerUserName.value = selectedOption?.dataset.userName || '';
        manualRunnerDisplayName.value = selectedOption?.dataset.displayName || '';
    });

    manualRunnerSubmitButton?.addEventListener('click', () => {
        if (!manualRunnerSelect?.value) {
            window.alert('走者を選択してください。');
            return;
        }

        if (!manualRunnerTargetBase?.value) {
            window.alert('配置先の塁を選択してください。');
            return;
        }

        submitRunnerEvent({
            action: manualActionName,
            targetBase: manualRunnerTargetBase.value,
            orderId: manualRunnerOrderId.value,
            userId: manualRunnerUserId.value,
            userName: manualRunnerUserName.value,
            displayName: manualRunnerDisplayName.value,
        });
    });

    if (panel.dataset.autoOpen === 'true') {
        openSheet();
    }
}
