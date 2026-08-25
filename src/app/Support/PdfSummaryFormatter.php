<?php

namespace App\Support;

final class PdfSummaryFormatter
{
    public static function format(string $text): string
    {
        return self::formatPreservingLineBreaks($text);
    }

    public static function toHtml(string $text): string
    {
        $text = self::formatPreservingLineBreaks($text);

        return nl2br(htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'), false);
    }

    public static function formatPreservingLineBreaks(string $text): string
    {
        $lines = preg_split('/\r\n|\r|\n/', $text) ?: [''];

        $formatted = implode("\n", array_map(static function (string $line): string {
            $line = preg_replace('/[ \t]+/u', ' ', $line) ?? $line;

            return rtrim($line);
        }, $lines));

        return preg_replace("/\n{3,}/", "\n\n", trim($formatted)) ?? $formatted;
    }
}
