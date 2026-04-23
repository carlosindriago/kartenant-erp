<?php

/**
 * Kartenant - Ferretero Ágil
 *
 * Este archivo es parte de Kartenant.
 *
 * @copyright Copyright (c) 2025-2026 Kartenant
 * @license   GNU AGPLv3 <https://www.gnu.org/licenses/agpl-3.0.txt>
 */

namespace App\Filament\App\Pages\POS;

use App\Modules\POS\Models\CashRegister;
use App\Services\POS\CashRegisterService;
use Filament\Actions\Action;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\HtmlString;

class CloseCashRegisterPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-lock-closed';

    protected static string $view = 'filament.app.pages.pos.close-cash-register';

    protected static ?string $navigationGroup = 'POS';

    protected static ?int $navigationSort = 2;

    protected static ?string $title = 'Cerrar Caja';

    protected static ?string $slug = 'pos/close-register';

    public static function getNavigationLabel(): string
    {
        return 'Cerrar Caja';
    }

    public ?array $data = [];

    public ?CashRegister $cashRegister = null;

    public array $summary = [];

    /**
     * Mount - Verificar si tiene caja abierta
     */
    public function mount()
    {
        $userId = auth('tenant')->id();

        // Verificar si el usuario tiene una caja abierta
        if (! $userId || ! CashRegister::userHasOpenRegister($userId)) {
            Notification::make()
                ->warning()
                ->title('No tienes caja abierta')
                ->body('Debes abrir una caja antes de poder cerrarla.')
                ->persistent()
                ->send();

            return redirect(\App\Filament\App\Pages\POS\OpenCashRegisterPage::getUrl());
        }

        // Obtener la caja abierta
        $this->cashRegister = CashRegister::getUserOpenRegister($userId);

        // Obtener resumen del turno
        $service = new CashRegisterService;
        $this->summary = $service->getCashRegisterSummary($this->cashRegister->id);

        $this->form->fill([
            'actual_amount' => $this->summary['expected_cash_amount'],
        ]);
    }

    /**
     * Form schema
     */
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Resumen del Turno')
                    ->description('Total de ventas y movimientos durante tu turno')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                Placeholder::make('total_sales')
                                    ->label('Transacciones')
                                    ->content(fn () => new HtmlString(
                                        '<span class="text-2xl font-bold text-primary-600 dark:text-primary-400">'.
                                        $this->summary['total_sales'].
                                        '</span>'
                                    )),

                                Placeholder::make('total_amount')
                                    ->label('Total Ventas')
                                    ->content(fn () => new HtmlString(
                                        '<span class="text-2xl font-bold text-success-600 dark:text-success-400">$'.
                                        number_format($this->summary['total_amount'], 0).
                                        '</span>'
                                    )),

                                Placeholder::make('hours_open')
                                    ->label('Tiempo Abierto')
                                    ->content(fn () => new HtmlString(
                                        '<span class="text-2xl font-bold text-info-600 dark:text-info-400">'.
                                        $this->summary['hours_open'].'h'.
                                        '</span>'
                                    )),

                                Placeholder::make('cancelled_sales')
                                    ->label('Cancelaciones')
                                    ->content(fn () => new HtmlString(
                                        '<span class="text-2xl font-bold '.
                                        ($this->summary['cancelled_sales'] > 0 ? 'text-danger-600 dark:text-danger-400' : 'text-gray-600 dark:text-gray-400').'">'.
                                        $this->summary['cancelled_sales'].
                                        '</span>'
                                    )),
                            ]),
                    ]),

                Section::make('Desglose por Método de Pago')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Placeholder::make('cash_sales')
                                    ->label('💵 Efectivo')
                                    ->content(fn () => '$'.number_format($this->summary['cash_sales'], 0)),

                                Placeholder::make('card_sales')
                                    ->label('💳 Tarjeta')
                                    ->content(fn () => '$'.number_format($this->summary['card_sales'], 0)),

                                Placeholder::make('transfer_sales')
                                    ->label('🏦 Transferencia')
                                    ->content(fn () => '$'.number_format($this->summary['transfer_sales'], 0)),
                            ]),
                    ]),

                Section::make('Arqueo de Caja')
                    ->description('Cuenta el efectivo real en tu caja y verifica el desglose')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Placeholder::make('initial_amount')
                                    ->label('Monto Inicial')
                                    ->content(fn () => new HtmlString(
                                        '<span class="text-lg font-semibold text-gray-900 dark:text-white">$'.
                                        number_format($this->summary['initial_amount'], 2).
                                        '</span>'
                                    )),

                                Placeholder::make('cash_sales_display')
                                    ->label('+ Ventas Efectivo')
                                    ->content(fn () => new HtmlString(
                                        '<span class="text-lg font-semibold text-success-600 dark:text-success-400">$'.
                                        number_format($this->summary['cash_sales'], 2).
                                        '</span>'
                                    )),

                                Placeholder::make('cash_returns_display')
                                    ->label('- Devoluciones')
                                    ->content(fn () => new HtmlString(
                                        '<span class="text-lg font-semibold text-danger-600 dark:text-danger-400">$'.
                                        number_format($this->summary['cash_returns'], 2).
                                        '</span>'
                                    )),
                            ]),

                        Placeholder::make('expected_amount')
                            ->label('💰 Monto Esperado en Caja')
                            ->content(fn () => new HtmlString(
                                '<div class="text-3xl font-bold text-primary-700 dark:text-primary-400 bg-primary-50 dark:bg-primary-950/20 rounded-lg p-4 text-center">'.
                                '$'.number_format($this->summary['expected_cash_amount'], 2).
                                '</div>'
                            )),

                        TextInput::make('actual_amount')
                            ->label('💵 Efectivo Real Contado')
                            ->prefix('$')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->reactive()
                            ->helperText('Cuenta todo el efectivo en tu caja'),

                        Placeholder::make('difference')
                            ->label('Diferencia')
                            ->content(function () {
                                $actual = $this->form->getState()['actual_amount'] ?? 0;
                                $expected = $this->summary['expected_cash_amount'];
                                $diff = $actual - $expected;

                                $color = $diff == 0 ? 'success' : ($diff > 0 ? 'warning' : 'danger');
                                $icon = $diff == 0 ? '✓' : ($diff > 0 ? '⬆' : '⬇');
                                $label = $diff == 0 ? 'Exacto' : ($diff > 0 ? 'Sobrante' : 'Faltante');

                                return new HtmlString(
                                    '<div class="bg-'.$color.'-50 dark:bg-'.$color.'-950/20 rounded-lg p-4 border-2 border-'.$color.'-500">'.
                                    '<div class="text-center">'.
                                    '<div class="text-'.$color.'-600 dark:text-'.$color.'-400 font-semibold mb-1">'.$icon.' '.$label.'</div>'.
                                    '<div class="text-2xl font-bold text-'.$color.'-700 dark:text-'.$color.'-300">$'.number_format(abs($diff), 2).'</div>'.
                                    '</div>'.
                                    '</div>'
                                );
                            })
                            ->reactive(),

                        Textarea::make('closing_notes')
                            ->label('Notas de Cierre')
                            ->placeholder('Observaciones, explicación de diferencias, incidentes...')
                            ->rows(3)
                            ->maxLength(500),
                    ])
                    ->columns(1),
            ])
            ->statePath('data');
    }

    /**
     * Get form actions
     */
    protected function getFormActions(): array
    {
        return [
            Action::make('close')
                ->label('Cerrar Caja')
                ->color('danger')
                ->icon('heroicon-o-lock-closed')
                ->size('lg')
                ->requiresConfirmation()
                ->modalHeading('Confirmar Cierre de Caja')
                ->modalDescription(function () {
                    $actual = $this->form->getState()['actual_amount'] ?? 0;
                    $expected = $this->summary['expected_cash_amount'];
                    $diff = $actual - $expected;

                    return '¿Confirmas el cierre con $'.number_format($actual, 2).' contados? '.
                           ($diff != 0 ? 'Diferencia: $'.number_format(abs($diff), 2).' '.($diff > 0 ? '(sobrante)' : '(faltante)') : '(Exacto ✓)');
                })
                ->modalSubmitActionLabel('Sí, Cerrar Caja')
                ->action('closeRegister'),
        ];
    }

    /**
     * Cerrar caja
     */
    public function closeRegister(): void
    {
        $data = $this->form->getState();
        $service = new CashRegisterService;

        try {
            // Cerrar la caja
            $cashRegister = $service->closeRegister(
                registerId: $this->cashRegister->id,
                actualAmount: $data['actual_amount'],
                cashBreakdown: null, // TODO: Implementar desglose de billetes/monedas
                notes: $data['closing_notes'] ?? null,
                closedByUserId: auth('tenant')->id()
            );

            $diff = $cashRegister->difference;
            $diffText = $diff == 0 ? 'exacto ✓' :
                       ($diff > 0 ? '$'.number_format($diff, 2).' sobrante' : '$'.number_format(abs($diff), 2).' faltante');

            Notification::make()
                ->success()
                ->title('¡Caja Cerrada!')
                ->body("Turno finalizado. Arqueo: {$diffText}")
                ->seconds(10)
                ->send();

            // Redirigir al dashboard
            $this->redirect(\App\Filament\App\Pages\Dashboard::getUrl());

        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title('Error al cerrar caja')
                ->body($e->getMessage())
                ->persistent()
                ->send();
        }
    }

    /**
     * Should register navigation
     */
    public static function shouldRegisterNavigation(): bool
    {
        // Solo mostrar si el usuario tiene caja abierta
        $userId = auth('tenant')->id();

        return $userId ? CashRegister::userHasOpenRegister($userId) : false;
    }

    /**
     * Can access
     */
    public static function canAccess(): bool
    {
        return auth('tenant')->user()?->can('pos.close_register') ?? false;
    }
}
