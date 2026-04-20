<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            ユーザー編集
        </h2>
        <x-input-error class="mb-4" :messages="$errors->all()" />
        <x-input-error class="mb-4" :messages="session('error')" />
        <x-message :message="session('message')" />
    </x-slot>

    <div class="max-w-4xl mx-auto px-2 sm:px-6 lg:px-8">
        <div class="sm:p-8">
            <div class="mb-4 flex flex-wrap gap-2 text-sm text-gray-600">
                <a href="{{ route('register.allshow') }}" class="bg-slate-700 hover:bg-slate-800 text-white font-semibold py-2 px-4 rounded-lg">
                    ユーザー一覧に戻る
                </a>
                <a href="{{ route('register') }}" class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded-lg">
                    新規ユーザー登録
                </a>
            </div>

            <form method="POST" action="{{ route('register.update', ['id' => $editUser->id]) }}" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                @csrf

                <div class="grid gap-5 md:grid-cols-2">
                    <div>
                        <x-input-label for="name" value="名前" />
                        <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name', $editUser->name)" required autofocus autocomplete="name" />
                    </div>

                    <div>
                        <x-input-label for="email" value="メールアドレス" />
                        <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email', $editUser->email)" required autocomplete="username" />
                    </div>

                    <div>
                        <x-input-label for="role" value="権限" />
                        <select id="role" name="role" class="block mt-1 w-full rounded-md border-gray-300" required>
                            @foreach($roleOptions as $roleValue => $roleLabel)
                                <option value="{{ $roleValue }}" @selected((string) old('role', (string) $editUser->role) === (string) $roleValue)>{{ $roleLabel }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <x-input-label for="active_flg" value="状態" />
                        <select id="active_flg" name="active_flg" class="block mt-1 w-full rounded-md border-gray-300" required>
                            <option value="1" @selected((string) old('active_flg', (string) $editUser->active_flg) === '1')>有効</option>
                            <option value="0" @selected((string) old('active_flg', (string) $editUser->active_flg) === '0')>無効</option>
                        </select>
                    </div>
                </div>

                <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                    パスワード変更はこの画面では行いません。必要なら別途パスワード再設定フローを使ってください。
                </div>

                <div class="mt-6 flex justify-end">
                    <x-primary-button>
                        更新する
                    </x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
