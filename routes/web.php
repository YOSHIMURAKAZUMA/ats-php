<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\JobPostingController;
use App\Http\Controllers\PublicJobController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    // 求人票管理(採用担当者・管理者)REQ-001, 002, 003
    Route::middleware('role:recruiter,admin')->group(function () {
        Route::get('/job-postings', [JobPostingController::class, 'index'])->name('job-postings.index');
        Route::get('/job-postings/create', [JobPostingController::class, 'create'])->name('job-postings.create');
        Route::post('/job-postings', [JobPostingController::class, 'store'])->name('job-postings.store');
        Route::get('/job-postings/{job_posting}/edit', [JobPostingController::class, 'edit'])->name('job-postings.edit');
        Route::put('/job-postings/{job_posting}', [JobPostingController::class, 'update'])->name('job-postings.update');
        Route::patch('/job-postings/{job_posting}/status', [JobPostingController::class, 'updateStatus'])->name('job-postings.updateStatus');
    });

    // 他ロールの仮ルート(タスク9では触れない)
    Route::get('/users', fn () => 'ユーザー管理画面(仮)')->middleware('role:admin');
    Route::get('/candidacies', fn () => '応募者一覧画面(仮)')->middleware('role:interviewer,recruiter,admin');
});

Route::get('/', [PublicJobController::class, 'index'])->name('public.jobs.index');
Route::get('/jobs/{id}', [PublicJobController::class, 'show'])->name('public.jobs.show');
