<style>
    .responsive-table {
        white-space: nowrap;
    }

    td {
        border: solid 1px gray;
    }

    table {
        border-collapse: separate;
        border-spacing: 0;
    }

    thead tr th {
    background-color: rgb(234 88 12 / var(--tw-bg-opacity)) !important;
    }

    thead tr:first-child th:first-child {
        position: sticky;
        left: 0;
        z-index: 2;
        background-color: rgb(234 88 12 / var(--tw-bg-opacity));
    }

    tfoot tr:first-child th:first-child {
        position: sticky;
        left: 0;
        z-index: 99;
        background-color: #fff;
    }

    tr:first-child,
    td:first-child {
        position: sticky;
        left: 0;
        background-color: #fff;
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
    <button class="btn btn-primary bg-blue-500" onclick="printTable()">出力</button>
    @feature('attendances-management')
    <!-- Button trigger modal -->
    <button type="button" class="btn btn-primary bg-blue-500" data-bs-toggle="modal" data-bs-target="#exampleModal">
        一括入金登録
    </button>

    <!-- Modal -->
    <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
            <h5 class="modal-title" id="exampleModalLabel">入金一括登録</h5>
            <button type="button" class="btn btn-secondary text-white bg-gray-500" data-bs-dismiss="modal" aria-label="Close">閉じる</button>
            </div>
            <div class="modal-body">
                <form id="bulkPaymentForm" method="post" action="{{ route('payment.bulkStore') }}">
                    @csrf
                    <div class="md:flex items-center mt-8">
                        <div class="w-full flex flex-col">
                            <label for="payment_Year" class="font-semibold leading-none">入金年</label>
                            <input type="number" name="payment_Year" class="w-auto py-2 placeholder-gray-300 border border-gray-300 rounded-md" id="payment_Year" />
                            <label for="payment_month" class="font-semibold leading-none mt-4">入金月を選択してください</label>
                            <select name="payment_month" class="w-auto py-2 placeholder-gray-300 border border-gray-300 rounded-md" id="payment_month">
                                @for ($month = 1; $month <= 12; $month++)
                                    <option value="{{ $month }}">{{ $month }}月</option>
                                @endfor
                            </select>
                            <table class="table-auto w-full table-bordered table-striped mt-6">
                                <thead>
                                    <tr>
                                        <th class="px-6 py-3 bg-orange-600 border-b border-gray-300 text-center text-sm font-semibold text-white uppercase">ユーザー名</th>
                                        <th class="px-6 py-3 bg-orange-600 border-b border-gray-300 text-center text-sm font-semibold text-white uppercase">入金額</th>
                                        <th class="px-6 py-3 bg-orange-600 border-b border-gray-300 text-center text-sm font-semibold text-white uppercase">チェック</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($users as $user)
                                    <tr>
                                        <td class="px-6 py-4 text-center ">{{ $user->name ?? '' }}</td>
                                        <td class="px-6 py-4 text-center "><input type="number" name="payment_amounts[{{ $user->id }}]" class="py-2 border border-gray-300 rounded-md" style="width:6rem;" /></td>
                                        <td class="px-6 py-4 text-center "><input type="checkbox" name="users[]" value="{{ $user->id }}" /></td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="submit" form="bulkPaymentForm" class="btn btn-primary bg-blue-500 mt-4">一括登録</button>
            </div>
        </div>
        </div>
        <script>
            document.addEventListener("DOMContentLoaded", function () {
                // 現在の年と月を取得
                const currentDate = new Date();
                const currentYear = currentDate.getFullYear();
                const currentMonth = currentDate.getMonth() + 1; // 月は0から始まるため+1する

                // 入金年のフィールドを取得して今年を設定
                const paymentYearField = document.getElementById('payment_Year');
                if (paymentYearField) {
                    paymentYearField.value = currentYear;
                }

                // 入金月のフィールドを取得して今月を設定
                const paymentMonthField = document.getElementById('payment_month');
                if (paymentMonthField) {
                    paymentMonthField.value = currentMonth;
                }
            });
        </script>
    </div>
    @endfeature
    <div id="printarea" class="overflow-x-auto">
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th class="sticky-top px-6 py-3 bg-orange-600 border-b border-gray-300 text-left text-sm font-semibold text-white uppercase">ユーザー名</th>
                    @foreach (range(1, 12) as $month)
                    <th class="px-6 py-3 bg-orange-600 border-b border-gray-300 text-center text-sm font-semibold text-white uppercase">{{ $month }}月</th>
                    @endforeach
                    <th class="px-6 py-3 bg-orange-600 border-b border-gray-300 text-center text-sm font-semibold text-white uppercase">合計</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($payments as $payment)
                <tr>
                    <td class="sticky-bottom px-6 py-4 whitespace-nowrap">{{ $payment->name ?? '' }}</td>
                    @foreach (range(1, 12) as $month)
                    <td class="px-6 py-4 text-center whitespace-nowrap">
                        @if($payment->{'month'.$month})
                            @feature('attendances-management')<a href="{{ route('payment.edit', [$payment->{'id'.$month}]) }}">@endfeature
                                {{ $payment->{'month'.$month} }}
                            @feature('attendances-management')</a>@endfeature
                        @else
                            0
                        @endif
                    </td>
                    @endforeach
                    <td class="px-6 py-4 text-center whitespace-nowrap">{{ $payment->total_amount }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <th class="sticky-bottom px-6 py-3 bg-600 border-t border-gray-300 text-left text-sm font-semibold uppercase">合計</th>
                    @php
                    $columnTotals = array_fill(0, 12, 0);
                    @endphp
                    @foreach ($payments as $payment)
                    @foreach (range(1, 12) as $month)
                    @php
                    $amount = $payment->{'month'.$month} ?? 0;
                    $columnTotals[$month-1] += $amount;
                    @endphp
                    @endforeach
                    @endforeach
                    @foreach (range(1, 12) as $month)
                    <th class="px-6 py-3 bg-600 border-t border-gray-300 text-center text-sm font-semibold uppercase">{{ $columnTotals[$month-1] }}</th>
                    @endforeach
                    <th class="sticky-bottom px-6 py-3 bg-600 border-t border-gray-300 text-center text-sm font-semibold uppercase">{{ $totalPayments }}</th>
                </tr>
            </tfoot>
        </table>
    </div>
    <script>
        function printTable() {
            var printContents = document.getElementById("printarea").innerHTML;
            var originalContents = document.body.innerHTML;

            document.body.innerHTML = printContents;

            window.print();

            document.body.innerHTML = originalContents;
        }
    </script>
</x-app-layout>
