@php
    $selectedResultId1 = (string) ($selectedResultId1 ?? '');
    $selectedResultId2 = (string) ($selectedResultId2 ?? '');
    $selectedResultId3 = (string) ($selectedResultId3 ?? '');

    $resultOptions = $results
        ->filter(fn ($result) => in_array((int) $result->type, [1, 2, 3], true))
        ->values();
    $directionOptions = $results
        ->filter(fn ($result) => (int) $result->type === 4)
        ->values();
    $rbiOptions = $results
        ->filter(fn ($result) => (int) $result->type === 5)
        ->values();

    $featuredResultNames = ['安打', '四球', 'ゴロ', 'フライ', '三振', '二塁打', '三塁打', '本塁打'];
    $featuredResults = collect($featuredResultNames)
        ->map(fn ($name) => $resultOptions->firstWhere('name', $name))
        ->filter()
        ->values();
    $otherResults = $resultOptions
        ->reject(fn ($result) => in_array($result->name, $featuredResultNames, true))
        ->values();

    $blankDirection = $directionOptions->first(fn ($direction) => trim((string) $direction->name) === '');
    $strikeDirections = $directionOptions
        ->filter(fn ($direction) => in_array($direction->name, ['空振', '見逃'], true))
        ->values();
    $fieldDirections = $directionOptions
        ->reject(fn ($direction) => trim((string) $direction->name) === '' || in_array($direction->name, ['空振', '見逃'], true))
        ->values();

    $fieldLayout = [
        '左' => ['top' => '24%', 'left' => '17%'],
        '左中間' => ['top' => '14%', 'left' => '31%'],
        '中' => ['top' => '9%', 'left' => '50%'],
        '右中間' => ['top' => '14%', 'left' => '69%'],
        '右' => ['top' => '24%', 'left' => '83%'],
        '遊' => ['top' => '49%', 'left' => '36%'],
        '二' => ['top' => '49%', 'left' => '64%'],
        '三' => ['top' => '68%', 'left' => '26%'],
        '投' => ['top' => '63%', 'left' => '50%'],
        '一' => ['top' => '68%', 'left' => '74%'],
        '捕' => ['top' => '84%', 'left' => '50%'],
    ];
    $mappedFieldDirections = $fieldDirections
        ->filter(fn ($direction) => array_key_exists($direction->name, $fieldLayout))
        ->sortBy(fn ($direction) => array_search($direction->name, array_keys($fieldLayout), true))
        ->values();
    $extraFieldDirections = $fieldDirections
        ->reject(fn ($direction) => array_key_exists($direction->name, $fieldLayout))
        ->values();

    $visualConfig = [
        'blankDirectionId' => $blankDirection ? (string) $blankDirection->id : '',
        'strikeDirectionIds' => $strikeDirections->pluck('id')->map(fn ($id) => (string) $id)->values()->all(),
        'swingMissDirectionId' => optional($strikeDirections->firstWhere('name', '空振'))->id ? (string) $strikeDirections->firstWhere('name', '空振')->id : '',
        'strikeResultNames' => ['三振'],
        'autoBlankResultNames' => ['四球', '死球'],
        'autoSwingMissResultNames' => ['振逃'],
    ];
@endphp

<div id="batting-result-selector" data-config='@json($visualConfig)' class="mt-8">
    <div class="rounded-2xl border border-emerald-100 bg-white p-3 shadow-sm sm:p-4">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <label class="font-semibold leading-none text-gray-900">入力方式</label>
                <p class="mt-2 text-xs text-gray-500">試合中はかんたん入力、細かい修正は通常入力に切り替えできます。</p>
            </div>
            <div class="inline-flex rounded-full bg-gray-100 p-1">
                <button type="button" data-role="mode-toggle" data-mode="visual" class="batting-mode-button rounded-full px-4 py-2 text-sm font-semibold text-gray-700">
                    かんたん入力
                </button>
                <button type="button" data-role="mode-toggle" data-mode="classic" class="batting-mode-button rounded-full px-4 py-2 text-sm font-semibold text-gray-700">
                    通常入力
                </button>
            </div>
        </div>

        <div data-panel="visual" class="mt-6">
            <section>
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-semibold text-gray-900">結果</h3>
                    </div>
                </div>
                <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
                    @foreach($featuredResults as $result)
                        <button
                            type="button"
                            data-role="result-button"
                            data-value="{{ $result->id }}"
                            class="batting-choice-button w-full rounded-2xl border border-gray-200 bg-gray-50 px-4 py-4 text-sm font-semibold text-gray-700"
                        >
                            {{ $result->name }}
                        </button>
                    @endforeach
                </div>

                @if($otherResults->isNotEmpty())
                    <details class="mt-4 rounded-2xl border border-gray-200 bg-gray-50 p-4">
                        <summary class="cursor-pointer text-sm font-semibold text-gray-700">その他の結果</summary>
                        <div class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-4">
                            @foreach($otherResults as $result)
                                <button
                                    type="button"
                                    data-role="result-button"
                                    data-value="{{ $result->id }}"
                                    class="batting-choice-button w-full rounded-2xl border border-gray-200 bg-white px-4 py-4 text-sm font-semibold text-gray-700"
                                >
                                    {{ $result->name }}
                                </button>
                            @endforeach
                        </div>
                    </details>
                @endif
            </section>

            <section data-section="direction-prompt" class="mt-6 rounded-2xl border border-dashed border-gray-300 bg-gray-50 px-4 py-4">
                <h3 class="text-base font-semibold text-gray-900">打球方向</h3>
                <p class="mt-1 text-sm text-gray-600">結果を選ぶと、ここに打球方向の入力を表示します。</p>
            </section>

            <section data-section="field-direction" class="mt-6 hidden">
                <div>
                    <h3 class="text-base font-semibold text-gray-900">打球方向</h3>
                    <p class="mt-1 text-xs text-gray-500">球場図をタップして選択します。</p>
                </div>

                <div class="batting-field mt-4">
                    <svg
                        class="batting-field-svg"
                        viewBox="0 0 400 360"
                        preserveAspectRatio="xMidYMid meet"
                        aria-hidden="true"
                    >
                        <defs>
                            <radialGradient id="battingFieldGrass" cx="50%" cy="15%" r="90%">
                                <stop offset="0%" stop-color="#dcfce7" />
                                <stop offset="45%" stop-color="#86efac" />
                                <stop offset="100%" stop-color="#15803d" />
                            </radialGradient>
                            <linearGradient id="battingFieldDirt" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" stop-color="#f6ad55" />
                                <stop offset="100%" stop-color="#b45309" />
                            </linearGradient>
                        </defs>
                        <rect x="0" y="0" width="400" height="360" rx="28" fill="url(#battingFieldGrass)" />
                        <path d="M200 312 L56 168 A214 214 0 0 1 344 168 Z" fill="rgba(255,255,255,0.08)" />
                        <path d="M200 312 L108 220 A130 130 0 0 1 292 220 Z" fill="url(#battingFieldDirt)" />
                        <path d="M200 312 L118 230 L200 148 L282 230 Z" fill="rgba(255,255,255,0.12)" />
                        <line x1="200" y1="312" x2="56" y2="168" stroke="rgba(255,255,255,0.85)" stroke-width="4" stroke-linecap="round" />
                        <line x1="200" y1="312" x2="344" y2="168" stroke="rgba(255,255,255,0.85)" stroke-width="4" stroke-linecap="round" />
                        <line x1="200" y1="312" x2="118" y2="230" stroke="rgba(255,255,255,0.85)" stroke-width="3" />
                        <line x1="118" y1="230" x2="200" y2="148" stroke="rgba(255,255,255,0.85)" stroke-width="3" />
                        <line x1="200" y1="148" x2="282" y2="230" stroke="rgba(255,255,255,0.85)" stroke-width="3" />
                        <line x1="282" y1="230" x2="200" y2="312" stroke="rgba(255,255,255,0.85)" stroke-width="3" />
                        <circle cx="200" cy="244" r="12" fill="rgba(255,255,255,0.28)" />
                        <rect x="112" y="224" width="14" height="14" transform="rotate(45 119 231)" fill="#fff8eb" />
                        <rect x="193" y="141" width="14" height="14" transform="rotate(45 200 148)" fill="#fff8eb" />
                        <rect x="274" y="224" width="14" height="14" transform="rotate(45 281 231)" fill="#fff8eb" />
                        <path d="M192 312 L200 302 L208 312 L208 320 L192 320 Z" fill="#fff8eb" />
                    </svg>
                    @foreach($mappedFieldDirections as $direction)
                        <button
                            type="button"
                            data-role="direction-button"
                            data-value="{{ $direction->id }}"
                            class="batting-field-point"
                            style="top: {{ $fieldLayout[$direction->name]['top'] }}; left: {{ $fieldLayout[$direction->name]['left'] }};"
                        >
                            {{ $direction->name }}
                        </button>
                    @endforeach
                </div>

                <p data-role="direction-summary" class="mt-3 text-center text-sm font-semibold text-emerald-700">打球方向を選択してください</p>
            </section>

            <section data-section="strike-direction" class="mt-6 hidden">
                <div>
                    <h3 class="text-base font-semibold text-gray-900">三振種別</h3>
                    <p class="mt-1 text-xs text-gray-500">三振時は空振か見逃を選択します。</p>
                </div>
                <div class="mt-4 grid grid-cols-2 gap-3">
                    @foreach($strikeDirections as $direction)
                        <button
                            type="button"
                            data-role="direction-button"
                            data-value="{{ $direction->id }}"
                            class="batting-choice-button rounded-2xl border border-gray-200 bg-gray-50 px-4 py-4 text-sm font-semibold text-gray-700"
                        >
                            {{ $direction->name }}
                        </button>
                    @endforeach
                </div>
            </section>

            <section data-section="auto-direction" class="mt-6 hidden rounded-2xl border border-amber-200 bg-amber-50 px-4 py-4">
                <h3 class="text-base font-semibold text-amber-900">打球方向</h3>
                <p data-role="auto-direction-message" class="mt-1 text-sm text-amber-800"></p>
            </section>

            <section class="mt-6">
                <div>
                    <h3 class="text-base font-semibold text-gray-900">打点</h3>
                    <p class="mt-1 text-xs text-gray-500">タップで選択します。</p>
                </div>
                <div class="mt-4 grid grid-cols-5 gap-2">
                    @foreach($rbiOptions as $result)
                        <button
                            type="button"
                            data-role="rbi-button"
                            data-value="{{ $result->id }}"
                            class="batting-rbi-button w-full rounded-2xl border border-gray-200 bg-gray-50 px-2 py-4 text-base font-semibold text-gray-700"
                        >
                            {{ $result->name }}
                        </button>
                    @endforeach
                </div>
            </section>

            <section class="mt-6 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                <h3 class="text-sm font-semibold text-slate-900">現在の入力</h3>
                <p data-role="summary" class="mt-2 text-sm text-slate-700">未選択</p>
            </section>
        </div>

        <div data-panel="classic" class="mt-6 hidden">
            <div class="rounded-2xl border border-gray-200 bg-gray-50 p-4">
                <p class="text-xs text-gray-500">従来のフォームをそのまま残しています。視覚入力と値は同期します。</p>

                <div class="md:flex items-center mt-6">
                    <div class="w-full flex flex-col">
                        <label for="resultId1" class="font-semibold leading-none mt-1">結果</label>
                        <select name="resultId1" class="w-auto py-2 placeholder-gray-300 border border-gray-300 rounded-md" id="resultId1" required>
                            <option value="">選択してください</option>
                            @foreach($resultOptions as $result)
                                <option value="{{ $result->id }}" {{ $selectedResultId1 === (string) $result->id ? 'selected' : '' }}>
                                    {{ $result->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="md:flex items-center mt-8">
                    <div class="w-full flex flex-col">
                        <label for="resultId2" class="font-semibold leading-none mt-1">打球方向</label>
                        <select name="resultId2" class="w-auto py-2 placeholder-gray-300 border border-gray-300 rounded-md" id="resultId2" required>
                            <option value="">選択してください</option>
                            @foreach($directionOptions as $result)
                                <option value="{{ $result->id }}" {{ $selectedResultId2 === (string) $result->id ? 'selected' : '' }}>
                                    {{ $result->name === '' ? '空欄' : $result->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="md:flex items-center mt-8">
                    <div class="w-full flex flex-col">
                        <label for="resultId3" class="font-semibold leading-none mt-1">打点</label>
                        <select name="resultId3" class="w-auto py-2 placeholder-gray-300 border border-gray-300 rounded-md" id="resultId3" required>
                            <option value="">選択してください</option>
                            @foreach($rbiOptions as $result)
                                <option value="{{ $result->id }}" {{ $selectedResultId3 === (string) $result->id ? 'selected' : '' }}>
                                    {{ $result->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@once
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const root = document.getElementById('batting-result-selector');
            if (!root) {
                return;
            }

            const config = JSON.parse(root.dataset.config || '{}');
            const modeButtons = Array.from(root.querySelectorAll('[data-role="mode-toggle"]'));
            const visualPanel = root.querySelector('[data-panel="visual"]');
            const classicPanel = root.querySelector('[data-panel="classic"]');
            const directionPrompt = root.querySelector('[data-section="direction-prompt"]');
            const fieldSection = root.querySelector('[data-section="field-direction"]');
            const strikeSection = root.querySelector('[data-section="strike-direction"]');
            const autoSection = root.querySelector('[data-section="auto-direction"]');
            const autoMessage = root.querySelector('[data-role="auto-direction-message"]');
            const directionSummary = root.querySelector('[data-role="direction-summary"]');
            const summary = root.querySelector('[data-role="summary"]');
            const resultSelect = document.getElementById('resultId1');
            const directionSelect = document.getElementById('resultId2');
            const rbiSelect = document.getElementById('resultId3');
            const resultButtons = Array.from(root.querySelectorAll('[data-role="result-button"]'));
            const directionButtons = Array.from(root.querySelectorAll('[data-role="direction-button"]'));
            const rbiButtons = Array.from(root.querySelectorAll('[data-role="rbi-button"]'));
            const modeStorageKey = 'battingInputMode';

            if (!resultSelect || !directionSelect || !rbiSelect) {
                return;
            }

            const getSelectedText = function (select) {
                const option = select.options[select.selectedIndex];
                return option ? option.text.trim() : '';
            };

            const setActive = function (buttons, currentValue) {
                buttons.forEach(function (button) {
                    const isActive = button.dataset.value === currentValue;
                    button.classList.toggle('is-active', isActive);
                    button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
                });
            };

            const getDirectionMode = function (resultName) {
                if (!resultName) {
                    return 'none';
                }

                if ((config.autoBlankResultNames || []).includes(resultName)) {
                    return 'auto-blank';
                }

                if ((config.autoSwingMissResultNames || []).includes(resultName)) {
                    return 'auto-swing';
                }

                if ((config.strikeResultNames || []).includes(resultName)) {
                    return 'strike';
                }

                return 'field';
            };

            const syncDirectionValue = function () {
                const resultName = getSelectedText(resultSelect);
                const directionMode = getDirectionMode(resultName);
                const strikeDirectionIds = config.strikeDirectionIds || [];

                if (directionMode === 'none') {
                    directionSelect.value = '';
                    return;
                }

                if (directionMode === 'auto-blank' && config.blankDirectionId) {
                    directionSelect.value = config.blankDirectionId;
                    return;
                }

                if (directionMode === 'auto-swing' && config.swingMissDirectionId) {
                    directionSelect.value = config.swingMissDirectionId;
                    return;
                }

                if (directionMode === 'strike') {
                    if (directionSelect.value && !strikeDirectionIds.includes(directionSelect.value)) {
                        directionSelect.value = '';
                    }
                    return;
                }

                if (directionSelect.value === config.blankDirectionId || strikeDirectionIds.includes(directionSelect.value)) {
                    directionSelect.value = '';
                }
            };

            const updateDirectionSections = function () {
                const resultName = getSelectedText(resultSelect);
                const directionMode = getDirectionMode(resultName);
                const directionName = getSelectedText(directionSelect);

                directionPrompt.classList.toggle('hidden', directionMode !== 'none');
                fieldSection.classList.toggle('hidden', directionMode !== 'field');
                strikeSection.classList.toggle('hidden', directionMode !== 'strike');
                autoSection.classList.toggle('hidden', directionMode !== 'auto-blank' && directionMode !== 'auto-swing');

                if (directionMode === 'field') {
                    directionSummary.textContent = directionName && directionName !== '空欄'
                        ? '選択中: ' + directionName
                        : '打球方向を選択してください';
                }

                if (directionMode === 'auto-blank') {
                    autoMessage.textContent = resultName + ' は打球方向を自動で空欄にします。';
                } else if (directionMode === 'auto-swing') {
                    autoMessage.textContent = resultName + ' は打球方向を自動で空振にします。';
                } else {
                    autoMessage.textContent = '';
                }
            };

            const updateSummary = function () {
                const resultName = getSelectedText(resultSelect);
                const directionName = getSelectedText(directionSelect);
                const rbiName = getSelectedText(rbiSelect);

                if (!resultName && !directionName && !rbiName) {
                    summary.textContent = '未選択';
                    return;
                }

                const parts = [];
                if (resultName) {
                    parts.push((directionName && directionName !== '空欄' ? directionName : '') + resultName);
                }
                if (rbiName) {
                    parts.push('打点 ' + rbiName);
                }

                summary.textContent = parts.filter(Boolean).join(' / ');
            };

            const applyMode = function (mode) {
                const currentMode = mode === 'classic' ? 'classic' : 'visual';
                visualPanel.classList.toggle('hidden', currentMode !== 'visual');
                classicPanel.classList.toggle('hidden', currentMode !== 'classic');
                modeButtons.forEach(function (button) {
                    const isActive = button.dataset.mode === currentMode;
                    button.classList.toggle('is-active', isActive);
                    button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
                });
                localStorage.setItem(modeStorageKey, currentMode);
            };

            const syncAll = function () {
                syncDirectionValue();
                updateDirectionSections();
                updateSummary();
                setActive(resultButtons, resultSelect.value);
                setActive(directionButtons, directionSelect.value);
                setActive(rbiButtons, rbiSelect.value);
            };

            modeButtons.forEach(function (button) {
                button.addEventListener('click', function () {
                    applyMode(button.dataset.mode);
                });
            });

            resultButtons.forEach(function (button) {
                button.addEventListener('click', function () {
                    resultSelect.value = button.dataset.value;
                    resultSelect.dispatchEvent(new Event('change', { bubbles: true }));
                });
            });

            directionButtons.forEach(function (button) {
                button.addEventListener('click', function () {
                    directionSelect.value = button.dataset.value;
                    directionSelect.dispatchEvent(new Event('change', { bubbles: true }));
                });
            });

            rbiButtons.forEach(function (button) {
                button.addEventListener('click', function () {
                    rbiSelect.value = button.dataset.value;
                    rbiSelect.dispatchEvent(new Event('change', { bubbles: true }));
                });
            });

            resultSelect.addEventListener('change', syncAll);
            directionSelect.addEventListener('change', syncAll);
            rbiSelect.addEventListener('change', syncAll);

            applyMode(localStorage.getItem(modeStorageKey) || 'visual');
            syncAll();
        });
    </script>

    <style>
        .batting-mode-button.is-active,
        .batting-choice-button.is-active,
        .batting-sub-button.is-active,
        .batting-rbi-button.is-active,
        .batting-field-point.is-active {
            border-color: #047857 !important;
            background-color: #047857 !important;
            color: #fff;
            box-shadow: 0 10px 25px rgba(4, 120, 87, 0.22);
        }

        .batting-choice-button,
        .batting-sub-button,
        .batting-rbi-button,
        .batting-field-point,
        .batting-mode-button {
            transition: transform 0.15s ease, box-shadow 0.15s ease, background-color 0.15s ease, border-color 0.15s ease;
        }

        .batting-choice-button:active,
        .batting-sub-button:active,
        .batting-rbi-button:active,
        .batting-mode-button:active {
            transform: scale(0.98);
        }

        .batting-field-point:active {
            transform: translate(-50%, -50%) scale(0.98);
        }

        .batting-field {
            position: relative;
            overflow: hidden;
            width: 100%;
            margin: 0 auto;
            aspect-ratio: 10 / 9;
            border: 1px solid #bbf7d0;
            border-radius: 28px;
            box-shadow: inset 0 12px 32px rgba(255, 255, 255, 0.2);
        }

        .batting-field-svg {
            display: block;
            width: 100%;
            height: 100%;
        }

        .batting-field-point {
            position: absolute;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: clamp(3.25rem, 11vw, 4.4rem);
            min-height: clamp(2.8rem, 8vw, 3.35rem);
            transform: translate(-50%, -50%);
            border: 1px solid rgba(255, 255, 255, 0.8);
            border-radius: 9999px;
            background: rgba(255, 255, 255, 0.92);
            padding: 0.55rem 0.7rem;
            font-size: clamp(0.74rem, 2vw, 0.86rem);
            font-weight: 700;
            line-height: 1.1;
            white-space: nowrap;
            color: #166534;
            backdrop-filter: blur(6px);
        }

        @media (max-width: 640px) {
            .batting-field-point {
                min-width: clamp(3rem, 14vw, 3.7rem);
                min-height: 2.7rem;
                padding: 0.45rem 0.55rem;
                font-size: 0.78rem;
            }
        }
    </style>
@endonce
