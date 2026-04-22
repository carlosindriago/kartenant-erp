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

use Filament\Pages\Page;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Section;
use Filament\Notifications\Notification;
use Filament\Actions\Action;
use App\Services\POS\CashRegisterService;
use App\Modules\POS\Models\CashRegister;
use Illuminate\Support\Facades\Auth;

class OpenCashRegisterPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static string $view = 'filament.app.pages.pos.open-cash-register';
    
    protected static ?string $navigationGroup = 'POS';
    
    protected static ?int $navigationSort = 1;
    
    protected static ?string $title = 'Abrir Caja';
    
    protected static ?string $slug = 'pos/open-register';
    
    public static function getNavigationLabel(): string
    {
        return 'Abrir Caja';
    }
    
    public ?array $data = [];
    
    /**
     * Mount - Verificar si ya tiene caja abierta
     */
    public function mount()
    {
        // Usar el guard tenant explícitamente
        $userId = auth('tenant')->id();
        
        // Debug: Log para verificar el usuario
        \Log::info('OpenCashRegister mount', [
            'user_id' => $userId,
            'user_name' => auth('tenant')->user()->name ?? 'unknown',
            'guard' => 'tenant',
        ]);
        
        // Verificar si el usuario ya tiene una caja abierta
        if ($userId && CashRegister::userHasOpenRegister($userId)) {
            $openRegister = CashRegister::getUserOpenRegister($userId);
            
            // Debug: Log de la caja encontrada
            \Log::info('Usuario ya tiene caja abierta', [
                'user_id' => $userId,
                'register_id' => $openRegister->id,
                'register_number' => $openRegister->register_number,
                'opened_by' => $openRegister->opened_by_user_id,
            ]);
            
            Notification::make()
                ->warning()
                ->title('Ya tienes una caja abierta')
                ->body("Tu caja {$openRegister->register_number} está abierta desde {$openRegister->opened_at->format('H:i')}")
                ->persistent()
                ->send();
            
            // Redirigir al dashboard
            return redirect(\App\Filament\App\Pages\Dashboard::getUrl());
        }
        
        $this->form->fill([
            'initial_amount' => 0,
        ]);
    }
    
    /**
     * Form schema
     */
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Apertura de Caja')
                    ->description('Ingresa el monto inicial con el que abres tu caja')
                    ->schema([
                        TextInput::make('initial_amount')
                            ->label('Monto Inicial')
                            ->prefix('$')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->required()
                            ->helperText('Cuenta el efectivo con el que inicias tu turno')
                            ->reactive()
                            ->afterStateUpdated(function ($state, $set) {
                                $set('initial_amount', abs($state ?? 0));
                            }),
                        
                        Textarea::make('opening_notes')
                            ->label('Notas de Apertura')
                            ->placeholder('Ej: Billetes grandes, monedas, observaciones...')
                            ->rows(3)
                            ->maxLength(500),
                    ])
                    ->columns(1),
            ])
            ->statePath('data');
    }
    
    /**
     * Get actions
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('cancel')
                ->label('Cancelar')
                ->color('gray')
                ->url(\App\Filament\App\Pages\Dashboard::getUrl())
                ->icon('heroicon-o-x-mark'),
        ];
    }
    
    /**
     * Get form actions
     */
    protected function getFormActions(): array
    {
        return [
            Action::make('open')
                ->label('Abrir Caja')
                ->color('success')
                ->icon('heroicon-o-lock-open')
                ->size('lg')
                ->requiresConfirmation()
                ->modalHeading('Confirmar Apertura de Caja')
                ->modalDescription(function () {
                    $amount = $this->form->getState()['initial_amount'] ?? 0;
                    return "¿Confirmas que inicias con $" . number_format($amount, 2) . " en la caja?";
                })
                ->modalSubmitActionLabel('Sí, Abrir Caja')
                ->action('openRegister'),
        ];
    }
    
    /**
     * Abrir caja
     */
    public function openRegister(): void
    {
        $data = $this->form->getState();
        $service = new CashRegisterService();
        $userId = auth('tenant')->id();
        
        // Log adicional
        \Log::info('Intentando abrir caja', [
            'amount' => $data['initial_amount'],
            'notes' => $data['opening_notes'] ?? '',
            'user_id' => $userId,
            'guard' => 'tenant',
        ]);
        
        try {
            // Verificar una vez más antes de abrir
            $canOpen = $service->canUserOpenRegister($userId);
            
            if (!$canOpen['can_open']) {
                Notification::make()
                    ->danger()
                    ->title('No puedes abrir caja')
                    ->body($canOpen['reason'])
                    ->persistent()
                    ->send();
                    
                return;
            }
            
            // Abrir la caja
            $cashRegister = $service->openRegister(
                userId: $userId,
                initialAmount: $data['initial_amount'],
                notes: $data['opening_notes'] ?? null
            );
            
            Notification::make()
                ->success()
                ->title('¡Caja Abierta!')
                ->body("Caja {$cashRegister->register_number} abierta exitosamente con $" . number_format($cashRegister->initial_amount, 2))
                ->seconds(5)
                ->send();
            
            // Redirigir al dashboard
            $this->redirect(\App\Filament\App\Pages\Dashboard::getUrl());
            
        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title('Error al abrir caja')
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
        // Solo mostrar en navegación si el usuario NO tiene caja abierta
        $userId = auth('tenant')->id();
        return $userId ? !CashRegister::userHasOpenRegister($userId) : false;
    }
    
    /**
     * Can access
     */
    public static function canAccess(): bool
    {
        return auth('tenant')->user()?->can('pos.open_register') ?? false;
    }
}
