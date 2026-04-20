<x-app-layout>
    <x-slot name="header">
        <h2 class="text-lg sm:text-xl font-semibold text-gray-800 leading-tight">
            試合一覧
        </h2>
        <x-message :message="session('message')" />
    </x-slot>
    <form method="get" action="{{ route('game.index') }}" class="w-full inline-block mb-2 text-center text-sm">
            <input type="text" name="year" class="w-auto py-2 placeholder-gray-300 border border-gray-300 rounded-md" placeholder="年度を入力" value="{{ request('year', date('Y')) }}">
            <button type="submit" class="py-2 px-2 bg-blue-500 text-white font-semibold rounded-md ml-2">
                検索
            </button>
            <a href="{{ route('game.create') }}" class="py-2 px-2 bg-red-500 hover:bg-red-600 text-white font-semibold rounded-lg ml-2">
                新規試合登録
            </a>
    </form>
    <div class="container mx-auto mt-8">
        @foreach ($games as $game)
            <div class="bg-white rounded-lg shadow-lg mb-8">
                <div class="py-4 px-2">
                    <h1 class="text-2xl font-semibold mb-2">{{ $game->gameName }} &nbsp;&nbsp; -{{ \Carbon\Carbon::parse($game->gameDates)->format('Y/m/d') }}-</h1>
                    <hr class="border-t border-gray-200 my-4">
                    <p class="mt-4 text-sm text-gray-600">
                        <a href="{{ route('game.show', $game->gameId) }}" class="text-sm bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded-lg">
                            試合詳細
                        </a>
                        <a href="{{ route('game.edit', $game->gameId) }}" class="text-sm ml-4 bg-green-500 hover:bg-green-600 text-white font-semibold py-2 px-4 rounded-lg">
                            基本情報編集
                        </a>
                    </p>
                    <table class="table-auto w-full text-left mt-4 border border-gray-400 text-sm">
                        <thead class="bg-orange-500 text-white">
                            <tr>
                                <th class="px-2 border">チーム名</th>
                                <th class="px-2 border">1</th>
                                <th class="px-2 border">2</th>
                                <th class="px-2 border">3</th>
                                <th class="px-2 border">4</th>
                                <th class="px-2 border">5</th>
                                <th class="px-2 border">6</th>
                                <th class="px-2 border">7</th>
                                <th class="px-2 border">8</th>
                                <th class="px-2 border">9</th>
                                <th class="px-2 border">合計</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if ($game->gameFirstFlg == 0)
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
                                <td class="px-2 py-3 border">OM</td>
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
        @endforeach
    </div>
</x-app-layout>
