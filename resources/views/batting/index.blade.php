<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            打撃編集一覧
        </h2>
        <x-message :message="session('message')" />
    </x-slot>
    <div class="container mx-auto mt-8">
        <p class="mt-4 mb-4 text-sm text-gray-600">
            <a href="{{ route('game.show', $game->gameId) }}" class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded-lg">
                試合詳細に戻る
            </a>
        </p>
        <div class="bg-white rounded-lg shadow-lg mb-8">
            <div class="bg-white rounded-lg shadow-lg mb-8">
                <div class="p-4">
                    <div class="relative">
                        <div class="bg-red-500 h-full absolute top-0 left-0 w-2"></div>
                        <div class="bg-gray-700 pl-3 text-white py-3">
                          <h1 class="text-xl font-semibold">打撃成績<span class="text-red-500">※成績をクリックすると編集できます</span></h1>
                        </div>
                    </div>
                    <hr class="border-t border-gray-200 my-4">
                    <div class="overflow-x-auto mt-4">
                    <table class="table-auto w-full text-left mt-4 border border-gray-400 text-sm whitespace-no-wrap">
                        <thead class="bg-orange-500 text-white">
                            <tr>
                                <th class="px-2 py-1 border max-w-xs whitespace-nowrap">打順</th>
                                <th class="px-2 py-1 border max-w-xs whitespace-nowrap">守備位置</th>
                                <th class="px-2 py-1 border max-w-xs whitespace-nowrap">選手名</th>
                                @foreach ($battingColumns as $column)
                                    <th class="px-2 py-1 border max-w-xs whitespace-nowrap">{{ $column['label'] }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($orders as $order)
                                    @php
                                        $playerKey = $order->userId !== null
                                            ? 'id:' . $order->userId
                                            : 'name:' . trim((string) $order->userName);
                                    @endphp
                                    <tr>
                                        <td class="px-2 py-1 border max-w-xs whitespace-nowrap">
                                            @if ( $order->ranking === 1)
                                            {{ $order->battingOrder }}
                                            @endif
                                        </td>
                                        <td class="px-2 py-1 border max-w-xs whitespace-nowrap">
                                            @if ( $order->ranking === 1)
                                            ({{ $order->position->positionName }})
                                            @else
                                            {{ $order->position->positionName }}
                                            @endif
                                        </td>
                                        <td class="px-2 py-1 border max-w-xs whitespace-nowrap">
                                            @if (!is_null($order->userId))
                                                {{ $order->user->name }}
                                            @else
                                                {{ $order->userName }}
                                            @endif
                                        </td>
                                        @foreach ($battingColumns as $column)
                                            @php
                                                $stats = $battingCellMap[$playerKey][$column['inning']][$column['turn']] ?? null;
                                            @endphp
                                            <td class="px-2 py-1 border max-w-xs whitespace-nowrap">
                                                @if($stats)
                                                    <label class="{{ $stats->result1->type === 1 ? 'text-blue-500 font-semibold' : '' }}">
                                                        <a href="{{ route('batting.edit', $stats) }}" class="underline">{{ $stats->result2->name }}{{ $stats->result1->name }} @if($statsId == $stats->id) <span class="text-red-500">NEW!</span> @endif</a>
                                                    </label>
                                                @else
                                                    <a href="{{ route('batting.create', ['game' => $game, 'inning' => $column['inning'], 'userId' => $order->userId, 'userName' => $order->userId ? null : $order->userName, 'fromEdit' => true]) }}" class="btn btn-primary">新規作成</a>
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                            @endforeach
                        </tbody>
                    </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
