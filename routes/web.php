<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PayController;
use App\Http\Controllers\dCategoryController;
use App\Http\Controllers\disburController;


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
Route::resource('dcategory', dCategoryController::class)->middleware(['auth', 'verified','features:attendances-management']);

Route::get('disbur', [disburController::class, 'index'])->middleware(['auth', 'verified'])->name('disbur.index');
Route::get('score', [disburController::class, 'score'])->middleware(['auth', 'verified'])->name('disbur.score');
Route::get('disbur/create', [disburController::class, 'create'])->middleware(['auth', 'verified','features:attendances-management'])->name('disbur.create');
Route::get('disbur/getScode', [disburController::class, 'getScode'])->middleware(['auth', 'verified'])->name('disbur.getScode');
Route::post('disbur', [disburController::class, 'store'])->middleware(['auth', 'verified','features:attendances-management'])->name('disbur.store');
Route::get('disbur/{disbur}', [disburController::class, 'show'])->middleware(['auth', 'verified','features:attendances-management'])->name('disbur.show');
Route::get('disbur/{disbur}/edit', [disburController::class, 'edit'])->middleware(['auth', 'verified','features:attendances-management'])->name('disbur.edit');
Route::patch('disbur/{disbur}', [disburController::class, 'update'])->middleware(['auth', 'verified','features:attendances-management'])->name('disbur.update');
Route::delete('disbur/{dibur}', [disburController::class, 'destroy'])->middleware(['auth', 'verified','features:attendances-management'])->name('disbur.destroy');

require __DIR__.'/auth.php';
