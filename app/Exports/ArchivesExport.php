<?php

namespace App\Exports;

use App\Models\Archive;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ArchivesExport implements FromCollection, WithHeadings, WithMapping, WithColumnWidths, ShouldAutoSize, WithStyles
{
    protected $request;

    public function __construct($request)
    {
        $this->request = $request;
    }

    public function collection(): \Illuminate\Support\Collection
    {
        $query = Archive::with('archivedBy')->latest('archived_at');

        $user = auth()->user();
        if ($user->isAdmin()) {
            if ($this->request->filled('user_id')) {
                $query->where('archived_by', $this->request->user_id);
            }
        } else {
            $query->where('archived_by', $user->id);
        }

        if ($this->request->filled('type')) {
            $query->where('archivable_type', $this->request->type);
        }

        if ($this->request->filled('date_from')) {
            $dateFrom = Carbon::parse($this->request->date_from)->startOfDay()->setTimezone('UTC');
            $query->where('archived_at', '>=', $dateFrom);
        }
        if ($this->request->filled('date_to')) {
            $dateTo = Carbon::parse($this->request->date_to)->endOfDay()->setTimezone('UTC');
            $query->where('archived_at', '<=', $dateTo);
        }

        if ($this->request->filled('search')) {
            $search = $this->request->search;
            $query->where(function ($q) use ($search) {
                $q->where('reason', 'like', "%{$search}%")
                  ->orWhere('archivable_type', 'like', "%{$search}%")
                  ->orWhereHas('archivedBy', fn ($q) => $q->where('name', 'like', "%{$search}%"));

                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $search)) {
                    $q->orWhereDate('archived_at', '=', $search);
                }
            });
        }

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Type',
            'Reason',
            'Archived By',
            'Archived At',
            'Record Title',
            'Driver/Vehicle',
            'Location',
            'Status',
            'Penalty',
            'Notes',
        ];
    }

    public function map($archive): array
    {
        $snap = $archive->snapshot ?? [];
        $type = class_basename($archive->archivable_type);

        $recordTitle = '';
        $driverVehicle = '';
        $location = '';
        $status = $snap['status'] ?? '';
        $penalty = '';
        $notes = '';

        match ($type) {
            'Citation' => [
                $recordTitle = $snap['citation_number'] ?? "CIT-{$archive->archivable_id}",
                $driverVehicle = ($snap['driver_name'] ?? '') . ' / ' . ($snap['vehicle_plate'] ?? ''),
                $location = $snap['location'] ?? '',
                $penalty = isset($snap['penalty_amount']) ? number_format($snap['penalty_amount'], 2) : '',
                $notes = $snap['notes'] ?? '',
            ],
            'Appeal' => [
                $recordTitle = "Appeal #{$archive->archivable_id}",
                $driverVehicle = 'Citation: ' . ($snap['citation_number'] ?? '#' . ($snap['citation_id'] ?? '')),
                $location = '',
                $penalty = '',
                $notes = $snap['reason'] ?? '',
            ],
            'ClampingRecord' => [
                $recordTitle = $snap['notice_number'] ?? "CLP-{$archive->archivable_id}",
                $driverVehicle = $snap['vehicle_plate'] ?? '',
                $location = $snap['location'] ?? '',
                $penalty = '',
                $notes = $snap['notes'] ?? '',
            ],
            'ClampingRequest' => [
                $recordTitle = $snap['requester_name'] ?? "Request #{$archive->archivable_id}",
                $driverVehicle = $snap['vehicle_plate'] ?? '',
                $location = $snap['location_address'] ?? '',
                $penalty = '',
                $notes = $snap['additional_notes'] ?? '',
            ],
            default => [
                $recordTitle = "Record #{$archive->archivable_id}",
                $driverVehicle = '',
                $location = '',
                $penalty = '',
                $notes = '',
            ],
        };

        return [
            $archive->id,
            $type,
            $archive->reason,
            $archive->archivedBy?->name ?? 'System',
            $archive->archived_at->format('Y-m-d H:i:s'),
            $recordTitle,
            $driverVehicle,
            $location,
            $status,
            $penalty,
            $notes,
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 8,
            'B' => 18,
            'C' => 30,
            'D' => 20,
            'E' => 22,
            'F' => 25,
            'G' => 30,
            'H' => 30,
            'I' => 18,
            'J' => 12,
            'K' => 40,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        // Header row bold
        $sheet->getStyle('A1:K1')->getFont()->setBold(true);
        // Auto filter
        $sheet->setAutoFilter('A1:K1');
        // Freeze header
        $sheet->freezePane('A2');
        return [];
    }
}