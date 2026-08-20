<?php

namespace App\Services\Document;

use App\ResumeData;
use App\Support\DocxTextFormatter;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;

final class ResumeDocxGenerator
{
    public function generate(ResumeData $resume): string
    {
        $data = $this->formatTextValues($resume->toArray());
        $word = new PhpWord();
        $word->setDefaultFontName('IPAexGothic');
        $word->setDefaultFontSize(10);
        $word->addTitleStyle(1, ['name' => 'IPAexGothic', 'size' => 20, 'bold' => true, 'color' => '20252A'], [
            'alignment' => Jc::START,
            'spaceAfter' => 160,
            'borderBottomSize' => 18,
            'borderBottomColor' => '176B6B',
        ]);

        $section = $word->addSection([
            'paperSize' => 'A4',
            'marginTop' => 900,
            'marginRight' => 900,
            'marginBottom' => 900,
            'marginLeft' => 900,
        ]);
        $section->addTitle('職務経歴書', 1);
        $this->addMultilineText($section, ($data['as_of_date'] ?? '') . "\n氏名：" . ($data['full_name'] ?? ''), ['bold' => true, 'size' => 10], ['alignment' => Jc::END, 'spaceAfter' => 120]);

        $this->addHeading($section, '職務要約');
        $this->addMultilineText($section, $data['summary'] ?? '', [], ['spaceAfter' => 100]);
        $this->addHeading($section, '得意業務');
        $this->addMultilineText($section, $data['specialty'] ?? '', [], ['spaceAfter' => 100]);

        $this->addHeading($section, '技術系アカウント・ポートフォリオ');
        foreach ($data['links'] ?? [] as $link) {
            if (($link['url'] ?? '') === '') {
                continue;
            }
            $type = ($link['type'] ?? '') === 'その他' ? ($link['type_custom'] ?? '') : ($link['type'] ?? '');
            $this->addMultilineText($section, $type . '：' . $link['url'], [], ['spaceAfter' => 50]);
        }

        $this->addHeading($section, 'PCスキル / テクニカルスキル');
        $table = $section->addTable(['borderSize' => 6, 'borderColor' => 'B8C2C8', 'cellMargin' => 80]);
        $table->addRow();
        foreach (['カテゴリ', 'スキル', '経験年数', '経験区分', '備考'] as $index => $heading) {
            $table->addCell([1700, 2200, 1400, 1500, 2400][$index])->addText($heading, ['bold' => true]);
        }
        foreach ($data['skills'] ?? [] as $skill) {
            $table->addRow();
            foreach (['category', 'name', 'years', 'level', 'note'] as $index => $key) {
                $table->addCell([1700, 2200, 1400, 1500, 2400][$index])->addText((string) ($skill[$key] ?? ''), ['name' => 'IPAexGothic', 'color' => '20252A']);
            }
        }

        $this->addHeading($section, '職務経歴');
        foreach ($data['companies'] ?? [] as $company) {
            $companyName = $company['name'] ?: (($company['employment_type'] ?? '') === 'フリーランス' ? 'フリーランス' : '所属企業名未入力');
            $section->addText($companyName . '（' . ($company['period_from'] ?? '') . '〜' . ($company['period_to'] ?? '') . '）', ['bold' => true, 'size' => 11], ['spaceBefore' => 100, 'spaceAfter' => 70, 'borderBottomSize' => 6, 'borderBottomColor' => 'B8C2C8']);
            $companyMeta = collect([
                ($company['employment_type'] ?? '') === 'その他' ? ($company['employment_type_custom'] ?? '') : ($company['employment_type'] ?? ''),
                $company['industry'] ?? '',
                ($company['established'] ?? '') ? '設立：' . $company['established'] : null,
                ($company['capital'] ?? '') ? '資本金：' . $company['capital'] : null,
                ($company['employees'] ?? '') ? '従業員数：' . $company['employees'] : null,
            ])->filter()->join(' / ');
            if ($companyMeta !== '') {
                $section->addText($companyMeta, ['size' => 9], ['spaceAfter' => 40]);
            }
            if (($company['business_overview'] ?? '') !== '') {
                $this->addMultilineText($section, '【業務概要】' . "\n" . $company['business_overview'], [], ['spaceAfter' => 50]);
            }
            foreach ($company['projects'] ?? [] as $project) {
                $section->addText('■ ' . ($project['name'] ?? '') . '（' . ($project['period_from'] ?? '') . '〜' . ($project['period_to'] ?? '') . '）', ['bold' => true], ['spaceBefore' => 70, 'spaceAfter' => 40, 'indentation' => ['left' => 180]]);
                $this->addMultilineText($section, $project['description'] ?? '', [], ['indentation' => ['left' => 180], 'spaceAfter' => 40]);
                $this->addMultilineText($section, '【担当工程】' . "\n" . ($project['processes'] ?? ''), [], ['indentation' => ['left' => 180], 'spaceAfter' => 30]);
                $this->addMultilineText($section, '【使用技術・DB・OS】' . "\n" . ($project['technologies'] ?? ''), [], ['indentation' => ['left' => 180], 'spaceAfter' => 30]);
                $role = ($project['role'] ?? '') === 'その他' ? ($project['role_custom'] ?? '') : ($project['role'] ?? '');
                $this->addMultilineText($section, '【組織・役割】' . "\n" . $role . ' / ' . ($project['team'] ?? ''), [], ['indentation' => ['left' => 180], 'spaceAfter' => 70]);
            }
        }

        $this->addHeading($section, '資格');
        foreach ($data['certifications'] ?? [] as $certification) {
            if (($certification['name'] ?? '') !== '') {
                $this->addMultilineText($section, $this->formatMonth($certification['date'] ?? '') . '　' . ($certification['name'] ?? ''), ['name' => 'IPAexGothic', 'color' => '20252A']);
            }
        }
        $this->addHeading($section, '自己PR');
        $this->addMultilineText($section, $data['self_pr'] ?? '', ['name' => 'IPAexGothic', 'color' => '20252A']);
        if (($data['considerations'] ?? '') !== '') {
            $this->addHeading($section, '配慮事項');
            $this->addMultilineText($section, $data['considerations'], ['name' => 'IPAexGothic', 'color' => '20252A']);
        }
        $this->addMultilineText($section, '以上', ['name' => 'IPAexGothic', 'color' => '20252A'], ['alignment' => Jc::END, 'spaceBefore' => 180]);
        $this->addMultilineText($section, '是非、面接の機会をいただければと思います。何卒よろしくお願いいたします。', ['name' => 'IPAexGothic', 'color' => '20252A'], ['alignment' => Jc::CENTER]);

        $writer = IOFactory::createWriter($word, 'Word2007');
        ob_start();
        $writer->save('php://output');

        return (string) ob_get_clean();
    }

    private function addHeading(object $section, string $text): void
    {
        $section->addText($text, ['name' => 'IPAexGothic', 'bold' => true, 'size' => 12, 'color' => '20252A'], [
            'alignment' => Jc::START,
            'spaceBefore' => 180,
            'spaceAfter' => 80,
            'keepNext' => true,
            'borderBottomSize' => 8,
            'borderBottomColor' => '20252A',
        ]);
    }

    private function addMultilineText(object $container, string $text, array $fontStyle = [], array $paragraphStyle = []): void
    {
        $lines = preg_split('/\r\n|\r|\n/', $text) ?: [''];
        $run = $container->addTextRun($paragraphStyle);

        foreach ($lines as $index => $line) {
            if ($index > 0) {
                $run->addTextBreak();
            }

            $run->addText($line, array_merge(['name' => 'IPAexGothic', 'color' => '20252A'], $fontStyle));
        }
    }

    private function formatTextValues(mixed $value): mixed
    {
        if (is_string($value)) {
            return DocxTextFormatter::format($value);
        }

        if (is_array($value)) {
            return array_map(fn(mixed $item): mixed => $this->formatTextValues($item), $value);
        }

        return $value;
    }

    private function formatMonth(string $value): string
    {
        return preg_match('/^\d{4}-\d{2}$/', $value) === 1
            ? str_replace('-', '年', $value) . '月'
            : $value;
    }
}
