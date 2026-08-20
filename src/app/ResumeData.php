<?php

namespace App;

final class ResumeData
{
    public const SKILL_CATEGORY_ORDER = [
        '担当業務',
        '言語',
        'フレームワーク',
        'ミドルウェア',
        'OS',
        'インフラ',
        'データベース',
        'デザインツール',
        '開発ツール・その他',
    ];

    public function __construct(private array $data)
    {
        $this->data = $this->normalize($data);
    }

    public static function fromArray(array $data): self
    {
        return new self($data);
    }

    public function toArray(): array
    {
        return $this->data;
    }

    private function normalize(array $data): array
    {
        $data['considerations'] = trim((string) ($data['considerations'] ?? ''));
        $data['links'] = $this->normalizeLinks($data['links'] ?? []);
        $data['skills'] = $this->normalizeSkills($data['skills'] ?? []);
        $data['companies'] = $this->normalizeCompanies($data['companies'] ?? []);
        $data['certifications'] = $this->normalizeCertifications($data['certifications'] ?? []);

        return $data;
    }

    private function normalizeLinks(array $links): array
    {
        $filtered = array_values(array_filter($links, function (mixed $link): bool {
            if (! is_array($link)) {
                return false;
            }

            return trim((string) ($link['url'] ?? '')) !== ''
                || trim((string) ($link['type'] ?? '')) !== ''
                || trim((string) ($link['type_custom'] ?? '')) !== '';
        }));

        foreach ($filtered as $index => $link) {
            $filtered[$index] = [
                'type' => trim((string) ($link['type'] ?? '')),
                'type_custom' => trim((string) ($link['type_custom'] ?? '')),
                'url' => trim((string) ($link['url'] ?? '')),
            ];
        }

        return $filtered;
    }

    private function normalizeSkills(array $skills): array
    {
        $normalized = array_values(array_filter($skills, function (mixed $skill): bool {
            if (! is_array($skill)) {
                return false;
            }

            return trim((string) ($skill['category'] ?? '')) !== ''
                || trim((string) ($skill['name'] ?? '')) !== ''
                || trim((string) ($skill['years'] ?? '')) !== ''
                || trim((string) ($skill['level'] ?? '')) !== ''
                || trim((string) ($skill['note'] ?? '')) !== '';
        }));

        foreach ($normalized as $index => $skill) {
            $category = trim((string) ($skill['category'] ?? ''));
            $normalized[$index] = [
                'category' => $category,
                'name' => trim((string) ($skill['name'] ?? '')),
                'years' => trim((string) ($skill['years'] ?? '')),
                'level' => $category === '担当業務' ? '' : trim((string) ($skill['level'] ?? '')),
                'note' => trim((string) ($skill['note'] ?? '')),
            ];
        }

        usort($normalized, function (array $left, array $right): int {
            $leftOrder = array_search($left['category'] ?: '未分類', self::SKILL_CATEGORY_ORDER, true);
            $rightOrder = array_search($right['category'] ?: '未分類', self::SKILL_CATEGORY_ORDER, true);

            $leftOrder = $leftOrder === false ? count(self::SKILL_CATEGORY_ORDER) : $leftOrder;
            $rightOrder = $rightOrder === false ? count(self::SKILL_CATEGORY_ORDER) : $rightOrder;

            if ($leftOrder !== $rightOrder) {
                return $leftOrder <=> $rightOrder;
            }

            return strcmp($left['name'] ?: '', $right['name'] ?: '');
        });

        return $normalized;
    }

    private function normalizeCompanies(array $companies): array
    {
        $normalized = array_values(array_filter($companies, fn(mixed $company): bool => is_array($company)));

        foreach ($normalized as $index => $company) {
            $company['name'] = trim((string) ($company['name'] ?? ''));
            $company['business_overview'] = trim((string) ($company['business_overview'] ?? ''));
            if ($company['name'] === '' && (($company['employment_type'] ?? '') === 'フリーランス')) {
                $company['name'] = 'フリーランス';
            }
            $company['projects'] = $this->normalizeProjects($company['projects'] ?? []);
            $normalized[$index] = $company;
        }

        usort($normalized, function (array $left, array $right): int {
            $leftValue = $this->sortDateValue($left['period_from'] ?? '');
            $rightValue = $this->sortDateValue($right['period_from'] ?? '');

            if ($leftValue === $rightValue) {
                return 0;
            }

            return $rightValue <=> $leftValue;
        });

        return $normalized;
    }

    private function normalizeProjects(array $projects): array
    {
        $normalized = array_values(array_filter($projects, fn(mixed $project): bool => is_array($project)));

        foreach ($normalized as $index => $project) {
            $normalized[$index] = [
                'name' => trim((string) ($project['name'] ?? '')),
                'period_from' => trim((string) ($project['period_from'] ?? '')),
                'period_to' => trim((string) ($project['period_to'] ?? '')),
                'is_current' => filter_var($project['is_current'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'description' => trim((string) ($project['description'] ?? '')),
                'role' => trim((string) ($project['role'] ?? '')),
                'role_custom' => trim((string) ($project['role_custom'] ?? '')),
                'team' => trim((string) ($project['team'] ?? '')),
                'processes' => trim((string) ($project['processes'] ?? '')),
                'technologies' => trim((string) ($project['technologies'] ?? '')),
            ];
        }

        usort($normalized, function (array $left, array $right): int {
            $leftValue = $this->sortDateValue($left['period_from'] ?? '');
            $rightValue = $this->sortDateValue($right['period_from'] ?? '');

            if ($leftValue === $rightValue) {
                return 0;
            }

            return $rightValue <=> $leftValue;
        });

        return $normalized;
    }

    private function normalizeCertifications(array $certifications): array
    {
        $normalized = array_values(array_filter($certifications, fn(mixed $item): bool => is_array($item)));

        foreach ($normalized as $index => $item) {
            $normalized[$index] = [
                'date' => trim((string) ($item['date'] ?? '')),
                'name' => trim((string) ($item['name'] ?? '')),
            ];
        }

        usort($normalized, function (array $left, array $right): int {
            $leftValue = $this->sortDateValue($left['date'] ?? '');
            $rightValue = $this->sortDateValue($right['date'] ?? '');

            if ($leftValue === $rightValue) {
                return 0;
            }

            return $rightValue <=> $leftValue;
        });

        return $normalized;
    }

    private function sortDateValue(string $value): int
    {
        if ($value === '') {
            return 0;
        }

        $timestamp = strtotime($value . '-01');

        return $timestamp === false ? 0 : (int) $timestamp;
    }
}
