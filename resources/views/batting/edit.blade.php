<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            打撃編集
        </h2>
        <x-input-error class="mb-4" :messages="$errors->all()" />
            <x-input-error class="mb-4" :messages="session('error')" />
        <x-message :message="session('message')" />
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mx-4 sm:p-8">
            <p class="mt-4 mb-4 text-sm text-gray-600">
                <button type="button" onClick="history.back()" class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded-lg">打撃成績に戻る</button>
            </p>
            <form method="POST" action="{{ route('batting.update',$batting->id) }}" enctype="multipart/form-data">
                @csrf

                <div class="md:flex items-center mt-8">
                    <div class="w-full flex flex-col">
                        <input type="hidden" name="gameId" value="{{$batting->gameId}}">
                        <label for="gameName" class="font-semibold leading-none mt-4">試合</label>
                        <input type="text" name="gamename" class="w-auto py-2 placeholder-gray-300 border border-gray-300 rounded-md" id="gameName" value="{{$batting->game->gameName}}" @readonly(true)>
                    </div>
                </div>
                <div class="md:flex items-center mt-8">
                    <div class="w-full flex flex-col">
                        <label for="gameFirstFlg" class="font-semibold leading-none mt-4">ユーザー</label>
                        <input type="text" name="" class="w-auto py-2 placeholder-gray-300 border border-gray-300 rounded-md" id="userId" value="{{$batting->userId ? $batting->user->name : $batting->userName}}" @readonly(true)>
                        <input type="hidden" name="{{$batting->userId ? 'userId' : 'userName'}}" class="w-auto py-2 placeholder-gray-300 border border-gray-300 rounded-md" id="userId" value="{{$batting->userId ? $batting->userId : $batting->userName}}" @readonly(true)>
                    </div>
                </div>

                <div class="md:flex items-center mt-8">
                    <div class="w-full flex flex-col">
                        <label for="inning" class="font-semibold leading-none mt-4">イニング</label>
                        <input type="number" name="inning" class="w-auto py-2 placeholder-gray-300 border border-gray-300 rounded-md" id="inning" value="{{$batting->inning}}" readonly>
                    </div>
                </div>

                <div class="md:flex items-center mt-8">
                    <div class="w-full flex flex-col">
                        <label for="resultId1" class="font-semibold leading-none mt-4">結果</label>
                        <select name="resultId1" class="w-auto py-2 placeholder-gray-300 border border-gray-300 rounded-md" id="resultId1" required>
                            <option value="">選択してください</option>
                            @foreach($results as $result)
                            @if($result->type === 1 || $result->type === 2 || $result->type === 3)
                            <option value="{{$result->id}}" {{$batting->resultId1 === $result->id ? 'selected' : '' }}>{{$result->name}}</option>
                            @endif
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="md:flex items-center mt-8">
                    <div class="w-full flex flex-col">
                        <label for="resultId2" class="font-semibold leading-none mt-4">打球方向</label>
                        <select name="resultId2" class="w-auto py-2 placeholder-gray-300 border border-gray-300 rounded-md" id="resultId2" required>
                            <option value="">選択してください</option>
                            @foreach($results as $result)
                            @if($result->type === 4)
                            <option value="{{$result->id}}" {{$batting->resultId2 === $result->id ? 'selected' : '' }}>{{$result->name}}</option>
                            @endif
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="md:flex items-center mt-8">
                    <div class="w-full flex flex-col">
                        <label for="resultId3" class="font-semibold leading-none mt-4">打点</label>
                        <select name="resultId3" class="w-auto py-2 placeholder-gray-300 border border-gray-300 rounded-md" id="resultId3" required>
                            <option value="">選択してください</option>
                            @foreach($results as $result)
                            @if($result->type === 5)
                            <option value="{{$result->id}}" {{$batting->resultId3 === $result->id ? 'selected' : '' }}>{{$result->name}}</option>
                            @endif
                            @endforeach
                        </select>
                    </div>
                </div>
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
