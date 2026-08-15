<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class GenerateResumeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // 基本情報と職務要約は単一項目として検証する。
            'full_name' => ['required', 'string', 'max:100'],
            'as_of_date' => ['required', 'date'],
            'summary' => ['nullable', 'string', 'max:3000'],
            'specialty' => ['nullable', 'string', 'max:500'],

            // スキルは画面上で追加・削除される配列として検証する。
            'skills' => ['array', 'max:100'],
            'skills.*.category' => ['required', 'string', 'max:50'],
            'skills.*.name' => ['required', 'string', 'max:100'],
            'skills.*.years' => ['nullable', 'string', 'max:30'],
            'skills.*.level' => ['nullable', 'string', 'in:業務使用,個人開発,自己研鑽'],
            'skills.*.note' => ['nullable', 'string', 'max:200'],

            // 所属企業を親、プロジェクトを子とする階層データを検証する。
            'companies' => ['array', 'max:20'],
            'companies.*.name' => ['required', 'string', 'max:200'],
            'companies.*.period_from' => ['nullable', 'date_format:Y-m'],
            'companies.*.period_to' => ['nullable', 'date_format:Y-m', 'after_or_equal:companies.*.period_from'],
            'companies.*.industry' => ['nullable', 'string', 'max:200'],
            'companies.*.established' => ['nullable', 'string', 'max:50'],
            'companies.*.capital' => ['nullable', 'string', 'max:100'],
            'companies.*.employees' => ['nullable', 'string', 'max:100'],
            'companies.*.projects' => ['array', 'max:30'],
            'companies.*.projects.*.period_from' => ['nullable', 'date_format:Y-m'],
            'companies.*.projects.*.period_to' => ['nullable', 'date_format:Y-m', 'after_or_equal:companies.*.projects.*.period_from'],
            'companies.*.projects.*.name' => ['required', 'string', 'max:200'],
            'companies.*.projects.*.description' => ['nullable', 'string', 'max:3000'],
            'companies.*.projects.*.role' => ['nullable', 'string', 'max:100'],
            'companies.*.projects.*.team' => ['nullable', 'string', 'max:500'],
            'companies.*.projects.*.processes' => ['nullable', 'string', 'max:500'],
            'companies.*.projects.*.technologies' => ['nullable', 'string', 'max:500'],

            // 資格も複数登録できる繰り返し項目として扱う。
            'certifications' => ['array', 'max:30'],
            'certifications.*.date' => ['nullable', 'string', 'max:30'],
            'certifications.*.name' => ['required', 'string', 'max:200'],
            'self_pr' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
