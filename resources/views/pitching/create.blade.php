<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            投手成績新規登録
        </h2>
        <x-message :message="session('message')" />
        <x-input-error class="mb-4" :messages="session('error')" />
    </x-slot>
    <div class="container mx-auto mt-8">
        <div class="bg-white rounded-lg shadow-lg mb-8">
            <div class="bg-white rounded-lg shadow-lg mb-8">
                <div class="p-4">
                    <p class="mt-4 mb-4 text-sm text-gray-600">
                        <a href="{{ route('pitching', $gameId) }}" class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded-lg">
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
                    <form method="POST" action="{{ route('pitching.store',$gameId) }}">
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
                                    <input type="number" name="pitchingOrder" class="border rounded-lg p-2 w-16" value="">
                                </td>
                                <td class="px-2 py-1 border">
                                    <select class="border rounded-lg p-2 w-46" name="userId">
                                        <option value="">選択</option>
                                        @foreach ($users as $user)
                                            <option value="{{ $user->id }}">
                                                {{ $user->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="px-2 py-1 border max-w-xs whitespace-nowrap">
                                    <select name="result" class="border rounded-lg p-2 w-16">
                                        <option></option>
                                        <option value="勝">勝</option>
                                        <option value="負">負</option>
                                    </select>
                                </td>
                                <td class="px-2 py-1 border max-w-xs whitespace-nowrap">
                                    <select name="save" class="border rounded-lg p-2 w-16">
                                        <option></option>
                                        <option value="1" >〇</option>
                                        <option value="0" >なし</option>
                                    </select>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    </div>
                    <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded-lg">登録</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
