<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            打撃登録
        </h2>
        <x-input-error class="mb-4" :messages="$errors->all()" />
        <x-input-error class="mb-4" :messages="session('error')" />
        <x-message :message="session('message')" />
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mx-4 sm:p-8">
            <p class="mt-4 mb-4 text-sm text-gray-600">
                <a href="{{ route('game.show', $game->gameId) }}" class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded-lg">
                    試合詳細に戻る
                </a>
            </p>
            <form method="POST" action="{{ route('batting.store',$game) }}" enctype="multipart/form-data" onsubmit="showLoading()">
                @csrf
                <input type="hidden" name="fromEdit" value="{{ request('fromEdit', false) }}">

                <div class="md:flex items-center mt-8">
                    <div class="w-full flex flex-col">
                        <input type="hidden" name="gameId" value="{{$game->gameId}}">
                        <label for="gameName" class="font-semibold leading-none mt-4">試合</label>
                        <label type="text" name="gameName" class="w-auto py-2 placeholder-gray-300 border border-gray-300 rounded-md" id="gameName">{{$game->gameName}}</label>
                    </div>
                </div>
                <div class="md:flex items-center mt-8">
                    <div class="w-full flex flex-col">
                        <label for="gameFirstFlg" class="font-semibold leading-none mt-4">ユーザー</label>
                        <select name="userId" class="w-auto py-2 placeholder-gray-300 border border-gray-300 rounded-md" id="userId" >
                            <option value="">選択してください</option>
                            @foreach($orders as $order)
                                @php
                                    $user = $users->where('id', $order->userId)->first();
                                @endphp
                                @if($user)
                                    <option value="{{$user->id}}"{{ $user->id === old('userId') ? ' selected' : '' }}>
                                        {{$order->battingOrder}}番{{$user->name}}
                                    </option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="md:flex items-center mt-8">
                    <div class="w-full flex flex-col">
                        <label for="year" class="font-semibold leading-none mt-4">ユーザー名<span class="text-red-500">※登録されていないユーザーのみ</span></label>
                        <input type="text" name="userName" class="w-auto py-2 placeholder-gray-300 border border-gray-300 rounded-md" id="userName" value="{{ old('userName') }}" >
                    </div>
                </div>

                <div class="md:flex items-center mt-8">
                    <div class="w-full flex flex-col">
                        <label for="inning" class="font-semibold leading-none mt-4">イニング</label>
                        <input type="number" name="inning" class="w-auto py-2 placeholder-gray-300 border border-gray-300 rounded-md" id="inning" value="{{ request('inning', $maxInning) }}" required>
                    </div>
                </div>

                <div class="md:flex items-center mt-8">
                    <div class="w-full flex flex-col">
                        <label for="resultId1" class="font-semibold leading-none mt-4">結果</label>
                        <select name="resultId1" class="w-auto py-2 placeholder-gray-300 border border-gray-300 rounded-md" id="resultId1" required>
                            <option value="">選択してください</option>
                            @foreach($results as $result)
                            @if($result->type === 1 || $result->type === 2 || $result->type === 3)
                            <option value="{{$result->id}}">{{$result->name}}</option>
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
                            <option value="{{$result->id}}">{{$result->name}}</option>
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
                            <option value="{{$result->id}}">{{$result->name}}</option>
                            @endif
                            @endforeach
                        </select>
                    </div>
                </div>
                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded-lg mt-4 w-100" style="margin-bottom: 50px;">登録</button>
            </form>
        </div>
    </div>
    <div id="loading" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; z-index:1000; background-color: rgba(128, 128, 128, 0.8);">
        <div class="loader" style="top: 50%; left: 50%; position: absolute; transform: translate(-50%, -50%);"></div>
    </div>
</x-app-layout>
<script>
    function showLoading() {
        document.getElementById('loading').style.display = 'block';
        clearMessages();
    }

    function clearMessages() {
        const errors = document.querySelectorAll('.x-input-error');
        errors.forEach(error => error.innerHTML = '');
        const message = document.querySelector('.x-message');
        if (message) {
            message.innerHTML = '';
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        // フォームの各要素を取得
        const userIdSelect = document.getElementById('userId');
        const userNameInput = document.getElementById('userName');
        const result1Select = document.getElementById('resultId1');
        const result2Select = document.getElementById('resultId2');
        const result3Select = document.getElementById('resultId3');
        const inningSelect = document.getElementById('inning'); // イニングのセレクトボックスを取得

        // クエリパラメータから値を取得
        const urlParams = new URLSearchParams(window.location.search);
        const userIdFromQuery = urlParams.get('userId');
        const inningFromQuery = urlParams.get('inning');

        // フォームの各要素の値をローカルストレージから取得してセット
        userIdSelect.value = userIdFromQuery || localStorage.getItem('userId') || '';
        userNameInput.value = localStorage.getItem('userName') || '';
        result1Select.value = localStorage.getItem('result1') || '';
        result2Select.value = localStorage.getItem('result2') || '';
        result3Select.value = localStorage.getItem('result3') || '';
        inningSelect.value = inningFromQuery || {{ $maxInning }}; // イニングの値をセット

        // フォームの各要素の値が変更されたら、ローカルストレージに保存
        userIdSelect.addEventListener('change', function() {
            localStorage.setItem('userId', userIdSelect.value);
            clearMessages();
        });
    });

    userNameInput.addEventListener('input', function() {
        localStorage.setItem('userName', userNameInput.value);
    });

    result1Select.addEventListener('change', function() {
        localStorage.setItem('result1', result1Select.value);
    });

    result2Select.addEventListener('change', function() {
        localStorage.setItem('result2', result2Select.value);
    });

    result3Select.addEventListener('change', function() {
        localStorage.setItem('result3', result3Select.value);
    });
</script>
<style>
    .loader {
        border: 16px solid #f3f3f3; /* Light grey */
        border-top: 16px solid #3498db; /* Blue */
        border-radius: 50%;
        width: 60px;
        height: 60px;
        animation: spin 2s linear infinite;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
</style>
