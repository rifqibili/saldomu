<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\AjukanDokumenSopMutu; // Pastikan model ini diimpor
use App\Models\StatusProgress;       // Pastikan model ini diimpor
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class VerificationStatusChart extends ChartWidget
{
    protected static ?string $heading = 'Status Verifikasi Dokumen per Bulan';
    protected static ?int $sort = 2; // Urutan setelah Stats Overview

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        // Ambil ID status
        $statusDiterimaId = StatusProgress::where('status', 'Diterima')->value('id');
        $statusDitolakId = StatusProgress::where('status', 'Ditolak')->value('id');

        // Data untuk status "Diterima"
        $acceptedData = AjukanDokumenSopMutu::select(
                DB::raw('MONTH(updated_at) as month'), // Gunakan updated_at karena ini adalah status akhir
                DB::raw('COUNT(*) as total')
            )
            ->where('status_progress_id', $statusDiterimaId)
            ->whereYear('updated_at', Carbon::now()->year)
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->pluck('total', 'month')
            ->toArray();

        // Data untuk status "Ditolak"
        $rejectedData = AjukanDokumenSopMutu::select(
                DB::raw('MONTH(updated_at) as month'),
                DB::raw('COUNT(*) as total')
            )
            ->where('status_progress_id', $statusDitolakId)
            ->whereYear('updated_at', Carbon::now()->year)
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->pluck('total', 'month')
            ->toArray();

        $months = [];
        $acceptedCounts = [];
        $rejectedCounts = [];

        for ($i = 1; $i <= 12; $i++) {
            $monthName = Carbon::create(null, $i, 1)->translatedFormat('M'); // Jan, Feb, dst.
            $months[] = $monthName;
            $acceptedCounts[] = $acceptedData[$i] ?? 0;
            $rejectedCounts[] = $rejectedData[$i] ?? 0;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Diterima',
                    'data' => $acceptedCounts,
                    'borderColor' => '#10b981', // green-500
                    'backgroundColor' => 'rgba(16, 185, 129, 0.2)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Ditolak',
                    'data' => $rejectedCounts,
                    'borderColor' => '#ef4444', // red-500
                    'backgroundColor' => 'rgba(239, 68, 68, 0.2)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
            ],
            'labels' => $months,
        ];
    }

    protected function getOptions(): array
    {
        return [ 
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'grid' => [
                        'color' => 'rgba(255, 255, 255, 0.1)',
                    ],
                    'ticks' => [
                        'color' => '#ffffff',
                    ],
                ],
                'x' => [
                    'grid' => [
                        'color' => 'rgba(255, 255, 255, 0.1)',
                    ],
                    'ticks' => [
                        'color' => '#ffffff',
                    ],
                ],
            ],
            'plugins' => [
                'legend' => [
                    'labels' => [
                        'color' => '#ffffff',
                    ],
                ],
            ],
            'elements' => [
                'point' => [
                    'backgroundColor' => '#facc15',
                    'borderColor' => '#facc15',
                ],
            ],
        ];
    }
}