<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CandidacyController;
use App\Http\Controllers\EntryController;
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

    Route::get('/candidacies', [CandidacyController::class, 'index'])->name('candidacies.index')->middleware('role:interviewer,recruiter,admin');

    Route::get('/candidacies/{id}', [CandidacyController::class, 'show'])->name('candidacies.show')->middleware('role:interviewer,recruiter,admin');

    Route::patch('/candidacies/{id}/status', [CandidacyController::class, 'updateStatus'])->name('candidacies.update-status')->middleware('role:recruiter,admin');

    Route::put('/candidacies/{id}/interviewers', [CandidacyController::class, 'manageInterviewers'])->name('candidacies.interviewers')->middleware('role:recruiter,admin');

    Route::get('/candidacies/{id}/resume', [CandidacyController::class, 'resume'])->name('candidacies.resume')->middleware('role:interviewer,recruiter,admin');
});

// 公開ページ(未ログインの応募者向け)REQ-015, 016, 004 - authの外
Route::get('/', [PublicJobController::class, 'index'])->name('public.jobs.index');
Route::get('/jobs/{id}', [PublicJobController::class, 'show'])->name('public.jobs.show');

// 応募エントリー(REQ-004)
Route::get('/jobs/{id}/entry', [EntryController::class, 'create'])->name('public.entries.create');
Route::post('/jobs/{id}/entries', [EntryController::class, 'store'])->name('public.entries.store');
Route::get('/jobs/{id}/complete', [EntryController::class, 'complete'])->name('public.entries.complete');
