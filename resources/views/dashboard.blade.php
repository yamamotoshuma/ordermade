<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            O管理アプリ
        </h2>
    </x-slot>
    <div class="container mx-auto mt-8">
        <div id="pwa-install-card" class="mx-3 mb-6 sm:mx-auto sm:max-w-7xl sm:px-6 lg:px-8" hidden>
            <div class="relative overflow-hidden rounded-3xl border border-slate-800 bg-slate-950 p-4 text-white shadow-2xl shadow-slate-300/50 sm:p-5">
                <div class="pointer-events-none absolute -right-10 -top-12 h-32 w-32 rounded-full bg-red-500/25 blur-2xl"></div>
                <div class="pointer-events-none absolute -bottom-16 left-12 h-36 w-36 rounded-full bg-orange-400/20 blur-3xl"></div>
                <div class="relative flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-4">
                        <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-white text-slate-950 shadow-lg shadow-black/20">
                            <i class="fa-solid fa-mobile-screen-button text-2xl"></i>
                        </div>
                        <div>
                            <p class="text-xs font-bold uppercase tracking-[0.22em] text-red-200">Quick Launch</p>
                            <h3 class="mt-1 text-lg font-black leading-tight sm:text-xl">ホーム画面に追加</h3>
                            <p class="mt-1 text-sm leading-relaxed text-slate-300">
                                試合中でもワンタップで起動できます。ブラウザを探す手間を減らします。
                            </p>
                        </div>
                    </div>
                    <div class="flex shrink-0 gap-2 sm:flex-col">
                        <button
                            id="InstallBtn"
                            type="button"
                            class="inline-flex flex-1 items-center justify-center gap-2 rounded-2xl bg-red-500 px-5 py-3 text-sm font-black text-white shadow-lg shadow-red-950/30 transition hover:bg-red-600 active:scale-[0.98] sm:flex-none"
                        >
                            <span>追加する</span>
                            <i class="fa-solid fa-arrow-right"></i>
                        </button>
                        <button
                            id="InstallDismissBtn"
                            type="button"
                            class="inline-flex flex-1 items-center justify-center rounded-2xl border border-white/15 px-4 py-3 text-sm font-bold text-slate-300 transition hover:bg-white/10 sm:flex-none"
                        >
                            あとで
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow-lg mb-8">
            <div class="bg-white rounded-lg shadow-lg mb-8">
                <div class="p-4">
                    <div class="">
                        <div class="relative">
                            <div class="bg-red-500 h-full absolute top-0 left-0 w-2"></div>
                            <div class="bg-gray-700 pl-3 text-white py-3">
                              <h1 class="text-xl font-semibold">試合関連</h1>
                            </div>
                        </div>
                        <p>試合に関連する情報の閲覧、更新を行います。</p>
                        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mt-6">
                            <div class="bg-white overflow-hidden rounded-lg shadow-sm border sm:rounded-lg">
                                <div class="p-6 text-gray-900 text-center">
                                    <a href="{{ route('game.index') }}"><h1>試合</h1></a>
                                </div>
                            </div>
                        </div>
                        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mt-6">
                            <div class="bg-white overflow-hidden rounded-lg shadow-sm border sm:rounded-lg">
                                <div class="p-6 text-gray-900 text-center">
                                    <a href="{{ route('battingStats') }}"><h1>打撃成績</h1></a>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow-lg mb-8">
            <div class="bg-white rounded-lg shadow-lg mb-8">
                <div class="p-4">
                    <div class="">
                        <div class="relative">
                            <div class="bg-red-500 h-full absolute top-0 left-0 w-2"></div>
                            <div class="bg-gray-700 pl-3 text-white py-3">
                              <h1 class="text-xl font-semibold">経理関連</h1>
                            </div>
                        </div>
                        <p>経理に関連する情報の閲覧を行います。</p>
                        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mt-6">
                            <div class="bg-white overflow-hidden shadow-sm border sm:rounded-lg">
                                <div class="p-6 text-gray-900 text-center">
                                    <a href="{{ route('payment.index') }}"><h1>入金一覧</h1></a>
                                </div>
                            </div>
                        </div>
                        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mt-6">
                            <div class="bg-white overflow-hidden shadow-sm border sm:rounded-lg">
                                <div class="p-6 text-gray-900 text-center">
                                    <a href="{{ route('disbur.index') }}"><h1>出金一覧</h1></a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow-lg mb-8">
            <div class="bg-white rounded-lg shadow-lg mb-8">
                <div class="p-4">
                    <div class="">
                        <div class="relative">
                            <div class="bg-red-500 h-full absolute top-0 left-0 w-2"></div>
                            <div class="bg-gray-700 pl-3 text-white py-3">
                              <h1 class="text-xl font-semibold">その他</h1>
                            </div>
                        </div>
                        <p>その他に関連する情報の閲覧、更新を行います。</p>
                        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mt-6">
                            <div class="bg-white overflow-hidden shadow-sm border sm:rounded-lg">
                                <div class="p-6 text-gray-900 text-center">
                                    <a href="{{ route('contact') }}"><h1>目安箱</h1></a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow-lg mb-8">
            <div class="bg-white rounded-lg shadow-lg mb-8">
                <div class="p-4">
                    <div class="">
                        @feature('attendances-management')
                        <div class="relative">
                            <div class="bg-red-500 h-full absolute top-0 left-0 w-2"></div>
                            <div class="bg-gray-700 pl-3 text-white py-3">
                              <h1 class="text-xl font-semibold">管理者用機能</h1>
                            </div>
                        </div>
                        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mt-6">
                            <div class="bg-white overflow-hidden shadow-sm border sm:rounded-lg">
                                <div class="p-6 text-gray-900 text-center">
                                    <a href="{{ route('payment.create') }}"><h1>入金登録</h1></a>
                                </div>
                            </div>
                        </div>
                        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mt-6">
                            <div class="bg-white overflow-hidden shadow-sm border sm:rounded-lg">
                                <div class="p-6 text-gray-900 text-center">
                                    <a href="{{ route('disbur.create') }}"><h1>出金登録</h1></a>
                                </div>
                            </div>
                        </div>
                        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mt-6">
                            <div class="bg-white overflow-hidden shadow-sm border sm:rounded-lg">
                                <div class="p-6 text-gray-900 text-center">
                                    <a href="{{ route('dcategory.index') }}"><h1>カテゴリマスタ</h1></a>
                                </div>
                            </div>
                        </div>
                        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mt-6">
                            <div class="bg-white overflow-hidden shadow-sm border sm:rounded-lg">
                                <div class="p-6 text-gray-900 text-center">
                                    <a href="{{ route('register') }}"><h1>新規ユーザー登録</h1></a>
                                </div>
                            </div>
                        </div>
                        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mt-6">
                            <div class="bg-white overflow-hidden shadow-sm border sm:rounded-lg">
                                <div class="p-6 text-gray-900 text-center">
                                    <a href="{{ route('register.allshow') }}"><h1>ユーザーマスタ</h1></a>
                                </div>
                            </div>
                        </div>
                        @endfeature
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const installCard = document.getElementById('pwa-install-card');
    const installButton = document.getElementById('InstallBtn');
    const dismissButton = document.getElementById('InstallDismissBtn');
    let deferredInstallPrompt = null;

    if (!installCard || !installButton || !dismissButton) {
        return;
    }

    const hideInstallCard = function() {
        installCard.hidden = true;
    };

    const showInstallCard = function() {
        if (sessionStorage.getItem('pwa-install-dismissed') === '1') {
            return;
        }

        installCard.hidden = false;
    };

    window.addEventListener('beforeinstallprompt', function(event) {
        event.preventDefault();
        deferredInstallPrompt = event;
        showInstallCard();
    });

    window.addEventListener('appinstalled', function() {
        deferredInstallPrompt = null;
        hideInstallCard();
    });

    installButton.addEventListener('click', async function() {
        if (!deferredInstallPrompt) {
            return;
        }

        installButton.disabled = true;
        deferredInstallPrompt.prompt();
        await deferredInstallPrompt.userChoice;
        deferredInstallPrompt = null;
        installButton.disabled = false;
        hideInstallCard();
    });

    dismissButton.addEventListener('click', function() {
        sessionStorage.setItem('pwa-install-dismissed', '1');
        hideInstallCard();
    });
});
</script>
