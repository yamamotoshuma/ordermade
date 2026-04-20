<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            O管理アプリ
        </h2>
    </x-slot>
    <div class="container mt-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <button id="InstallBtn" style="display: none; width:100%;" class="text-center bg-blue-500 hover:bg-blue-600 text-white font-semibold py-4 px-6 rounded-lg">
                    アプリをインストールする
                </button>
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
                                    <a href="game/"><h1>試合</h1></a>
                                </div>
                            </div>
                        </div>
                        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mt-6">
                            <div class="bg-white overflow-hidden rounded-lg shadow-sm border sm:rounded-lg">
                                <div class="p-6 text-gray-900 text-center">
                                    <a href="battingStats/index"><h1>打撃成績</h1></a>
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
                                    <a href="payment/"><h1>入金一覧</h1></a>
                                </div>
                            </div>
                        </div>
                        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mt-6">
                            <div class="bg-white overflow-hidden shadow-sm border sm:rounded-lg">
                                <div class="p-6 text-gray-900 text-center">
                                    <a href="disbur/"><h1>出金一覧</h1></a>
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
                                    <a href="contact"><h1>目安箱</h1></a>
                                </div>
                            </div>
                        </div>
                        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mt-6">
                            <div class="bg-white overflow-hidden shadow-sm border sm:rounded-lg">
                                <div class="p-6 text-gray-900 text-center">
                                    <a href="score"><h1>スコア表作成</h1></a>
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
                                    <a href="payment/create"><h1>入金登録</h1></a>
                                </div>
                            </div>
                        </div>
                        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mt-6">
                            <div class="bg-white overflow-hidden shadow-sm border sm:rounded-lg">
                                <div class="p-6 text-gray-900 text-center">
                                    <a href="disbur/create"><h1>出金登録</h1></a>
                                </div>
                            </div>
                        </div>
                        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mt-6">
                            <div class="bg-white overflow-hidden shadow-sm border sm:rounded-lg">
                                <div class="p-6 text-gray-900 text-center">
                                    <a href="dcategory"><h1>カテゴリマスタ</h1></a>
                                </div>
                            </div>
                        </div>
                        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mt-6">
                            <div class="bg-white overflow-hidden shadow-sm border sm:rounded-lg">
                                <div class="p-6 text-gray-900 text-center">
                                    <a href="register"><h1>新規ユーザー登録</h1></a>
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
//バナーの代わりに表示するボタンを登録する
registerInstallAppEvent(document.getElementById("InstallBtn"));

//バナー表示をキャンセルし、代わりに表示するDOM要素を登録する関数
//引数１：イベントを登録するHTMLElement
function registerInstallAppEvent(elem){
  //インストールバナー表示条件満足時のイベントを乗っ取る
  window.addEventListener('beforeinstallprompt', function(event){
    console.log("beforeinstallprompt: ", event);
    event.preventDefault(); //バナー表示をキャンセル
    elem.promptEvent = event; //eventを保持しておく
    elem.style.display = "block"; //要素を表示する
    return false;
  });
  //インストールダイアログの表示処理
  function installApp() {
    if(elem.promptEvent){
      elem.promptEvent.prompt(); //ダイアログ表示
      elem.promptEvent.userChoice.then(function(choice){
        elem.style.display = "none";
        elem.promptEvent = null; //一度しか使えないため後始末
      });//end then
    }
  }//end installApp
  //ダイアログ表示を行うイベントを追加
  elem.addEventListener("click", installApp);
}//end registerInstallAppEvent
</script>
