@php
    $runnerBases = $offenseState['bases'] ?? [];
    $runnerBaseMap = collect($runnerBases)->keyBy('base');
    $manualRunnerOptions = $offenseState['manualRunnerOptions'] ?? [];
    $stateVersion = (int) ($offenseState['version'] ?? 1);
    $baseChipPositions = [
        1 => ['top' => '66%', 'left' => '76%'],
        2 => ['top' => '39%', 'left' => '50%'],
        3 => ['top' => '66%', 'left' => '24%'],
    ];
@endphp

<section id="offense-state-panel" data-auto-open="{{ !empty($offenseState['needsRunnerConfirmation']) ? 'true' : 'false' }}" class="mt-6 rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            <p class="text-[11px] font-black uppercase tracking-[0.18em] text-slate-400">現在の攻撃状況</p>
            <p class="mt-1 text-lg font-black text-slate-900">
                {{ $offenseState['inning'] ?? 1 }}回 / {{ $offenseState['outCount'] ?? 0 }}アウト
            </p>
            <p class="mt-1 truncate text-sm font-bold text-slate-600">
                次打者: {{ $offenseState['batterLabel'] ?? '未選択' }}
            </p>
        </div>
        <button type="button" data-role="runner-sheet-open" class="inline-flex shrink-0 items-center justify-center rounded-2xl border border-slate-200 bg-slate-900 px-4 py-3 text-sm font-black text-white shadow-sm hover:bg-slate-800">
            走者操作
        </button>
    </div>

    @if(!empty($offenseState['needsRunnerConfirmation']))
        <div class="mt-4 rounded-2xl border border-amber-300 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-900">
            {{ $offenseState['runnerConfirmationMessage'] ?? '走者状況を確認してください。' }}
        </div>
    @endif

    <div class="mt-4 grid gap-4 sm:grid-cols-[minmax(0,18rem)_minmax(0,1fr)]">
        <div class="offense-diamond">
            <svg class="offense-diamond-svg" viewBox="0 0 220 220" preserveAspectRatio="xMidYMid meet" aria-hidden="true">
                <defs>
                    <linearGradient id="offenseGrass" x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop offset="0%" stop-color="#dcfce7" />
                        <stop offset="100%" stop-color="#15803d" />
                    </linearGradient>
                </defs>
                <rect x="0" y="0" width="220" height="220" rx="28" fill="url(#offenseGrass)" />
                <path d="M110 176 L40 106 L110 36 L180 106 Z" fill="rgba(146, 64, 14, 0.42)" />
                <path d="M110 176 L52 118 L110 60 L168 118 Z" fill="rgba(255,255,255,0.1)" />
                <line x1="110" y1="176" x2="40" y2="106" stroke="rgba(255,255,255,0.85)" stroke-width="4" />
                <line x1="40" y1="106" x2="110" y2="36" stroke="rgba(255,255,255,0.85)" stroke-width="4" />
                <line x1="110" y1="36" x2="180" y2="106" stroke="rgba(255,255,255,0.85)" stroke-width="4" />
                <line x1="180" y1="106" x2="110" y2="176" stroke="rgba(255,255,255,0.85)" stroke-width="4" />
                <path d="M101 176 L110 167 L119 176 L119 186 L101 186 Z" fill="#fff8eb" />
                <rect x="102" y="28" width="16" height="16" transform="rotate(45 110 36)" fill="#fff8eb" />
                <rect x="172" y="98" width="16" height="16" transform="rotate(45 180 106)" fill="#fff8eb" />
                <rect x="32" y="98" width="16" height="16" transform="rotate(45 40 106)" fill="#fff8eb" />
            </svg>
            @foreach($runnerBases as $base)
                <button type="button" data-role="runner-sheet-open" class="offense-base-chip{{ $base['occupied'] ? ' is-occupied' : '' }}" style="top: {{ $baseChipPositions[$base['base']]['top'] }}; left: {{ $baseChipPositions[$base['base']]['left'] }};">
                    <span class="offense-base-chip-base">{{ $base['baseName'] }}</span>
                    <span class="offense-base-chip-name">{{ $base['occupied'] ? $base['shortLabel'] : '空' }}</span>
                </button>
            @endforeach
        </div>

        <div class="grid gap-2 content-start sm:pt-1">
            @foreach($runnerBases as $base)
                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-3 py-3">
                    <p class="text-xs font-black uppercase tracking-[0.18em] text-slate-500">{{ $base['baseName'] }}</p>
                    <p class="mt-1 text-sm font-bold text-slate-900">{{ $base['label'] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

<div id="runner-sheet-backdrop" class="runner-sheet-backdrop hidden"></div>
<div id="runner-sheet" class="runner-sheet hidden">
    <div class="runner-sheet-panel">
        <div class="flex items-start justify-between gap-3">
            <div>
                <p class="text-[11px] font-black uppercase tracking-[0.18em] text-slate-400">走者操作</p>
                <p class="mt-1 text-lg font-black text-slate-900">{{ $offenseState['inning'] ?? 1 }}回 / {{ $offenseState['outCount'] ?? 0 }}アウト</p>
                <p class="mt-1 text-sm font-bold text-slate-600">次打者: {{ $offenseState['batterLabel'] ?? '未選択' }}</p>
            </div>
            <button type="button" data-role="runner-sheet-close" class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-slate-200 bg-white text-xl font-black text-slate-700 shadow-sm">
                ×
            </button>
        </div>

        <button type="button" data-role="runner-undo-submit" class="mt-4 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-black text-slate-700">
            直前の走者操作を取り消す
        </button>

        <div class="mt-4 grid gap-3">
            @foreach($runnerBases as $base)
                <section class="rounded-3xl border border-slate-200 bg-slate-50 p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-black uppercase tracking-[0.18em] text-slate-500">{{ $base['baseName'] }}走者</p>
                            <p class="mt-1 text-base font-black text-slate-900">{{ $base['label'] }}</p>
                        </div>
                    </div>

                    @if($base['occupied'])
                        @php
                            $nextBase = $base['base'] + 1;
                            $nextBaseOccupied = $nextBase <= 3
                                ? (bool) ($runnerBaseMap->get($nextBase)['occupied'] ?? false)
                                : false;
                            $stealAction = [
                                'action' => $nextBaseOccupied ? \App\Services\OffenseStateService::EVENT_DOUBLE_STEAL : \App\Services\OffenseStateService::EVENT_STOLEN_BASE,
                                'label' => $base['base'] === 3 ? '本盗' : ($nextBaseOccupied ? '重盗' : '盗塁'),
                                'class' => 'runner-action-steal',
                            ];
                            $runnerActions = [
                                $stealAction,
                                ['action' => \App\Services\OffenseStateService::EVENT_ADVANCE, 'label' => $base['base'] === 3 ? '生還' : '進塁', 'class' => 'runner-action-advance'],
                                ['action' => \App\Services\OffenseStateService::EVENT_CAUGHT_STEALING, 'label' => $base['base'] === 3 ? '本盗死' : '盗塁死', 'class' => 'runner-action-out'],
                                ['action' => \App\Services\OffenseStateService::EVENT_PICKOFF_OUT, 'label' => '牽制死', 'class' => 'runner-action-out'],
                                ['action' => \App\Services\OffenseStateService::EVENT_RUNNER_OUT, 'label' => '走塁死', 'class' => 'runner-action-out'],
                                ['action' => \App\Services\OffenseStateService::EVENT_CLEAR_BASE, 'label' => 'ベースを空にする', 'class' => 'runner-action-clear'],
                            ];
                        @endphp
                        <div class="mt-3 grid grid-cols-2 gap-2">
                            @foreach($runnerActions as $action)
                                <button
                                    type="button"
                                    data-role="runner-action-submit"
                                    data-action="{{ $action['action'] }}"
                                    data-base="{{ $base['base'] }}"
                                    class="runner-action-button {{ $action['class'] }}"
                                >
                                    {{ $action['label'] }}
                                </button>
                            @endforeach
                        </div>
                    @else
                        <p class="mt-3 text-sm font-semibold text-slate-500">走者はいません。</p>
                    @endif
                </section>
            @endforeach
        </div>

        <section class="mt-4 rounded-3xl border border-slate-200 bg-white p-4">
            <p class="text-xs font-black uppercase tracking-[0.18em] text-slate-500">手動配置</p>
            @if($manualRunnerOptions !== [])
                <div id="manual-runner-form" class="mt-3 grid gap-3">
                    <input type="hidden" name="orderId" id="manualRunnerOrderId" value="">
                    <input type="hidden" name="userId" id="manualRunnerUserId" value="">
                    <input type="hidden" name="userName" id="manualRunnerUserName" value="">
                    <input type="hidden" name="displayName" id="manualRunnerDisplayName" value="">

                    <select id="manualRunnerSelect" class="h-14 rounded-2xl border border-slate-200 bg-white px-3 text-base font-bold text-slate-900">
                        <option value="">走者を選択してください</option>
                        @foreach($manualRunnerOptions as $option)
                            <option
                                value="{{ $option['label'] }}"
                                data-order-id="{{ $option['orderId'] }}"
                                data-user-id="{{ $option['userId'] }}"
                                data-user-name="{{ $option['userName'] }}"
                                data-display-name="{{ $option['displayName'] }}"
                            >
                                {{ $option['label'] }}
                            </option>
                        @endforeach
                    </select>

                    <select id="manualRunnerTargetBase" class="h-14 rounded-2xl border border-slate-200 bg-white px-3 text-base font-bold text-slate-900" required>
                        <option value="">配置先の塁を選択してください</option>
                        <option value="1">一塁へ置く</option>
                        <option value="2">二塁へ置く</option>
                        <option value="3">三塁へ置く</option>
                    </select>

                    <button type="button" data-role="manual-runner-submit" data-manual-action="{{ \App\Services\OffenseStateService::EVENT_MANUAL_PLACE }}" class="runner-action-button runner-action-manual">
                        走者を置く
                    </button>
                </div>
            @else
                <p class="mt-3 text-sm font-semibold text-slate-500">現在配置できる選手がいません。</p>
            @endif
        </section>
    </div>
</div>
