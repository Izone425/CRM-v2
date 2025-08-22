<?php

namespace App\Providers\Filament;

use App\Filament\Pages\AdminRepairDashboard;
use App\Filament\Pages\Auth\EditProfile;
use App\Filament\Pages\Calendar;
use App\Filament\Pages\ChatRoom;
use App\Filament\Pages\DashboardForm;
use App\Filament\Pages\DemoAnalysis;
use App\Filament\Pages\DemoAnalysisTableForm;
use App\Filament\Pages\DemoRanking;
use App\Filament\Pages\DepartmentCalendar;
use App\Filament\Pages\FutureEnhancement as PagesFutureEnhancement;
use App\Filament\Pages\HardwareDashboardAll;
use App\Filament\Pages\HardwareDashboardPendingStock;
use App\Filament\Pages\ImplementationSession;
use App\Filament\Pages\ImplementerAuditList;
use App\Filament\Pages\ImplementerCalendar;
use App\Filament\Pages\ImplementerDataFile;
use App\Filament\Pages\ImplementerRequestCount;
use App\Filament\Pages\ImplementerRequestList;
use App\Filament\Pages\KickOffMeetingSession;
use App\Filament\Pages\LeadAnalysis;
use App\Filament\Pages\MarketingAnalysis;
use App\Filament\Pages\MonthlyCalendar;
use App\Filament\Pages\OnsiteRepairList;
use App\Filament\Pages\OvertimeCalendar;
use App\Filament\Pages\ProformaInvoices;
use App\Filament\Pages\ProjectAnalysis;
use App\Filament\Pages\ProjectCategoryClosed;
use App\Filament\Pages\ProjectCategoryDelay;
use App\Filament\Pages\ProjectCategoryInactive;
use App\Filament\Pages\ProjectCategoryOpen;
use App\Filament\Pages\RankingForm;
use App\Filament\Pages\RankingFormPage;
use App\Filament\Pages\SalesAdminAnalysisV1;
use App\Filament\Pages\SalesAdminAnalysisV2;
use App\Filament\Pages\SalesAdminAnalysisV3;
use App\Filament\Pages\SalesForecast;
use App\Filament\Pages\SalesForecastSummary;
use App\Filament\Pages\SalesLead;
use App\Filament\Pages\SalespersonAppointment;
use App\Filament\Pages\SalespersonAuditList;
use App\Filament\Pages\SalespersonCalendarV1;
use App\Filament\Pages\SalespersonCalendarV2;
use App\Filament\Pages\SalespersonLeadSequence;
use App\Filament\Pages\SearchLead;
use App\Filament\Pages\SoftwareHandoverAnalysis;
use App\Filament\Pages\SoftwareHandoverAnalysisV2;
use App\Filament\Pages\SupportCallLog;
use App\Filament\Pages\TechnicianAppointment;
use App\Filament\Pages\TechnicianCalendar;
use App\Filament\Pages\TrainingCalendar;
use App\Filament\Pages\TrainingCalendarBulkManagement;
use App\Filament\Pages\WeeklyCalendarV2;
use App\Filament\Pages\Whatsapp;
use App\Filament\Resources\AdminRepairResource;
use App\Filament\Resources\CallCategoryResource;
use App\Filament\Resources\ChatMessageResource;
use App\Filament\Resources\DashboardResource;
use App\Filament\Resources\DemoResource;
use App\Filament\Resources\DeviceModelResource;
use App\Filament\Resources\EmailTemplateResource;
use App\Filament\Resources\HardwareAttachmentResource;
use App\Filament\Resources\HardwarePendingStockResource;
use App\Filament\Resources\IndustryResource;
use App\Filament\Resources\InstallerResource;
use App\Filament\Resources\InvalidLeadReasonResource;
use App\Filament\Resources\LeadResource;
use App\Filament\Resources\LeadSourceResource;
use App\Filament\Resources\ProductResource;
use App\Filament\Resources\QuotationResource;
use App\Filament\Resources\UserResource;
use App\Filament\Resources\ResellerResource;
use App\Filament\Resources\RoleResource;
use App\Filament\Resources\SoftwareAttachmentResource;
use App\Filament\Resources\SoftwareHandoverResource;
use App\Filament\Resources\SoftwareResource;
use App\Filament\Resources\SparePartResource;
use App\Filament\Resources\TrainingBookingResource;
use App\Filament\Widgets\LeadChartWidget;
use App\Livewire\FutureEnhancement;
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
use App\Models\Role;
use App\Models\SparePart;
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
            // ->passwordReset()
            ->emailVerification()
            ->profile(EditProfile::class)
            // ->databaseNotifications()
            ->brandName('TimeTec CRM')
            ->colors([
                'primary' => '#431fa1',
            ])
            ->assets([
                Css::make('styles', public_path('/css/app/styles.css')),
                Css::make('sidebar', public_path('/css/custom-sidebar.css')),
                \Filament\Support\Assets\Js::make('sidebar-js', public_path('/js/custom-sidebar.js')),
            ])
            ->renderHook(
                \Filament\View\PanelsRenderHook::USER_MENU_BEFORE,
                fn (): string => \Illuminate\Support\Facades\Blade::render('@livewire(\App\Livewire\CountryDivisionSelector::class)'),
            )
            ->renderHook(
                'panels::body.start',  // This is critical - it needs to be panels::body.start
                fn (): string => view('layouts.custom-sidebar')->render()
            )
            ->renderHook(
                'panels::body.start',
                function (): string {
                    // Only render the sidebar for authenticated users
                    if (auth()->check()) {
                        return view('layouts.custom-sidebar')->render();
                    }
                    return ''; // Return empty string for unauthenticated users
                }
            )
            ->renderHook(
                'panels::content.start',
                function (): string {
                    if (auth()->check()) {
                        return '<div class="custom-content-wrapper">';
                    }
                    return '';
                }
            )
            ->renderHook(
                'panels::content.end',
                function (): string {
                    if (auth()->check()) {
                        return '</div>';
                    }
                    return '';
                }
            )
            ->renderHook(
                'panels::styles.after',
                fn (): string => <<<'HTML'
                <style>
                    /* Multi-line tabs styling */
                    .multiline-tabs .fi-tabs {
                        flex-wrap: wrap;
                        max-height: none !important;
                    }

                    .multiline-tabs .fi-tabs-item {
                        margin-bottom: 0.5rem;
                    }
                </style>
                HTML
            )
            // ->navigation(false)
            ->darkMode(false)
            // ->sidebarCollapsibleOnDesktop()
            ->sidebarWidth('4rem')
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
                ResellerResource::class,
                SoftwareResource::class,
                RoleResource::class,
                SoftwareAttachmentResource::class,
                HardwareAttachmentResource::class,
                InstallerResource::class,
                HardwarePendingStockResource::class,
                SparePartResource::class,
                AdminRepairResource::class,
                TrainingBookingResource::class,
                EmailTemplateResource::class,
                DeviceModelResource::class,
                CallCategoryResource::class,
            ])
            // ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                // Pages\Dashboard::class,
                SalespersonCalendarV1::class,
                SalespersonCalendarV2::class,
                MonthlyCalendar::class,
                TechnicianCalendar::class,
                DemoRanking::class,
                DashboardForm::class,
                ProformaInvoices::class,
                Whatsapp::class,
                LeadAnalysis::class,
                DemoAnalysis::class,
                MarketingAnalysis::class,
                SalesForecast::class,
                SalesAdminAnalysisV1::class,
                SalesAdminAnalysisV2::class,
                SalesAdminAnalysisV3::class,
                SalesForecastSummary::class,
                PagesFutureEnhancement::class,
                SearchLead::class,
                HardwareDashboardAll::class,
                HardwareDashboardPendingStock::class,
                OnsiteRepairList::class,
                TrainingCalendar::class,
                TrainingCalendarBulkManagement::class,
                ImplementerCalendar::class,
                ImplementerDataFile::class,
                ProjectAnalysis::class,
                DemoAnalysisTableForm::class,
                TechnicianAppointment::class,
                SalespersonAppointment::class,
                ImplementerAuditList::class,
                SalespersonLeadSequence::class,
                Calendar::class,
                ImplementerRequestCount::class,
                ImplementerRequestList::class,
                ProjectCategoryOpen::class,
                ProjectCategoryDelay::class,
                ProjectCategoryInactive::class,
                ProjectCategoryClosed::class,
                KickOffMeetingSession::class,
                ImplementationSession::class,
                SoftwareHandoverAnalysisV2::class,
                OvertimeCalendar::class,
                SupportCallLog::class,
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
            ->authMiddleware([
                Authenticate::class,
            ])
            ->plugins([
                FilamentFullCalendarPlugin::make()
            ])
            ->maxContentWidth(MaxWidth::Full);
    }
}
