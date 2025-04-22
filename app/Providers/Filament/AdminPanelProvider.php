<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\EditProfile;
use App\Filament\Pages\Calendar;
use App\Filament\Pages\ChatRoom;
use App\Filament\Pages\DashboardForm;
use App\Filament\Pages\DemoAnalysis;
use App\Filament\Pages\DemoRanking;
use App\Filament\Pages\LeadAnalysis;
use App\Filament\Pages\MarketingAnalysis;
use App\Filament\Pages\MonthlyCalendar;
use App\Filament\Pages\ProformaInvoices;
use App\Filament\Pages\RankingForm;
use App\Filament\Pages\RankingFormPage;
use App\Filament\Pages\SalesAdminAnalysisV1;
use App\Filament\Pages\SalesAdminAnalysisV2;
use App\Filament\Pages\SalesAdminAnalysisV3;
use App\Filament\Pages\SalesForecast;
use App\Filament\Pages\SalesForecastSummary;
use App\Filament\Pages\WeeklyCalendarV2;
use App\Filament\Resources\ChatMessageResource;
use App\Filament\Resources\DashboardResource;
use App\Filament\Resources\DemoResource;
use App\Filament\Resources\IndustryResource;
use App\Filament\Resources\InvalidLeadReasonResource;
use App\Filament\Resources\LeadResource;
use App\Filament\Resources\LeadSourceResource;
use App\Filament\Resources\ProductResource;
use App\Filament\Resources\QuotationResource;
use App\Filament\Resources\UserResource;
use App\Filament\Widgets\LeadChartWidget;
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
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use App\Models\ChatMessage;
use Filament\Navigation\NavigationItem;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            // ->notifications()
            // ->livewire(Notification::class)
            // ->registration()
            ->passwordReset()
            ->emailVerification()
            ->profile(EditProfile::class)
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
                DemoResource::class,
                IndustryResource::class,
                LeadSourceResource::class,
                InvalidLeadReasonResource::class,
                UserResource::class,
            ])
            // ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                // Pages\Dashboard::class,
                Calendar::class,
                WeeklyCalendarV2::class,
                MonthlyCalendar::class,
                DemoRanking::class,
                DashboardForm::class,
                ProformaInvoices::class,
                LeadAnalysis::class,
                DemoAnalysis::class,
                MarketingAnalysis::class,
                SalesForecast::class,
                SalesAdminAnalysisV1::class,
                SalesAdminAnalysisV2::class,
                SalesAdminAnalysisV3::class,
                SalesForecastSummary::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                Widgets\FilamentInfoWidget::class,
                // LeadChartWidget::class,
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
                    ->label('Sales Forecast')
                    ->icon('heroicon-s-arrow-trending-up')
                    ->collapsed(),
                NavigationGroup::make()
                    ->label('Calendar')
                    ->icon('heroicon-s-calendar-days')
                    ->collapsed(),
                NavigationGroup::make()
                    ->label('Analysis')
                    ->icon('heroicon-s-chart-bar')
                    ->collapsed(),
                NavigationGroup::make()
                    ->label('Settings')
                    ->icon('heroicon-s-adjustments-horizontal')
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
