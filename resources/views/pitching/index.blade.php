<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            投手成績編集一覧
        </h2>
        <x-message :message="session('message')" />
        <x-input-error class="mb-4" :messages="session('error')" />
    </x-slot>
    <div class="container mx-auto mt-8">
        <div class="bg-white rounded-lg shadow-lg mb-8">
            <div class="bg-white rounded-lg shadow-lg mb-8">
                <div class="p-4">
                    <p class="mt-4 mb-4 text-sm text-gray-600">
                        <a href="{{ route('game.show', $game->gameId) }}" class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded-lg">
                            試合詳細に戻る
                        </a>
                    </p>
                    <div class="relative">
                        <div class="bg-red-500 h-full absolute top-0 left-0 w-2"></div>
                        <div class="bg-gray-700 pl-3 text-white py-3">
                          <h1 class="text-xl font-semibold">投手成績</h1>
                        </div>
                      </div>
                    <hr class="border-t border-gray-200 my-4">
                    <p class="mt-4 mb-4 text-sm text-gray-600">
                        <a href="{{ route('pitching.create', $game->gameId) }}" class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded-lg">
                            新規登録
                        </a>
                    </p>
                    <div class="overflow-x-auto mt-4">
                    <table class="table-auto w-full text-left mt-4 border border-gray-400 text-sm whitespace-no-wrap">
                        <thead class="bg-orange-500 text-white">
                            <tr>
                                <th class="px-2 py-1 border max-w-xs whitespace-nowrap">No.</th>
                                <th class="px-2 py-1 border max-w-xs whitespace-nowrap">選手名</th>
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
                                <th class="px-2 py-1 border max-w-xs whitespace-nowrap">編集</th>
                                <th class="px-2 py-1 border max-w-xs whitespace-nowrap">削除</th>
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
                                <td class="px-2 py-1 border whitespace-nowrap">
                                    <a href="{{ route('pitching.edit',$pitchingStat) }}" class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-1 px-1 rounded-lg mt-4">
                                        編集
                                    </a>
                                </td>
                                <td class="px-2 py-1 border whitespace-nowrap">
                                    <form method="POST" action="{{ route('pitching.destroy', $pitchingStat) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="bg-red-500 hover:bg-red-600 text-white font-semibold py-1 px-1 rounded-lg" onclick="return confirmDelete(event);">
                                            削除
                                        </button>
                                    </form>
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
<script>
    function confirmDelete(event) {
        if (!confirm('削除してもよろしいですか？')) {
            event.preventDefault(); // ボタンクリック時のデフォルトのフォーム送信を防ぐ
        }
    }
</script>
