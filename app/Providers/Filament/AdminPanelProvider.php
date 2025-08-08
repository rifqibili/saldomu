<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
// use App\Filament\Widgets\StatsOverview;
use App\Filament\Widgets\VerificationStatusChart;
use App\Filament\Widgets\VerifikasiStatsOverview;
// use App\Filament\Widgets\AdminStatsOverview;
use App\Filament\Widgets\SubstansiDistributionChart;
use App\Filament\Widgets\SubmissionsChart;
use App\Filament\Widgets\LatestSubmissions;
use App\Filament\Widgets\PendingReviewTable;
use App\Filament\Widgets\DocumentCategoryOverview;
use App\Filament\Resources\DocumentHistoryResource;


class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->databaseNotifications()
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->resources([ 
                DocumentHistoryResource::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                Widgets\FilamentInfoWidget::class,
                // // StatsOverview::class,
                // AdminStatsOverview::class,
                SubmissionsChart::class,
                SubstansiDistributionChart::class,
                LatestSubmissions::class,
                PendingReviewTable::class,
                VerifikasiStatsOverview::class,
                VerificationStatusChart::class,
                DocumentCategoryOverview::class,
            ])            
            ->renderHook(
                'panels::sidebar.header', // Lokasi di bagian atas sidebar
                fn () => \App\Filament\Widgets\CustomAccountWidget::make()
            )
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->plugins([
                \BezhanSalleh\FilamentShield\FilamentShieldPlugin::make(),
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->brandName(fn () => auth()->user()->nama ?? 'Filament')
            ->globalSearchKeyBindings(['/']);
    }
}
