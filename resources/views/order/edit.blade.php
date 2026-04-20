<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            打順登録
        </h2>
        <x-input-error class="mb-4" :messages="$errors->all()" />
        <x-input-error class="mb-4" :messages="session('error')" />
        <x-message :message="session('message')" />
    </x-slot>

    <div class="max-w-7xl mx-auto px-2 sm:px-6 lg:px-8">
        <div class="sm:p-8">
            <p class="mt-4 mb-4 text-sm text-gray-600">
                <a href="{{ route('game.show', $id) }}" class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded-lg">
                    試合詳細に戻る
                </a>
            </p>
            <div class="mb-4 flex flex-wrap gap-2">
                <form method="POST" action="{{ route('order.importSheet', ['order' => $id]) }}">
                    @csrf
                    <button
                        type="submit"
                        class="bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-2 px-4 rounded-lg"
                        onclick="return confirm('スプレッドシートの内容で現在の打順を上書きします。よろしいですか？');"
                    >
                        スプレッドシート反映
                    </button>
                </form>
            </div>
            <form method="POST" action="{{ route('order.store') }}" enctype="multipart/form-data">
                @csrf

                <div class="overflow-x-auto">
                    <table class="min-w-full border rounded-lg text-sm">
                        <thead>
                            <tr>
                                <th class="px-2 py-3 text-white bg-orange-500">打順</th>
                                <th class="px-2 py-3 text-white bg-orange-500">守備位置</th>
                                <th class="px-2 py-3 text-white bg-orange-500">選手</th>
                                <th class="px-2 py-3 text-white bg-orange-500">選手名<span class="text-red-600">※登録がないユーザーの場合</span></th>
                                <th class="px-2 py-3 text-white bg-orange-500">順番</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $max = $orders->max('battingOrder');
                                if ($max < 9 || !$max) {
                                    $max = 9;
                                }
                            @endphp
                            @for ($i = 1; $i <= $max; $i++)
                                @php
                                    $orderGroup = $orders->where('battingOrder', $i);
                                    $orderGroup = $orderGroup->sortBy('ranking');
                                @endphp
                                @if(count($orderGroup) <= 0)
                                    <tr>
                                        <td class="px-2 py-2">
                                            <input class="border rounded-lg p-2 w-12" type="number" name="battingOrder[]" value="{{ $i }}">
                                        </td>
                                        <td class="px-2 py-2">
                                            <select class="border rounded-lg p-2 w-16" name="positionId[]">
                                                <option value="">選択</option>
                                                @foreach ($positions as $position)
                                                    <option value="{{ $position->positionId }}">
                                                        {{ $position->positionName }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td class="px-2 py-2">
                                            <select class="border rounded-lg p-2 w-46" name="userId[]">
                                                <option value="">選択</option>
                                                @foreach ($users as $user)
                                                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td class="px-2 py-2">
                                            <input class="border rounded-lg p-2 w-60" type="text" name="userName[]" value="">
                                        </td>
                                        <td class="px-2 py-2">
                                            <input class="border rounded-lg bg-slate-50 p-2 w-16" type="number" name="ranking[]" value="1" readonly>
                                        </td>
                                    </tr>
                                @else
                                @foreach ($orderGroup as $order)
                                    <tr>
                                        <td class="px-2 py-2">
                                            <input class="border rounded-lg p-2 w-12" type="number" name="battingOrder[]" value="{{ $i }}">
                                        </td>
                                        <td class="px-2 py-2">
                                            <select class="border rounded-lg p-2 w-16" name="positionId[]">
                                                <option value="">選択してください</option>
                                                @foreach ($positions as $position)
                                                    <option value="{{ $position->positionId }}" {{ $order && $order->positionId == $position->positionId ? 'selected' : '' }}>
                                                        {{ $position->positionName }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td class="px-2 py-2">
                                            <select class="border rounded-lg p-2 w-46" name="userId[]">
                                                <option value="">選択してください</option>
                                                @foreach ($users as $user)
                                                    <option value="{{ $user->id }}" {{ $order && $order->userId == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td class="px-2 py-2">
                                            <input class="border rounded-lg p-2 w-60" type="text" name="userName[]" value="{{ $order && $order->userName ? $order->userName : '' }}">
                                        </td>
                                        <td class="px-2 py-2">
                                            <input class="border rounded-lg bg-slate-50 p-2 w-16" type="number" name="ranking[]" value="{{ $order && $order->ranking ? $order->ranking : 1 }}" readonly>
                                        </td>
                                    </tr>
                                @endforeach
                                @endif
                            @endfor
                        </tbody>
                    </table>
                </div>
                <input type="hidden" name="gameId" value="{{ $id }}">

                <div class="mt-4">
                    <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded-lg mr-2">保存</button>
                    <button type="button" id="addRow" class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded-lg">行を追加</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
<script>
    var count = {{$max}};

    document.getElementById('addRow').addEventListener('click', function() {
        var table = document.querySelector('table');
        var newRow = table.insertRow(table.rows.length);

        count += 1;

        // Add cells to the new row with appropriate classes and styling
        var cell0 = newRow.insertCell(0);
        cell0.className = 'px-2 py-2';
        cell0.innerHTML = '<input class="border rounded-lg p-2 w-12" type="number" name="battingOrder[]" value="' + count + '">';

        var cell1 = newRow.insertCell(1);
        cell1.className = 'px-2 py-2';
        cell1.innerHTML = '<select class="border rounded-lg p-2 w-16" name="positionId[]">' +
            '<option value="">選択</option>' +
            @foreach ($positions as $position)
                '<option value="{{ $position->positionId }}">{{ $position->positionName }}</option>' +
            @endforeach
            '</select>';

        var cell2 = newRow.insertCell(2);
        cell2.className = 'px-2 py-2';
        cell2.innerHTML = '<select class="border rounded-lg p-2 w-46" name="userId[]">' +
            '<option value="">選択</option>' +
            @foreach ($users as $user)
                '<option value="{{ $user->id }}">{{ $user->name }}</option>' +
            @endforeach
            '</select>';

        var cell3 = newRow.insertCell(3);
        cell3.className = 'px-2 py-2';
        cell3.innerHTML = '<input class="border rounded-lg p-2 w-60" type="text" name="userName[]" value="">';

        var cell4 = newRow.insertCell(4);
        cell4.className = 'px-2 py-2';
        cell4.innerHTML = '<input class="border rounded-lg bg-slate-50 p-2 w-16" type="number" name="ranking[]" value="1" readonly>';

        bindRankingListeners(newRow.querySelector('input[name="battingOrder[]"]'));
        syncRankings();
    });

    function syncRankings() {
        var rows = document.querySelectorAll('tbody tr');
        var counters = {};

        rows.forEach(function(row) {
            var battingOrderInput = row.querySelector('input[name="battingOrder[]"]');
            var rankingInput = row.querySelector('input[name="ranking[]"]');

            if (!battingOrderInput || !rankingInput) {
                return;
            }

            var battingOrder = battingOrderInput.value.trim();

            if (battingOrder === '') {
                rankingInput.value = 1;
                return;
            }

            counters[battingOrder] = (counters[battingOrder] || 0) + 1;
            rankingInput.value = counters[battingOrder];
        });
    }

    function bindRankingListeners(input) {
        if (!input) {
            return;
        }

        input.addEventListener('input', syncRankings);
        input.addEventListener('change', syncRankings);
    }

    document.querySelectorAll('input[name="battingOrder[]"]').forEach(function(input) {
        bindRankingListeners(input);
    });

    syncRankings();
</script>
