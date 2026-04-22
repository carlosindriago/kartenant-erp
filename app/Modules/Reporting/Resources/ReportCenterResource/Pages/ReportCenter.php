<?php

/**
 * Kartenant - Ferretero Ágil
 * 
 * Este archivo es parte de Kartenant.
 * 
 * @copyright Copyright (c) 2025-2026 Kartenant
 * @license   GNU AGPLv3 <https://www.gnu.org/licenses/agpl-3.0.txt>
 */

namespace App\Modules\Reporting\Resources\ReportCenterResource\Pages;

use App\Modules\Reporting\Resources\ReportCenterResource;
use App\Modules\Reporting\Services\InventoryReportService;
use App\Modules\Reporting\Services\ABCAnalysisService;
use App\Modules\Reporting\Services\ProfitabilityService;
use App\Modules\Reporting\Services\TurnoverService;
use Filament\Resources\Pages\Page;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Carbon;

class ReportCenter extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = ReportCenterResource::class;
    protected static string $view = 'reporting::pages.report-center';
    protected static ?string $title = 'Centro de Reportes Avanzados';

    public ?array $data = [];

    // Data properties for each report type
    public ?array $inventoryValueData = null;
    public ?array $abcAnalysisData = null;
    public ?array $profitabilityData = null;
    public ?array $turnoverData = null;

    // Active tab
    public string $activeTab = 'inventory_value';

    /**
     * Mount the component
     */
    public function mount(): void
    {
        $this->form->fill();
        $this->loadDefaultData();
    }

    /**
     * Get form schema with filters for each report type
     */
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Reportes')
                    ->tabs([
                        // Tab 1: Valor de Inventario
                        Forms\Components\Tabs\Tab::make('Valor de Inventario')
                            ->id('inventory_value')
                            ->icon('heroicon-o-currency-dollar')
                            ->schema([
                                Forms\Components\Section::make('Filtros')
                                    ->description('Configura los parámetros del reporte de valor de inventario')
                                    ->schema([
                                        Forms\Components\Select::make('inventory_period')
                                            ->label('Período de Tendencia')
                                            ->options([
                                                '7' => 'Últimos 7 días',
                                                '15' => 'Últimos 15 días',
                                                '30' => 'Últimos 30 días',
                                                '60' => 'Últimos 60 días',
                                                '90' => 'Últimos 90 días',
                                            ])
                                            ->default('30')
                                            ->reactive(),

                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\DatePicker::make('inventory_compare_start')
                                                    ->label('Fecha de Inicio (Comparar)')
                                                    ->default(now()->subDays(30))
                                                    ->maxDate(now()),

                                                Forms\Components\DatePicker::make('inventory_compare_end')
                                                    ->label('Fecha de Fin (Comparar)')
                                                    ->default(now())
                                                    ->maxDate(now()),
                                            ]),
                                    ])
                                    ->collapsible(),
                            ]),

                        // Tab 2: Análisis ABC
                        Forms\Components\Tabs\Tab::make('Análisis ABC')
                            ->id('abc_analysis')
                            ->icon('heroicon-o-chart-pie')
                            ->schema([
                                Forms\Components\Section::make('Filtros')
                                    ->description('Análisis de Pareto (80/20) basado en ventas')
                                    ->schema([
                                        Forms\Components\Select::make('abc_period')
                                            ->label('Período de Análisis')
                                            ->options([
                                                'null' => 'Todo el tiempo',
                                                '30' => 'Últimos 30 días',
                                                '60' => 'Últimos 60 días',
                                                '90' => 'Últimos 90 días',
                                                '180' => 'Últimos 6 meses',
                                                '365' => 'Último año',
                                            ])
                                            ->default('90')
                                            ->reactive(),

                                        Forms\Components\Placeholder::make('abc_explanation')
                                            ->label('¿Qué es el Análisis ABC?')
                                            ->content('
                                                - **Clase A**: Productos que generan el 80% de tus ingresos (normalmente 20% de productos)
                                                - **Clase B**: Productos que generan el 15% de tus ingresos (normalmente 30% de productos)
                                                - **Clase C**: Productos que generan el 5% de tus ingresos (normalmente 50% de productos)
                                            '),
                                    ])
                                    ->collapsible(),
                            ]),

                        // Tab 3: Rentabilidad
                        Forms\Components\Tabs\Tab::make('Rentabilidad')
                            ->id('profitability')
                            ->icon('heroicon-o-banknotes')
                            ->schema([
                                Forms\Components\Section::make('Filtros')
                                    ->description('Análisis de productos más y menos rentables')
                                    ->schema([
                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\DatePicker::make('profitability_start')
                                                    ->label('Fecha de Inicio')
                                                    ->default(now()->subDays(30))
                                                    ->maxDate(now())
                                                    ->reactive(),

                                                Forms\Components\DatePicker::make('profitability_end')
                                                    ->label('Fecha de Fin')
                                                    ->default(now())
                                                    ->maxDate(now())
                                                    ->reactive(),
                                            ]),

                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\TextInput::make('profitability_top_limit')
                                                    ->label('Top Productos Más Rentables')
                                                    ->numeric()
                                                    ->default(10)
                                                    ->minValue(5)
                                                    ->maxValue(50),

                                                Forms\Components\TextInput::make('profitability_bottom_limit')
                                                    ->label('Top Productos Menos Rentables')
                                                    ->numeric()
                                                    ->default(10)
                                                    ->minValue(5)
                                                    ->maxValue(50),
                                            ]),
                                    ])
                                    ->collapsible(),
                            ]),

                        // Tab 4: Rotación de Inventario
                        Forms\Components\Tabs\Tab::make('Rotación de Inventario')
                            ->id('turnover')
                            ->icon('heroicon-o-arrow-path')
                            ->schema([
                                Forms\Components\Section::make('Filtros')
                                    ->description('Análisis de velocidad de rotación de productos')
                                    ->schema([
                                        Forms\Components\Select::make('turnover_period')
                                            ->label('Período de Análisis')
                                            ->options([
                                                '30' => 'Últimos 30 días',
                                                '60' => 'Últimos 60 días',
                                                '90' => 'Últimos 90 días',
                                            ])
                                            ->default('30')
                                            ->reactive(),

                                        Forms\Components\TextInput::make('turnover_limit')
                                            ->label('Número de Productos a Mostrar')
                                            ->numeric()
                                            ->default(20)
                                            ->minValue(10)
                                            ->maxValue(100),

                                        Forms\Components\Placeholder::make('turnover_explanation')
                                            ->label('¿Qué es la Rotación de Inventario?')
                                            ->content('
                                                La rotación indica qué tan rápido se venden tus productos:
                                                - **Alta rotación**: Se venden rápido (bueno)
                                                - **Baja rotación**: Se venden lento (atención)
                                                - **DSI (Days Sales of Inventory)**: Días que tardaría en venderse el stock actual
                                            '),
                                    ])
                                    ->collapsible(),
                            ]),

                        // Tab 5: Comparativas de Períodos
                        Forms\Components\Tabs\Tab::make('Comparativas')
                            ->id('comparatives')
                            ->icon('heroicon-o-scale')
                            ->schema([
                                Forms\Components\Section::make('Comparar Dos Períodos')
                                    ->description('Compara métricas entre dos períodos de tiempo')
                                    ->schema([
                                        Forms\Components\Fieldset::make('Período 1')
                                            ->schema([
                                                Forms\Components\Grid::make(2)
                                                    ->schema([
                                                        Forms\Components\DatePicker::make('compare_period1_start')
                                                            ->label('Fecha de Inicio')
                                                            ->default(now()->subDays(60))
                                                            ->maxDate(now())
                                                            ->reactive(),

                                                        Forms\Components\DatePicker::make('compare_period1_end')
                                                            ->label('Fecha de Fin')
                                                            ->default(now()->subDays(30))
                                                            ->maxDate(now())
                                                            ->reactive(),
                                                    ]),
                                            ]),

                                        Forms\Components\Fieldset::make('Período 2')
                                            ->schema([
                                                Forms\Components\Grid::make(2)
                                                    ->schema([
                                                        Forms\Components\DatePicker::make('compare_period2_start')
                                                            ->label('Fecha de Inicio')
                                                            ->default(now()->subDays(30))
                                                            ->maxDate(now())
                                                            ->reactive(),

                                                        Forms\Components\DatePicker::make('compare_period2_end')
                                                            ->label('Fecha de Fin')
                                                            ->default(now())
                                                            ->maxDate(now())
                                                            ->reactive(),
                                                    ]),
                                            ]),
                                    ])
                                    ->collapsible(),
                            ]),
                    ])
                    ->activeTab($this->activeTab)
                    ->contained(false),
            ])
            ->statePath('data');
    }

    /**
     * Load default data for all reports
     */
    protected function loadDefaultData(): void
    {
        // Load Inventory Value Report
        $this->loadInventoryValueReport();

        // Load ABC Analysis
        $this->loadABCAnalysis();

        // Load Profitability Report
        $this->loadProfitabilityReport();

        // Load Turnover Report
        $this->loadTurnoverReport();
    }

    /**
     * Load Inventory Value Report Data
     */
    public function loadInventoryValueReport(): void
    {
        $service = app(InventoryReportService::class);

        $this->inventoryValueData = [
            'summary' => $service->calculateTotalInventoryValue(),
            'by_category' => $service->getInventoryValueByCategory(),
            'trend' => $service->getInventoryValueTrend(30),
            'top_products' => $service->getTopValueProducts(10),
        ];
    }

    /**
     * Load ABC Analysis Data
     */
    public function loadABCAnalysis(): void
    {
        $service = app(ABCAnalysisService::class);
        $days = 90; // Default to 90 days

        $this->abcAnalysisData = [
            'distribution' => $service->getABCDistribution($days),
            'recommendations' => $service->getRecommendations($days),
        ];
    }

    /**
     * Load Profitability Report Data
     */
    public function loadProfitabilityReport(): void
    {
        $service = app(ProfitabilityService::class);
        $dateRange = [
            'start' => now()->subDays(30),
            'end' => now(),
        ];

        $this->profitabilityData = [
            'summary' => $service->getProfitabilitySummary($dateRange),
            'most_profitable' => $service->getMostProfitableProducts(10, $dateRange),
            'least_profitable' => $service->getLeastProfitableProducts(10, $dateRange),
            'by_category' => $service->getProfitabilityByCategory($dateRange),
            'trend' => $service->getProfitTrend(30),
        ];
    }

    /**
     * Load Turnover Report Data
     */
    public function loadTurnoverReport(): void
    {
        $service = app(TurnoverService::class);
        $days = 30;

        $this->turnoverData = [
            'summary' => $service->getTurnoverSummary($days),
            'fast_movers' => $service->getFastMovingProducts($days, 20),
            'slow_movers' => $service->getSlowMovingProducts(90, 20),
        ];
    }

    /**
     * Action: Generate Report
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label('Actualizar Datos')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->action(function () {
                    $this->loadDefaultData();

                    Notification::make()
                        ->title('Reportes Actualizados')
                        ->success()
                        ->send();
                }),

            Action::make('export')
                ->label('Exportar a Excel')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->visible(fn () => auth()->user()->can('inventory.export_reports'))
                ->action(function () {
                    Notification::make()
                        ->title('Exportación en proceso')
                        ->body('La descarga comenzará en breve...')
                        ->info()
                        ->send();

                    // TODO: Implement export functionality
                }),
        ];
    }
}
