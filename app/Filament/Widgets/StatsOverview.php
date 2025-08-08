<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\DokumenSopMutu; // Pastikan model ini diimpor
use App\Models\AjukanDokumenSopMutu; // Pastikan model ini diimpor
use App\Models\User; // Pastikan model ini diimpor
use Carbon\Carbon; // Pastikan Carbon diimpor

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        // Data untuk "Revenue" (kita asumsikan ini adalah total dokumen SOP Mutu yang "Aktif")
        $totalAktifDokumen = DokumenSopMutu::whereHas('status', function ($query) {
            $query->where('status', 'Aktif');
        })->count();

        $lastMonthAktifDokumen = DokumenSopMutu::whereHas('status', function ($query) {
            $query->where('status', 'Aktif');
        })
        ->whereBetween('created_at', [Carbon::now()->subMonth()->startOfMonth(), Carbon::now()->subMonth()->endOfMonth()])
        ->count();

        $currentMonthAktifDokumen = DokumenSopMutu::whereHas('status', function ($query) {
            $query->where('status', 'Aktif');
        })
        ->whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
        ->count();

        $revenueChange = $currentMonthAktifDokumen - $lastMonthAktifDokumen;
        $revenueDescription = abs($revenueChange) . ' dokumen ' . ($revenueChange >= 0 ? 'increase' : 'decrease');
        $revenueColor = $revenueChange >= 0 ? 'success' : 'danger';
        $revenueIcon = $revenueChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down';

        // Data untuk "New customers" (kita asumsikan ini adalah pengguna baru yang mendaftar bulan ini)
        $newUsersThisMonth = User::whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])->count();
        $newUsersLastMonth = User::whereBetween('created_at', [Carbon::now()->subMonth()->startOfMonth(), Carbon::now()->subMonth()->endOfMonth()])->count();

        $userChange = $newUsersThisMonth - $newUsersLastMonth;
        $userDescription = abs($userChange) . ' user ' . ($userChange >= 0 ? 'increase' : 'decrease');
        $userColor = $userChange >= 0 ? 'success' : 'danger';
        $userIcon = $userChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down';

        // Data untuk "New orders" (kita asumsikan ini adalah pengajuan dokumen SOP Mutu bulan ini)
        $newSubmissionsThisMonth = AjukanDokumenSopMutu::whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])->count();
        $newSubmissionsLastMonth = AjukanDokumenSopMutu::whereBetween('created_at', [Carbon::now()->subMonth()->startOfMonth(), Carbon::now()->subMonth()->endOfMonth()])->count();

        $submissionChange = $newSubmissionsThisMonth - $newSubmissionsLastMonth;
        $submissionDescription = abs($submissionChange) . ' pengajuan ' . ($submissionChange >= 0 ? 'increase' : 'decrease');
        $submissionColor = $submissionChange >= 0 ? 'success' : 'danger';
        $submissionIcon = $submissionChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down';


        return [
            Stat::make('Total Dokumen Aktif', $totalAktifDokumen)
                ->description($revenueDescription)
                ->descriptionIcon($revenueIcon)
                ->color($revenueColor)
                ->chart([
                    $lastMonthAktifDokumen,
                    $currentMonthAktifDokumen,
                ]),
            Stat::make('Pengguna Baru Bulan Ini', $newUsersThisMonth)
                ->description($userDescription)
                ->descriptionIcon($userIcon)
                ->color($userColor)
                ->chart([
                    $newUsersLastMonth,
                    $newUsersThisMonth,
                ]),
            Stat::make('Pengajuan Dokumen Bulan Ini', $newSubmissionsThisMonth)
                ->description($submissionDescription)
                ->descriptionIcon($submissionIcon)
                ->color($submissionColor)
                ->chart([
                    $newSubmissionsLastMonth,
                    $newSubmissionsThisMonth,
                ]),
        ];
    }
}