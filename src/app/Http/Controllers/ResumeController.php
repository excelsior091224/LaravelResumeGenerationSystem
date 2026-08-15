<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateResumeRequest;
use Illuminate\View\View;

class ResumeController extends Controller
{
    public function create(): View
    {
        // スキル候補はDBに保存せず、静的JSONをリクエストごとに読み込む。
        return view('resume.create', [
            'skillCategories' => json_decode(
                file_get_contents(resource_path('data/skills.json')),
                true,
                512,
                JSON_THROW_ON_ERROR,
            ),
        ]);
    }

    public function preview(GenerateResumeRequest $request): View
    {
        // validated()で許可された項目だけを使い、入力内容を保存せず画面へ渡す。
        return view('resume.preview', ['resume' => $request->validated()]);
    }
}
