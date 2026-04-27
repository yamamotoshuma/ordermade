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
                    'currentStateInning' => $createDefaults['currentStateInning'] ?? $defaultInning,
                    'currentOutCount' => $createDefaults['currentOutCount'] ?? 0,
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

                    $batterKey = (string) ($order->orderId ?? ('order-' . $index));
                    $isSelected = ($initialUserId !== '' && (int) $order->userId === (int) $initialUserId)
                        || ($initialUserId === '' && $initialUserName !== '' && trim((string) $order->userName) === $initialUserName);

                    if ($isSelected) {
                        $selectedBatterKey = $batterKey;
                    }

                    $rankingSuffix = (int) $order->ranking > 1 ? '-' . $order->ranking : '';
                    $batterOptions[] = [
                        'key' => $batterKey,
                        'orderId' => $order->orderId ? (string) $order->orderId : '',
                        'label' => $order->battingOrder . '番' . $rankingSuffix . ' ' . $batterName,
                        'userId' => $order->userId ? (string) $order->userId : '',
                        'userName' => $order->userId ? '' : $batterName,
                    ];
                }

                if ($selectedBatterKey === '' && $initialUserName !== '') {
                    $selectedBatterKey = 'manual-current';
                    $batterOptions[] = [
                        'key' => $selectedBatterKey,
                        'orderId' => '',
                        'label' => $initialUserName,
                        'userId' => '',
                        'userName' => $initialUserName,
                    ];
                }

                $battingConfirmation = session('batting_confirmation');
                $createConfirmationResolution = (string) ($battingConfirmation['resolution'] ?? '');
                $createConfirmationButtonLabel = match ($createConfirmationResolution) {
                    'rbi' => 'このまま登録する',
                    default => 'このまま追加する',
                };
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

            @if($battingConfirmation)
                @include('batting.partials.confirmation-alert', [
                    'id' => 'batting-conflict-alert',
                    'title' => $battingConfirmation['title'] ?? '確認が必要です。',
                    'message' => $battingConfirmation['message'] ?? 'このまま登録するか確認してください。',
                    'action' => route('batting.store', $game),
                    'resolution' => $createConfirmationResolution,
                    'buttonLabel' => $createConfirmationButtonLabel,
                    'cancelUrl' => route('batting.create', ['game' => $game]),
                    'hiddenFields' => [],
                ])
            @endif

            @if($lastBattingStat && ! $battingConfirmation)
                <section id="latest-batting-card" class="mt-6 overflow-hidden rounded-3xl border border-emerald-200 bg-emerald-50 shadow-sm">
                    <div class="border-b border-emerald-100 bg-white/80 px-4 py-3">
                        <p class="text-xs font-bold uppercase tracking-[0.18em] text-emerald-700">直前の入力</p>
                    </div>
                    <div class="p-4">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h3 class="text-lg font-black text-slate-900">
                                    {{ $lastBattingStat->inning }}回@if(($lastBattingStat->inningTurn ?? 1) > 1) {{ $lastBattingStat->inningTurn }}打席目@endif / {{ $lastBatterLabel }} / {{ $lastResultLabel }}
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

            <form id="batting-create-form" method="POST" action="{{ route('batting.store',$game) }}" enctype="multipart/form-data" data-create-config='@json($createConfig)' novalidate>
                @csrf
                <input type="hidden" name="fromEdit" value="{{ old('fromEdit', request('fromEdit', false)) }}">
                <input type="hidden" name="offenseStateVersion" value="{{ old('offenseStateVersion', $createDefaults['offenseStateVersion'] ?? 1) }}">
                <input type="hidden" name="userId" id="userId" value="{{ $initialUserId }}">
                <input type="hidden" name="userName" id="userName" value="{{ $initialUserName }}">

                <section id="batting-meta-panel" class="mt-5 rounded-2xl border border-slate-200 bg-slate-50/80 p-3 shadow-sm">
                    <input type="hidden" name="gameId" value="{{$game->gameId}}">

                    <div>
                        <p class="text-[10px] font-black uppercase tracking-[0.18em] text-slate-400">試合</p>
                        <p id="gameName" class="mt-1 truncate text-xs font-bold text-slate-500">{{$game->gameName}}</p>
                    </div>

                    <div class="mt-3 grid gap-2 sm:grid-cols-[minmax(0,1fr)_11rem]">
                        <div class="rounded-xl border border-slate-200 bg-white p-2.5">
                            <label for="batterSelect" class="text-[10px] font-black uppercase tracking-[0.18em] text-slate-500">打者</label>
                            <select name="selectedOrderId" class="mt-1.5 h-12 w-full rounded-xl border border-slate-200 bg-white px-3 text-base font-bold text-slate-900" id="batterSelect">
                                <option value="">選択してください</option>
                                @foreach($batterOptions as $option)
                                    <option
                                        value="{{ $option['key'] }}"
                                        data-order-id="{{ $option['orderId'] }}"
                                        data-user-id="{{ $option['userId'] }}"
                                        data-user-name="{{ $option['userName'] }}"
                                        {{ $option['key'] === $selectedBatterKey ? ' selected' : '' }}
                                    >
                                        {{ $option['label'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="rounded-xl border border-slate-200 bg-white p-2.5">
                            <label for="inning" class="text-[10px] font-black uppercase tracking-[0.18em] text-slate-500">イニング</label>
                            <div class="mt-1.5 grid grid-cols-[2.75rem_minmax(0,1fr)_2.75rem] gap-1.5">
                                <button type="button" data-role="inning-decrement" class="rounded-xl border border-slate-200 bg-slate-50 text-xl font-black text-slate-700">−</button>
                                <input type="number" name="inning" class="h-12 w-full rounded-xl border border-slate-200 bg-white text-center text-xl font-black text-slate-900" id="inning" value="{{ $initialInning }}" min="1" required>
                                <button type="button" data-role="inning-increment" class="rounded-xl border border-slate-200 bg-slate-50 text-xl font-black text-slate-700">＋</button>
                            </div>
                            <p data-role="inning-status" class="mt-1.5 text-xs text-slate-500"></p>
                        </div>
                    </div>

                    <p data-role="batting-meta-summary" class="mt-2 text-xs font-bold text-slate-600">
                        {{ $initialInning }}回 / {{ $initialBatterLabel }}
                    </p>
                </section>

                @include('batting.partials.offense-state-panel', [
                    'game' => $game,
                    'offenseState' => $offenseState,
                ])

                @include('batting.partials.result-selector', [
                    'results' => $results,
                    'selectedResultId1' => old('resultId1'),
                    'selectedResultId2' => old('resultId2'),
                    'selectedResultId3' => old('resultId3', $defaultRbiId),
                ])
            </form>
            <div class="h-32 sm:h-28"></div>
            <div id="batting-submit-bar" class="fixed inset-x-0 bottom-0 z-40 border-t border-slate-200 bg-white/95 shadow-[0_-12px_30px_rgba(15,23,42,0.12)] backdrop-blur" style="padding-bottom: max(env(safe-area-inset-bottom), 0px);">
                <div class="mx-auto flex max-w-7xl items-center gap-3 px-3 py-3 sm:px-6 lg:px-8">
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap gap-1.5">
                            <span data-role="sticky-inning-chip" class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-black text-slate-700">{{ $initialInning }}回</span>
                            <span data-role="sticky-out-count-chip" class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-black text-slate-700">{{ $createDefaults['currentOutCount'] ?? 0 }}アウト</span>
                            <span data-role="sticky-batter-chip" class="max-w-full truncate rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-black text-emerald-800">次: {{ $initialBatterLabel }}</span>
                        </div>
                        <p data-role="sticky-submit-summary" class="mt-1.5 truncate text-base font-black text-slate-950 sm:text-lg">
                            結果未選択 / 打点 0
                        </p>
                    </div>
                    <button type="button" data-role="batting-submit-button" class="inline-flex shrink-0 items-center justify-center rounded-2xl bg-emerald-600 px-6 py-4 text-base font-black text-white shadow-lg shadow-emerald-900/20 transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:bg-slate-400 sm:px-10">
                        登録する
                    </button>
                </div>
            </div>
            <form id="runner-event-form" method="POST" action="{{ route('batting.runnerEvents.store', ['game' => $game]) }}" class="hidden">
                @csrf
                <input type="hidden" name="offenseStateVersion" value="{{ $offenseState['version'] ?? 1 }}">
                <input type="hidden" name="action" id="runnerEventAction" value="">
                <input type="hidden" name="base" id="runnerEventBase" value="">
                <input type="hidden" name="targetBase" id="runnerEventTargetBase" value="">
                <input type="hidden" name="orderId" id="runnerEventOrderId" value="">
                <input type="hidden" name="userId" id="runnerEventUserId" value="">
                <input type="hidden" name="userName" id="runnerEventUserName" value="">
                <input type="hidden" name="displayName" id="runnerEventDisplayName" value="">
            </form>
            <form id="runner-undo-form" method="POST" action="{{ route('batting.runnerEvents.destroyLatest', ['game' => $game]) }}" class="hidden">
                @csrf
                @method('DELETE')
                <input type="hidden" name="offenseStateVersion" value="{{ $offenseState['version'] ?? 1 }}">
            </form>
        </div>
    </div>
    <div id="loading">
        <div class="loader"></div>
    </div>
</x-app-layout>
