<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            打撃成績
        </h2>
        <x-message :message="session('message')" />
    </x-slot>
    <div class="container mt-8">
        <div class="bg-white rounded-lg shadow-lg mb-8">
            <div class="bg-white rounded-lg shadow-lg mb-8">
                <div class="p-4">
                    <div class="relative">
                        <div class="bg-red-500 h-full absolute top-0 left-0 w-2"></div>
                        <div class="bg-gray-700 pl-3 text-white py-3">
                          <h1 class="text-xl font-semibold">打撃成績</h1>
                        </div>
                    </div>
                    <div class="p-4">
                        <form method="get" action="{{ route('battingStats') }}">
                            <select class="border rounded-lg p-2 w-48" name="year" id="year">
                                @foreach($years as $year)
                                    <option value="{{ $year }}" {{ $year == request('year') || ($year == date('Y') && !request('year')) ? 'selected' : '' }}>{{ $year }}</option>
                                @endforeach
                            </select>
                            <label for="year">年</label>
                            <br />
                            <select class="border rounded-lg p-2 w-48" name="sort" id="sort">
                                @foreach($sortColumns as $column)
                                    <option value="{{ $column }}" {{ $column == request('sort') ? 'selected' : '' }}>{{ $column }}</option>
                                @endforeach
                            </select>
                            <label for="sort">並び替え</label>
                            <br />
                            <select class="border rounded-lg p-2 w-48" name="direction" id="direction">
                                <option value="asc" {{ request('direction') == 'asc' ? 'selected' : '' }}>昇順</option>
                                <option value="desc" {{ request('direction') == 'desc' ? 'selected' : '' }}>降順</option>
                            </select>
                            <label for="direction">順序</label>
                            <br /><button class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded-lg mr-2" type="submit">検索</button>
                        </form>
                    </div>
                    <hr class="border-t border-gray-200 my-4">
                    <div class="overflow-x-auto mt-4">
                    <table class="table-auto w-full text-left mt-4 border border-gray-400 text-sm whitespace-no-wrap">
                        <thead class="bg-orange-500 text-white">
                            <tr>
                                <th class="px-2 py-1 border max-w-xs whitespace-nowrap">順位</th>
                                <th class="px-2 py-1 border max-w-xs whitespace-nowrap">選手</th>
                                <th class="px-2 py-1 border max-w-xs whitespace-nowrap">打率</th>
                                <th class="px-2 py-1 border max-w-xs whitespace-nowrap">出塁率</th>
                                <th class="px-2 py-1 border max-w-xs whitespace-nowrap">試合</th>
                                <th class="px-2 py-1 border max-w-xs whitespace-nowrap">打席</th>
                                <th class="px-2 py-1 border max-w-xs whitespace-nowrap">打数</th>
                                <th class="px-2 py-1 border max-w-xs whitespace-nowrap">安打</th>
                                <th class="px-2 py-1 border max-w-xs whitespace-nowrap">二塁打</th>
                                <th class="px-2 py-1 border max-w-xs whitespace-nowrap">三塁打</th>
                                <th class="px-2 py-1 border max-w-xs whitespace-nowrap">本塁打</th>
                                <th class="px-2 py-1 border max-w-xs whitespace-nowrap">打点</th>
                                <th class="px-2 py-1 border max-w-xs whitespace-nowrap">四球</th>
                                <th class="px-2 py-1 border max-w-xs whitespace-nowrap">死球</th>
                                <th class="px-2 py-1 border max-w-xs whitespace-nowrap">三振</th>
                                <th class="px-2 py-1 border max-w-xs whitespace-nowrap">併殺</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($battingStats as $index => $battingStat)
                                <tr>
                                    <td class="px-2 py-1 border max-w-xs whitespace-nowrap">{{ $index + 1 }}</td>
                                    <td class="px-2 py-1 border max-w-xs whitespace-nowrap">{{ $battingStat->選手名 }}</td>
                                    <td class="px-2 py-1 border max-w-xs whitespace-nowrap">{{ $battingStat->打率 }}</td>
                                    <td class="px-2 py-1 border max-w-xs whitespace-nowrap">{{ $battingStat->出塁率 }}</td>
                                    <td class="px-2 py-1 border max-w-xs whitespace-nowrap">{{ $battingStat->試合 }}</td>
                                    <td class="px-2 py-1 border max-w-xs whitespace-nowrap">{{ $battingStat->打席 }}</td>
                                    <td class="px-2 py-1 border max-w-xs whitespace-nowrap">{{ $battingStat->打数 }}</td>
                                    <td class="px-2 py-1 border max-w-xs whitespace-nowrap">{{ $battingStat->安打 }}</td>
                                    <td class="px-2 py-1 border max-w-xs whitespace-nowrap">{{ $battingStat->二塁打 }}</td>
                                    <td class="px-2 py-1 border max-w-xs whitespace-nowrap">{{ $battingStat->三塁打 }}</td>
                                    <td class="px-2 py-1 border max-w-xs whitespace-nowrap">{{ $battingStat->本塁打 }}</td>
                                    <td class="px-2 py-1 border max-w-xs whitespace-nowrap">{{ $battingStat->打点 }}</td>
                                    <td class="px-2 py-1 border max-w-xs whitespace-nowrap">{{ $battingStat->四球 }}</td>
                                    <td class="px-2 py-1 border max-w-xs whitespace-nowrap">{{ $battingStat->死球 }}</td>
                                    <td class="px-2 py-1 border max-w-xs whitespace-nowrap">{{ $battingStat->三振 }}</td>
                                    <td class="px-2 py-1 border max-w-xs whitespace-nowrap">{{ $battingStat->併殺 }}</td>
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
