<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TopController;
use App\Http\Controllers\RestaurantController;
use App\Http\Controllers\CuisineController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\RouletteHistoryController;
use App\Http\Controllers\RouletteController;
use App\Http\Controllers\Admin\Auth\LoginController as AdminLogin;
use App\Http\Controllers\Admin\Auth\RegisterController as AdminRegister;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\UserReportController;
use App\Http\Controllers\MemberCuisineStateController;


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

    // 履歴
    Route::get('/histories', [RouletteHistoryController::class, 'index'])->name('histories.index');
    Route::post('/histories', [RouletteHistoryController::class, 'store'])->name('histories.store'); 
    Route::patch('/histories/{history}', [RouletteHistoryController::class,'update'])->name('histories.update');
    Route::delete('/histories/{history}', [RouletteHistoryController::class,'destroy'])->name('histories.destroy');

    // ⭐トグル
    Route::post('/favorites/toggle', [FavoriteController::class, 'toggle'])
        ->name('favorites.toggle');

    Route::post('/roulette/histories/visit', [RouletteHistoryController::class, 'visit'])
        ->name('roulette.visit');

    Route::get('/roulette/place/{placeId}', [RestaurantController::class, 'placeDetail'])
        ->name('roulette.place.detail');

    // ② 会員限定ルーレット：実店舗検索→ルーレット
    Route::prefix('roulette')->name('roulette.')->group(function () {
        Route::get('/search', [RouletteController::class, 'searchForm'])->name('search.form');      // 条件入力（駅/距離/価格/営業時間など）
        Route::post('/search/spin', [RouletteController::class, 'searchSpin'])->name('search.spin'); // 条件に合う店舗から抽選
        // 結果表示は上の /roulette/result を共通利用
    });

    // 追加: ジオコード用（GET /roulette/geocode?q=...）
    Route::get('/roulette/geocode', [\App\Http\Controllers\RouletteController::class, 'geocode'])
        ->name('roulette.geocode');

    
    Route::get('/roulette/reverse', [RestaurantController::class, 'reverseGeocode'])
        ->name('roulette.reverse');

    // 選択削除（会員のみ推奨）
    Route::delete('/cuisines/bulk-delete', [CuisineController::class, 'destroyMany'])
        ->name('cuisines.destroyMany');

    Route::get('/user/cuisine-states',  [MemberCuisineStateController::class,'index'])
        ->name('user.cuisine.states.index');
    Route::post('/user/cuisine-states', [MemberCuisineStateController::class,'upsert'])
        ->name('user.cuisine.states.upsert');

});
// ==================== 管理者 ====================
Route::prefix('admin')->name('admin.')->group(function () {
    // /admin 直叩きは管理者トップへ
    Route::redirect('/', '/admin/users');

    // 未ログイン（adminガード）だけ
    Route::middleware('guest:admin')->group(function () {
        Route::get('login',    [AdminLogin::class, 'showLoginForm'])->name('login');
        Route::post('login',   [AdminLogin::class, 'login']);
        Route::get('register', [AdminRegister::class, 'showRegistrationForm'])->name('register');
        Route::post('register',[AdminRegister::class, 'register']);
    });

    // 管理者ログイン後のみ
    Route::middleware('auth:admin')->group(function () {
    Route::get('dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::post('logout',   [AdminLogin::class, 'logout'])->name('logout');

    Route::get('users',         [AdminUserController::class, 'index'])->name('users.index');
    Route::get('users/deleted', [AdminUserController::class, 'deleted'])->name('users.deleted');

    // ★ 固定パスを先に！
    Route::get('users/stats',   [UserReportController::class, 'stats'])->name('users.stats');
    Route::get('users/export',  [UserReportController::class, 'export'])->name('users.export');

    // ↓ これらの“動的”は最後に
    Route::get('users/{user}',         [AdminUserController::class, 'show'])->name('users.show');
    Route::delete('users/{user}',      [AdminUserController::class, 'destroy'])->name('users.destroy');
    Route::patch('users/{id}/restore', [AdminUserController::class, 'restore'])->name('users.restore');
    Route::delete('users/{id}/force',  [AdminUserController::class, 'forceDelete'])->name('users.forceDelete');
});
});

// ===============================================
// （必要なら）管理者向けの一覧
Route::get('/restaurants', [RestaurantController::class, 'index'])->name('restaurants.index');

// Breezeのナビが参照するため、ダミーでも定義しておく
Route::view('/dashboard', 'dashboard')->middleware(['auth'])->name('dashboard');


require __DIR__.'/auth.php';
