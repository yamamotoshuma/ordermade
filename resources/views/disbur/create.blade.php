<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            出金登録
        </h2>
        <x-input-error class="mb-4" :messages="$errors->all()" />
        <x-message :message="session('message')" />
    </x-slot>

    {{-- 最初に作成した部分 --}}
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mx-4 sm:p-8">
            <form method="post" action="{{ route('disbur.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="md:flex items-center mt-8">
                    <div class="w-full flex flex-col">
                        <label for="Mcode" class="font-semibold leading-none mt-4">カテゴリ中分類</label>
                        <select name="Mcode" class="w-auto py-2 placeholder-gray-300 border border-gray-300 rounded-md" id="Mcode">
                            @foreach ($disburCategories as $disbur)
                            <option value="{{ $disbur->Mcode }}">{{ $disbur->Mname }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="md:flex items-center mt-8">
                    <div class="w-full flex flex-col">
                        <label for="Scode" class="font-semibold leading-none mt-4">カテゴリ小分類</label>
                        <select name="Scode" class="w-auto py-2 placeholder-gray-300 border border-gray-300 rounded-md" id="Scode">
                            @foreach ($Scodes as $disbur)
                            <option value="{{ $disbur->Scode }}">{{ $disbur->Sname }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="md:flex items-center mt-8">
                    <div class="w-full flex flex-col">
                        <label for="disbur_year" class="font-semibold leading-none mt-4">出金年</label>
                        <input type="number" name="disbur_year" class="w-auto py-2 placeholder-gray-300 border border-gray-300 rounded-md" id="disbur_year" value="{{ old('disbur_year') }}">
                    </div>
                </div>
                <div class="md:flex items-center mt-8">
                    <div class="w-full flex flex-col">
                        <label for="disbur_month" class="font-semibold leading-none mt-4">出金月</label>
                        <select name="disbur_month" class="w-auto py-2 placeholder-gray-300 border border-gray-300 rounded-md" id="disbur_month">
                            @for ($month = 1; $month <= 12; $month++) <option value="{{ $month }}" {{ old('disbur_month') == $month ? 'selected' : '' }}>
                                {{ $month }}
                                </option>
                                @endfor
                        </select>
                    </div>
                </div>
                <div class="md:flex items-center mt-8">
                    <div class="w-full flex flex-col">
                        <label for="disbur_amount" class="font-semibold leading-none mt-4">出金額</label>
                        <input type="number" name="disbur_amount" class="w-auto py-2 placeholder-gray-300 border border-gray-300 rounded-md" id="disbur_amount" value="{{ old('payment_amount') }}">
                    </div>
                </div>
                <x-primary-button name="create" class="mt-4">
                    登録
                </x-primary-button>
                <x-danger-button name="delete" class="mt-4">
                    削除用検索
                </x-danger-button>
            </form>
        </div>
    </div>
    {{-- 最初に作成した部分ここまで --}}
    <!-- JavaScript -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function() {
        // 大分類が選択されたときに小分類を取得してセレクトボックスを更新
        $('#Mcode').change(function() {
            var category = $(this).val();
            if (category) {
                $.ajax({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    url: "{{route('disbur.getScode')}}",
                    type: 'GET',
                    contentType: 'charset=utf-8',
                    data: {
                        category: category
                    },
                    success: function(response) {
                        // 小分類のセレクトボックスをクリア
                        $('#Scode option').remove();

                        // レスポンスの小分類をセレクトボックスに追加
                        $.each(response,function(key,value) {
                            var opt=Object.assign(document.createElement('option'),{value:value.Scode,textContent:value.Sname});
                            document.querySelector('#Scode').appendChild(opt);
                        });
                    }
                });
            } else {
                // 大分類が選択されていない場合、小分類のセレクトボックスをクリア
                $('#smallCategorySelect').empty().append('<option value="">中分類を選択してください</option>');
            }
        });
    });
</script>
</x-app-layout>