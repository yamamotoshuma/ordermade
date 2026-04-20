<x-app-layout>
    <style>
        .team-name {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
    </style>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            ゲームを編集
        </h2>
        <x-input-error class="mb-4" :messages="$errors->all()" />
        <x-message :message="session('success')" />
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mx-4 sm:p-8">
            <p class="mt-4 mb-4 text-sm text-gray-600">
                <a href="{{ route('game.show', $game->gameId) }}" class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded-lg">
                    試合詳細に戻る
                </a>
            </p>
            <div class="mt-8">
                <label class="font-semibold leading-none mt-4">点数登録</label>
                <form method="POST" action="{{ route('game.updateOrInsert') }}">
                    @csrf
                    <input type="hidden" name="gameId" value="{{ $game->gameId }}">
                    <div class="overflow-x-auto">
                    <table class="table-auto text-left mt-4 border border-gray-400">

                        <thead class="bg-orange-500 text-white">
                            <tr>
                                <th class="px-2 py-1 border team-name">チーム名</th>
                                @for ($i = 1; $i <= 9; $i++)
                                    <th class="px-2 py-1 border">{{ $i }}</th>
                                @endfor
                                <th class="px-2 py-1 border team-name">合計</th>
                            </tr>
                        </thead>
                        </thead>
                        <tbody>
                            @if($game->gameFirstFlg === 1)
                                <tr>
                                    <td class="px-2 py-1 border team-name">{{ $game->enemyName }}</td>
                                    @php
                                    $total = 0; // 合計値を初期化
                                    @endphp
                                    @for ($i = 1; $i <= 9; $i++)
                                        @php
                                        $score = null; // イニングのスコアを初期化
                                        @endphp
                                        @foreach ($points as $point)
                                            @if ($point->gameId == $game->gameId && $point->inning == $i && $point->inning_side == 1)
                                                @php
                                                $score = $point->score; // スコアを設定
                                                $total += $score; // 合計を更新
                                                @endphp
                                                @break
                                            @endif
                                        @endforeach
                                        <td class="px-2 py-1 border">
                                            <input type="number" class="w-16" name="inning[{{ $i }}][0]" value="{{ $score }}">
                                        </td>
                                    @endfor
                                    <td class="px-2 py-1 border">{{ $total }}</td> <!-- 合計セル -->
                                </tr>
                                <tr>
                                    <td class="px-2 py-1 border team-name">オーダーメイド</td>
                                    @php
                                    $total = 0; // 合計値を初期化
                                    @endphp
                                    @for ($i = 1; $i <= 9; $i++)
                                        @php
                                        $score = null; // イニングのスコアを初期化
                                        @endphp
                                        @foreach ($points as $point)
                                            @if ($point->gameId == $game->gameId && $point->inning == $i && $point->inning_side == 0)
                                                @php
                                                $score = $point->score; // スコアを設定
                                                $total += $score; // 合計を更新
                                                @endphp
                                                @break
                                            @endif
                                        @endforeach
                                        <td class="px-2 py-1 border">
                                            <input type="number" class="w-16" name="inning[{{ $i }}][1]" value="{{ $score }}">
                                        </td>
                                    @endfor
                                    <td class="px-2 py-1 border">{{ $total }}</td> <!-- 合計セル -->
                                </tr>
                            @else
                                <tr>
                                    <td class="px-2 py-1 border team-name">オーダーメイド</td>
                                    @php
                                    $total = 0; // 合計値を初期化
                                    @endphp
                                    @for ($i = 1; $i <= 9; $i++)
                                        @php
                                        $score = null; // イニングのスコアを初期化
                                        @endphp
                                        @foreach ($points as $point)
                                            @if ($point->gameId == $game->gameId && $point->inning == $i && $point->inning_side == 0)
                                                @php
                                                $score = $point->score; // スコアを設定
                                                $total += $score; // 合計を更新
                                                @endphp
                                                @break
                                            @endif
                                        @endforeach
                                        <td class="px-2 py-1 border">
                                            <input type="number" class="w-16" name="inning[{{ $i }}][0]" value="{{ $score }}">
                                        </td>
                                    @endfor
                                    <td class="px-2 py-1 border">{{ $total }}</td> <!-- 合計セル -->
                                </tr>
                                <tr>
                                    <td class="px-2 py-1 border team-name">{{ $game->enemyName }}</td>
                                    @php
                                    $total = 0; // 合計値を初期化
                                    @endphp
                                    @for ($i = 1; $i <= 9; $i++)
                                        @php
                                        $score = null; // イニングのスコアを初期化
                                        @endphp
                                        @foreach ($points as $point)
                                            @if ($point->gameId == $game->gameId && $point->inning == $i && $point->inning_side == 1)
                                                @php
                                                $score = $point->score; // スコアを設定
                                                $total += $score; // 合計を更新
                                                @endphp
                                                @break
                                            @endif
                                        @endforeach
                                        <td class="px-2 py-1 border">
                                            <input type="number" class="w-16" name="inning[{{ $i }}][1]" value="{{ $score }}">
                                        </td>
                                    @endfor
                                    <td class="px-2 py-1 border">{{ $total }}</td> <!-- 合計セル -->
                                </tr>
                            @endif
                        </tbody>
                    </table>
                    </div>
                    <button type="submit" class="bg-blue-500 hover-bg-blue-600 text-white font-semibold py-2 px-4 rounded-lg mt-4">一括更新/登録</button>
                </form>
            </div>

            <form method="POST" action="{{ route('game.update', $game) }}" enctype="multipart/form-data">
                @csrf
                @method('patch')

                <div class="md:flex items-center mt-2">
                    <div class="w-full flex flex-col">
                        <label for="gameName" class="font-semibold leading-none mt-4">ゲーム名</label>
                        <input type="text" name="gameName" class="w-auto py-2 placeholder-gray-300 border border-gray-300 rounded-md" id="gameName" value="{{ $game->gameName }}" required>
                    </div>
                </div>

                <div class="md:flex items-center mt-2">
                    <div class="w-full flex flex-col">
                        <label for="year" class="font-semibold leading-none mt-4">年度</label>
                        <input type="number" name="year" class="w-auto py-2 placeholder-gray-300 border border-gray-300 rounded-md" id="year" value="{{ $game->year }}" required>
                    </div>
                </div>

                <div class="md:flex items-center mt-2">
                    <div class="w-full flex flex-col">
                        <label for="gameDates" class="font-semibold leading-none mt-4">試合日時</label>
                        <input type="datetime-local" name="gameDates" class="w-auto py-2 placeholder-gray-300 border border-gray-300 rounded-md" id="gameDates" value="{{ \Carbon\Carbon::parse($game->gameDates)->format('Y-m-d\TH:i') }}" required>
                    </div>
                </div>

                <div class="md:flex items-center mt-2">
                    <div class="w-full flex flex-col">
                        <label for="enemyName" class="font-semibold leading-none mt-4">対戦相手</label>
                        <input type="text" name="enemyName" class="w-auto py-2 placeholder-gray-300 border border-gray-300 rounded-md" id="enemyName" value="{{ $game->enemyName }}" required>
                    </div>
                </div>

                <div class="md:flex items-center mt-2">
                    <div class="w-full flex flex-col">
                        <label for="gameFirstFlg" class="font-semibold leading-none mt-4">先攻チームフラグ</label>
                        <select name="gameFirstFlg" class="w-auto py-2 placeholder-gray-300 border border-gray-300 rounded-md" id="gameFirstFlg" required>
                            <option value="0" {{ $game->gameFirstFlg === 0 ? 'selected' : '' }}>先攻</option>
                            <option value="1" {{ $game->gameFirstFlg === 1 ? 'selected' : '' }}>後攻</option>
                        </select>
                    </div>
                </div>

                <div class="md:flex items-center mt-2">
                    <div class="w-full flex flex-col">
                        <label for="winFlg" class="font-semibold leading-none mt-4">勝敗</label>
                        <select name="winFlg" class="w-auto py-2 placeholder-gray-300 border border-gray-300 rounded-md" id="winFlg">
                            <option value="" {{ is_null($game->winFlg) ? 'selected' : '' }}>-</option>
                            <option value="0" {{ $game->winFlg === 0 ? 'selected' : '' }}>勝利</option>
                            <option value="1" {{ $game->winFlg === 1 ? 'selected' : '' }}>敗北</option>
                        </select>
                    </div>
                </div>

                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded-lg mt-4">更新</button>
            </form>

            <form method="POST" action="{{ route('game.destroy', $game) }}">
                @csrf
                @method('DELETE')
                <button type="submit" class="bg-red-500 hover:bg-red-600 text-white font-semibold py-2 px-4 rounded-lg mt-4" onclick="confirm('削除してもよろしいですか？');">
                    削除
                </button>
            </form>
        </div>
    </div>
</x-app-layout>
