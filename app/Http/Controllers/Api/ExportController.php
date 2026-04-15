<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\User;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Laravel\Sanctum\PersonalAccessToken;

class ExportController extends Controller
{
    private function verifyToken(Request $request)
    {
        $token = $request->query('token');
        if (!$token) return null;

        $accessToken = PersonalAccessToken::findToken($token);
        if (!$accessToken || !$accessToken->tokenable) return null;

        return $accessToken->tokenable;
    }

    public function attendanceExport(Request $request)
    {
        $user = $this->verifyToken($request);
        if (!$user || !in_array($user->role, ['admin', 'spv', 'hr'])) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $status = $request->query('status');
        $employeeId = $request->query('employee_id');

        $query = Attendance::with(['user', 'shift'])
            ->whereBetween('date', [$startDate, $endDate]);

        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        if ($employeeId && $employeeId !== '') {
            $query->where('user_id', $employeeId);
        }

        $records = $query->orderBy('date', 'desc')->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Laporan Absensi');

        // Header
        $headers = ['Tanggal', 'Nama Karyawan', 'Shift', 'Jam Masuk', 'Jam Keluar', 'Durasi', 'Status'];
        foreach ($headers as $key => $header) {
            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($key + 1);
            $sheet->setCellValue($col . '1', $header);
        }

        // Style Header
        $sheet->getStyle('A1:G1')->getFont()->setBold(true);
        $sheet->getStyle('A1:G1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFE0E0E0');

        // Data
        $row = 2;
        foreach ($records as $reg) {
            $sheet->setCellValue('A' . $row, $reg->date->format('Y-m-d'));
            $sheet->setCellValue('B' . $row, $reg->user->name);
            $sheet->setCellValue('C' . $row, $reg->shift ? $reg->shift->shift_name : '-');
            $sheet->setCellValue('D' . $row, $reg->check_in_time ? $reg->check_in_time->format('H:i:s') : '-');
            $sheet->setCellValue('E' . $row, $reg->check_out_time ? $reg->check_out_time->format('H:i:s') : '-');
            
            // Calc duration
            $duration = '-';
            if ($reg->check_in_time && $reg->check_out_time) {
                $start = $reg->check_in_time;
                $end = $reg->check_out_time;
                $diff = $start->diff($end);
                $duration = $diff->format('%H:%I:%S');
            }
            $sheet->setCellValue('F' . $row, $duration);
            $sheet->setCellValue('G' . $row, ucfirst($reg->status));
            $row++;
        }

        // Auto size columns
        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $fileName = "Laporan_Absensi_{$startDate}_sampai_{$endDate}.xlsx";
        $writer = new Xlsx($spreadsheet);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $fileName . '"');
        header('Cache-Control: max-age=0');
        
        $writer->save('php://output');
        exit;
    }

    public function leaveExport(Request $request)
    {
        $user = $this->verifyToken($request);
        if (!$user || !in_array($user->role, ['admin', 'spv', 'hr'])) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $month = $request->query('month'); // YYYY-MM
        $status = $request->query('status');
        $type = $request->query('type');
        $employeeId = $request->query('user_id');

        $query = LeaveRequest::with('user');
        
        if ($month) {
            $carbonMonth = Carbon::parse($month . '-01');
            $query->whereYear('start_date', $carbonMonth->year)
                  ->whereMonth('start_date', $carbonMonth->month);
        }

        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        if ($type && $type !== 'all') {
            $query->where('type', $type);
        }

        if ($employeeId && $employeeId !== '') {
            $query->where('user_id', $employeeId);
        }

        $records = $query->orderBy('created_at', 'desc')->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Laporan Izin Cuti');

        // Header
        $headers = ['Tgl Pengajuan', 'Nama Karyawan', 'Tipe', 'Mulai', 'Selesai', 'Hari', 'Alasan', 'Status'];
        foreach ($headers as $key => $header) {
            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($key + 1);
            $sheet->setCellValue($col . '1', $header);
        }

        $sheet->getStyle('A1:H1')->getFont()->setBold(true);

        $row = 2;
        foreach ($records as $reg) {
            $sheet->setCellValue('A' . $row, $reg->created_at->format('Y-m-d'));
            $sheet->setCellValue('B' . $row, $reg->user->name);
            $sheet->setCellValue('C' . $row, ucfirst($reg->type));
            $sheet->setCellValue('D' . $row, $reg->start_date->format('Y-m-d'));
            $sheet->setCellValue('E' . $row, $reg->end_date->format('Y-m-d'));
            $sheet->setCellValue('F' . $row, $reg->work_days);
            $sheet->setCellValue('G' . $row, $reg->reason);
            $sheet->setCellValue('H' . $row, ucfirst($reg->status));
            $row++;
        }


        foreach (range('A', 'H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $fileName = "Laporan_Izin_Cuti_{$month}.xlsx";
        $writer = new Xlsx($spreadsheet);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $fileName . '"');
        header('Cache-Control: max-age=0');
        
        $writer->save('php://output');
        exit;
    }
}
