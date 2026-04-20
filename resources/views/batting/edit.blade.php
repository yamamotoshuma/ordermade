<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            打撃編集
        </h2>
        <x-input-error class="mb-4" :messages="$errors->all()" />
            <x-input-error class="mb-4" :messages="session('error')" />
        <x-message :message="session('message')" />
    </x-slot>

    <div class="max-w-7xl mx-auto px-2 sm:px-6 lg:px-8">
        <div class="sm:p-8">
            <p class="mt-4 mb-4 text-sm text-gray-600">
                <button type="button" onClick="history.back()" class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded-lg">打撃成績に戻る</button>
            </p>
            <form method="POST" action="{{ route('batting.update',$batting->id) }}" enctype="multipart/form-data">
                @csrf
                @php
                    $metaPanelOpen = $errors->hasAny(['userId', 'userName', 'inning']);
                    $batterLabel = $batting->userId ? $batting->user->name : $batting->userName;
                    $metaPanelOpen = $metaPanelOpen || blank($batterLabel);
                @endphp

                <details class="mt-8 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm" @if($metaPanelOpen) open @endif>
                    <summary class="cursor-pointer list-none">
                        <div>
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">試合・打者・イニング</p>
                                <p class="mt-1 text-sm text-slate-700">
                                    試合 {{ $batting->game->gameName }} / 打者 {{ $batterLabel }} / イニング {{ $batting->inning }}
                                </p>
                            </div>
                        </div>
                    </summary>

                    <div class="mt-4 space-y-4">
                        <div class="w-full flex flex-col">
                            <input type="hidden" name="gameId" value="{{$batting->gameId}}">
                            <label for="gameName" class="font-semibold leading-none">試合</label>
                            <input type="text" name="gamename" class="mt-2 w-auto rounded-md border border-gray-300 py-2 px-3 text-sm text-gray-700" id="gameName" value="{{$batting->game->gameName}}" @readonly(true)>
                        </div>

                        <div class="w-full flex flex-col">
                            <label for="userDisplay" class="font-semibold leading-none">打者</label>
                            <input type="text" name="" class="mt-2 w-auto rounded-md border border-gray-300 py-2 px-3 text-sm text-gray-700" id="userDisplay" value="{{$batterLabel}}" @readonly(true)>
                            <input type="hidden" name="{{$batting->userId ? 'userId' : 'userName'}}" value="{{$batting->userId ? $batting->userId : $batting->userName}}" @readonly(true)>
                        </div>

                        <div class="w-full flex flex-col">
                            <label for="inning" class="font-semibold leading-none">イニング</label>
                            <input type="number" name="inning" class="mt-2 w-auto rounded-md border border-gray-300 py-2 px-3 text-sm text-gray-700" id="inning" value="{{$batting->inning}}" readonly>
                        </div>
                    </div>
                </details>

                @include('batting.partials.result-selector', [
                    'results' => $results,
                    'selectedResultId1' => old('resultId1', $batting->resultId1),
                    'selectedResultId2' => old('resultId2', $batting->resultId2),
                    'selectedResultId3' => old('resultId3', $batting->resultId3),
                ])
                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded-lg mt-4">更新</button>
            </form>
            <form method="POST" action="{{ route('batting.destroy', $batting->id) }}">
                @csrf
                @method('DELETE')
                <button type="submit" class="bg-red-500 hover:bg-red-600 text-white font-semibold py-2 px-4 rounded-lg mt-4" onclick="confirm('削除してもよろしいですか？');">
                    削除
                </button>
            </form>
        </div>
    </div>
</x-app-layout>
