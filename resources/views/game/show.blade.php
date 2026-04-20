<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            試合詳細
        </h2>
        <x-message :message="session('message')" />
    </x-slot>
    <div class="container mt-8">
        <p class="mt-4 mb-4 text-sm text-gray-600">
            <a href="{{ route('order.edit', $game->gameId) }}" class="bg-green-500 hover:bg-green-600 text-white font-semibold py-2 px-4 rounded-lg">
                打順登録
            </a>
            <a href="{{ route('batting.create', $game->gameId) }}" class="ml-4 bg-red-500 hover:bg-red-600 text-white font-semibold py-2 px-4 rounded-lg">
                打撃登録
            </a>
            <a href="{{ route('steal.index', $game) }}" class="ml-4 bg-red-500 hover:bg-red-600 text-white font-semibold py-2 px-4 rounded-lg">
                盗塁登録
            </a>
        </p>
            <div class="bg-white rounded-lg shadow-lg mb-8">
                <div class="px-2 py-4">
                    <h1 class="text-2xl font-semibold mb-2">{{ $game->gameName }} &nbsp;&nbsp; -{{ \Carbon\Carbon::parse($game->gameDates)->format('Y/m/d') }}-</h1>
                    <hr class="border-t border-gray-200 my-4">
                    <p class="mt-4 text-sm text-gray-600">
                        <a href="{{ route('game.edit', $game->gameId) }}" class="bg-green-500 hover:bg-green-600 text-white font-semibold py-2 px-4 rounded-lg">
                            基本情報編集
                        </a>
                    </p>
                    <table class="table-auto w-full text-left mt-4 border border-gray-400 text-sm">
                        <thead class="bg-orange-500 text-white">
                            <tr>
                                <th class="px-2 py-1 border">チーム名</th>
                                <th class="px-2 py-1 border">1</th>
                                <th class="px-2 py-1 border">2</th>
                                <th class="px-2 py-1 border">3</th>
                                <th class="px-2 py-1 border">4</th>
                                <th class="px-2 py-1 border">5</th>
                                <th class="px-2 py-1 border">6</th>
                                <th class="px-2 py-1 border">7</th>
                                <th class="px-2 py-1 border">8</th>
                                <th class="px-2 py-1 border">9</th>
                                <th class="px-2 py-1 border">合計</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if ($game->gameFirstFlg == 0)
                            <tr>
                                <td class="px-2 py-1border">OM</td>
                                @php
                                $total = 0; // 合計値を初期化
                                $count = 0; // カウント数を初期化
                                @endphp
                                @foreach ($points as $point)
                                    @if ($point->gameId == $game->gameId && $point->inning_side == 0)
                                        <td class="px-2 py-1 border">{{ $point->score }}</td>
                                        @php
                                        $total += $point->score; // 合計を更新
                                        $count += 1;
                                        @endphp
                                    @endif
                                @endforeach
                                <!-- 足りない列には空セルを追加 -->
                                @for ($i = $count; $i < 9; $i++)
                                    <td class="px-2 py-1 border"></td>
                                @endfor
                                <td class="px-2 py-1 border">{{ $total }}</td> <!-- 合計セル -->
                            </tr>
                            <tr>
                                <td class="px-2 py-1 border">{{ $game->enemyName }}</td>
                                @php
                                $total = 0; // 合計値を初期化
                                $count = 0; // カウント数を初期化
                                @endphp
                                @foreach ($points as $point)
                                    @if ($point->gameId == $game->gameId && $point->inning_side == 1)
                                        <td class="px-2 py-1 border">{{ $point->score }}</td>
                                        @php
                                        $total += $point->score; // 合計を更新
                                        $count += 1;
                                        @endphp
                                    @endif
                                @endforeach
                                <!-- 足りない列には空セルを追加 -->
                                @for ($i = $count; $i < 9; $i++)
                                    <td class="px-2 py-1 border"></td>
                                @endfor
                                <td class="px-2 py-1 border">{{ $total }}</td> <!-- 合計セル -->
                            </tr>
                            @else
                            <tr>
                                <td class="px-2 py-1 border">{{ $game->enemyName }}</td>
                                @php
                                $total = 0; // 合計値を初期化
                                $count = 0; // カウント数を初期化
                                @endphp
                                @foreach ($points as $point)
                                    @if ($point->gameId == $game->gameId && $point->inning_side == 1)
                                        <td class="px-2 py-1 border">{{ $point->score }}</td>
                                        @php
                                        $total += $point->score; // 合計を更新
                                        $count += 1;
                                        @endphp
                                    @endif
                                @endforeach
                                <!-- 足りない列には空セルを追加 -->
                                @for ($i = $count; $i < 9; $i++)
                                    <td class="px-2 py-1 border"></td>
                                @endfor
                                <td class="px-2 py-1 border">{{ $total }}</td> <!-- 合計セル -->
                            </tr>
                            <tr>
                                <td class="px-2 py-1 border">OM</td>
                                @php
                                $total = 0; // 合計値を初期化
                                $count = 0; // カウント数を初期化
                                @endphp
                                @foreach ($points as $point)
                                    @if ($point->gameId == $game->gameId && $point->inning_side == 0)
                                        <td class="px-2 py-1 border">{{ $point->score }}</td>
                                        @php
                                        $total += $point->score; // 合計を更新
                                        $count += 1;
                                        @endphp
                                    @endif
                                @endforeach
                                 <!-- 足りない列には空セルを追加 -->
                                 @for ($i = $count; $i < 9; $i++)
                                 <td class="px-2 py-1 border"></td>
                                 @endfor
                                <td class="px-2 py-1 border">{{ $total }}</td> <!-- 合計セル -->
                            </tr>
                            @endif
                        </tbody>
                    </table>
                    <br />
                    @if($game->winFlg === 0)
                        <span class="px-3 py-2 bg-blue-500 text-white text-lg font-semibold">勝利！</span>
                    @elseif($game->winFlg === 1)
                        <span class="px-3 py-2 bg-red-500 text-white text-lg font-semibold">敗北...</span>
                    @else
                        <span class="px-3 py-2 bg-green-500 text-white text-lg font-semibold">試合中！</span>
                    @endif
                </div>
            </div>
            <div class="bg-white rounded-lg shadow-lg mb-8">
                <div class="bg-white rounded-lg shadow-lg mb-8">
                    <div class="p-4">
                        <div class="relative">
                            <div class="bg-red-500 h-full absolute top-0 left-0 w-2"></div>
                            <div class="bg-gray-700 pl-3 text-white py-3">
                              <h1 class="text-xl font-semibold">打撃成績</h1>
                            </div>
                        </div>
                        <hr class="border-t border-gray-200 my-4">
                        <p class="mt-4 text-sm text-gray-600">
                            <a href="{{ route('batting.index', $game) }}" class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded-lg">
                                打撃成績編集
                            </a>
                        </p>
                        <div class="overflow-x-auto mt-4">
                        <table class="table-auto w-full text-left mt-4 border border-gray-400 text-sm whitespace-no-wrap">
                            <thead class="bg-orange-500 text-white">
                                <tr>
                                    <th class="px-2 py-1 border max-w-xs whitespace-nowrap">打順</th>
                                    <th class="px-2 py-1 border max-w-xs whitespace-nowrap">守備位置</th>
                                    <th class="px-2 py-1 border max-w-xs whitespace-nowrap">選手名</th>
                                    <th class="px-2 py-1 border max-w-xs whitespace-nowrap">打数</th>
                                    <th class="px-2 py-1 border max-w-xs whitespace-nowrap">安打</th>
                                    <th class="px-2 py-1 border max-w-xs whitespace-nowrap">打点</th>
                                    <th class="px-2 py-1 border max-w-xs whitespace-nowrap">打率</th>
                                    <th class="px-2 py-1 border max-w-xs whitespace-nowrap">盗塁</th>
                                    @for ($i = 1; $i <= $battingStats->max('inning');$i++)
                                    <th class="px-2 py-1 border max-w-xs whitespace-nowrap">{{$i}}</th>
                                    @endfor
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $currentBattingOrder = 0;
                                    $isSubstitute = false;
                                @endphp
                                @foreach($orders as $order)
                                        @php
                                            $currentBattingOrder = $order->battingOrder;
                                            $isSubstitute = false;
                                        @endphp
                                        <tr>
                                            <td class="px-2 py-1 border">
                                                @if ( $order->ranking === 1)
                                                {{ $order->battingOrder }}
                                                @endif
                                            </td>
                                            <td class="px-2 py-1 border">
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
                                            @php
                                                $totalBatting = 0;
                                                $totalHitsNo = 0;
                                                $totalHits = 0;

                                                if ($order->userId !== null) {
                                                    $user = $order->userId;
                                                    $totalBatting = $battingStats->where('userId', $user)->count();
                                                    $totalHitsNo = $battingStats->where('userId', $user)->filter(function ($stats) {
                                                        return $stats->result1->type === 2;
                                                    })->count();
                                                    $totalHits = $battingStats->where('userId', $user)->filter(function ($stats) {
                                                        return $stats->result1->type === 1;
                                                    })->count();
                                                } else {
                                                    $user = $order->userName;
                                                    $totalBatting = $battingStats->where('userName', $user)->count();
                                                    $totalHitsNo = $battingStats->where('userName', $user)->filter(function ($stats) {
                                                        return $stats->result1->type === 2;
                                                    })->count();
                                                    $totalHits = $battingStats->where('userName', $user)->filter(function ($stats) {
                                                        return $stats->result1->type === 1;
                                                    })->count();
                                                }

                                                $totalBatting -= $totalHitsNo;
                                                $battingAverage = $totalBatting > 0 ? number_format($totalHits / $totalBatting, 3) : 0.000;
                                            @endphp
                                            <td class="px-2 py-1 border max-w-xs whitespace-nowrap">
                                                {{ $totalBatting }}
                                            </td>
                                            <td class="px-2 py-1 border max-w-xs whitespace-nowrap">
                                                {{ $totalHits }}
                                            </td>
                                            <td class="px-2 py-1 border max-w-xs whitespace-nowrap">
                                                {{ $battingStats
                                                    ->where($order->userId !== null ? 'userId' : 'userName', $user)
                                                    ->filter(function ($stats) {
                                                        return $stats->result3->type === 5;
                                                    })
                                                    ->sum(function ($stats) {
                                                        return $stats->result3->name;
                                                    })
                                                }}
                                            </td>
                                            <td class="px-2 py-1 border max-w-xs whitespace-nowrap">
                                                {{ $battingAverage }}
                                            </td>
                                            <td class="px-2 py-1 border max-w-xs whitespace-nowrap">
                                                {{$stealCounts->where('userId',$order->userId)->pluck('count')->first()}}
                                            </td>
                                            @for ($i = 1; $i <= $battingStats->max('inning');$i++)
                                            <td class="px-2 py-1 border max-w-xs whitespace-nowrap">
                                                @foreach ($battingStats as $stats)
                                                @if($stats->inning == $i)
                                                @if(($order->userId && $order->userId === $stats->userId) || ($order->userName && $order->userName == $stats->userName))
                                                <label class="{{$stats->result1->type === 1 ? 'text-blue-500 font-semibold' : ''}}{{$stats->result3->name > 0 ? 'text-red-500 font-semibold' : ''}}">
                                                    {{$stats->result2->name}}{{$stats->result1->name}}
                                                </label>
                                                @endif
                                                @endif
                                                @endforeach
                                            </td>
                                            @endfor
                                        </tr>
                                @endforeach
                            </tbody>
                        </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow-lg mb-8">
                <div class="bg-white rounded-lg shadow-lg mb-8">
                    <div class="p-4">
                        <div class="relative">
                            <div class="bg-red-500 h-full absolute top-0 left-0 w-2"></div>
                            <div class="bg-gray-700 pl-3 text-white py-3">
                              <h1 class="text-xl font-semibold">投手成績</h1>
                            </div>
                        </div>
                        <hr class="border-t border-gray-200 my-4">
                        <p class="mt-4 text-sm text-gray-600">
                            <a href="{{ route('pitching', $game) }}" class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded-lg">
                                投手成績編集
                            </a>
                        </p>
                        <div class="overflow-x-auto mt-4">
                        <table class="table-auto w-full text-left mt-4 border border-gray-400 text-sm whitespace-no-wrap">
                            <thead class="bg-orange-500 text-white">
                                <tr>
                                    <th class="px-2 py-1 border max-w-xs whitespace-nowrap">No.</th>
                                    <th class="px-2 py-1 border max-w-xs whitespace-nowrap">選手名</th>
                                    <th class="px-2 py-1 border max-w-xs whitespace-nowrap">防御率</th>
                                    <th class="px-2 py-1 border max-w-xs whitespace-nowrap">勝敗</th>
                                    <th class="px-2 py-1 border max-w-xs whitespace-nowrap">セーブ</th>
                                    <th class="px-2 py-1 border max-w-xs whitespace-nowrap">イニング</th>
                                    <th class="px-2 py-1 border max-w-xs whitespace-nowrap">被安打</th>
                                    <th class="px-2 py-1 border max-w-xs whitespace-nowrap">被本塁打</th>
                                    <th class="px-2 py-1 border max-w-xs whitespace-nowrap">奪三振</th>
                                    <th class="px-2 py-1 border max-w-xs whitespace-nowrap">四死球</th>
                                    <th class="px-2 py-1 border max-w-xs whitespace-nowrap">暴投</th>
                                    <th class="px-2 py-1 border max-w-xs whitespace-nowrap">ボーク</th>
                                    <th class="px-2 py-1 border max-w-xs whitespace-nowrap">失点</th>
                                    <th class="px-2 py-1 border max-w-xs whitespace-nowrap">自責点</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($pitchingStats as $pitchingStat)
                                <tr>
                                    <td class="px-2 py-1 border">
                                        {{ $pitchingStat->pitchingOrder }}
                                    </td>
                                    <td class="px-2 py-1 border">
                                        {{ $pitchingStat->user->name }}
                                    </td>
                                    <td class="px-2 py-1 border max-w-xs whitespace-nowrap">
                                        @if ($pitchingStat->inning && $pitchingStat->earnedRuns)
                                            @php
                                                $adjustedInning = ($pitchingStat->inning % 1 == 0.1) ? ($pitchingStat->inning - 0.1) : (($pitchingStat->inning % 1 == 0.2) ? ($pitchingStat->inning - 0.2) : $pitchingStat->inning);
                                                $defenseRate = number_format(($pitchingStat->earnedRuns * 9) / $adjustedInning, 2);
                                            @endphp
                                            {{ $defenseRate }}
                                        @else
                                            0.00
                                        @endif
                                    </td>
                                    <td class="px-2 py-1 border max-w-xs whitespace-nowrap">
                                        {{ $pitchingStat->result }}
                                    </td>
                                    <td class="px-2 py-1 border max-w-xs whitespace-nowrap">
                                        @if ($pitchingStat->save == 1)
                                            〇
                                        @else
                                            なし
                                        @endif
                                    </td>
                                    <td class="px-2 py-1 border max-w-xs whitespace-nowrap">
                                        {{ floor($pitchingStat->inning) }}回
                                        @php
                                            $decimalPart = number_format($pitchingStat->inning - floor($pitchingStat->inning), 1);
                                        @endphp
                                        @if ($decimalPart == 0.1)
                                            1/3
                                        @elseif ($decimalPart == 0.2)
                                            2/3
                                        @endif
                                    </td>
                                    <td class="px-2 py-1 border max-w-xs whitespace-nowrap">
                                        {{ $pitchingStat->hitsAllowed }}
                                    </td>
                                    <td class="px-2 py-1 border max-w-xs whitespace-nowrap">
                                        {{ $pitchingStat->homeRunsAllowed }}
                                    </td>
                                    <td class="px-2 py-1 border max-w-xs whitespace-nowrap">
                                        {{ $pitchingStat->strikeouts }}
                                    </td>
                                    <td class="px-2 py-1 border max-w-xs whitespace-nowrap">
                                        {{ $pitchingStat->walks }}
                                    </td>
                                    <td class="px-2 py-1 border max-w-xs whitespace-nowrap">
                                        {{ $pitchingStat->wildPitches }}
                                    </td>
                                    <td class="px-2 py-1 border max-w-xs whitespace-nowrap">
                                        {{ $pitchingStat->balks }}
                                    </td>
                                    <td class="px-2 py-1 border max-w-xs whitespace-nowrap">
                                        {{ $pitchingStat->runsAllowed }}
                                    </td>
                                    <td class="px-2 py-1 border max-w-xs whitespace-nowrap">
                                        {{ $pitchingStat->earnedRuns }}
                                    </td>
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
