<?php

namespace App\Services;

use App\Models\NewsModel;
use App\Models\NewsletterSubscriberModel;
use App\Models\EngagementEventModel;

/**
 * Export data to CSV, Excel, TXT and PDF formats.
 */
class ExportService
{
    /**
     * Export news to CSV string.
     */
    public function newsToCsv(array $filters = []): string
    {
        $news = $this->getNewsData($filters);

        $output = fopen('php://temp', 'r+');
        // BOM for Excel UTF-8
        fwrite($output, "\xEF\xBB\xBF");
        fputcsv($output, ['ID', 'Title', 'Slug', 'Status', 'Author', 'Featured', 'Created At', 'Published At']);

        foreach ($news as $item) {
            fputcsv($output, [
                $item->id,
                $item->title,
                $item->slug,
                $item->status,
                $item->author_id ?? '',
                $item->featured ? 'Yes' : 'No',
                $item->created_at,
                $item->published_at ?? $item->scheduled_at ?? '',
            ]);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }

    /**
     * Export news to Excel (XLSX) using PhpSpreadsheet.
     */
    public function newsToExcel(array $filters = []): string
    {
        $news = $this->getNewsData($filters);

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('News');

        $headers = ['ID', 'Title', 'Slug', 'Status', 'Featured', 'Created At', 'Published At'];
        foreach ($headers as $col => $header) {
            $sheet->setCellValue([$col + 1, 1], $header);
        }

        $row = 2;
        foreach ($news as $item) {
            $sheet->setCellValue([1, $row], $item->id);
            $sheet->setCellValue([2, $row], $item->title);
            $sheet->setCellValue([3, $row], $item->slug);
            $sheet->setCellValue([4, $row], $item->status);
            $sheet->setCellValue([5, $row], $item->featured ? 'Yes' : 'No');
            $sheet->setCellValue([6, $row], $item->created_at);
            $sheet->setCellValue([7, $row], $item->published_at ?? $item->scheduled_at ?? '');
            $row++;
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'netxus_export_');
        $writer   = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($tempFile);

        $content = file_get_contents($tempFile);
        unlink($tempFile);

        return $content;
    }

    /**
     * Export news to plain TXT.
     */
    public function newsToTxt(array $filters = []): string
    {
        $news = $this->getNewsData($filters);
        $lines = [];

        $lines[] = str_pad('NETXUS NEWS EXPORT', 60, ' ', STR_PAD_BOTH);
        $lines[] = str_repeat('=', 60);
        $lines[] = 'Generated: ' . date('Y-m-d H:i:s');
        $lines[] = 'Total: ' . count($news);
        $lines[] = str_repeat('-', 60);

        foreach ($news as $item) {
            $lines[] = '';
            $lines[] = "Title:     {$item->title}";
            $lines[] = "Status:    {$item->status}";
            $lines[] = "Featured:  " . ($item->featured ? 'Yes' : 'No');
            $lines[] = "Created:   {$item->created_at}";
            $lines[] = "Published: " . ($item->published_at ?? $item->scheduled_at ?? 'N/A');
            $lines[] = str_repeat('-', 40);
        }

        return implode("\n", $lines);
    }

    /**
     * Export news to PDF using DomPDF.
     */
    public function newsToPdf(array $filters = []): string
    {
        $news = $this->getNewsData($filters);

        $html = '<html><head><meta charset="utf-8">';
        $html .= '<style>body{font-family:Arial,sans-serif;font-size:12px} table{width:100%;border-collapse:collapse;margin-top:10px} th,td{border:1px solid #ccc;padding:6px;text-align:left} th{background:#1A2A5A;color:white}</style>';
        $html .= '</head><body>';
        $html .= '<h1 style="color:#1A2A5A">Netxus — News Report</h1>';
        $html .= '<p>Generated: ' . date('Y-m-d H:i:s') . ' | Total: ' . count($news) . '</p>';
        $html .= '<table><tr><th>Title</th><th>Status</th><th>Featured</th><th>Created</th><th>Published</th></tr>';

        foreach ($news as $item) {
            $html .= '<tr>';
            $html .= '<td>' . htmlspecialchars($item->title, ENT_QUOTES, 'UTF-8') . '</td>';
            $html .= '<td>' . $item->status . '</td>';
            $html .= '<td>' . ($item->featured ? 'Yes' : 'No') . '</td>';
            $html .= '<td>' . substr($item->created_at ?? '', 0, 10) . '</td>';
            $html .= '<td>' . substr((string) ($item->published_at ?? $item->scheduled_at ?? ''), 0, 10) . '</td>';
            $html .= '</tr>';
        }

        $html .= '</table></body></html>';

        $dompdf = new \Dompdf\Dompdf(['isRemoteEnabled' => false]);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        return $dompdf->output();
    }

    /**
     * Export subscribers to CSV.
     */
    public function subscribersToCsv(?array $subs = null): string
    {
        if ($subs === null) {
            $model = new NewsletterSubscriberModel();
            $subs  = $model->orderBy('created_at', 'DESC')->findAll();
        }

        $output = fopen('php://temp', 'r+');
        fwrite($output, "\xEF\xBB\xBF");
        fputcsv($output, ['ID', 'Email', 'Status', 'Created At', 'Updated At']);

        foreach ($subs as $sub) {
            fputcsv($output, [
                $sub->id,
                $sub->email,
                $sub->status,
                $sub->created_at ?? '',
                $sub->updated_at ?? '',
            ]);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }

    /**
     * Export engagement metrics to CSV.
     */
    public function metricsToCsv(string $from, string $to): string
    {
        $eventModel = new EngagementEventModel();
        $daily      = $eventModel->getDailySeries($from, $to);

        $output = fopen('php://temp', 'r+');
        fwrite($output, "\xEF\xBB\xBF");
        fputcsv($output, ['Date', 'Event Type', 'Count']);

        foreach ($daily as $row) {
            fputcsv($output, [$row['date'], $row['event_type'], $row['count']]);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }

    private function getNewsData(array $filters = []): array
    {
        $model   = new NewsModel();
        $builder = $model->where('deleted_at', null);

        if (!empty($filters['status'])) {
            $builder->where('status', $filters['status']);
        }

        return $builder->orderBy('created_at', 'DESC')->findAll();
    }
}
