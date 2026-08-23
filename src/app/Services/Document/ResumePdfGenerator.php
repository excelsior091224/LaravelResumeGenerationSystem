<?php

namespace App\Services\Document;

use App\ResumeData;
use App\Support\PdfSummaryFormatter;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\File;

final class ResumePdfGenerator
{
    public function generate(ResumeData $resume): string
    {
        $data = $resume->toArray();
        $data['summary_html'] = PdfSummaryFormatter::toHtml($data['summary'] ?? '');
        $data['self_pr_html'] = PdfSummaryFormatter::toHtml($data['self_pr'] ?? '');

        File::ensureDirectoryExists(storage_path('fonts'));
        File::ensureDirectoryExists(storage_path('app/dompdf'));

        $options = new Options();
        $options->set('defaultFont', 'IPAexGothic');
        $options->setChroot(base_path());
        $options->setFontDir(storage_path('fonts'));
        $options->setFontCache(storage_path('fonts'));
        $options->setTempDir(storage_path('app/dompdf'));
        $options->set('isRemoteEnabled', false);
        $options->set('isPhpEnabled', false);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml(view('resume.document', ['resume' => $data])->render(), 'UTF-8');
        $dompdf->setPaper('A4');
        $dompdf->render();

        return $dompdf->output();
    }
}
