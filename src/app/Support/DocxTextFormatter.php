<?php

namespace App\Support;

final class DocxTextFormatter
{
    public static function format(string $text): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);

        return $text;
    }
}
