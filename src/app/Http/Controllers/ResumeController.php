<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateResumeRequest;
use App\Http\Requests\GenerateResumeSummaryRequest;
use App\ResumeData;
use App\Services\Document\ResumeDocxGenerator;
use App\Services\Document\ResumePdfGenerator;
use App\Services\ResumeSummaryProvider;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

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
            'teamRoles' => json_decode(
                file_get_contents(resource_path('data/team-roles.json')),
                true,
                512,
                JSON_THROW_ON_ERROR,
            ),
        ]);
    }

    public function preview(GenerateResumeRequest $request): View
    {
        // validated()で許可された項目だけを使い、入力内容を保存せず画面へ渡す。
        $resume = ResumeData::fromArray($request->validated());

        return view('resume.preview', ['resume' => $resume->toArray()]);
    }

    public function summarize(GenerateResumeSummaryRequest $request, ResumeSummaryProvider $provider): JsonResponse
    {
        $careerData = $request->careerData();
        if (empty($careerData['companies']) && empty($careerData['skills']) && empty($careerData['certifications'])) {
            return response()->json(['message' => '職歴、スキル、資格のいずれかを入力してください。'], 422);
        }

        try {
            return response()->json(['summary' => $provider->summarize($careerData)]);
        } catch (ConnectionException | RequestException) {
            return response()->json(['message' => 'AI要約サービスに接続できませんでした。時間をおいて再試行してください。'], 503);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }
    }

    public function downloadPdf(GenerateResumeRequest $request, ResumePdfGenerator $generator): Response
    {
        $resume = ResumeData::fromArray($request->validated());
        $filename = 'resume-' . now()->format('Ymd-His') . '.pdf';

        return response($generator->generate($resume), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function downloadDocx(GenerateResumeRequest $request, ResumeDocxGenerator $generator): Response
    {
        $resume = ResumeData::fromArray($request->validated());
        $filename = 'resume-' . now()->format('Ymd-His') . '.docx';

        return response($generator->generate($resume), 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
