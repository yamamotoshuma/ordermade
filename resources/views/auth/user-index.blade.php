<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            ユーザーマスタ
        </h2>
        <x-input-error class="mb-4" :messages="$errors->all()" />
        <x-input-error class="mb-4" :messages="session('error')" />
        <x-message :message="session('message')" />
    </x-slot>

    <div class="max-w-7xl mx-auto px-2 sm:px-6 lg:px-8">
        <div class="sm:p-8">
            <div class="mb-4 flex flex-wrap gap-2 text-sm text-gray-600">
                <a href="{{ route('register') }}" class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded-lg">
                    新規ユーザー登録
                </a>
                <a href="{{ route('dashboard') }}" class="bg-slate-700 hover:bg-slate-800 text-white font-semibold py-2 px-4 rounded-lg">
                    ダッシュボード
                </a>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-4 py-3 text-sm text-slate-600">
                    削除ボタンは、関連データがあるユーザーでは安全のため自動的に無効化へ切り替わります。
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-100 text-slate-700">
                            <tr>
                                <th class="px-4 py-3 text-left">ID</th>
                                <th class="px-4 py-3 text-left">名前</th>
                                <th class="px-4 py-3 text-left">メール</th>
                                <th class="px-4 py-3 text-left">権限</th>
                                <th class="px-4 py-3 text-left">状態</th>
                                <th class="px-4 py-3 text-left">操作</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            @foreach($users as $user)
                                <tr class="{{ (int) $user->active_flg === 0 ? 'bg-slate-50 text-slate-500' : '' }}">
                                    <td class="px-4 py-3">{{ $user->id }}</td>
                                    <td class="px-4 py-3 font-semibold">{{ $user->name }}</td>
                                    <td class="px-4 py-3">{{ $user->email }}</td>
                                    <td class="px-4 py-3">{{ $roleOptions[$user->role] ?? $user->role }}</td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ (int) $user->active_flg === 1 ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-700' }}">
                                            {{ (int) $user->active_flg === 1 ? '有効' : '無効' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex flex-wrap gap-2">
                                            <a href="{{ route('register.edit', ['id' => $user->id]) }}" class="bg-amber-500 hover:bg-amber-600 text-white font-semibold py-2 px-3 rounded-lg">
                                                編集
                                            </a>
                                            <form method="POST" action="{{ route('register.destroy', ['id' => $user->id]) }}" onsubmit="return confirm('このユーザーを削除します。関連データがある場合は無効化します。よろしいですか？');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="bg-rose-600 hover:bg-rose-700 text-white font-semibold py-2 px-3 rounded-lg">
                                                    削除
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
