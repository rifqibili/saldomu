<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\AjukanDokumenSopMutu;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SubmissionsChart extends ChartWidget
{
    protected static ?string $heading = 'Tren Pengajuan Dokumen per Bulan';
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = '1/2';

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $data = AjukanDokumenSopMutu::select(
                DB::raw('MONTH(created_at) as month'),
                DB::raw('COUNT(*) as total')
            )
            ->whereYear('created_at', Carbon::now()->year)
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->pluck('total', 'month')
            ->toArray();

        $months = [];
        $submissionsCount = [];

        for ($i = 1; $i <= 12; $i++) {
            $months[] = Carbon::create(null, $i, 1)->translatedFormat('M');
            $submissionsCount[] = $data[$i] ?? 0;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Jumlah Pengajuan',
                    'data' => $submissionsCount,
                    'borderColor' => '#facc15',
                    'backgroundColor' => 'rgba(250, 204, 21, 0.2)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
            ],
            'labels' => $months,
        ];
    }
}