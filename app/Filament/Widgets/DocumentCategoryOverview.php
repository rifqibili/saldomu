<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\DokumenSopMutu; // Menggunakan model DokumenSopMutu
use App\Models\SubKategoriSop;
use App\Models\Status; // Menggunakan model Status untuk status 'Aktif'
use App\Models\Substansi;

class DocumentCategoryOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        // Mendapatkan ID untuk status 'Aktif' dari model Status
        $statusAktifId = Status::where('status', 'Aktif')->value('id');

        // Mendapatkan ID untuk 'SOP Mikro' dan 'SOP Makro' dari model SubKategoriSop
        $sopMikroId = SubKategoriSop::where('jenis_sop', 'SOP Mikro')->value('id');
        $sopMakroId = SubKategoriSop::where('jenis_sop', 'SOP Makro')->value('id');

        $stats = [];

        // 1. Kartu Statistik: Total Dokumen SOP Mikro yang Aktif (keseluruhan)
        $totalSopMikroAktif = DokumenSopMutu::when($statusAktifId, fn ($query) => $query->where('status_id', $statusAktifId))
            ->when($sopMikroId, fn ($query) => $query->where('sub_kategori_sop_id', $sopMikroId))
            ->count();
        $stats[] = Stat::make('Total SOP Mikro Aktif', $totalSopMikroAktif)
            ->description('Jumlah dokumen SOP Mikro yang berstatus Aktif')
            ->color('success')
            ->chart([7, 2, 10, 3, 15, 4, 17]); // Contoh data grafik mini
            // Ganti dengan data aktual yang relevan, misalnya tren bulanan
            // ->chart([DokumenSopMutu::where('sub_kategori_sop_id', $sopMikroId)->where('status_id', $statusAktifId)->whereMonth('tanggal_terbit', 1)->count(), ...])

        // 2. Kartu Statistik: Total Dokumen SOP Makro yang Aktif (keseluruhan)
        $totalSopMakroAktif = DokumenSopMutu::when($statusAktifId, fn ($query) => $query->where('status_id', $statusAktifId))
            ->when($sopMakroId, fn ($query) => $query->where('sub_kategori_sop_id', $sopMakroId))
            ->count();
        $stats[] = Stat::make('Total SOP Makro Aktif', $totalSopMakroAktif)
            ->description('Jumlah dokumen SOP Makro yang berstatus Aktif')
            ->color('info')
            ->chart([3, 15, 10, 4, 7, 2, 17]); // Contoh data grafik mini

        // 3. Kartu Statistik: Dokumen SOP Mikro dan Makro per Substansi
        $substansis = Substansi::all();

        foreach ($substansis as $substansi) {
            // Menghitung jumlah SOP Mikro yang Aktif untuk substansi ini
            $sopMikroPerSubstansi = DokumenSopMutu::when($statusAktifId, fn ($query) => $query->where('status_id', $statusAktifId))
                ->when($sopMikroId, fn ($query) => $query->where('sub_kategori_sop_id', $sopMikroId))
                ->where('substansi_id', $substansi->id)
                ->count();
            $stats[] = Stat::make(
                'SOP Mikro ' . $substansi->substansi,
                $sopMikroPerSubstansi
            )
                ->description('Jumlah SOP Mikro Aktif di ' . $substansi->substansi)
                ->color('warning')
                ->chart([1, 5, 3, 8, 2, 9, 6]); // Contoh data grafik mini

            // Menghitung jumlah SOP Makro yang Aktif untuk substansi ini
            $sopMakroPerSubstansi = DokumenSopMutu::when($statusAktifId, fn ($query) => $query->where('status_id', $statusAktifId))
                ->when($sopMakroId, fn ($query) => $query->where('sub_kategori_sop_id', $sopMakroId))
                ->where('substansi_id', $substansi->id)
                ->count();
            $stats[] = Stat::make(
                'SOP Makro ' . $substansi->substansi,
                $sopMakroPerSubstansi
            )
                ->description('Jumlah SOP Makro Aktif di ' . $substansi->substansi)
                ->color('danger')
                ->chart([9, 6, 12, 5, 10, 7, 14]); // Contoh data grafik mini
        }

        return $stats;
    }
}
