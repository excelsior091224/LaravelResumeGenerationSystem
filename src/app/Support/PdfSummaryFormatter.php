<?php

namespace App\Support;

final class PdfSummaryFormatter
{
    public static function format(string $text): string
    {
        $normalized = preg_replace('/\s+/u', ' ', trim($text)) ?? trim($text);

        return str_replace(' ', "\u{00A0}", $normalized);
    }
}
