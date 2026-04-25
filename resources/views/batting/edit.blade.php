<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            打撃編集
        </h2>
        <x-input-error class="mb-4" :messages="$errors->all()" />
            <x-input-error class="mb-4" :messages="session('error')" />
        <x-message :message="session('message')" />
    </x-slot>

    <div class="max-w-7xl mx-auto px-2 sm:px-6 lg:px-8">
        <div class="sm:p-8">
            @php
                $initialUserId = (string) old('userId', $batting->userId);
                $initialUserName = (string) old('userName', $batting->userName);
                $initialInning = (string) old('inning', $batting->inning);
                $batterOptions = [];
                $selectedBatterKey = '';
                $batterLabel = '未選択';

                foreach ($orders as $index => $order) {
                    $user = $order->userId ? $users->firstWhere('id', (int) $order->userId) : null;
                    $batterName = $user ? $user->name : trim((string) $order->userName);

                    if ($batterName === '') {
                        continue;
                    }

                    $batterKey = 'order-' . ($order->orderId ?? $index);
                    $isSelected = ($initialUserId !== '' && (int) $order->userId === (int) $initialUserId)
                        || ($initialUserId === '' && $initialUserName !== '' && trim((string) $order->userName) === $initialUserName);

                    if ($isSelected) {
                        $selectedBatterKey = $batterKey;
                        $batterLabel = $order->battingOrder . '番 ' . $batterName;
                    }

                    $rankingSuffix = (int) $order->ranking > 1 ? '-' . $order->ranking : '';
                    $batterOptions[] = [
                        'key' => $batterKey,
                        'label' => $order->battingOrder . '番' . $rankingSuffix . ' ' . $batterName,
                        'userId' => $order->userId ? (string) $order->userId : '',
                        'userName' => $order->userId ? '' : $batterName,
                    ];
                }

                if ($selectedBatterKey === '' && $initialUserId !== '') {
                    $fallbackUser = $users->firstWhere('id', (int) $initialUserId) ?: $batting->user;
                    $fallbackName = optional($fallbackUser)->name ?: 'ID:' . $initialUserId;
                    $selectedBatterKey = 'current-user';
                    $batterLabel = $fallbackName;
                    $batterOptions[] = [
                        'key' => $selectedBatterKey,
                        'label' => $fallbackName,
                        'userId' => $initialUserId,
                        'userName' => '',
                    ];
                }

                if ($selectedBatterKey === '' && $initialUserName !== '') {
                    $selectedBatterKey = 'current-name';
                    $batterLabel = $initialUserName;
                    $batterOptions[] = [
                        'key' => $selectedBatterKey,
                        'label' => $initialUserName,
                        'userId' => '',
                        'userName' => $initialUserName,
                    ];
                }

                $directionLabel = trim((string) optional($batting->result2)->name);
                $resultName = trim((string) optional($batting->result1)->name);
                $resultLabel = trim(($directionLabel !== '' ? $directionLabel : '') . $resultName) ?: '結果未設定';
                $rbiLabel = optional($batting->result3)->name;
                $returnToCreate = request('returnTo') === 'create';
                $backUrl = $returnToCreate
                    ? route('batting.create', ['game' => $batting->gameId])
                    : route('batting.index', ['game' => $batting->gameId]);
                $battingConfirmation = session('batting_confirmation');
                $editConfirmationResolution = (string) ($battingConfirmation['resolution'] ?? '');
            @endphp
            <p class="mt-4 mb-4 text-sm text-gray-600">
                <a href="{{ $backUrl }}" class="inline-flex rounded-lg bg-blue-500 px-4 py-2 font-semibold text-white hover:bg-blue-600">
                    {{ $returnToCreate ? '登録画面に戻る' : '打撃成績に戻る' }}
                </a>
            </p>
            @if($battingConfirmation)
                @include('batting.partials.confirmation-alert', [
                    'title' => $battingConfirmation['title'] ?? '確認が必要です。',
                    'message' => $battingConfirmation['message'] ?? 'このまま更新するか確認してください。',
                    'action' => route('batting.update', $batting->id),
                    'resolution' => $editConfirmationResolution,
                    'buttonLabel' => 'このまま更新する',
                    'cancelUrl' => request()->fullUrl(),
                    'hiddenFields' => $returnToCreate ? ['returnTo' => 'create'] : [],
                ])
            @endif
            <form id="batting-edit-form" method="POST" action="{{ route('batting.update',$batting->id) }}" enctype="multipart/form-data">
                @csrf
                @if($returnToCreate)
                    <input type="hidden" name="returnTo" value="create">
                @endif
                <input type="hidden" name="userId" id="userId" value="{{ $initialUserId }}">
                <input type="hidden" name="userName" id="userName" value="{{ $initialUserName }}">

                <section id="batting-edit-meta-panel" class="mt-6 rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
                    <input type="hidden" name="gameId" value="{{$batting->gameId}}">

                    <div>
                        <p class="text-[11px] font-black uppercase tracking-[0.18em] text-slate-400">試合</p>
                        <p class="mt-1 truncate text-sm font-bold text-slate-600">{{$batting->game->gameName}}</p>
                    </div>

                    <div class="mt-4 grid gap-3 sm:grid-cols-[minmax(0,1fr)_14rem]">
                        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-3">
                            <label for="batterSelect" class="text-xs font-black uppercase tracking-[0.18em] text-emerald-700">打者</label>
                            <select class="mt-2 h-14 w-full rounded-2xl border border-emerald-200 bg-white px-3 text-lg font-black text-slate-900" id="batterSelect">
                                <option value="">選択してください</option>
                                @foreach($batterOptions as $option)
                                    <option
                                        value="{{ $option['key'] }}"
                                        data-user-id="{{ $option['userId'] }}"
                                        data-user-name="{{ $option['userName'] }}"
                                        {{ $option['key'] === $selectedBatterKey ? ' selected' : '' }}
                                    >
                                        {{ $option['label'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="rounded-2xl border border-sky-200 bg-sky-50 p-3">
                            <label for="inning" class="text-xs font-black uppercase tracking-[0.18em] text-sky-700">イニング</label>
                            <div class="mt-2 grid grid-cols-[3.25rem_minmax(0,1fr)_3.25rem] gap-2">
                                <button type="button" data-role="inning-decrement" class="rounded-2xl border border-sky-200 bg-white text-2xl font-black text-sky-800">−</button>
                                <input type="number" name="inning" class="h-14 w-full rounded-2xl border border-sky-200 bg-white text-center text-2xl font-black text-slate-900" id="inning" value="{{$initialInning}}" min="1" required>
                                <button type="button" data-role="inning-increment" class="rounded-2xl border border-sky-200 bg-white text-2xl font-black text-sky-800">＋</button>
                            </div>
                        </div>
                    </div>

                    <p data-role="edit-meta-summary" class="mt-3 text-sm font-bold text-slate-700">
                        {{ $initialInning }}回 / {{ $batterLabel }}
                    </p>
                </section>

                @include('batting.partials.result-selector', [
                    'results' => $results,
                    'selectedResultId1' => old('resultId1', $batting->resultId1),
                    'selectedResultId2' => old('resultId2', $batting->resultId2),
                    'selectedResultId3' => old('resultId3', $batting->resultId3),
                ])
                <div class="h-32 sm:h-28"></div>
            </form>
            <form id="batting-delete-form" method="POST" action="{{ route('batting.destroy', $batting->id) }}" class="hidden">
                @csrf
                @method('DELETE')
                @if($returnToCreate)
                    <input type="hidden" name="returnTo" value="create">
                @endif
            </form>
            <div id="batting-edit-action-bar" class="fixed inset-x-0 bottom-0 z-40 border-t border-slate-200 bg-white/95 shadow-[0_-12px_30px_rgba(15,23,42,0.12)] backdrop-blur" style="padding-bottom: max(env(safe-area-inset-bottom), 0px);">
                <div class="mx-auto flex max-w-7xl flex-col gap-3 px-3 py-3 sm:flex-row sm:items-center sm:px-6 lg:px-8">
                    <div class="min-w-0 flex-1">
                        <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-500">編集中の打撃成績</p>
                        <p data-role="edit-sticky-summary" class="mt-1 truncate text-sm font-bold text-slate-900 sm:text-base">
                            {{ $batting->inning }}回@if(($batting->inningTurn ?? 1) > 1) {{ $batting->inningTurn }}打席目@endif / {{ $batterLabel ?: '未設定' }} / {{ $resultLabel }} / 打点 {{ $rbiLabel ?? '未設定' }}
                        </p>
                    </div>
                    <div class="grid grid-cols-2 gap-2 sm:flex sm:shrink-0">
                        <button type="submit" form="batting-delete-form" class="inline-flex items-center justify-center rounded-2xl border border-red-200 bg-white px-5 py-4 text-sm font-black text-red-700 transition hover:bg-red-50" onclick="return confirm('削除してもよろしいですか？');">
                            削除
                        </button>
                        <button type="submit" form="batting-edit-form" data-role="batting-update-button" class="inline-flex items-center justify-center rounded-2xl bg-emerald-600 px-7 py-4 text-base font-black text-white shadow-lg shadow-emerald-900/20 transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:bg-slate-400 sm:px-10">
                            {{ $returnToCreate ? '更新して戻る' : '更新する' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('batting-edit-form');
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

        const getSelectedText = function(select) {
            if (!select || !select.value || select.selectedIndex < 0) {
                return '';
            }

            return select.options[select.selectedIndex].text.trim();
        };

        const getBatterText = function() {
            if (!batterSelect || !batterSelect.value || batterSelect.selectedIndex < 0) {
                return '未選択';
            }

            return batterSelect.options[batterSelect.selectedIndex].text.trim();
        };

        const syncBatterInputs = function() {
            if (!batterSelect || !userIdInput || !userNameInput) {
                return;
            }

            const selectedOption = batterSelect.options[batterSelect.selectedIndex];
            userIdInput.value = selectedOption && selectedOption.dataset.userId ? selectedOption.dataset.userId : '';
            userNameInput.value = selectedOption && selectedOption.dataset.userName ? selectedOption.dataset.userName : '';
        };

        const syncMetaPanelState = function() {
            if (!metaPanel || !batterSelect) {
                return;
            }

            if (batterSelect.value === '') {
                metaPanel.classList.add('ring-2', 'ring-amber-300');
                return;
            }

            metaPanel.classList.remove('ring-2', 'ring-amber-300');
        };

        const moveInning = function(delta) {
            if (!inningInput) {
                return;
            }

            const currentValue = Number(inningInput.value || 1);
            inningInput.value = Math.max(1, currentValue + delta);
            inningInput.dispatchEvent(new Event('input', { bubbles: true }));
            inningInput.dispatchEvent(new Event('change', { bubbles: true }));
        };

        const updateSummary = function() {
            const inning = inningInput && inningInput.value ? inningInput.value : '未設定';
            const batter = getBatterText();
            const resultName = getSelectedText(resultSelect);
            const directionName = getSelectedText(directionSelect);
            const rbiName = getSelectedText(rbiSelect);
            const resultSummary = resultName
                ? ((directionName && directionName !== '空欄' ? directionName : '') + resultName)
                : '結果未設定';

            if (metaSummary) {
                metaSummary.textContent = inning + '回 / ' + batter;
            }

            if (stickySummary) {
                stickySummary.textContent = inning + '回 / ' + batter + ' / ' + resultSummary + ' / 打点 ' + (rbiName || '未設定');
            }
        };

        [resultSelect, directionSelect, rbiSelect, inningInput].forEach(function(select) {
            if (select) {
                select.addEventListener('change', updateSummary);
                select.addEventListener('input', updateSummary);
            }
        });

        if (batterSelect) {
            batterSelect.addEventListener('change', function() {
                syncBatterInputs();
                syncMetaPanelState();
                updateSummary();
            });
        }

        if (inningDecrementButton) {
            inningDecrementButton.addEventListener('click', function() {
                moveInning(-1);
            });
        }

        if (inningIncrementButton) {
            inningIncrementButton.addEventListener('click', function() {
                moveInning(1);
            });
        }

        if (form) {
            form.addEventListener('submit', function(event) {
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
        }

        syncBatterInputs();
        syncMetaPanelState();
        updateSummary();
    });
</script>
