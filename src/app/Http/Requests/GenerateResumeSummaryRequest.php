<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GenerateResumeSummaryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ai_consent' => ['accepted'],
            'skills' => ['nullable', 'array', 'max:100'],
            'skills.*.category' => ['nullable', 'string', 'max:50'],
            'skills.*.name' => ['nullable', 'string', 'max:100'],
            'skills.*.years' => ['nullable', 'string', 'max:30'],
            'skills.*.level' => ['nullable', 'string', 'max:30'],
            'skills.*.note' => ['nullable', 'string', 'max:200'],
            'companies' => ['nullable', 'array', 'max:20'],
            'companies.*.name' => ['nullable', 'string', 'max:200'],
            'companies.*.employment_type' => ['nullable', 'string', 'max:100'],
            'companies.*.employment_type_custom' => ['nullable', 'string', 'max:100'],
            'companies.*.period_from' => ['nullable', 'date_format:Y-m'],
            'companies.*.period_to' => ['nullable', 'date_format:Y-m'],
            'companies.*.industry' => ['nullable', 'string', 'max:200'],
            'companies.*.business_overview' => ['nullable', 'string', 'max:1000'],
            'companies.*.projects' => ['nullable', 'array', 'max:30'],
            'companies.*.projects.*.name' => ['nullable', 'string', 'max:200'],
            'companies.*.projects.*.period_from' => ['nullable', 'date_format:Y-m'],
            'companies.*.projects.*.period_to' => ['nullable', 'date_format:Y-m'],
            'companies.*.projects.*.description' => ['nullable', 'string', 'max:3000'],
            'companies.*.projects.*.role' => ['nullable', 'string', 'max:100'],
            'companies.*.projects.*.role_custom' => ['nullable', 'string', 'max:100'],
            'companies.*.projects.*.team' => ['nullable', 'string', 'max:500'],
            'companies.*.projects.*.processes' => ['nullable', 'string', 'max:500'],
            'companies.*.projects.*.technologies' => ['nullable', 'string', 'max:500'],
            'certifications' => ['nullable', 'array', 'max:30'],
            'certifications.*.date' => ['nullable', 'string', 'max:30'],
            'certifications.*.name' => ['nullable', 'string', 'max:200'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $companies = array_values(array_filter(array_map(function (mixed $company): mixed {
            if (! is_array($company)) {
                return null;
            }

            $company['period_from'] = $this->nullIfBlank($company['period_from'] ?? null);
            $company['period_to'] = $this->nullIfBlank($company['period_to'] ?? null);
            $company['projects'] = array_values(array_filter(array_map(function (mixed $project): mixed {
                if (! is_array($project)) {
                    return null;
                }

                $project['period_from'] = $this->nullIfBlank($project['period_from'] ?? null);
                $project['period_to'] = $this->nullIfBlank($project['period_to'] ?? null);

                return $this->hasAnyValue($project, ['name', 'period_from', 'period_to', 'description', 'role', 'role_custom', 'team', 'processes', 'technologies']) ? $project : null;
            }, (array) ($company['projects'] ?? [])), fn(mixed $project): bool => is_array($project)));

            return $this->hasAnyValue($company, ['name', 'employment_type', 'employment_type_custom', 'period_from', 'period_to', 'industry', 'business_overview', 'projects']) ? $company : null;
        }, (array) $this->input('companies', [])), fn(mixed $company): bool => is_array($company)));

        $this->merge(['companies' => $companies]);
    }

    public function careerData(): array
    {
        return $this->safe()->only(['companies', 'skills', 'certifications']);
    }

    private function nullIfBlank(mixed $value): mixed
    {
        return is_string($value) && trim($value) === '' ? null : $value;
    }

    private function hasAnyValue(array $values, array $keys): bool
    {
        foreach ($keys as $key) {
            $value = $values[$key] ?? null;

            if (is_array($value) && $value !== []) {
                return true;
            }

            if (! is_array($value) && trim((string) $value) !== '') {
                return true;
            }
        }

        return false;
    }
}
