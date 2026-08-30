<?php

use App\Http\Controllers\ResumeController;
use Illuminate\Support\Facades\Route;

// 入力フォームを表示し、スキル候補を画面へ渡す。
Route::get('/', [ResumeController::class, 'create'])->name('resume.create');

Route::view('/privacy', 'legal.privacy')->name('privacy');

Route::get('/contact', function () {
    return view('contact', [
        'googleFormUrl' => config('services.google_form.url'),
    ]);
})->name('contact');

// 職務経歴書の書き方・コラム・提出マナーガイド
Route::view('/guides', 'guides.index')->name('guides.index');
Route::view('/guides/how-to-write-resume', 'guides.how-to-write-resume')->name('guides.how-to-write-resume');
Route::view('/guides/self-pr-examples', 'guides.self-pr-examples')->name('guides.self-pr-examples');
Route::view('/guides/pdf-word-submission-rules', 'guides.pdf-word-submission-rules')->name('guides.pdf-word-submission-rules');

// フォーム内容をバリデーションし、サーバー側のプレビューを表示する。
Route::post('/resume/preview', [ResumeController::class, 'preview'])->name('resume.preview');

Route::post('/resume/summary', [ResumeController::class, 'summarize'])->middleware('throttle:10,1')->name('resume.summary');

// 入力内容を保存せず、その場でPDF/DOCXを生成してダウンロードさせる。
Route::post('/resume/download/pdf', [ResumeController::class, 'downloadPdf'])->name('resume.download.pdf');
Route::post('/resume/download/docx', [ResumeController::class, 'downloadDocx'])->name('resume.download.docx');
