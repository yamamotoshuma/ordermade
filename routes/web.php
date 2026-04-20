<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PayController;
use App\Http\Controllers\dCategoryController;
use App\Http\Controllers\disburController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\BattingEditController;
use App\Http\Controllers\BattingOrderController;
use App\Http\Controllers\BattingStatsController;
use App\Http\Controllers\StealController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\PitchingStatsController;
use App\Models\pitchingStats;
use Illuminate\Support\Facades\URL;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');//サンプル　TODO：完成後は削除する

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::resource('payment', PayController::class)->middleware(['auth', 'verified']);
Route::post('payment/bulkStore', [PayController::class,'bulkStore'])->middleware(['auth', 'verified','features:attendances-management'])->name('payment.bulkStore');
Route::resource('dcategory', dCategoryController::class)->middleware(['auth', 'verified','features:attendances-management']);

Route::get('disbur', [disburController::class, 'index'])->middleware(['auth', 'verified'])->name('disbur.index');
Route::get('disbur/create', [disburController::class, 'create'])->middleware(['auth', 'verified','features:attendances-management'])->name('disbur.create');
Route::get('disbur/getScode', [disburController::class, 'getScode'])->middleware(['auth', 'verified'])->name('disbur.getScode');
Route::post('disbur', [disburController::class, 'store'])->middleware(['auth', 'verified','features:attendances-management'])->name('disbur.store');
Route::get('disbur/{disbur}', [disburController::class, 'show'])->middleware(['auth', 'verified','features:attendances-management'])->name('disbur.show');
Route::get('disbur/{disbur}/edit', [disburController::class, 'edit'])->middleware(['auth', 'verified','features:attendances-management'])->name('disbur.edit');
Route::patch('disbur/{disbur}', [disburController::class, 'update'])->middleware(['auth', 'verified','features:attendances-management'])->name('disbur.update');
Route::delete('disbur/{disbur}', [disburController::class, 'destroy'])->middleware(['auth', 'verified','features:attendances-management'])->name('disbur.destroy');
Route::resource('game', GameController::class)->middleware(['auth', 'verified']);
Route::post('/game/bulk-update-insert', [GameController::class,'bulkUpdateOrInsert'])->middleware(['auth', 'verified'])->name('game.updateOrInsert');

Route::get('batting/{game}', [BattingEditController::class, 'index'])->middleware(['auth', 'verified'])->name('batting.index');
Route::get('batting/{game}/create', [BattingEditController::class, 'create'])->middleware(['auth', 'verified'])->name('batting.create');
Route::post('batting/{game}', [BattingEditController::class, 'store'])->middleware(['auth', 'verified'])->name('batting.store');
Route::get('batting/{batting}/edit', [BattingEditController::class, 'edit'])->middleware(['auth', 'verified'])->name('batting.edit');
Route::post('batting/{batting}/update', [BattingEditController::class, 'update'])->middleware(['auth', 'verified'])->name('batting.update');
Route::delete('batting/{batting}/delete', [BattingEditController::class, 'destroy'])->middleware(['auth', 'verified'])->name('batting.destroy');

Route::get('steal/{game}', [StealController::class, 'index'])->middleware(['auth', 'verified'])->name('steal.index');
Route::post('steal', [StealController::class, 'store'])->middleware(['auth', 'verified'])->name('steal.store');
Route::delete('steal/delete', [StealController::class, 'destroy'])->middleware(['auth', 'verified'])->name('steal.destroy');

Route::post('order/{order}/import-sheet', [BattingOrderController::class, 'importFromSpreadsheet'])->middleware(['auth','verified'])->name('order.importSheet');
Route::resource('order', BattingOrderController::class)->middleware(['auth','verified']);

Route::get('/contact', [ContactController::class,'index'])->name('contact');
Route::post('/contact/store', [ContactController::class, 'store'])->name('contact.store');

Route::get('/pitching/{game}', [PitchingStatsController::class,'index'])->middleware(['auth', 'verified'])->name('pitching');
Route::delete('pitching/{pitching}/delete', [PitchingStatsController::class, 'destroy'])->middleware(['auth', 'verified'])->name('pitching.destroy');
Route::get('pitching/{pitching}/edit', [PitchingStatsController::class, 'edit'])->middleware(['auth', 'verified'])->name('pitching.edit');
Route::post('pitching/{pitching}/update', [PitchingStatsController::class, 'update'])->middleware(['auth', 'verified'])->name('pitching.update');
Route::get('pitching/{gameId}/create', [PitchingStatsController::class, 'create'])->middleware(['auth', 'verified'])->name('pitching.create');
Route::post('pitching/{gameId}', [PitchingStatsController::class, 'store'])->middleware(['auth', 'verified'])->name('pitching.store');
Route::post('pitching/{pitching}/updateNumber', [PitchingStatsController::class, 'updateNumber'])->middleware(['auth', 'verified'])->name('pitching.updateNumber');

Route::get('battingStats/index', [BattingStatsController::class, 'index'])->middleware(['auth', 'verified'])->name('battingStats');

require __DIR__.'/auth.php';
