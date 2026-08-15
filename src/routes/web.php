<?php

use App\Http\Controllers\ResumeController;
use Illuminate\Support\Facades\Route;

// 入力フォームを表示し、スキル候補を画面へ渡す。
Route::get('/', [ResumeController::class, 'create'])->name('resume.create');

// フォーム内容をバリデーションし、サーバー側のプレビューを表示する。
Route::post('/resume/preview', [ResumeController::class, 'preview'])->name('resume.preview');
