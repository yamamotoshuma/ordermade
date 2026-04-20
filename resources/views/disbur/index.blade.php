<style>
    .responsive-table {
        white-space: nowrap;
    }

    td {
        border: none; /* ボーダーを表示しないように設定 */
    }

    table {
        border-collapse: collapse; /* テーブルのボーダーを結合 */
        border-spacing: 0;
    }

    .table thead th{
        background-color: #FFA500; /* ヘッダーのオレンジ色の背景 */
        color: #fff; /* ホワイトのテキストカラー */
    }

    .table tfoot th {
        background-color: #FFA500; /* フッターのオレンジ色の背景 */
        color: #fff; /* ホワイトのテキストカラー */
    }
</style>
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl leading-tight header">
            出金一覧
        </h2>

        <x-message :message="session('message')" />
    </x-slot>
    <form action="{{ route('disbur.index') }}" method="get" class="mt-4">
        <input type="number" name="year" class="w-auto py-2 placeholder-gray-300 border border-gray-300 rounded-md" id="year" pattern="[0-9]{4}" required>
        <label for="year" class="font-semibold leading-none">年</label>
        <button type="submit" class="py-2 px-4 bg-blue-500 text-white font-semibold rounded-md ml-2">検索</button>
    </form>
    <x-input-error class="mb-4" :messages="$errors->all()" />
    <h2>現在の部費残高￥{{$total_balance->balance}}</h2>
    <button class="btn btn-primary" onclick="printTable()">出力</button>
    <div id="printarea" class="overflow-x-auto">
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-white text-uppercase">中分類名</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-white text-uppercase">小分類名</th>
                    @foreach (range(1, 12) as $month)
                    <th class="px-6 py-3 text-center text-sm font-semibold text-white text-uppercase">{{ $month }}月</th>
                    @endforeach
                    <th class="px-6 py-3 text-center text-sm font-semibold text-white text-uppercase">合計</th>
                </tr>
            </thead>
            <tbody>
                @php
                $name = '';
                $columnTotals = array_fill(0, 12, 0);
                @endphp
                @foreach ($disbur as $disbur)
                <tr>
                    @if($disbur->Mname != $name)
                    <td class="px-6 py-4 text-center whitespace-nowrap border-top">{{ $disbur->Mname }}</td>
                    @php
                    $name = $disbur->Mname;
                    @endphp
                    @else
                    <td class="px-6 py-4 text-center whitespace-nowrap"></td>
                    @endif
                    <td class="px-6 py-4 text-center whitespace-nowrap">{{ $disbur->Sname }}</td>
                    @foreach (range(1, 12) as $month)
                    <td class="px-6 py-4 text-center whitespace-nowrap">{{ $disbur->{'month'.$month} ?? 0 }}</td>
                    @php
                    $amount = $disbur->{'month'.$month} ?? 0;
                    $columnTotals[$month-1] += $amount;
                    @endphp
                    @endforeach
                    <td class="px-6 py-4 text-center whitespace-nowrap">{{ $disbur->total }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <th class="px-6 py-3 text-center text-sm font-semibold text-white text-uppercase">合計</th>
                    <th class="px-6 py-3 text-center text-sm font-semibold text-white text-uppercase"></th>
                    @foreach (range(1, 12) as $month)
                    <th class="px-6 py-3 text-center text-sm font-semibold text-white text-uppercase">{{ $columnTotals[$month-1] }}</th>
                    @endforeach
                    <th class="px-6 py-3 text-center text-sm font-semibold text-white text-uppercase">{{ $totaldisbur }}</th>
                </tr>
            </tfoot>
        </table>
    </div>
    <script>
        function printTable() {
            var area = document.getElementById("printarea").outerHTML;

            var head = "";
            var cmd = '<script>window.print();</' + 'script>';

            var links = document.getElementsByTagName("link");
            for (var i = 0; i < links.length; i++) {
                head = head + links[i].outerHTML;
            }

            var styles = document.getElementsByTagName("style");
            for (var i = 0; i < styles.length; i++) {
                head = head + styles[i].outerHTML;
            }

            var sub = window.open();
            sub.document.write("<html><head>" + head + "</head><body>" + area + cmd + "</body></html>");
            sub.document.close();
        }
    </script>
</x-app-layout>