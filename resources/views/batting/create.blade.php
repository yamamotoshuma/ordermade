<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            打撃登録
        </h2>
        <x-input-error class="mb-4" :messages="$errors->all()" />
        <x-input-error class="mb-4" :messages="session('error')" />
        <x-message :message="session('message')" />
    </x-slot>

    <div class="max-w-7xl mx-auto px-2 sm:px-6 lg:px-8">
        <div class="sm:p-8">
            <p class="mt-4 mb-4 text-sm text-gray-600">
                <a href="{{ route('game.show', $game->gameId) }}" class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded-lg">
                    試合詳細に戻る
                </a>
            </p>
            @php
                $defaultUserId = (string) ($createDefaults['defaultUserId'] ?? '');
                $defaultUserName = (string) ($createDefaults['defaultUserName'] ?? '');
                $defaultInning = (int) ($createDefaults['defaultInning'] ?? 1);
                $initialUserId = (string) old('userId', request('userId', $defaultUserId));
                $initialUserName = (string) old('userName', request('userName', $defaultUserName));
                $initialInning = (string) old('inning', request('inning', $defaultInning));
                $createConfig = [
                    'inningOutCounts' => $createDefaults['inningOutCounts'] ?? [],
                    'suggestedInning' => $defaultInning,
                ];
                $initialBatterLabel = '未選択';
                $batterOptions = [];
                $selectedBatterKey = '';

                if ($initialUserId !== '') {
                    $initialOrder = $orders->firstWhere('userId', (int) $initialUserId);
                    $initialUser = $users->firstWhere('id', (int) $initialUserId);

                    if ($initialOrder && $initialUser) {
                        $initialBatterLabel = $initialOrder->battingOrder . '番' . $initialUser->name;
                    }
                } elseif ($initialUserName !== '') {
                    $initialOrder = $orders->first(fn ($order) => trim((string) $order->userName) === $initialUserName);
                    $initialBatterLabel = $initialOrder ? $initialOrder->battingOrder . '番' . $initialUserName : $initialUserName;
                }

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
                    }

                    $rankingSuffix = (int) $order->ranking > 1 ? '-' . $order->ranking : '';
                    $batterOptions[] = [
                        'key' => $batterKey,
                        'label' => $order->battingOrder . '番' . $rankingSuffix . ' ' . $batterName,
                        'userId' => $order->userId ? (string) $order->userId : '',
                        'userName' => $order->userId ? '' : $batterName,
                    ];
                }

                if ($selectedBatterKey === '' && $initialUserName !== '') {
                    $selectedBatterKey = 'manual-current';
                    $batterOptions[] = [
                        'key' => $selectedBatterKey,
                        'label' => $initialUserName,
                        'userId' => '',
                        'userName' => $initialUserName,
                    ];
                }

                $battingConflict = session('batting_conflict');
                $lastBatterLabel = null;
                $lastResultLabel = null;
                $lastRbiLabel = null;
                $defaultRbiId = optional($results->first(fn ($result) => (int) $result->type === 5 && (string) $result->name === '0'))->id;

                if ($lastBattingStat) {
                    $lastBatterLabel = optional($lastBattingStat->user)->name ?: ($lastBattingStat->userName ?: '未設定');
                    $lastDirectionLabel = trim((string) optional($lastBattingStat->result2)->name);
                    $lastResultName = trim((string) optional($lastBattingStat->result1)->name);
                    $lastResultLabel = trim(($lastDirectionLabel !== '' ? $lastDirectionLabel : '') . $lastResultName) ?: '結果未設定';
                    $lastRbiLabel = optional($lastBattingStat->result3)->name;
                }
            @endphp

            @if($battingConflict)
                <div id="batting-conflict-alert" class="mt-6 rounded-2xl border border-amber-300 bg-amber-50 p-4 text-amber-900 shadow-sm">
                    <p class="font-semibold">同じ打者・同じイニングの成績がすでに登録されています。</p>
                    <p class="mt-1 text-sm">{{ $battingConflict['message'] ?? '既存データを更新しますか？' }}</p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <button type="button" data-role="confirm-conflict-update" class="rounded-lg bg-amber-600 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-700">
                            現在の入力内容で更新する
                        </button>
                        <a href="{{ route('batting.create', ['game' => $game]) }}" class="rounded-lg border border-amber-300 bg-white px-4 py-2 text-sm font-semibold text-amber-900 hover:bg-amber-100">
                            やめる
                        </a>
                    </div>
                </div>
            @endif

            @if($lastBattingStat && ! $battingConflict)
                <section id="latest-batting-card" class="mt-6 overflow-hidden rounded-3xl border border-emerald-200 bg-emerald-50 shadow-sm">
                    <div class="border-b border-emerald-100 bg-white/80 px-4 py-3">
                        <p class="text-xs font-bold uppercase tracking-[0.18em] text-emerald-700">直前の入力</p>
                    </div>
                    <div class="p-4">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h3 class="text-lg font-black text-slate-900">
                                    {{ $lastBattingStat->inning }}回 / {{ $lastBatterLabel }} / {{ $lastResultLabel }}
                                </h3>
                                <p class="mt-1 text-sm text-slate-600">
                                    打点 {{ $lastRbiLabel ?? '未設定' }}
                                </p>
                            </div>
                            <div class="grid grid-cols-3 gap-2 sm:flex sm:shrink-0">
                                <a href="{{ route('batting.edit', ['batting' => $lastBattingStat, 'returnTo' => 'create']) }}" class="inline-flex items-center justify-center rounded-2xl bg-slate-900 px-3 py-3 text-sm font-bold text-white hover:bg-slate-800">
                                    修正する
                                </a>
                                <form method="POST" action="{{ route('batting.destroy', ['batting' => $lastBattingStat]) }}">
                                    @csrf
                                    @method('DELETE')
                                    <input type="hidden" name="returnTo" value="create">
                                    <button type="submit" class="inline-flex w-full items-center justify-center rounded-2xl border border-red-200 bg-white px-3 py-3 text-sm font-bold text-red-700 hover:bg-red-50" onclick="return confirm('直前の入力を取り消しますか？');">
                                        取り消す
                                    </button>
                                </form>
                                <a href="#batting-create-form" class="inline-flex items-center justify-center rounded-2xl border border-emerald-200 bg-white px-3 py-3 text-sm font-bold text-emerald-700 hover:bg-emerald-100">
                                    次を入力
                                </a>
                            </div>
                        </div>
                    </div>
                </section>
            @endif

            <form id="batting-create-form" method="POST" action="{{ route('batting.store',$game) }}" enctype="multipart/form-data" data-create-config='@json($createConfig)'>
                @csrf
                <input type="hidden" name="fromEdit" value="{{ old('fromEdit', request('fromEdit', false)) }}">
                <input type="hidden" name="conflictResolution" id="batting-conflict-resolution" value="">
                <input type="hidden" name="userId" id="userId" value="{{ $initialUserId }}">
                <input type="hidden" name="userName" id="userName" value="{{ $initialUserName }}">

                <section id="batting-meta-panel" class="mt-6 rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
                    <input type="hidden" name="gameId" value="{{$game->gameId}}">

                    <div>
                        <p class="text-[11px] font-black uppercase tracking-[0.18em] text-slate-400">試合</p>
                        <p id="gameName" class="mt-1 truncate text-sm font-bold text-slate-600">{{$game->gameName}}</p>
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
                                <input type="number" name="inning" class="h-14 w-full rounded-2xl border border-sky-200 bg-white text-center text-2xl font-black text-slate-900" id="inning" value="{{ $initialInning }}" min="1" required>
                                <button type="button" data-role="inning-increment" class="rounded-2xl border border-sky-200 bg-white text-2xl font-black text-sky-800">＋</button>
                            </div>
                            <p data-role="inning-status" class="mt-2 text-sm text-slate-500"></p>
                        </div>
                    </div>

                    <p data-role="batting-meta-summary" class="mt-3 text-sm font-bold text-slate-700">
                        {{ $initialInning }}回 / {{ $initialBatterLabel }}
                    </p>
                </section>

                @include('batting.partials.result-selector', [
                    'results' => $results,
                    'selectedResultId1' => old('resultId1'),
                    'selectedResultId2' => old('resultId2'),
                    'selectedResultId3' => old('resultId3', $defaultRbiId),
                ])
                <div class="h-32 sm:h-28"></div>
                <div id="batting-submit-bar" class="fixed inset-x-0 bottom-0 z-40 border-t border-slate-200 bg-white/95 shadow-[0_-12px_30px_rgba(15,23,42,0.12)] backdrop-blur" style="padding-bottom: max(env(safe-area-inset-bottom), 0px);">
                    <div class="mx-auto flex max-w-7xl items-center gap-3 px-3 py-3 sm:px-6 lg:px-8">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap gap-1.5">
                                <span data-role="sticky-inning-chip" class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-black text-slate-700">{{ $initialInning }}回</span>
                                <span data-role="sticky-out-count-chip" class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-black text-slate-700">0アウト</span>
                                <span data-role="sticky-batter-chip" class="max-w-full truncate rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-black text-emerald-800">次: {{ $initialBatterLabel }}</span>
                            </div>
                            <p data-role="sticky-submit-summary" class="mt-1.5 truncate text-base font-black text-slate-950 sm:text-lg">
                                結果未選択 / 打点 0
                            </p>
                        </div>
                        <button type="submit" data-role="batting-submit-button" class="inline-flex shrink-0 items-center justify-center rounded-2xl bg-emerald-600 px-6 py-4 text-base font-black text-white shadow-lg shadow-emerald-900/20 transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:bg-slate-400 sm:px-10">
                            登録する
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <div id="loading" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; z-index:1000; background-color: rgba(128, 128, 128, 0.8);">
        <div class="loader" style="top: 50%; left: 50%; position: absolute; transform: translate(-50%, -50%);"></div>
    </div>
</x-app-layout>
<script>
    function showLoading() {
        document.getElementById('loading').style.display = 'block';
        clearMessages();
    }

    function hideLoading() {
        document.getElementById('loading').style.display = 'none';
    }

    function clearMessages() {
        const errors = document.querySelectorAll('.x-input-error');
        errors.forEach(error => error.innerHTML = '');
        const message = document.querySelector('.x-message');
        if (message) {
            message.innerHTML = '';
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('batting-create-form');
        const batterSelect = document.getElementById('batterSelect');
        const userIdInput = document.getElementById('userId');
        const userNameInput = document.getElementById('userName');
        const result1Select = document.getElementById('resultId1');
        const result2Select = document.getElementById('resultId2');
        const result3Select = document.getElementById('resultId3');
        const inningInput = document.getElementById('inning');
        const inningDecrementButton = document.querySelector('[data-role="inning-decrement"]');
        const inningIncrementButton = document.querySelector('[data-role="inning-increment"]');
        const inningStatus = document.querySelector('[data-role="inning-status"]');
        const gameNameLabel = document.getElementById('gameName');
        const metaSummary = document.querySelector('[data-role="batting-meta-summary"]');
        const metaPanel = document.getElementById('batting-meta-panel');
        const conflictUpdateButton = document.querySelector('[data-role="confirm-conflict-update"]');
        const conflictResolutionInput = document.getElementById('batting-conflict-resolution');
        const stickySubmitSummary = document.querySelector('[data-role="sticky-submit-summary"]');
        const stickyInningChip = document.querySelector('[data-role="sticky-inning-chip"]');
        const stickyOutCountChip = document.querySelector('[data-role="sticky-out-count-chip"]');
        const stickyBatterChip = document.querySelector('[data-role="sticky-batter-chip"]');
        const submitButton = document.querySelector('[data-role="batting-submit-button"]');
        const createConfig = form ? JSON.parse(form.dataset.createConfig || '{}') : {};
        let isSubmitting = false;

        const getSelectedText = function(select) {
            if (!select || !select.value || select.selectedIndex < 0) {
                return '';
            }

            return select.options[select.selectedIndex].text.trim();
        };

        const updateMetaSummary = function() {
            const selectedOption = batterSelect && batterSelect.selectedIndex >= 0
                ? batterSelect.options[batterSelect.selectedIndex]
                : null;
            const batterName = selectedOption && selectedOption.value
                ? selectedOption.text.trim()
                : '未選択';
            const inning = inningInput && inningInput.value ? inningInput.value : '未設定';

            if (metaSummary) {
                metaSummary.textContent = inning + '回 / ' + batterName;
            }

            if (stickySubmitSummary) {
                const resultName = getSelectedText(result1Select);
                const directionName = getSelectedText(result2Select);
                const rbiName = getSelectedText(result3Select);
                const resultSummary = resultName
                    ? ((directionName && directionName !== '空欄' ? directionName : '') + resultName)
                    : '結果未選択';
                const rbiSummary = ' / 打点 ' + (rbiName || '未設定');

                stickySubmitSummary.textContent = resultSummary + rbiSummary;
            }

            if (stickyInningChip) {
                stickyInningChip.textContent = inning + '回';
            }

            if (stickyOutCountChip) {
                const outCount = getCurrentOutCount();
                stickyOutCountChip.textContent = outCount + 'アウト';
                stickyOutCountChip.className = 'rounded-full px-2.5 py-1 text-xs font-black';
                stickyOutCountChip.classList.add(outCount >= 3 ? 'bg-amber-100' : 'bg-slate-100', outCount >= 3 ? 'text-amber-800' : 'text-slate-700');
            }

            if (stickyBatterChip) {
                stickyBatterChip.textContent = '次: ' + batterName;
            }
        };

        const lockSubmitButton = function() {
            if (!submitButton) {
                return;
            }

            submitButton.disabled = true;
            submitButton.textContent = '登録中...';
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

        const getCurrentOutCount = function() {
            if (!inningInput || !inningInput.value) {
                return 0;
            }

            const inningOutCounts = createConfig.inningOutCounts || {};

            return Number(inningOutCounts[inningInput.value] || 0);
        };

        const updateInningStatus = function() {
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
                inningStatus.textContent = inningInput.value + '回はすでに' + outCount + 'アウト入力されています。続けて登録する場合は確認が出ます。';
                inningStatus.classList.add('font-semibold', 'text-amber-700');
                return;
            }

            if (outCount > 0) {
                inningStatus.textContent = inningInput.value + '回は現在 ' + outCount + ' アウトです。';
                inningStatus.classList.add('text-slate-600');
                return;
            }

            if (String(createConfig.suggestedInning || '') === String(inningInput.value)) {
                inningStatus.textContent = inningInput.value + '回を初期表示しています。';
                inningStatus.classList.add('text-slate-500');
                return;
            }

            inningStatus.textContent = '';
            inningStatus.classList.add('text-slate-500');
        };

        if (batterSelect) {
            batterSelect.addEventListener('change', function() {
                clearMessages();
                syncBatterInputs();
                updateMetaSummary();
                syncMetaPanelState();
            });
        }

        if (result1Select) {
            result1Select.addEventListener('change', function() {
                updateMetaSummary();
            });
        }

        if (result2Select) {
            result2Select.addEventListener('change', function() {
                updateMetaSummary();
            });
        }

        if (result3Select) {
            result3Select.addEventListener('change', function() {
                updateMetaSummary();
            });
        }

        if (inningInput) {
            inningInput.addEventListener('input', updateMetaSummary);
            inningInput.addEventListener('change', updateMetaSummary);
            inningInput.addEventListener('input', updateInningStatus);
            inningInput.addEventListener('change', updateInningStatus);
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

                const outCount = getCurrentOutCount();

                if (outCount >= 3) {
                    const confirmed = window.confirm(
                        inningInput.value + '回にはすでに' + outCount + 'アウト入力されています。本当に登録しますか？'
                    );

                    if (!confirmed) {
                        hideLoading();
                        event.preventDefault();
                        return;
                    }
                }

                isSubmitting = true;
                lockSubmitButton();
                showLoading();
            });
        }

        if (conflictUpdateButton && form && conflictResolutionInput) {
            conflictUpdateButton.addEventListener('click', function() {
                const confirmed = window.confirm('すでに登録されている打撃成績を、現在の入力内容で更新しますか？');

                if (!confirmed) {
                    return;
                }

                conflictResolutionInput.value = 'update';
                isSubmitting = true;
                lockSubmitButton();
                showLoading();
                form.submit();
            });
        }

        syncBatterInputs();
        updateMetaSummary();
        syncMetaPanelState();
        updateInningStatus();

        [result1Select, result2Select, result3Select].forEach(function(select) {
            if (select) {
                select.dispatchEvent(new Event('change', { bubbles: true }));
            }
        });
    });
</script>
<style>
    .loader {
        border: 16px solid #f3f3f3; /* Light grey */
        border-top: 16px solid #3498db; /* Blue */
        border-radius: 50%;
        width: 60px;
        height: 60px;
        animation: spin 2s linear infinite;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
</style>
