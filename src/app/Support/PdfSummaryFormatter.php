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
        $html = nl2br(htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'), false);

        return preg_replace_callback(
            '/IT(?: |&nbsp;)エンジニア|GitHub(?: |&nbsp;)Copilot|Python(?: |&nbsp;)を|約(?: |&nbsp;)\d+(?: |&nbsp;)(?:年間|年|月|日)/u',
            static fn(array $match): string => '<span class="nowrap">' . $match[0] . '</span>',
            $html,
        ) ?? $html;
    }

    public static function formatPreservingLineBreaks(string $text): string
    {
        $lines = preg_split('/\r\n|\r|\n/', $text) ?: [''];

        return implode("\n", array_map(static function (string $line): string {
            $line = preg_replace('/[ \t]+/u', ' ', $line) ?? $line;

            return $line;
        }, $lines));
    }
}
