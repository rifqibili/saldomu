<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget; // Ganti dari StatsOverviewWidget
use App\Models\AjukanDokumenSopMutu;
use App\Models\DokumenSopMutu;
use App\Models\User;
use App\Models\StatusProgress;
use App\Models\Status;
use Carbon\Carbon;

class AdminStatsOverview extends ChartWidget // Ganti dari BaseWidget
{
    protected static ?string $heading = 'Ringkasan Metrik Utama';
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 'full';

    protected function getType(): string
    {
        return 'bar'; // Menggunakan bar chart untuk perbandingan
    }

    protected function getData(): array
    {
        $statusAktifId = Status::where('status', 'Aktif')->value('id');
        $statusMenungguReviewId = StatusProgress::where('status', 'Menunggu di Review')->value('id');
        $statusDiterimaId = StatusProgress::where('status', 'Diterima')->value('id');

        $totalDokumenAktif = DokumenSopMutu::where('status_id', $statusAktifId)->count();
        $totalPengajuanMenunggu = AjukanDokumenSopMutu::where('status_progress_id', $statusMenungguReviewId)->count();
        $totalPengguna = User::count();
        $acceptedThisMonth = AjukanDokumenSopMutu::where('status_progress_id', $statusDiterimaId)
                                                 ->whereMonth('updated_at', Carbon::now()->month)
                                                 ->count();

        $labels = [
            'Dokumen Aktif',
            'Pengajuan Menunggu',
            'Total Pengguna',
            'Diterima Bulan Ini'
        ];
        $dataCounts = [
            $totalDokumenAktif,
            $totalPengajuanMenunggu,
            $totalPengguna,
            $acceptedThisMonth,
        ];
        $colors = ['#10b981', '#f97316', '#3b82f6', '#10b981']; // Warna Hijau, Oranye, Biru, Hijau

        return [
            'datasets' => [
                [
                    'label' => 'Jumlah',
                    'data' => $dataCounts,
                    'backgroundColor' => $colors,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'color' => '#ffffff',
                        'precision' => 0,
                    ],
                    'grid' => [
                        'color' => 'rgba(255, 255, 255, 0.1)',
                    ],
                ],
                'x' => [
                    'ticks' => [
                        'color' => '#ffffff',
                    ],
                    'grid' => [
                        'color' => 'rgba(255, 255, 255, 0.1)',
                    ],
                ],
            ],
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
        ];
    }
}