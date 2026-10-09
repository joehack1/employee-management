<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveApplication;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $year = $request->input('year', Carbon::now()->year);
        $departmentId = $request->input('department_id');
        $leaveTypeId = $request->input('leave_type_id');
        $reportType = $request->input('report_type', 'utilization');

        $departments = Department::where('is_active', true)->get();
        $leaveTypes = LeaveType::all();

        // 1. Leave Applications query
        $appQuery = LeaveApplication::with(['employee.department', 'leaveType'])
            ->whereYear('start_date', $year);

        if ($departmentId) {
            $appQuery->whereHas('employee', fn($q) => $q->where('department_id', $departmentId));
        }

        if ($leaveTypeId) {
            $appQuery->where('leave_type_id', $leaveTypeId);
        }

        $applications = $appQuery->orderBy('start_date', 'desc')->get();

        // 2. Department aggregation
        $deptStats = Department::with(['employees.leaveApplications' => function($q) use ($year) {
            $q->where('status', 'approved')->whereYear('start_date', $year);
        }])->get()->map(function($dept) {
            $totalDays = 0;
            $approvedCount = 0;
            foreach ($dept->employees as $emp) {
                $approvedCount += $emp->leaveApplications->count();
                $totalDays += $emp->leaveApplications->sum('total_days');
            }
            return [
                'name' => $dept->name,
                'code' => $dept->code,
                'employee_count' => $dept->employees->count(),
                'application_count' => $approvedCount,
                'days_taken' => $totalDays,
            ];
        });

        // 3. Balances query
        $balQuery = LeaveBalance::with(['employee.department', 'leaveType'])
            ->where('year', $year);

        if ($departmentId) {
            $balQuery->whereHas('employee', fn($q) => $q->where('department_id', $departmentId));
        }

        if ($leaveTypeId) {
            $balQuery->where('leave_type_id', $leaveTypeId);
        }

        $balances = $balQuery->get();

        return view('reports.index', compact(
            'year',
            'reportType',
            'departments',
            'leaveTypes',
            'applications',
            'deptStats',
            'balances',
            'departmentId',
            'leaveTypeId'
        ));
    }

    /**
     * Download a formatted Excel workbook with one worksheet per leave type.
     */
    public function exportExcel(Request $request)
    {
        $request->validate([
            'year' => ['sometimes', 'integer', 'between:2000,2100'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'leave_type_id' => ['nullable', 'integer', 'exists:leave_types,id'],
            'report_type' => ['nullable', 'in:annual,applications,balances'],
        ]);
        $year = (int) $request->input('year', Carbon::now()->year);
        $type = $request->input('report_type') === 'balances' ? 'balances' : 'applications';
        $departmentId = $request->input('department_id');
        $leaveTypeId = $request->input('leave_type_id');
        $leaveTypesQuery = LeaveType::orderBy('name');
        if ($leaveTypeId) {
            $leaveTypesQuery->whereKey($leaveTypeId);
        }
        $leaveTypesToExport = $leaveTypesQuery->get();
        $departmentName = $departmentId
            ? (Department::find($departmentId)?->name ?? 'Selected Department')
            : 'All Departments';
        $isBalances = $type === 'balances';
        $title = $isBalances ? 'Leave Balances' : 'Leave Applications';
        $headers = $isBalances
            ? ['Employee No.', 'Employee Name', 'Department', 'Leave Type', 'Entitled Days', 'Carry Forward', 'Adjustments', 'Used Days', 'Pending Days', 'Available Days']
            : ['Application No.', 'Employee Name', 'Department', 'Leave Type', 'Start Date', 'End Date', 'Working Days', 'Half Day', 'Emergency', 'Status', 'Submitted At', 'Reason'];
        $widths = $isBalances
            ? [16, 27, 24, 19, 16, 16, 16, 14, 15, 17]
            : [18, 27, 24, 18, 16, 16, 16, 18, 14, 19, 22, 48];
        $lastColumn = Coordinate::stringFromColumnIndex(count($headers));

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setCreator('LeaveFlow')
            ->setTitle("{$title} - {$year}")
            ->setSubject("{$title} for {$year}");

        foreach ($leaveTypesToExport as $sheetIndex => $leaveType) {
        $sheet = $sheetIndex === 0 ? $spreadsheet->getActiveSheet() : $spreadsheet->createSheet();
        $sheetName = str_replace(['\\', '/', '?', '*', '[', ']', ':'], ' ', $leaveType->name);
        $sheet->setTitle(substr(sprintf('%02d %s', $sheetIndex + 1, $sheetName), 0, 31));
        $sheetTitle = $leaveType->name . ' — ' . $title;
        $sheet->setShowGridlines(false);
        $sheet->mergeCells("A1:{$lastColumn}1");
        $sheet->setCellValueExplicit('A1', "LEAVEFLOW  |  {$sheetTitle}", DataType::TYPE_STRING);
        $sheet->mergeCells("A2:{$lastColumn}2");
        $sheet->setCellValueExplicit('A2', "Reporting year: {$year}     Leave type: {$leaveType->name}     Department: {$departmentName}     Generated: " . Carbon::now()->format('d M Y, H:i'), DataType::TYPE_STRING);

        foreach ($headers as $index => $header) {
            $sheet->setCellValueExplicit(Coordinate::stringFromColumnIndex($index + 1) . '4', $header, DataType::TYPE_STRING);
        }

        $writeRow = function (array $values, int $rowNumber, array $numericColumns = [], array $dateColumns = []) use ($sheet): void {
            foreach ($values as $index => $value) {
                $cell = Coordinate::stringFromColumnIndex($index + 1) . $rowNumber;
                if ($value === null || $value === '') {
                    $sheet->setCellValue($cell, null);
                } elseif (in_array($index, $dateColumns, true)) {
                    $dateValue = $value instanceof Carbon ? $value->toDateTime() : Carbon::parse($value)->toDateTime();
                    $sheet->setCellValue($cell, ExcelDate::PHPToExcel($dateValue));
                } elseif (in_array($index, $numericColumns, true)) {
                    $sheet->setCellValue($cell, (float) $value);
                } else {
                    $sheet->setCellValueExplicit($cell, (string) $value, DataType::TYPE_STRING);
                }
            }
        };

        $rowNumber = 5;
        if ($isBalances) {
            $query = LeaveBalance::with(['employee.department', 'leaveType'])
                ->where('year', $year)
                ->where('leave_type_id', $leaveType->id);
            if ($departmentId) {
                $query->whereHas('employee', fn ($employeeQuery) => $employeeQuery->where('department_id', $departmentId));
            }

            foreach ($query->orderBy('employee_id')->get() as $balance) {
                $writeRow([
                    $balance->employee->employee_number ?? '',
                    $balance->employee->full_name ?? '',
                    $balance->employee->department->name ?? 'N/A',
                    $balance->leaveType->name ?? '',
                    $balance->entitled_days,
                    $balance->carried_forward_days,
                    $balance->manual_adjustment_days,
                    $balance->used_days,
                    $balance->pending_days,
                    $balance->available_days,
                ], $rowNumber, [4, 5, 6, 7, 8, 9]);
                if ($rowNumber % 2 === 0) {
                    $sheet->getStyle("A{$rowNumber}:{$lastColumn}{$rowNumber}")->getFill()
                        ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F2F8F8');
                }
                $rowNumber++;
            }
        } else {
            $query = LeaveApplication::with(['employee.department', 'leaveType'])
                ->whereYear('start_date', $year)
                ->where('leave_type_id', $leaveType->id);
            if ($departmentId) {
                $query->whereHas('employee', fn ($employeeQuery) => $employeeQuery->where('department_id', $departmentId));
            }

            foreach ($query->orderBy('start_date')->orderBy('id')->get() as $application) {
                $writeRow([
                    $application->application_number,
                    $application->employee->full_name ?? '',
                    $application->employee->department->name ?? 'N/A',
                    $application->leaveType->name ?? '',
                    $application->start_date,
                    $application->end_date,
                    $application->total_days,
                    $application->is_half_day ? 'Yes (' . ucfirst($application->half_day_type ?? '') . ')' : 'No',
                    $application->is_emergency ? 'Yes' : 'No',
                    ucfirst(str_replace('_', ' ', $application->status)),
                    $application->submitted_at,
                    $application->reason,
                ], $rowNumber, [6], [4, 5, 10]);

                if ($rowNumber % 2 === 0) {
                    $sheet->getStyle("A{$rowNumber}:{$lastColumn}{$rowNumber}")->getFill()
                        ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F2F8F8');
                }
                $statusColor = match ($application->status) {
                    'approved' => 'E2F3EC',
                    'rejected', 'cancelled' => 'FCE8E8',
                    default => 'FFF3D6',
                };
                $sheet->getStyle("J{$rowNumber}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($statusColor);
                if ($application->is_emergency) {
                    $sheet->getStyle("I{$rowNumber}")->getFont()->getColor()->setRGB('A01E22');
                    $sheet->getStyle("I{$rowNumber}")->getFont()->setBold(true);
                }
                $rowNumber++;
            }
        }

        $lastDataRow = $rowNumber - 1;
        if ($lastDataRow < 5) {
            $sheet->mergeCells("A5:{$lastColumn}5");
            $sheet->setCellValueExplicit('A5', 'No records found for the selected filters.', DataType::TYPE_STRING);
            $lastDataRow = 5;
        } else {
            $sheet->getStyle("A5:{$lastColumn}{$lastDataRow}")->getFont()->setName('Aptos')->setSize(10);
            $sheet->getStyle("A5:{$lastColumn}{$lastDataRow}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle("A5:{$lastColumn}{$lastDataRow}")->getBorders()->getBottom()
                ->setBorderStyle(Border::BORDER_HAIR)->getColor()->setRGB('DCE8EA');
        }

        $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
            'font' => ['name' => 'Aptos Display', 'bold' => true, 'size' => 18, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '173B43']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getStyle("A2:{$lastColumn}2")->applyFromArray([
            'font' => ['name' => 'Aptos', 'size' => 10, 'color' => ['rgb' => '536B70']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EAF2F3']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getStyle("A4:{$lastColumn}4")->applyFromArray([
            'font' => ['name' => 'Aptos', 'bold' => true, 'size' => 10, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1D9692']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
        ]);

        if ($rowNumber > 5) {
            $dateColumns = $isBalances ? [] : ['E', 'F', 'K'];
            foreach ($dateColumns as $column) {
                $format = $column === 'K' ? 'dd mmm yyyy hh:mm' : 'dd mmm yyyy';
                $sheet->getStyle("{$column}5:{$column}{$lastDataRow}")->getNumberFormat()->setFormatCode($format);
            }
            $numericColumns = $isBalances ? ['E', 'F', 'G', 'H', 'I', 'J'] : ['G'];
            foreach ($numericColumns as $column) {
                $sheet->getStyle("{$column}5:{$column}{$lastDataRow}")->getNumberFormat()->setFormatCode('#,##0.0;[Red]-#,##0.0;0.0');
                $sheet->getStyle("{$column}5:{$column}{$lastDataRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            }
        }

        foreach ($widths as $index => $width) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($index + 1))->setWidth($width);
        }
        $sheet->getRowDimension(1)->setRowHeight(34);
        $sheet->getRowDimension(2)->setRowHeight(24);
        $sheet->getRowDimension(4)->setRowHeight(34);
        $sheet->getStyle("A4:{$lastColumn}{$lastDataRow}")->getAlignment()->setWrapText(true);
        $sheet->getStyle("A5:{$lastColumn}{$lastDataRow}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->freezePane('A5');
        $sheet->setAutoFilter("A4:{$lastColumn}{$lastDataRow}");
        $sheet->getSheetView()->setZoomScale(90);
        $sheet->getPageSetup()->setOrientation('landscape')->setFitToWidth(1)->setFitToHeight(0);
        $sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(1, 4);
        $sheet->getPageMargins()->setTop(0.5)->setBottom(0.5)->setLeft(0.25)->setRight(0.25);
        }

        $filename = ($isBalances ? 'leave_balances' : 'leave_applications') . "_{$year}.xlsx";
        return response()->streamDownload(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0, no-cache, must-revalidate',
        ]);
    }

    /**
     * CSV Export for reports.
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $request->validate([
            'year' => ['sometimes', 'integer', 'between:2000,2100'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'leave_type_id' => ['nullable', 'integer', 'exists:leave_types,id'],
            'report_type' => ['nullable', 'in:annual,applications,balances'],
        ]);
        $year = (int) $request->input('year', Carbon::now()->year);
        $type = $request->input('report_type', 'annual');

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"" . ($type === 'balances' ? 'leave_balances' : 'leave_applications') . "_{$year}.csv\"",
        ];

        return response()->stream(function () use ($year, $type, $request) {
            $handle = fopen('php://output', 'w');

            if ($type === 'balances') {
                fputcsv($handle, ['Employee Number', 'Employee Name', 'Department', 'Leave Type', 'Year', 'Entitled', 'Carried Forward', 'Adjustments', 'Used', 'Pending', 'Available Balance']);

                $query = LeaveBalance::with(['employee.department', 'leaveType'])
                    ->where('year', $year);
                if ($request->filled('department_id')) {
                    $query->whereHas('employee', fn($q) => $q->where('department_id', $request->department_id));
                }
                if ($request->filled('leave_type_id')) {
                    $query->where('leave_type_id', $request->leave_type_id);
                }

                foreach ($query->cursor() as $b) {
                    fputcsv($handle, [
                        $b->employee->employee_number ?? '',
                        $b->employee->full_name ?? '',
                        $b->employee->department->name ?? 'N/A',
                        $b->leaveType->name ?? '',
                        $b->year,
                        $b->entitled_days,
                        $b->carried_forward_days,
                        $b->manual_adjustment_days,
                        $b->used_days,
                        $b->pending_days,
                        $b->available_days,
                    ]);
                }
            } else {
                // Applications export
                fputcsv($handle, ['Application #', 'Employee', 'Department', 'Leave Type', 'Start Date', 'End Date', 'Days', 'Half Day', 'Emergency', 'Status', 'Submitted At', 'Reason']);

                $query = LeaveApplication::with(['employee.department', 'leaveType'])
                    ->whereYear('start_date', $year);
                if ($request->filled('department_id')) {
                    $query->whereHas('employee', fn($q) => $q->where('department_id', $request->department_id));
                }
                if ($request->filled('leave_type_id')) {
                    $query->where('leave_type_id', $request->leave_type_id);
                }

                foreach ($query->cursor() as $app) {
                    fputcsv($handle, [
                        $app->application_number,
                        $app->employee->full_name ?? '',
                        $app->employee->department->name ?? '',
                        $app->leaveType->name ?? '',
                        $app->start_date->format('Y-m-d'),
                        $app->end_date->format('Y-m-d'),
                        $app->total_days,
                        $app->is_half_day ? 'Yes (' . $app->half_day_type . ')' : 'No',
                        $app->is_emergency ? 'Yes' : 'No',
                        $app->status,
                        $app->submitted_at ? $app->submitted_at->format('Y-m-d H:i') : '',
                        $app->reason,
                    ]);
                }
            }

            fclose($handle);
        }, 200, $headers);
    }
}
