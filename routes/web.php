<?php

use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    // 動作確認用の仮ルート(タスク9以降で実際の画面に置き換える)
    Route::get('/users', fn () => 'ユーザー管理画面(仮)')->middleware('role:admin');
    Route::get('/job-postings', fn () => '求人票一覧画面(仮)')->middleware('role:recruiter,admin');
    Route::get('/candidacies', fn () => '応募者一覧画面(仮)')->middleware('role:interviewer,recruiter,admin');
});

Route::get('/', function () {
    return view('welcome');
});
