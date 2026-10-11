<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReportRequest;
use App\Reports\AdminReport;
use App\Reports\AdminReportCsv;
use App\Reports\AdminReportXlsx;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Laporan admin (hanya baca): penjualan, test drive, dan stok menipis untuk satu periode.
 * Export: Excel (.xlsx, sudah rapi) atau CSV (data mentah) dengan periode yang sama.
 */
class ReportController extends Controller
{
    public function index(ReportRequest $request): View
    {
        return view('admin.reports.index', ['report' => new AdminReport($request->period())]);
    }

    public function export(ReportRequest $request): StreamedResponse
    {
        $csv = new AdminReportCsv(new AdminReport($request->period()));
        // Query dijalankan sebelum streaming agar error database tidak memotong file di tengah jalan.
        $rows = $csv->rows();

        return response()->streamDownload(function () use ($csv, $rows) {
            $handle = fopen('php://output', 'w');
            $csv->write($handle, $rows);
            fclose($handle);
        }, $csv->filename(), ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function exportExcel(ReportRequest $request): StreamedResponse
    {
        $excel = new AdminReportXlsx(new AdminReport($request->period()));
        // Lembar kerja disusun sebelum streaming agar error database tidak menghasilkan file rusak.
        $spreadsheet = $excel->spreadsheet();

        return response()->streamDownload(function () use ($excel, $spreadsheet) {
            $excel->save('php://output', $spreadsheet);
        }, $excel->filename(), ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }
}
