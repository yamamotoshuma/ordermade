<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            打撃登録
        </h2>
        <x-input-error class="mb-4" :messages="$errors->all()" />
        <x-input-error class="mb-4" :messages="session('error')" />
        <x-message :message="session('message')" />
    </x-slot>

    <div class="max-w-7xl mx-auto px-2 sm:px-6 lg:px-8">
        <div class="sm:p-8">
            <p class="mt-4 mb-4 text-sm text-gray-600">
                <a href="{{ route('game.show', $game->gameId) }}" class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded-lg">
                    試合詳細に戻る
                </a>
            </p>
            <form method="POST" action="{{ route('batting.store',$game) }}" enctype="multipart/form-data" onsubmit="showLoading()">
                @csrf
                <input type="hidden" name="fromEdit" value="{{ request('fromEdit', false) }}">
                @php
                    $initialUserId = old('userId', request('userId'));
                    $initialUserName = old('userName');
                    $metaPanelOpen = $errors->hasAny(['userId', 'userName', 'inning'])
                        || (blank($initialUserId) && blank($initialUserName));
                @endphp

                <details id="batting-meta-panel" class="mt-8 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm" @if($metaPanelOpen) open @endif>
                    <summary class="cursor-pointer list-none">
                        <div>
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">試合・打者・イニング</p>
                                <p data-role="batting-meta-summary" class="mt-1 text-sm text-slate-700">
                                    試合 {{ $game->gameName }} / 打者 未選択 / イニング {{ request('inning', $maxInning) }}
                                </p>
                            </div>
                        </div>
                    </summary>

                    <div class="mt-4 space-y-4">
                        <div class="w-full flex flex-col">
                            <input type="hidden" name="gameId" value="{{$game->gameId}}">
                            <label for="gameName" class="font-semibold leading-none">試合</label>
                            <label type="text" name="gameName" class="mt-2 w-auto rounded-md border border-gray-300 py-2 px-3 text-sm text-gray-700" id="gameName">{{$game->gameName}}</label>
                        </div>

                        <div class="w-full flex flex-col">
                            <label for="userId" class="font-semibold leading-none">打者</label>
                            <select name="userId" class="mt-2 w-auto rounded-md border border-gray-300 py-2" id="userId">
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

                        <div id="batting-manual-user-wrapper" class="w-full flex flex-col">
                            <label for="userName" class="font-semibold leading-none">打者名<span class="text-red-500"> ※登録外の打者のみ</span></label>
                            <input type="text" name="userName" class="mt-2 w-auto rounded-md border border-gray-300 py-2" id="userName" value="{{ old('userName') }}">
                        </div>

                        <div class="w-full flex flex-col">
                            <label for="inning" class="font-semibold leading-none">イニング</label>
                            <input type="number" name="inning" class="mt-2 w-auto rounded-md border border-gray-300 py-2" id="inning" value="{{ request('inning', $maxInning) }}" required>
                        </div>
                    </div>
                </details>

                @include('batting.partials.result-selector', [
                    'results' => $results,
                    'selectedResultId1' => old('resultId1'),
                    'selectedResultId2' => old('resultId2'),
                    'selectedResultId3' => old('resultId3'),
                ])
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
        const userIdSelect = document.getElementById('userId');
        const userNameInput = document.getElementById('userName');
        const result1Select = document.getElementById('resultId1');
        const result2Select = document.getElementById('resultId2');
        const result3Select = document.getElementById('resultId3');
        const inningInput = document.getElementById('inning');
        const gameNameLabel = document.getElementById('gameName');
        const metaSummary = document.querySelector('[data-role="batting-meta-summary"]');
        const manualUserWrapper = document.getElementById('batting-manual-user-wrapper');
        const metaPanel = document.getElementById('batting-meta-panel');

        const urlParams = new URLSearchParams(window.location.search);
        const userIdFromQuery = urlParams.get('userId');
        const inningFromQuery = urlParams.get('inning');

        const updateMetaSummary = function() {
            if (!metaSummary) {
                return;
            }

            const gameName = gameNameLabel ? gameNameLabel.textContent.trim() : '未設定';
            const selectedOption = userIdSelect && userIdSelect.selectedIndex >= 0
                ? userIdSelect.options[userIdSelect.selectedIndex]
                : null;
            const batterName = selectedOption && selectedOption.value
                ? selectedOption.text.trim()
                : (userNameInput && userNameInput.value.trim() ? '未登録 ' + userNameInput.value.trim() : '未選択');
            const inning = inningInput && inningInput.value ? inningInput.value : '未設定';

            metaSummary.textContent = '試合 ' + gameName + ' / 打者 ' + batterName + ' / イニング ' + inning;
        };

        const toggleManualUserInput = function() {
            if (!manualUserWrapper || !userIdSelect || !userNameInput) {
                return;
            }

            const hasSelectedUser = userIdSelect.value !== '';
            manualUserWrapper.classList.toggle('hidden', hasSelectedUser);

            if (hasSelectedUser && userNameInput.value !== '') {
                userNameInput.value = '';
                localStorage.setItem('batting.userName', '');
            }
        };

        const syncMetaPanelState = function() {
            if (!metaPanel || !userIdSelect || !userNameInput) {
                return;
            }

            if (userIdSelect.value === '' && userNameInput.value.trim() === '') {
                metaPanel.open = true;
            }
        };

        if (userIdSelect && !userIdSelect.value) {
            userIdSelect.value = userIdFromQuery || localStorage.getItem('batting.userId') || '';
        }

        if (userNameInput && !userNameInput.value) {
            userNameInput.value = localStorage.getItem('batting.userName') || '';
        }

        if (result1Select && !result1Select.value) {
            result1Select.value = localStorage.getItem('batting.result1') || '';
        }

        if (result2Select && !result2Select.value) {
            result2Select.value = localStorage.getItem('batting.result2') || '';
        }

        if (result3Select && !result3Select.value) {
            result3Select.value = localStorage.getItem('batting.result3') || '';
        }

        if (inningInput && inningFromQuery) {
            inningInput.value = inningFromQuery;
        }

        if (userIdSelect) {
            userIdSelect.addEventListener('change', function() {
                localStorage.setItem('batting.userId', userIdSelect.value);
                clearMessages();
                toggleManualUserInput();
                updateMetaSummary();
                syncMetaPanelState();
            });
        }

        if (userNameInput) {
            userNameInput.addEventListener('input', function() {
                if (userNameInput.value.trim() !== '' && userIdSelect) {
                    userIdSelect.value = '';
                    localStorage.setItem('batting.userId', '');
                }
                localStorage.setItem('batting.userName', userNameInput.value);
                toggleManualUserInput();
                updateMetaSummary();
                syncMetaPanelState();
            });
        }

        if (result1Select) {
            result1Select.addEventListener('change', function() {
                localStorage.setItem('batting.result1', result1Select.value);
            });
        }

        if (result2Select) {
            result2Select.addEventListener('change', function() {
                localStorage.setItem('batting.result2', result2Select.value);
            });
        }

        if (result3Select) {
            result3Select.addEventListener('change', function() {
                localStorage.setItem('batting.result3', result3Select.value);
            });
        }

        if (inningInput) {
            inningInput.addEventListener('input', updateMetaSummary);
            inningInput.addEventListener('change', updateMetaSummary);
        }

        toggleManualUserInput();
        updateMetaSummary();
        syncMetaPanelState();

        [result1Select, result2Select, result3Select].forEach(function(select) {
            if (select) {
                select.dispatchEvent(new Event('change', { bubbles: true }));
            }
        });
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
