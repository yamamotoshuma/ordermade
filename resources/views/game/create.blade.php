<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            ゲームを作成
        </h2>
        <x-input-error class="mb-4" :messages="$errors->all()" />
        <x-message :message="session('error')" />
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mx-4 sm:p-8">
            <form method="POST" action="{{ route('game.store') }}" enctype="multipart/form-data">
                @csrf

                <div class="md:flex items-center mt-8">
                    <div class="w-full flex flex-col">
                        <label for="gameName" class="font-semibold leading-none mt-4">ゲーム名</label>
                        <input type="text" name="gameName" class="w-auto py-2 placeholder-gray-300 border border-gray-300 rounded-md" id="gameName" required>
                    </div>
                </div>

                <div class="md:flex items-center mt-8">
                    <div class="w-full flex flex-col">
                        <label for="year" class="font-semibold leading-none mt-4">年度</label>
                        <input type="number" name="year" class="w-auto py-2 placeholder-gray-300 border border-gray-300 rounded-md" id="year" required>
                    </div>
                </div>

                <div class="md:flex items-center mt-8">
                    <div class="w-full flex flex-col">
                        <label for="gameDates" class="font-semibold leading-none mt-4">試合日時</label>
                        <input type="datetime-local" name="gameDates" class="w-auto py-2 placeholder-gray-300 border border-gray-300 rounded-md" id="gameDates" required>
                    </div>
                </div>

                <div class="md:flex items-center mt-8">
                    <div class="w-full flex flex-col">
                        <label for="enemyName" class="font-semibold leading-none mt-4">対戦相手</label>
                        <input type="text" name="enemyName" class="w-auto py-2 placeholder-gray-300 border border-gray-300 rounded-md" id="enemyName" required>
                    </div>
                </div>

                <div class="md:flex items-center mt-8">
                    <div class="w-full flex flex-col">
                        <label for="gameFirstFlg" class="font-semibold leading-none mt-4">先攻フラグ</label>
                        <select name="gameFirstFlg" class="w-auto py-2 placeholder-gray-300 border border-gray-300 rounded-md" id="gameFirstFlg" required>
                            <option value="0">先攻</option>
                            <option value="1">後攻</option>
                        </select>
                    </div>
                </div>

                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded-lg mt-4">ゲーム作成</button>
            </form>
        </div>
    </div>
</x-app-layout>
