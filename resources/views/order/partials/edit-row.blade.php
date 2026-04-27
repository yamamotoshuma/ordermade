@php
    $battingOrder = (string) ($row['battingOrder'] ?? '');
    $positionId = (string) ($row['positionId'] ?? '');
    $userId = (string) ($row['userId'] ?? '');
    $userName = (string) ($row['userName'] ?? '');
    $ranking = (int) ($row['ranking'] ?? 1);
    $selectedUser = $userId !== '' ? $users->firstWhere('id', (int) $userId) : null;
    $displayPlayerName = $selectedUser?->name ?: ($userName !== '' ? $userName : '未入力');
    $rowTitle = $battingOrder !== '' ? $battingOrder . '番' : '未設定';
    $rankingLabel = $ranking > 1 ? '控え順 ' . $ranking : '先発';
    $fieldClass = 'mt-1 h-11 w-full rounded-lg border border-slate-300 bg-white px-3 text-base text-slate-900 focus:border-blue-500 focus:ring-blue-500';
    $labelClass = 'text-xs font-bold text-slate-500 md:text-[11px]';
@endphp

<div
    class="rounded-lg border border-slate-200 bg-white p-3 shadow-sm md:grid md:grid-cols-[4.5rem_6rem_minmax(10rem,1fr)_minmax(12rem,1fr)_5.5rem] md:items-end md:gap-3 md:rounded-none md:border-0 md:border-b md:p-3 md:shadow-none"
    data-order-row
>
    <div class="mb-3 flex items-center justify-between gap-3 md:hidden">
        <div class="min-w-0">
            <p class="text-base font-black text-slate-900" data-order-row-title>{{ $rowTitle }}</p>
            <p class="truncate text-sm font-semibold text-slate-500" data-order-row-player>{{ $displayPlayerName }}</p>
        </div>
    </div>

    <label class="block">
        <span class="{{ $labelClass }}">打順</span>
        <input
            class="{{ $fieldClass }} text-center font-black"
            type="number"
            inputmode="numeric"
            min="1"
            name="battingOrder[]"
            value="{{ $battingOrder }}"
            data-order-batting-order
        >
    </label>

    <label class="mt-3 block md:mt-0">
        <span class="{{ $labelClass }}">守備位置</span>
        <select class="{{ $fieldClass }}" name="positionId[]">
            <option value="">選択</option>
            @foreach ($positions as $position)
                <option value="{{ $position->positionId }}" {{ $positionId !== '' && (int) $positionId === (int) $position->positionId ? 'selected' : '' }}>
                    {{ $position->positionName }}
                </option>
            @endforeach
        </select>
    </label>

    <label class="mt-3 block md:mt-0">
        <span class="{{ $labelClass }}">選手</span>
        <select class="{{ $fieldClass }}" name="userId[]" data-order-user-id>
            <option value="">選択</option>
            @foreach ($users as $user)
                <option value="{{ $user->id }}" {{ $userId !== '' && (int) $userId === (int) $user->id ? 'selected' : '' }}>
                    {{ $user->name }}
                </option>
            @endforeach
        </select>
    </label>

    <label class="mt-3 block md:mt-0">
        <span class="{{ $labelClass }}">登録外選手名</span>
        <input
            class="{{ $fieldClass }}"
            type="text"
            name="userName[]"
            value="{{ $userName }}"
            autocomplete="off"
            data-order-user-name
        >
    </label>

    <div class="mt-3 rounded-lg bg-slate-50 px-3 py-2 md:mt-0 md:bg-transparent md:px-0 md:py-0">
        <span class="{{ $labelClass }}">順番</span>
        <span class="mt-1 flex h-11 items-center rounded-lg border border-slate-200 bg-slate-100 px-3 text-sm font-black text-slate-700 md:justify-center" data-order-ranking-label>
            {{ $rankingLabel }}
        </span>
        <input type="hidden" name="ranking[]" value="{{ $ranking }}" data-order-ranking-input>
    </div>
</div>
