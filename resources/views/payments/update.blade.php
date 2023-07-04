<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            入金削除
        </h2>
        <x-input-error class="mb-4" :messages="$errors->all()" />
        <x-message :message="session('message')" />
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mx-4 sm:p-8">
            <form method="post" action="{{ route('payment.destroy', $payment) }}" onsubmit="return confirm('本当に削除しますか？')">
                @csrf
                @method('DELETE')
                <div class="md:flex items-center mt-8">
                    <div class="w-full flex flex-col">
                        <label class="font-semibold leading-none mt-4">ユーザーID</label>
                        <label class="font-semibold leading-none mt-4">{{ $payment->user_id }}</label>
                    </div>
                </div>
                <div class="md:flex items-center mt-8">
                    <div class="w-full flex flex-col">
                        <label for="payment_year" class="font-semibold leading-none mt-4">入金年</label>
                        <label class="font-semibold leading-none mt-4">{{ $payment->payment_year }}</label>
                    </div>
                </div>
                <div class="md:flex items-center mt-8">
                    <div class="w-full flex flex-col">
                        <label for="payment_month" class="font-semibold leading-none mt-4">入金月</label>
                        <label class="font-semibold leading-none mt-4">{{ $payment->payment_month }}</label>
                    </div>
                </div>
                <div class="md:flex items-center mt-8">
                    <div class="w-full flex flex-col">
                        <label for="payment_amount" class="font-semibold leading-none mt-4">入金額</label>
                        <label class="font-semibold leading-none mt-4">{{ $payment->payment_amount }}</label>
                    </div>
                </div>
                <x-danger-button class="mt-4">
                    削除する
                </x-danger-button>
            </form>
        </div>
    </div>
</x-app-layout>