<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            投手成績編集
        </h2>
        <x-message :message="session('message')" />
        <x-input-error class="mb-4" :messages="session('error')" />
    </x-slot>
    <div class="container mx-auto mt-8">
        <div class="bg-white rounded-lg shadow-lg mb-8">
            <div class="bg-white rounded-lg shadow-lg mb-8">
                <div class="p-4">
                    <p class="mt-4 mb-4 text-sm text-gray-600">
                        <a href="{{ route('pitching', $pitching->gameId) }}" class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded-lg">
                            一覧に戻る
                        </a>
                    </p>
                    <div class="relative">
                        <div class="bg-red-500 h-full absolute top-0 left-0 w-2"></div>
                        <div class="bg-gray-700 pl-3 text-white py-3">
                          <h1 class="text-xl font-semibold">投手成績</h1>
                        </div>
                      </div>
                    <hr class="border-t border-gray-200 my-4">
                    <form method="POST" action="{{ route('pitching.update',$pitching) }}">
                        @csrf
                    <div class="overflow-x-auto mt-4">
                    <table class="table-auto w-full text-left mt-4 border border-gray-400 text-sm whitespace-no-wrap">
                        <thead class="bg-orange-500 text-white">
                            <tr>
                                <th class="px-2 py-1 border max-w-xs whitespace-nowrap">No.</th>
                                <th class="px-2 py-1 border max-w-xs whitespace-nowrap">選手</th>
                                <th class="px-2 py-1 border max-w-xs whitespace-nowrap">勝敗</th>
                                <th class="px-2 py-1 border max-w-xs whitespace-nowrap">セーブ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="px-2 py-1 border">
                                    <input type="number" name="pitchingOrder" class="border rounded-lg p-2 w-16" value="{{ $pitching->pitchingOrder }}">
                                </td>
                                <td class="px-2 py-1 border">
                                    <select class="border rounded-lg p-2 w-46" name="userId">
                                        <option value="">選択</option>
                                        @foreach ($users as $user)
                                            <option value="{{ $user->id }}" {{ $user->id == $pitching->userId ? 'selected' : '' }}>
                                                {{ $user->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="px-2 py-1 border max-w-xs whitespace-nowrap">
                                    <select name="result" class="border rounded-lg p-2 w-16">
                                        <option></option>
                                        <option value="勝" {{ $pitching->result == '勝' ? 'selected' : '' }}>勝</option>
                                        <option value="負" {{ $pitching->result == '負' ? 'selected' : '' }}>負</option>
                                    </select>
                                </td>
                                <td class="px-2 py-1 border max-w-xs whitespace-nowrap">
                                    <select name="save" class="border rounded-lg p-2 w-16">
                                        <option></option>
                                        <option value="1" {{ $pitching->save == 1 ? 'selected' : '' }}>〇</option>
                                        <option value="0" {{ $pitching->save == 0 ? 'selected' : '' }}>なし</option>
                                    </select>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    </div>
                    <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded-lg">更新</button>
                    </form>
                    <div class="overflow-x-auto mt-4">
                        <table class="table-auto w-full text-left mt-4 border border-gray-400 text-sm whitespace-no-wrap">
                            <thead class="bg-orange-500 text-white">
                                <tr>
                                    <th class="px-2 py-1 border max-w-xs whitespace-nowrap">項目</th>
                                    <th class="px-2 py-1 border max-w-xs whitespace-nowrap">値</th>
                                    <th class="px-2 py-1 border max-w-xs whitespace-nowrap">増減</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="px-2 py-1 border">
                                        イニング
                                    </td>
                                    <form method="POST" action="{{ route('pitching.updateNumber',$pitching) }}">
                                        @csrf
                                    <td class="px-2 py-1 border">
                                        <input type="hidden" name="type" value="inning">
                                        <input type="number" name="inning" class="border rounded-lg p-2 w-16" value="{{ $pitching->inning }}" step="0.1">
                                    </td>

                                    <td class="px-2 py-1 border text-center">
                                        <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded-lg">
                                            更新
                                        </button>
                                    </td>
                                    </form>
                                </tr>
                                <tr>
                                    <td class="px-2 py-1 border">
                                        被安打
                                    </td>
                                    <td class="px-2 py-1 border">
                                        {{ $pitching->hitsAllowed }}
                                    </td>
                                    <td class="px-2 py-1 border text-center">
                                        <form method="POST" action="{{ route('pitching.updateNumber',$pitching) }}">
                                            @csrf
                                            <input type="hidden" name="type" value="hitsAllowed">
                                            <input type="hidden" name="hitsAllowed" value="{{ $pitching->hitsAllowed + 1 }}">
                                            <button type="submit" class="bg-green-500 hover:bg-green-600 text-white font-semibold py-2 px-2 rounded-lg text-sm">
                                                <i class="fas fa-arrow-up"></i> <!-- FontAwesomeの矢印アイコン -->
                                            </button>
                                        </form>

                                        <form method="POST" action="{{ route('pitching.updateNumber',$pitching) }}">
                                            @csrf
                                            <input type="hidden" name="type" value="hitsAllowed">
                                            <input type="hidden" name="hitsAllowed" value="{{ $pitching->hitsAllowed - 1 }}">
                                            <button type="submit" class="bg-red-500 hover:bg-red-600 text-white font-semibold py-2 px-2 rounded-lg">
                                                <i class="fas fa-arrow-down"></i> <!-- FontAwesomeの矢印アイコン -->
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="px-2 py-1 border">
                                        被本塁打
                                    </td>
                                    <td class="px-2 py-1 border">
                                        {{ $pitching->homeRunsAllowed }}
                                    </td>
                                    <td class="px-2 py-1 border text-center">
                                        <form method="POST" action="{{ route('pitching.updateNumber',$pitching) }}">
                                            @csrf
                                            <input type="hidden" name="type" value="homeRunsAllowed">
                                            <input type="hidden" name="homeRunsAllowed" value="{{ $pitching->homeRunsAllowed + 1 }}">
                                            <button type="submit" class="bg-green-500 hover:bg-green-600 text-white font-semibold py-2 px-2 rounded-lg text-sm">
                                                <i class="fas fa-arrow-up"></i> <!-- FontAwesomeの矢印アイコン -->
                                            </button>
                                        </form>

                                        <form method="POST" action="{{ route('pitching.updateNumber',$pitching) }}">
                                            @csrf
                                            <input type="hidden" name="type" value="homeRunsAllowed">
                                            <input type="hidden" name="homeRunsAllowed" value="{{ $pitching->homeRunsAllowed - 1 }}">
                                            <button type="submit" class="bg-red-500 hover:bg-red-600 text-white font-semibold py-2 px-2 rounded-lg">
                                                <i class="fas fa-arrow-down"></i> <!-- FontAwesomeの矢印アイコン -->
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="px-2 py-1 border">
                                        奪三振
                                    </td>
                                    <td class="px-2 py-1 border">
                                        {{ $pitching->strikeouts }}
                                    </td>
                                    <td class="px-2 py-1 border text-center">
                                        <form method="POST" action="{{ route('pitching.updateNumber',$pitching) }}">
                                            @csrf
                                            <input type="hidden" name="type" value="strikeouts">
                                            <input type="hidden" name="strikeouts" value="{{ $pitching->strikeouts + 1 }}">
                                            <button type="submit" class="bg-green-500 hover:bg-green-600 text-white font-semibold py-2 px-2 rounded-lg text-sm">
                                                <i class="fas fa-arrow-up"></i> <!-- FontAwesomeの矢印アイコン -->
                                            </button>
                                        </form>

                                        <form method="POST" action="{{ route('pitching.updateNumber',$pitching) }}">
                                            @csrf
                                            <input type="hidden" name="type" value="strikeouts">
                                            <input type="hidden" name="strikeouts" value="{{ $pitching->strikeouts - 1 }}">
                                            <button type="submit" class="bg-red-500 hover:bg-red-600 text-white font-semibold py-2 px-2 rounded-lg">
                                                <i class="fas fa-arrow-down"></i> <!-- FontAwesomeの矢印アイコン -->
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="px-2 py-1 border">
                                        四死球
                                    </td>
                                    <td class="px-2 py-1 border">
                                        {{ $pitching->walks }}
                                    </td>
                                    <td class="px-2 py-1 border text-center">
                                        <form method="POST" action="{{ route('pitching.updateNumber',$pitching) }}">
                                            @csrf
                                            <input type="hidden" name="type" value="walks">
                                            <input type="hidden" name="walks" value="{{ $pitching->walks + 1 }}">
                                            <button type="submit" class="bg-green-500 hover:bg-green-600 text-white font-semibold py-2 px-2 rounded-lg text-sm">
                                                <i class="fas fa-arrow-up"></i> <!-- FontAwesomeの矢印アイコン -->
                                            </button>
                                        </form>

                                        <form method="POST" action="{{ route('pitching.updateNumber',$pitching) }}">
                                            @csrf
                                            <input type="hidden" name="type" value="walks">
                                            <input type="hidden" name="walks" value="{{ $pitching->walks - 1 }}">
                                            <button type="submit" class="bg-red-500 hover:bg-red-600 text-white font-semibold py-2 px-2 rounded-lg">
                                                <i class="fas fa-arrow-down"></i> <!-- FontAwesomeの矢印アイコン -->
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="px-2 py-1 border">
                                        暴投
                                    </td>
                                    <td class="px-2 py-1 border">
                                        {{ $pitching->wildPitches }}
                                    </td>
                                    <td class="px-2 py-1 border text-center">
                                        <form method="POST" action="{{ route('pitching.updateNumber',$pitching) }}">
                                            @csrf
                                            <input type="hidden" name="type" value="wildPitches">
                                            <input type="hidden" name="wildPitches" value="{{ $pitching->wildPitches + 1 }}">
                                            <button type="submit" class="bg-green-500 hover:bg-green-600 text-white font-semibold py-2 px-2 rounded-lg text-sm">
                                                <i class="fas fa-arrow-up"></i> <!-- FontAwesomeの矢印アイコン -->
                                            </button>
                                        </form>

                                        <form method="POST" action="{{ route('pitching.updateNumber',$pitching) }}">
                                            @csrf
                                            <input type="hidden" name="type" value="wildPitches">
                                            <input type="hidden" name="wildPitches" value="{{ $pitching->wildPitches - 1 }}">
                                            <button type="submit" class="bg-red-500 hover:bg-red-600 text-white font-semibold py-2 px-2 rounded-lg">
                                                <i class="fas fa-arrow-down"></i> <!-- FontAwesomeの矢印アイコン -->
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="px-2 py-1 border">
                                        ボーク
                                    </td>
                                    <td class="px-2 py-1 border">
                                        {{ $pitching->balks }}
                                    </td>
                                    <td class="px-2 py-1 border text-center">
                                        <form method="POST" action="{{ route('pitching.updateNumber',$pitching) }}">
                                            @csrf
                                            <input type="hidden" name="type" value="balks">
                                            <input type="hidden" name="balks" value="{{ $pitching->balks + 1 }}">
                                            <button type="submit" class="bg-green-500 hover:bg-green-600 text-white font-semibold py-2 px-2 rounded-lg text-sm">
                                                <i class="fas fa-arrow-up"></i> <!-- FontAwesomeの矢印アイコン -->
                                            </button>
                                        </form>

                                        <form method="POST" action="{{ route('pitching.updateNumber',$pitching) }}">
                                            @csrf
                                            <input type="hidden" name="type" value="balks">
                                            <input type="hidden" name="balks" value="{{ $pitching->balks - 1 }}">
                                            <button type="submit" class="bg-red-500 hover:bg-red-600 text-white font-semibold py-2 px-2 rounded-lg">
                                                <i class="fas fa-arrow-down"></i> <!-- FontAwesomeの矢印アイコン -->
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="px-2 py-1 border">
                                        失点
                                    </td>
                                    <td class="px-2 py-1 border">
                                        {{ $pitching->runsAllowed }}
                                    </td>
                                    <td class="px-2 py-1 border text-center">
                                        <form method="POST" action="{{ route('pitching.updateNumber',$pitching) }}">
                                            @csrf
                                            <input type="hidden" name="type" value="runsAllowed">
                                            <input type="hidden" name="runsAllowed" value="{{ $pitching->runsAllowed + 1 }}">
                                            <button type="submit" class="bg-green-500 hover:bg-green-600 text-white font-semibold py-2 px-2 rounded-lg text-sm">
                                                <i class="fas fa-arrow-up"></i> <!-- FontAwesomeの矢印アイコン -->
                                            </button>
                                        </form>

                                        <form method="POST" action="{{ route('pitching.updateNumber',$pitching) }}">
                                            @csrf
                                            <input type="hidden" name="type" value="runsAllowed">
                                            <input type="hidden" name="runsAllowed" value="{{ $pitching->runsAllowed - 1 }}">
                                            <button type="submit" class="bg-red-500 hover:bg-red-600 text-white font-semibold py-2 px-2 rounded-lg">
                                                <i class="fas fa-arrow-down"></i> <!-- FontAwesomeの矢印アイコン -->
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="px-2 py-1 border">
                                        自責点
                                    </td>
                                    <td class="px-2 py-1 border">
                                        {{ $pitching->earnedRuns }}
                                    </td>
                                    <td class="px-2 py-1 border text-center">
                                        <form method="POST" action="{{ route('pitching.updateNumber',$pitching) }}">
                                            @csrf
                                            <input type="hidden" name="type" value="earnedRuns">
                                            <input type="hidden" name="earnedRuns" value="{{ $pitching->earnedRuns + 1 }}">
                                            <button type="submit" class="bg-green-500 hover:bg-green-600 text-white font-semibold py-2 px-2 rounded-lg text-sm">
                                                <i class="fas fa-arrow-up"></i> <!-- FontAwesomeの矢印アイコン -->
                                            </button>
                                        </form>

                                        <form method="POST" action="{{ route('pitching.updateNumber',$pitching) }}">
                                            @csrf
                                            <input type="hidden" name="type" value="earnedRuns">
                                            <input type="hidden" name="earnedRuns" value="{{ $pitching->earnedRuns - 1 }}">
                                            <button type="submit" class="bg-red-500 hover:bg-red-600 text-white font-semibold py-2 px-2 rounded-lg">
                                                <i class="fas fa-arrow-down"></i> <!-- FontAwesomeの矢印アイコン -->
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
