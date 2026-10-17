<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Services\EventReport;
use Dompdf\Dompdf;
use Dompdf\Options as PdfOptions;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    /**
     * Classeur Excel : invités, régimes alimentaires et plan de table.
     */
    public function xlsx(Event $event): BinaryFileResponse
    {
        Gate::authorize('view', $event);

        $report = new EventReport($event);
        $path = tempnam(sys_get_temp_dir(), 'eventhub_');
        $bold = (new Style)->withFontBold(true);

        $writer = new XlsxWriter;
        $writer->openToFile($path);

        $writer->getCurrentSheet()->setName('Invités');
        $writer->addRow(Row::fromValuesWithStyle(EventReport::guestHeaders(), $bold));
        foreach ($report->guests() as $guest) {
            $writer->addRow(Row::fromValues($report->guestRow($guest)));
        }

        $writer->addNewSheetAndMakeItCurrent()->setName('Régimes');
        $writer->addRow(Row::fromValuesWithStyle(['Invité', 'Table', 'Régime alimentaire', 'Couverts concernés'], $bold));
        foreach ($report->dietary() as $row) {
            $writer->addRow(Row::fromValues(array_map(EventReport::safeCell(...), [$row['guest'], (string) $row['table'], $row['restriction'], $row['seats']])));
        }

        $writer->addNewSheetAndMakeItCurrent()->setName('Plan de table');
        $writer->addRow(Row::fromValuesWithStyle(['Table', 'Invité', 'Réponse', 'Couverts'], $bold));
        foreach ($report->seating() as $table) {
            foreach ($table['guests'] as $guest) {
                $seats = $guest->rsvp_status->value === 'accepted' ? 1 + $guest->companions_count : 1;
                $writer->addRow(Row::fromValues(array_map(EventReport::safeCell(...), [$table['table'], $guest->name, $guest->rsvp_status->label(), $seats])));
            }
        }

        $writer->close();

        return response()->download($path, $this->filename($event, 'invites', 'xlsx'), [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend();
    }

    /**
     * CSV (séparateur « ; » et BOM UTF-8 : s'ouvre directement dans Excel en français).
     */
    public function csv(Event $event): StreamedResponse
    {
        Gate::authorize('view', $event);

        $report = new EventReport($event);

        return response()->streamDownload(function () use ($report): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, EventReport::guestHeaders(), ';', '"', '');

            foreach ($report->guests() as $guest) {
                fputcsv($out, $report->guestRow($guest), ';', '"', '');
            }

            fclose($out);
        }, $this->filename($event, 'invites', 'csv'), ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * PDF pour le traiteur : couverts attendus et régimes alimentaires.
     */
    public function caterer(Event $event): Response
    {
        Gate::authorize('view', $event);

        $report = new EventReport($event);

        return $this->pdf('pdf.caterer', [
            'event' => $event,
            'rsvp' => $report->rsvp(),
            'dietary' => $report->dietary(),
            'dietarySeats' => $report->dietary()->sum('seats'),
        ], $this->filename($event, 'traiteur', 'pdf'));
    }

    /**
     * PDF du plan de table.
     */
    public function seating(Event $event): Response
    {
        Gate::authorize('view', $event);

        $report = new EventReport($event);

        return $this->pdf('pdf.seating', [
            'event' => $event,
            'tables' => $report->seating(),
            'rsvp' => $report->rsvp(),
        ], $this->filename($event, 'plan-de-table', 'pdf'));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function pdf(string $view, array $data, string $filename): Response
    {
        $options = new PdfOptions;
        $options->set('isRemoteEnabled', false);
        $options->set('isPhpEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('chroot', resource_path('views'));

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml(view($view, $data)->render(), 'UTF-8');
        $dompdf->setPaper('A4');
        $dompdf->render();

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    private function filename(Event $event, string $kind, string $extension): string
    {
        return "{$event->slug}-{$kind}.{$extension}";
    }
}
