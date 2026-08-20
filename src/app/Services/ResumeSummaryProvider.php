<?php

namespace App\Services;

interface ResumeSummaryProvider
{
    public function summarize(array $careerData): string;
}
