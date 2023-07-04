<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            入金登録
        </h2>
        <x-input-error class="mb-4" :messages="$errors->all()" />
        <x-message :message="session('message')" />
    </x-slot>

    {{-- 最初に作成した部分 --}}
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mx-4 sm:p-8">
            <form method="post" action="{{ route('payment.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="md:flex items-center mt-8">
                    <div class="w-full flex flex-col">
                        <label for="user_name" class="font-semibold leading-none mt-4">ユーザーID</label>
                        <select name="user_name" class="w-auto py-2 placeholder-gray-300 border border-gray-300 rounded-md" id="user_name">
                            @foreach ($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="md:flex items-center mt-8">
                    <div class="w-full flex flex-col">
                        <label for="payment_year" class="font-semibold leading-none mt-4">入金年</label>
                        <input type="number" name="payment_year" class="w-auto py-2 placeholder-gray-300 border border-gray-300 rounded-md" id="payment_year" value="{{ old('payment_year') }}">
                    </div>
                </div>
                <div class="md:flex items-center mt-8">
                    <div class="w-full flex flex-col">
                        <label for="payment_month" class="font-semibold leading-none mt-4">入金月</label>
                        <select name="payment_month" class="w-auto py-2 placeholder-gray-300 border border-gray-300 rounded-md" id="payment_month">
                            @for ($month = 1; $month <= 12; $month++) <option value="{{ $month }}" {{ old('payment_month') == $month ? 'selected' : '' }}>
                                {{ $month }}
                                </option>
                                @endfor
                        </select>
                    </div>
                </div>
                <div class="md:flex items-center mt-8">
                    <div class="w-full flex flex-col">
                        <label for="payment_amount" class="font-semibold leading-none mt-4">入金額</label>
                        <input type="number" name="payment_amount" class="w-auto py-2 placeholder-gray-300 border border-gray-300 rounded-md" id="payment_amount" value="{{ old('payment_amount') }}">
                    </div>
                </div>
                <x-primary-button name="insert" class="mt-4">
                    登録
                </x-primary-button>
                <x-danger-button name="delete" class="mt-4">
                    削除
                </x-danger-button>
            </form>
        </div>
    </div>
    {{-- 最初に作成した部分ここまで --}}

</x-app-layout>