<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            打順登録
        </h2>
        <x-input-error class="mb-4" :messages="$errors->all()" />
        <x-input-error class="mb-4" :messages="session('error')" />
        <x-message :message="session('message')" />
    </x-slot>

    @php
        $blankOrderRow = [
            'battingOrder' => '',
            'positionId' => '',
            'userId' => '',
            'userName' => '',
            'ranking' => 1,
        ];
    @endphp

    <div class="mx-auto max-w-5xl px-3 pb-24 pt-4 sm:px-6 lg:px-8" data-order-edit-page>
        <div class="mb-4">
            <a href="{{ route('game.show', $id) }}" class="inline-flex min-h-11 items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-bold text-white hover:bg-blue-700">
                <i class="fa-solid fa-arrow-left mr-2" aria-hidden="true"></i>
                試合詳細に戻る
            </a>
        </div>

        <section class="rounded-lg border border-emerald-200 bg-white p-4 shadow-sm">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="text-lg font-black text-slate-900">スプレッドシート反映</h3>
                    <p class="mt-1 text-sm font-semibold text-slate-600">基本の打順はここから取り込みます。</p>
                </div>
                <form method="POST" action="{{ route('order.importSheet', ['order' => $id]) }}" class="sm:shrink-0">
                    @csrf
                    <button
                        type="submit"
                        class="inline-flex min-h-12 w-full items-center justify-center rounded-lg bg-emerald-600 px-4 py-2 text-base font-black text-white hover:bg-emerald-700 sm:w-auto"
                        onclick="return confirm('スプレッドシートの内容で現在の打順を上書きします。よろしいですか？');"
                    >
                        <i class="fa-solid fa-file-import mr-2" aria-hidden="true"></i>
                        スプレッドシート反映
                    </button>
                </form>
            </div>
        </section>

        <form method="POST" action="{{ route('order.store') }}" enctype="multipart/form-data" class="mt-5">
            @csrf
            <input type="hidden" name="gameId" value="{{ $id }}">

            <section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                <div class="flex flex-col gap-3 border-b border-slate-200 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h3 class="text-lg font-black text-slate-900">手入力で調整</h3>
                        <p class="mt-1 text-sm font-semibold text-slate-600">取り込み後に必要な行だけ修正できます。</p>
                    </div>
                    <button
                        type="button"
                        class="hidden min-h-11 items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50 sm:inline-flex"
                        data-order-add-row
                    >
                        <i class="fa-solid fa-plus mr-2" aria-hidden="true"></i>
                        行を追加
                    </button>
                </div>

                <div class="p-3 sm:p-4">
                    <div class="hidden rounded-t-lg bg-orange-500 text-sm font-black text-white md:grid md:grid-cols-[4.5rem_6rem_minmax(10rem,1fr)_minmax(12rem,1fr)_5.5rem] md:gap-3">
                        <div class="px-3 py-3">打順</div>
                        <div class="px-3 py-3">守備位置</div>
                        <div class="px-3 py-3">選手</div>
                        <div class="px-3 py-3">登録外選手名</div>
                        <div class="px-3 py-3 text-center">順番</div>
                    </div>

                    <div class="space-y-3 md:space-y-0 md:overflow-hidden md:rounded-b-lg md:border md:border-t-0 md:border-slate-200" data-order-rows>
                        @foreach ($orderRows as $row)
                            @include('order.partials.edit-row', [
                                'row' => $row,
                                'positions' => $positions,
                                'users' => $users,
                            ])
                        @endforeach
                    </div>
                </div>
            </section>

            <div class="mt-5 flex flex-col gap-2 pb-20 sm:flex-row sm:items-center sm:justify-end sm:pb-8">
                <button
                    type="button"
                    class="inline-flex min-h-12 w-full items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-base font-bold text-slate-700 hover:bg-slate-50 sm:hidden"
                    data-order-add-row
                >
                    <i class="fa-solid fa-plus mr-2" aria-hidden="true"></i>
                    行を追加
                </button>
                <button type="submit" class="inline-flex min-h-12 w-full items-center justify-center rounded-lg bg-blue-600 px-5 py-2 text-base font-black text-white hover:bg-blue-700 sm:w-auto">
                    <i class="fa-solid fa-floppy-disk mr-2" aria-hidden="true"></i>
                    保存
                </button>
            </div>
        </form>

        <template data-order-row-template>
            @include('order.partials.edit-row', [
                'row' => $blankOrderRow,
                'positions' => $positions,
                'users' => $users,
            ])
        </template>
    </div>
</x-app-layout>
