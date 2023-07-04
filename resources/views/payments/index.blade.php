<style>
    .responsive-table {
      white-space: nowrap;
    }
</style>
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            入金一覧
        </h2>

        <x-message :message="session('message')" />
    </x-slot>
    <form action="{{ route('payment.index') }}" method="get" class="mt-4">
        <input type="number" name="year" class="w-auto py-2 placeholder-gray-300 border border-gray-300 rounded-md" id="year" pattern="[0-9]{4}" required>
        <label for="year" class="font-semibold leading-none">年</label>
        <button type="submit" class="py-2 px-4 bg-blue-500 text-white font-semibold rounded-md ml-2">検索</button>
    </form>
    <x-input-error class="mb-4" :messages="$errors->all()" />
    <h2>現在の部費残高￥{{$total_balance->balance}}</h2>
    <div class="overflow-x-auto">
        <table class="responsive-table min-w-full bg-white border border-gray-300 table-fixed">
            <thead>
                <tr>
                    <th class="px-6 py-3 bg-orange-600 border-b border-gray-300 text-left text-sm font-semibold text-white uppercase">ユーザー名</th>
                    @foreach (range(1, 12) as $month)
                    <th class="px-6 py-3 bg-orange-600 border-b border-gray-300 text-center text-sm font-semibold text-white uppercase">{{ $month }}月</th>
                    @endforeach
                    <th class="px-6 py-3 bg-orange-600 border-b border-gray-300 text-center text-sm font-semibold text-white uppercase">合計</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($payments as $payment)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap">{{ $payment->name ?? '' }}</td>
                    @foreach (range(1, 12) as $month)
                    <td class="px-6 py-4 text-center whitespace-nowrap">{{ $payment->{'month'.$month} ?? 0 }}</td>
                    @endforeach
                    <td class="px-6 py-4 text-center whitespace-nowrap">{{ $payment->total_amount }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <th class="px-6 py-3 bg-600 border-t border-gray-300 text-left text-sm font-semibold uppercase">合計</th>
                    @foreach (range(1, 12) as $month)
                    <th class="px-6 py-3 bg-600 border-t border-gray-300 text-center text-sm font-semibold uppercase">{{ $month }}月</th>
                    @endforeach
                    <th class="px-6 py-3 bg-600 border-t border-gray-300 text-center text-sm font-semibold uppercase">{{ $totalPayments }}</th>
                </tr>
            </tfoot>
        </table>
    </div>

</x-app-layout>