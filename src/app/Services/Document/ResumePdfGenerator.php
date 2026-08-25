<?php

namespace App\Services\Document;

use App\ResumeData;
use App\Support\PdfSummaryFormatter;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

final class ResumePdfGenerator
{
    public function generate(ResumeData $resume): string
    {
        $data = $resume->toArray();
        $data['summary_html'] = PdfSummaryFormatter::toHtml($data['summary'] ?? '');
        $data['self_pr_html'] = PdfSummaryFormatter::toHtml($data['self_pr'] ?? '');
        $data['considerations_html'] = PdfSummaryFormatter::toHtml($data['considerations'] ?? '');

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

        $html = view('resume.document', ['resume' => $data])->render();

        if (is_executable('/usr/bin/chromium')) {
            return $this->generateWithChromium($html);
        }

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4');
        $dompdf->render();

        return $dompdf->output();
    }

    private function generateWithChromium(string $html): string
    {
        $directory = storage_path('app/dompdf');
        $htmlPath = tempnam($directory, 'resume-');
        $htmlPathWithExtension = $htmlPath . '.html';
        rename($htmlPath, $htmlPathWithExtension);
        $htmlPath = $htmlPathWithExtension;
        $pdfPath = tempnam($directory, 'resume-') . '.pdf';

        try {
            File::put($htmlPath, $html);
            $process = new Process([
                '/usr/bin/chromium',
                '--headless=new',
                '--no-sandbox',
                '--disable-gpu',
                '--no-pdf-header-footer',
                '--allow-file-access-from-files',
                '--print-to-pdf=' . $pdfPath,
                'file://' . $htmlPath,
            ]);
            $process->setTimeout(120);
            $process->mustRun();

            return File::get($pdfPath);
        } finally {
            File::delete([$htmlPath, $pdfPath]);
        }
    }
}
