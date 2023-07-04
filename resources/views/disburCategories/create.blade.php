<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            カテゴリマスタ登録
        </h2>
        <a href="/dcategory/">一覧表示へ</a>
        <x-input-error class="mb-4" :messages="$errors->all()" />
        <x-message :message="session('message')" />
    </x-slot>

    {{-- 最初に作成した部分 --}}
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mx-4 sm:p-8">
            <form method="post" action="{{route('dcategory.store')}}" enctype="multipart/form-data">
                @csrf
                <div class="md:flex items-center mt-8">
                    <div class="w-full flex flex-col">
                        <label for="body" class="font-semibold leading-none mt-4">中分類コード</label>
                        <input type="number" name="Mcode" class="w-auto py-2 placeholder-gray-300 border border-gray-300 rounded-md" id="Mcode" value="{{ old('Mcode') }}">
                        <label for="body" class="font-semibold leading-none mt-4">中分類名</label>
                        <input type="text" name="Mname" class="w-auto py-2 placeholder-gray-300 border border-gray-300 rounded-md" id="Mname" value="{{ old('Mname') }}">
                    </div>
                </div>

                <div class="md:flex items-center mt-8">
                    <div class="w-full flex flex-col">
                        <label for="body" class="font-semibold leading-none mt-4">小分類コード</label>
                        <input type="number" name="Scode" class="w-auto py-2 placeholder-gray-300 border border-gray-300 rounded-md" id="Scode" value="{{ old('Scode') }}">
                        <label for="body" class="font-semibold leading-none mt-4">小分類名</label>
                        <input type="text" name="Sname" class="w-auto py-2 placeholder-gray-300 border border-gray-300 rounded-md" id="Sname" value="{{ old('Sname') }}">
                    </div>
                </div>

                <x-primary-button name="create" class="mt-4">
                    登録
                </x-primary-button>
            </form>
        </div>
    </div>
    {{-- 最初に作成した部分ここまで --}}

</x-app-layout>