<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Calendar;
use App\Filament\Pages\DashboardForm;
use App\Filament\Pages\ProformaInvoices;
use App\Filament\Pages\RankingForm;
use App\Filament\Pages\RankingFormPage;
use App\Filament\Resources\DashboardResource;
use App\Filament\Resources\DemoResource;
use App\Filament\Resources\LeadResource;
use App\Filament\Resources\ProductResource;
use App\Filament\Resources\QuotationResource;
use Filament\Pages;
use Filament\Panel;
use Filament\Widgets;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Navigation\NavigationGroup;
use Filament\Http\Middleware\Authenticate;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Support\Assets\Css;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Saade\FilamentFullCalendar\FilamentFullCalendarPlugin;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            // ->registration()
            ->passwordReset()
            ->emailVerification()
            ->profile()
            ->brandName('TimeTec CRM')
            ->colors([
                'primary' => '#431fa1',
            ])
            ->assets([
                Css::make('styles', public_path('/css/app/styles.css')),
            ])
            ->darkMode(false)
            ->sidebarCollapsibleOnDesktop()
            // ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->resources([  // Manually registering specific resources
                LeadResource::class,
                ProductResource::class,
                QuotationResource::class,
                DemoResource::class
            ])
            // ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                // Pages\Dashboard::class,
                Calendar::class,
                DashboardForm::class,
                ProformaInvoices::class,
                RankingFormPage::class
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                Widgets\FilamentInfoWidget::class,
            ])
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
            ->navigationGroups([
                NavigationGroup::make()
                    ->label('Settings')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->collapsed(),
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->plugins([
                FilamentFullCalendarPlugin::make()
            ])
            ->maxContentWidth(MaxWidth::Full);
    }
}
