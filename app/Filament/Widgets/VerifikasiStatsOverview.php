<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\AjukanDokumenSopMutu;
use App\Models\StatusProgress;
use Carbon\Carbon;

class VerifikasiStatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $statusMenungguReviewId = StatusProgress::where('status', 'Menunggu di Review')->value('id');
        $statusReviewId = StatusProgress::where('status', 'Review')->value('id');
        $statusDitolakId = StatusProgress::where('status', 'Ditolak')->value('id');
        $statusRevisiId = StatusProgress::where('status', 'Revisi')->value('id');

        $countMenungguReview = AjukanDokumenSopMutu::where('status_progress_id', $statusMenungguReviewId)->count();
        $countReview = AjukanDokumenSopMutu::where('status_progress_id', $statusReviewId)->count();
        $countDitolakBulanIni = AjukanDokumenSopMutu::where('status_progress_id', $statusDitolakId)
                                                    ->whereMonth('updated_at', Carbon::now()->month)->count();
        $countRevisiBulanIni = AjukanDokumenSopMutu::where('status_progress_id', $statusRevisiId)
                                                   ->whereMonth('updated_at', Carbon::now()->month)->count();

        return [
            Stat::make('Tugas Menunggu di Review', $countMenungguReview)
                ->description('Jumlah dokumen yang belum ditinjau')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning')
                ->chart([5, 10, 15, 20, 25, 30]), 
            Stat::make('Dokumen Sedang di Review', $countReview)
                ->description('Pekerjaan yang sedang berjalan')
                ->descriptionIcon('heroicon-m-magnifying-glass')
                ->color('info')
                ->chart([2, 4, 6, 8, 10, 12]),
            Stat::make('Pengajuan Ditolak Bulan Ini', $countDitolakBulanIni)
                ->description('Perlu ditindaklanjuti')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger')
                ->chart([2, 4, 6, 8, 10, 12]),
            Stat::make('Pengajuan Revisi Bulan Ini', $countRevisiBulanIni)
                ->description('Perlu perbaikan')
                ->descriptionIcon('heroicon-m-pencil-square')
                ->color('gray')
                ->chart([2, 4, 6, 8, 10, 12]),
        ];
    }
}