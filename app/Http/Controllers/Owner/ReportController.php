<?php

namespace App\Http\Controllers\Owner;

use App\Actions\BuildOperationalReportAction;
use App\Enums\PermissionName;
use App\Http\Controllers\Controller;
use App\Support\TenantContext;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\View\View;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request, TenantContext $tenant, BuildOperationalReportAction $reports): View
    {
        [$business, $branch, $section, $from, $to] = $this->context($request, $tenant);

        return view('owner.reports.index', ['business' => $business, 'branch' => $branch, 'section' => $section, 'from' => $from, 'to' => $to, 'report' => $reports->execute($branch, $section, $from, $to)]);
    }

    public function export(Request $request, TenantContext $tenant, BuildOperationalReportAction $reports): StreamedResponse
    {
        [, $branch, $section, $from, $to] = $this->context($request, $tenant);
        $report = $reports->execute($branch, $section, $from, $to);

        return response()->streamDownload(function () use ($report) {
            $stream = fopen('php://output', 'w');
            fputcsv($stream, ['Group', 'Amount']);
            foreach ($report['rows'] as $row) {
                fputcsv($stream, [$row->label, $row->amount]);
            }
            fclose($stream);
        }, 'hardflow-'.$section.'-report-'.$from->format('Ymd').'-'.$to->format('Ymd').'.csv', ['Content-Type' => 'text/csv']);
    }

    public function printable(Request $request, TenantContext $tenant, BuildOperationalReportAction $reports): View
    {
        [$business, $branch, $section, $from, $to] = $this->context($request, $tenant);

        return view('owner.reports.print', [
            'business' => $business,
            'branch' => $branch,
            'section' => $section,
            'from' => $from,
            'to' => $to,
            'report' => $reports->execute($branch, $section, $from, $to),
        ]);
    }

    public function pdf(Request $request, TenantContext $tenant, BuildOperationalReportAction $reports): Response
    {
        [$business, $branch, $section, $from, $to] = $this->context($request, $tenant);
        $report = $reports->execute($branch, $section, $from, $to);
        $filename = $this->filename($section, $from, $to, 'pdf');
        $pdf = true;

        return Pdf::loadView('owner.reports.print', compact('business', 'branch', 'section', 'from', 'to', 'report'))
            ->setPaper('a4')
            ->download($filename);
    }

    public function spreadsheet(Request $request, TenantContext $tenant, BuildOperationalReportAction $reports): BinaryFileResponse
    {
        [$business, $branch, $section, $from, $to] = $this->context($request, $tenant);
        $report = $reports->execute($branch, $section, $from, $to);
        $path = tempnam(sys_get_temp_dir(), 'hardflow-report-');
        abort_if($path === false, 500, 'Unable to create report file.');

        $writer = new Writer;
        $writer->openToFile($path);
        $writer->addRow(Row::fromValues([$business->name, $branch->name]));
        $writer->addRow(Row::fromValues([$report['title'], $from->toDateString().' to '.$to->toDateString()]));
        $writer->addRow(Row::fromValues([]));
        $writer->addRow(Row::fromValues(['Group', 'Value']));
        foreach ($report['rows'] as $row) {
            $writer->addRow(Row::fromValues([$row->label, (float) $row->amount]));
        }
        $writer->close();

        return response()->download(
            $path,
            $this->filename($section, $from, $to, 'xlsx'),
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        )->deleteFileAfterSend(true);
    }

    private function context(Request $request, TenantContext $tenant): array
    {
        $business = $tenant->businessOrFail();
        abort_unless($request->user()->hasPermissionInBusiness(PermissionName::ReportsView, $business), 403);
        $branch = $tenant->branch();
        abort_if($branch === null, 422, 'Select a branch first.');
        $sections = ['sales', 'profit', 'inventory', 'expenses', 'purchases', 'debt', 'payments', 'movements', 'low-stock', 'fast-moving', 'slow-moving', 'salespeople', 'supplier-balances', 'outstanding-purchases'];
        $section = in_array($request->string('section')->toString(), $sections, true) ? $request->string('section')->toString() : 'sales';
        if ($section === 'profit') {
            abort_unless($request->user()->hasPermissionInBusiness(PermissionName::ReportsProfit, $business), 403);
        }
        $from = $request->date('from') ?? now()->subDays(29)->startOfDay();
        $to = $request->date('to') ?? now()->endOfDay();
        abort_if($from->greaterThan($to) || $from->diffInDays($to) > 366, 422, 'Choose a valid reporting period of up to 366 days.');

        return [$business, $branch, $section, $from, $to];
    }

    private function filename(string $section, CarbonInterface $from, CarbonInterface $to, string $extension): string
    {
        return 'hardflow-'.$section.'-report-'.$from->format('Ymd').'-'.$to->format('Ymd').'.'.$extension;
    }
}
