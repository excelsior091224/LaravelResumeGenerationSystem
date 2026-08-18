<?php

namespace App\Services\Document;

use App\ResumeData;
use App\Support\PdfSummaryFormatter;
use Dompdf\Dompdf;
use Dompdf\Options;

final class ResumePdfGenerator
{
    public function generate(ResumeData $resume): string
    {
        $data = $resume->toArray();
        $data['summary'] = PdfSummaryFormatter::format($data['summary'] ?? '');

        $options = new Options();
        $options->set('defaultFont', 'IPAexGothic');
        $options->setChroot(base_path());
        $options->set('isRemoteEnabled', false);
        $options->set('isPhpEnabled', false);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml(view('resume.document', ['resume' => $data])->render(), 'UTF-8');
        $dompdf->setPaper('A4');
        $dompdf->render();

        return $dompdf->output();
    }
}
