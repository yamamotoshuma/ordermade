<style>
    .responsive-table {
      white-space: nowrap;
    }
</style>
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            カテゴリ一覧
        </h2>
        <a href="/dcategory/create">カテゴリ登録へ</a>
        <x-message :message="session('message')" />
    </x-slot>
    <div class="overflow-x-auto">
        <table class="responsive-table min-w-full bg-white border border-gray-300">
            <thead>
                <tr>
                    <th class="px-6 py-3 bg-orange-600 border-b border-gray-300 text-center text-sm font-semibold text-white uppercase">中分類コード</th>
                    <th class="px-6 py-3 bg-orange-600 border-b border-gray-300 text-center text-sm font-semibold text-white uppercase">中分類名</th>
                    <th class="px-6 py-3 bg-orange-600 border-b border-gray-300 text-center text-sm font-semibold text-white uppercase">小分類コード</th>
                    <th class="px-6 py-3 bg-orange-600 border-b border-gray-300 text-center text-sm font-semibold text-white uppercase">小分類名</th>
                    <th class="px-6 py-3 bg-orange-600 border-b border-gray-300 text-center text-sm font-semibold text-white uppercase">削除</th>
                </tr>
            </thead>
            <tbody>
            @foreach ($dc as $dc)
                <tr>
                    <td class="px-6 py-4 text-center whitespace-nowrap">{{ $dc->Mcode }}</td>
                    <td class="px-6 py-4 text-center whitespace-nowrap">{{ $dc->Mname }}</td>
                    <td class="px-6 py-4 text-center whitespace-nowrap">{{ $dc->Scode }}</td>
                    <td class="px-6 py-4 text-center whitespace-nowrap">{{ $dc->Sname }}</></td>
                    <td class="px-6 py-4 flex justify-center whitespace-nowrap">
                        <form method="post" action="{{route('dcategory.destroy', $dc->id)}}">
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