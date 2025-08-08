<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\AjukanDokumenSopMutu;
use App\Models\StatusProgress;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class VerificationApprovalTrend extends ChartWidget
{
    protected static ?string $heading = 'Tren Persetujuan vs Penolakan per Bulan';
    protected static ?int $sort = 2;

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $statusDiterimaId = StatusProgress::where('status', 'Diterima')->value('id');
        $statusDitolakId = StatusProgress::where('status', 'Ditolak')->value('id');

        $acceptedData = AjukanDokumenSopMutu::where('status_progress_id', $statusDiterimaId)
            ->whereYear('updated_at', Carbon::now()->year)
            ->select(DB::raw('MONTH(updated_at) as month'), DB::raw('COUNT(*) as total'))
            ->groupBy('month')
            ->pluck('total', 'month')
            ->toArray();

        $rejectedData = AjukanDokumenSopMutu::where('status_progress_id', $statusDitolakId)
            ->whereYear('updated_at', Carbon::now()->year)
            ->select(DB::raw('MONTH(updated_at) as month'), DB::raw('COUNT(*) as total'))
            ->groupBy('month')
            ->pluck('total', 'month')
            ->toArray();

        $months = [];
        $acceptedCounts = [];
        $rejectedCounts = [];

        for ($i = 1; $i <= 12; $i++) {
            $months[] = Carbon::create(null, $i, 1)->translatedFormat('M');
            $acceptedCounts[] = $acceptedData[$i] ?? 0;
            $rejectedCounts[] = $rejectedData[$i] ?? 0;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Diterima',
                    'data' => $acceptedCounts,
                    'borderColor' => '#10b981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.2)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Ditolak',
                    'data' => $rejectedCounts,
                    'borderColor' => '#ef4444',
                    'backgroundColor' => 'rgba(239, 68, 68, 0.2)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
            ],
            'labels' => $months,
        ];
    }
}