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
            'as_of_date' => ['required', 'date_format:Y-m-d'],
            'summary' => ['nullable', 'string', 'max:3000'],
            'specialty' => ['nullable', 'string', 'max:500'],
            'links' => ['array', 'max:10'],
            'links.*.type' => ['nullable', 'string', 'in:GitHub,Qiita,Zenn,ポートフォリオ,その他'],
            'links.*.type_custom' => ['nullable', 'string', 'max:100'],
            'links.*.url' => ['nullable', 'url', 'regex:/^https?:\/\//i', 'max:500'],

            // スキルは画面上で追加・削除される配列として検証する。
            'skills' => ['array', 'max:100'],
            'skills.*.category' => ['required', 'string', 'max:50'],
            'skills.*.name' => ['required', 'string', 'max:100'],
            'skills.*.years' => ['nullable', 'string', 'max:30'],
            'skills.*.level' => ['nullable', 'string', 'in:業務使用,個人開発,自己研鑽'],
            'skills.*.note' => ['nullable', 'string', 'max:200'],

            // 所属企業を親、プロジェクトを子とする階層データを検証する。
            'companies' => ['array', 'max:20'],
            'companies.*.name' => ['nullable', 'string', 'max:200'],
            'companies.*.employment_type' => ['required', 'string', 'in:正社員,契約社員,派遣社員,パート・アルバイト,業務委託,フリーランス,役員,その他'],
            'companies.*.employment_type_custom' => ['nullable', 'string', 'max:100'],
            'companies.*.is_current' => ['nullable', 'boolean'],
            'companies.*.period_from' => ['nullable', 'date_format:Y-m'],
            'companies.*.period_to' => [
                'nullable',
                'date_format:Y-m',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $segments = explode('.', $attribute);
                    $companyIndex = $segments[1] ?? null;

                    if ($companyIndex === null || $value === null || $value === '') {
                        return;
                    }

                    $isCurrent = filter_var(data_get($this->input('companies'), $companyIndex . '.is_current'), FILTER_VALIDATE_BOOLEAN);
                    if ($isCurrent) {
                        return;
                    }

                    $companyFrom = data_get($this->input('companies'), $companyIndex . '.period_from');

                    if (is_string($companyFrom) && $companyFrom !== '' && $value < $companyFrom) {
                        $fail('終了日は開始日以降で入力してください。');
                    }
                },
            ],
            'companies.*.industry' => ['nullable', 'string', 'max:200'],
            'companies.*.established' => ['nullable', 'string', 'max:50'],
            'companies.*.capital' => ['nullable', 'string', 'max:100'],
            'companies.*.employees' => ['nullable', 'string', 'max:100'],
            'companies.*.projects' => ['array', 'max:30'],
            'companies.*.projects.*.is_current' => ['nullable', 'boolean'],
            'companies.*.projects.*.period_from' => ['nullable', 'date_format:Y-m'],
            'companies.*.projects.*.period_to' => [
                'nullable',
                'date_format:Y-m',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $segments = explode('.', $attribute);
                    $companyIndex = $segments[1] ?? null;
                    $projectIndex = $segments[3] ?? null;

                    if ($companyIndex === null || $projectIndex === null || $value === null || $value === '') {
                        return;
                    }

                    $isCurrent = filter_var(data_get($this->input('companies'), $companyIndex . '.projects.' . $projectIndex . '.is_current'), FILTER_VALIDATE_BOOLEAN);
                    if ($isCurrent) {
                        return;
                    }

                    $projectFrom = data_get($this->input('companies'), $companyIndex . '.projects.' . $projectIndex . '.period_from');

                    if (is_string($projectFrom) && $projectFrom !== '' && $value < $projectFrom) {
                        $fail('プロジェクトの終了日は開始日以降で入力してください。');
                    }
                },
            ],
            'companies.*.projects.*.name' => ['required', 'string', 'max:200'],
            'companies.*.projects.*.description' => ['nullable', 'string', 'max:3000'],
            'companies.*.projects.*.role' => ['nullable', 'string', 'max:100'],
            'companies.*.projects.*.role_custom' => ['nullable', 'string', 'max:100'],
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

    public function withValidator(\Illuminate\Validation\Validator $validator): void
    {
        $validator->after(function (\Illuminate\Validation\Validator $validator): void {
            foreach ((array) $this->input('links', []) as $index => $link) {
                $type = (string) ($link['type'] ?? '');
                $custom = trim((string) ($link['type_custom'] ?? ''));

                if ($type === 'その他' && $custom === '') {
                    $validator->errors()->add("links.{$index}.type_custom", 'リンク種別が「その他」の場合は、サイト名を入力してください。');
                }
            }

            foreach ((array) $this->input('companies', []) as $companyIndex => $company) {
                $employmentType = (string) ($company['employment_type'] ?? '');
                $employmentCustom = trim((string) ($company['employment_type_custom'] ?? ''));

                if ($employmentType === 'その他' && $employmentCustom === '') {
                    $validator->errors()->add("companies.{$companyIndex}.employment_type_custom", '雇用形態が「その他」の場合は、契約形態を入力してください。');
                }

                foreach ((array) ($company['projects'] ?? []) as $projectIndex => $project) {
                    $role = (string) ($project['role'] ?? '');
                    $roleCustom = trim((string) ($project['role_custom'] ?? ''));

                    if ($role === 'その他' && $roleCustom === '') {
                        $validator->errors()->add("companies.{$companyIndex}.projects.{$projectIndex}.role_custom", '担当工程が「その他」の場合は、具体的な役割を入力してください。');
                    }
                }
            }
        });
    }
}
