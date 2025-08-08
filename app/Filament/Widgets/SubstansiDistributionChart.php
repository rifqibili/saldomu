<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\AjukanDokumenSopMutu;
use App\Models\Substansi;
use Illuminate\Support\Facades\DB;

class SubstansiDistributionChart extends ChartWidget
{
    protected static ?string $heading = 'Distribusi Pengajuan per Substansi';
    protected static ?int $sort = 3;
    protected int | string | array $columnSpan = '1/2';

    protected function getType(): string
    {
        return 'pie';
    }

    protected function getData(): array
    {
        $counts = AjukanDokumenSopMutu::select('substansi_id', DB::raw('count(*) as total'))
            ->groupBy('substansi_id')
            ->pluck('total', 'substansi_id')
            ->toArray();

        $substansiNames = Substansi::whereIn('id', array_keys($counts))
            ->pluck('substansi', 'id')
            ->toArray();

        $labels = [];
        $data = [];
        $colors = ['#facc15', '#10b981', '#3b82f6', '#ef4444', '#6b7280', '#a855f7'];

        foreach ($counts as $substansiId => $count) {
            $labels[] = $substansiNames[$substansiId] ?? 'N/A';
            $data[] = $count;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Jumlah Pengajuan',
                    'data' => $data,
                    'backgroundColor' => array_slice($colors, 0, count($labels)),
                ],
            ],
            'labels' => $labels,
        ];
    }
}