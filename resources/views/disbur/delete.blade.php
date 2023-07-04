<style>
    .responsive-table {
      white-space: nowrap;
    }
</style>
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            出金削除
        </h2>
        <a href="/disbur/create">出金登録へ</a>
        <x-message :message="session('message')" />
    </x-slot>
    <div class="overflow-x-auto">
        <table class="responsive-table min-w-full bg-white border border-gray-300">
            <thead>
                <tr>
                    <th class="px-6 py-3 bg-orange-600 border-b border-gray-300 text-center text-sm font-semibold text-white uppercase">中分類コード</th>
                    <th class="px-6 py-3 bg-orange-600 border-b border-gray-300 text-center text-sm font-semibold text-white uppercase">小分類コード</th>
                    <th class="px-6 py-3 bg-orange-600 border-b border-gray-300 text-center text-sm font-semibold text-white uppercase">出金年</th>
                    <th class="px-6 py-3 bg-orange-600 border-b border-gray-300 text-center text-sm font-semibold text-white uppercase">出金月</th>
                    <th class="px-6 py-3 bg-orange-600 border-b border-gray-300 text-center text-sm font-semibold text-white uppercase">出金額</th>
                    <th class="px-6 py-3 bg-orange-600 border-b border-gray-300 text-center text-sm font-semibold text-white uppercase">削除</th>
                </tr>
            </thead>
            <tbody>
            @foreach ($disburDelete as $dd)
                <tr>
                    <td class="px-6 py-4 text-center whitespace-nowrap">{{ $dd->Mcode }}</td>
                    <td class="px-6 py-4 text-center whitespace-nowrap">{{ $dd->Scode }}</td>
                    <td class="px-6 py-4 text-center whitespace-nowrap">{{ $dd->disbur_year }}</td>
                    <td class="px-6 py-4 text-center whitespace-nowrap">{{ $dd->disbur_month }}</></td>
                    <td class="px-6 py-4 text-center whitespace-nowrap">{{ $dd->disbur_amount }}</></td>
                    <td class="px-6 py-4 flex justify-center whitespace-nowrap">
                        <form method="post" action="{{route('disbur.destroy', $dd->id)}}">
                            @csrf
                            @method('delete')
                            <x-danger-button class="bg-red-700 float-right ml-4" onClick="return confirm('本当に削除しますか？');">削除</x-danger-button>
                        </form>
                    </td>
                </tr>
            @endforeach
            </tbody>
            <tfoot>
                
            </tfoot>
        </table>
    </div>

</x-app-layout>