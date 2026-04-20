<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            盗塁登録
        </h2>
        <x-input-error class="mb-4" :messages="$errors->all()" />
        <x-input-error class="mb-4" :messages="session('error')" />
        <x-message :message="session('message')" />
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-2 lg:px-8">
        <div class="sm:p-8">
            <p class="mt-4 mb-4 text-sm text-gray-600">
                <a href="{{ route('game.show', $game->gameId) }}" class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded-lg">
                    試合詳細に戻る
                </a>
            </p>
                <div class="overflow-x-auto bg-white">
                    <table class="min-w-full border rounded-lg text-sm">
                        <thead>
                            <tr>
                                <th class="px-1 py-3 text-white bg-orange-500">打順</th>
                                <th class="px-1 py-3 text-white bg-orange-500">守備</th>
                                <th class="px-2 py-3 text-white bg-orange-500">選手</th>
                                <th class="px-1 py-3 text-white bg-orange-500">盗塁</th>
                                <th class="px-2 py-3 text-white bg-orange-500">増減</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($battingOrders as $battingOrder)
                            @if($battingOrder->user)
                            <tr>
                                <td class="px-1 py-1 border">{{$battingOrder->battingOrder}}</td>
                                <td class="px-1 py-1 border">{{$battingOrder->position->positionName}}</td>
                                <td class="px-2 py-1 border">{{$battingOrder->user->name}}</td>
                                <td class="px-1 py-1 border">{{$stealCounts->where('userId',$battingOrder->userId)->pluck('count')->first()}}</td>
                                <td class="px-2 py-1 border text-center">
                                    <form method="POST" action="{{ route('steal.store') }}">
                                        @csrf
                                        <input type="hidden" name="gameId" value="{{ $game->gameId }}">
                                        <input type="hidden" name="userId" value="{{ $battingOrder->userId }}">
                                        <button type="submit" class="bg-green-500 hover:bg-green-600 text-white font-semibold py-2 px-2 rounded-lg text-sm">
                                            <i class="fas fa-arrow-up"></i> <!-- FontAwesomeの矢印アイコン -->
                                        </button>
                                    </form>

                                    <form method="POST" action="{{ route('steal.destroy') }}">
                                        @csrf
                                        @method('DELETE')
                                        <input type="hidden" name="gameId" value="{{ $game->gameId }}">
                                        <input type="hidden" name="userId" value="{{ $battingOrder->userId }}">
                                        <button type="submit" class="bg-red-500 hover:bg-red-600 text-white font-semibold py-2 px-2 rounded-lg">
                                            <i class="fas fa-arrow-down"></i> <!-- FontAwesomeの矢印アイコン -->
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
        </div>
    </div>
</x-app-layout>
