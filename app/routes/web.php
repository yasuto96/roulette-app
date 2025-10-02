<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TopController;
use App\Http\Controllers\RestaurantController;
use App\Http\Controllers\CuisineController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\RouletteHistoryController;
use App\Http\Controllers\RouletteController;

// トップ
Route::get('/', [TopController::class, 'index'])->name('top');

// 一般公開（誰でも）
Route::get('/cuisines', [CuisineController::class, 'index'])->name('cuisines.index');

// ① 公開ルーレット：カテゴリ（料理ジャンル）
Route::prefix('roulette')->name('roulette.')->group(function () {
    Route::get('/category', [RouletteController::class, 'categoryForm'])->name('category.form');   // フォーム表示
    Route::post('/category/spin', [RouletteController::class, 'categorySpin'])->name('category.spin'); // 抽選
    Route::get('/result', [RouletteController::class, 'result'])->name('result'); // 結果表示（共通）
});

Route::post('/cuisines', [\App\Http\Controllers\CuisineController::class, 'store'])
    ->name('cuisines.store');




// 会員限定（履歴・お気に入り・検索ルーレット）
Route::middleware('auth')->group(function () {

    // お気に入り
    Route::get('/favorites', [FavoriteController::class, 'index'])->name('favorites.index');
    Route::post('/favorites/{restaurant}', [FavoriteController::class, 'store'])->name('favorites.store');
    Route::delete('/favorites/{restaurant}', [FavoriteController::class, 'destroy'])->name('favorites.destroy');

    // 履歴
    Route::get('/histories', [RouletteHistoryController::class, 'index'])->name('histories.index');

    // ② 会員限定ルーレット：実店舗検索→ルーレット
    Route::prefix('roulette')->name('roulette.')->group(function () {
        Route::get('/search', [RouletteController::class, 'searchForm'])->name('search.form');      // 条件入力（駅/距離/価格/営業時間など）
        Route::post('/search/spin', [RouletteController::class, 'searchSpin'])->name('search.spin'); // 条件に合う店舗から抽選
        // 結果表示は上の /roulette/result を共通利用
    });

    // 選択削除（会員のみ推奨）
    Route::delete('/cuisines/bulk-delete', [CuisineController::class, 'destroyMany'])
        ->name('cuisines.destroyMany');

});

// （必要なら）管理者向けの一覧
Route::get('/restaurants', [RestaurantController::class, 'index'])->name('restaurants.index');

// Breezeのナビが参照するため、ダミーでも定義しておく
Route::view('/dashboard', 'dashboard')->middleware(['auth'])->name('dashboard');


require __DIR__.'/auth.php';
