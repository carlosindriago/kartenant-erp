<?php

namespace App\Filament\App\Pages;

use App\Models\PaymentProof;
use App\Models\Tenant;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Http;

class BillingDashboard extends Page implements HasForms, HasTable
{
    use InteractsWithForms, InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static string $view = 'filament.app.pages.billing-dashboard';

    protected static ?string $navigationLabel = 'Facturación';

    protected static ?string $title = 'Centro de Facturación';

    protected static ?string $navigationGroup = 'Configuración';

    protected static ?int $navigationSort = 1;

    protected static bool $shouldRegisterNavigation = true;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function getHeading(): string
    {
        return 'Centro de Facturación';
    }

    public function getSubheading(): ?string
    {
        $tenant = Tenant::current();

        return $tenant ? 'Gestiona los pagos de tu suscripción' : 'Gestión de Suscripción';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label('Actualizar')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->action(fn () => $this->refreshData()),
        ];
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                // Payment proof upload section
                Section::make('Subir Comprobante de Pago')
                    ->description('Sube el comprobante de tu transferencia o depósito bancario')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->schema([
                        FileUpload::make('payment_proof')
                            ->label('Comprobante de Pago')
                            ->helperText('Formatos permitidos: PDF, JPG, PNG. Máximo 5MB')
                            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                            ->maxSize(5120) // 5MB
                            ->directory('payment-proofs')
                            ->visibility('private')
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(fn ($state, callable $set) => $state ? $set('file_uploaded', true) : $set('file_uploaded', false)
                            ),

                        Textarea::make('notes')
                            ->label('Notas (Opcional)')
                            ->helperText('Añade cualquier información adicional sobre este pago')
                            ->rows(3)
                            ->maxLength(500),

                        Placeholder::make('file_info')
                            ->label('Información del Archivo')
                            ->content(function (callable $get) {
                                $file = $get('payment_proof');
                                if (! $file) {
                                    return 'No se ha seleccionado ningún archivo';
                                }

                                return '✅ Archivo listo para subir: '.basename($file);
                            })
                            ->visible(fn (callable $get) => $get('payment_proof')),
                    ])
                    ->columns(1),
            ])
            ->statePath('data')
            ->model(Tenant::class);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                // This will be populated by API call
                PaymentProof::query()->where('tenant_id', Tenant::current()?->id)
            )
            ->columns([
                TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->description(fn ($record): string => 'Hace '.$record->created_at->diffForHumans()),

                TextColumn::make('amount')
                    ->label('Monto')
                    ->money('USD')
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('payment_method')
                    ->label('Método')
                    ->searchable()
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'transfer' => 'success',
                        'deposit' => 'warning',
                        default => 'gray',
                    }),

                IconColumn::make('status')
                    ->label('Estado')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-clock')
                    ->trueColor('success')
                    ->falseColor('warning')
                    ->label(fn ($record): string => match ($record->status) {
                        'approved' => 'Aprobado',
                        'pending' => 'Pendiente',
                        'rejected' => 'Rechazado',
                        default => 'Desconocido',
                    }),

                TextColumn::make('file_name')
                    ->label('Comprobante')
                    ->formatStateUsing(fn ($state) => basename($state))
                    ->limit(20),
            ])
            ->actions([
                Tables\Actions\Action::make('download')
                    ->label('Descargar')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->url(fn ($record): string => $record->file_url)
                    ->openUrlInNewTab(),

                Tables\Actions\Action::make('view')
                    ->label('Ver')
                    ->icon('heroicon-o-eye')
                    ->modalContent(fn ($record): View => view(
                        'filament.app.pages.payment-proof-modal',
                        ['record' => $record]
                    ))
                    ->modalWidth('lg'),
            ])
            ->emptyStateHeading('No hay comprobantes registrados')
            ->emptyStateDescription('Sube tu primer comprobante de pago para comenzar')
            ->emptyStateActions([
                Tables\Actions\Action::make('create')
                    ->label('Subir Primer Comprobante')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->action(fn () => $this->scrollToUploadSection()),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public function submitPaymentProof(): void
    {
        $data = $this->form->getState();

        if (! isset($data['payment_proof'])) {
            Notification::make()
                ->danger()
                ->title('Error')
                ->body('Por favor selecciona un archivo de comprobante')
                ->send();

            return;
        }

        try {
            // Get current tenant
            $tenant = Tenant::current();
            if (! $tenant) {
                Notification::make()
                    ->danger()
                    ->title('Error')
                    ->body('No se pudo identificar tu cuenta')
                    ->send();

                return;
            }

            // Call API endpoint
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->post('/api/v1/billing', [
                'tenant_id' => $tenant->id,
                'payment_proof' => $data['payment_proof'],
                'notes' => $data['notes'] ?? null,
            ]);

            if ($response->successful()) {
                Notification::make()
                    ->success()
                    ->title('¡Comprobante Enviado!')
                    ->body('Tu comprobante ha sido recibido y será procesado en las próximas horas.')
                    ->send();

                // Reset form
                $this->form->fill();
                $this->refreshData();

            } else {
                Notification::make()
                    ->danger()
                    ->title('Error al Subir')
                    ->body('No se pudo subir el comprobante. Intenta nuevamente.')
                    ->send();
            }

        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title('Error de Conexión')
                ->body('No se pudo conectar con el servidor. Intenta en unos minutos.')
                ->send();
        }
    }

    public function refreshData(): void
    {
        Notification::make()
            ->info()
            ->title('Actualizando')
            ->body('Obteniendo información más reciente...')
            ->send();
    }

    public function scrollToUploadSection(): void
    {
        $this->dispatch('scroll-to-upload');
    }

    public function getBillingData(): array
    {
        try {
            $tenant = Tenant::current();
            if (! $tenant) {
                return $this->getDefaultBillingData();
            }

            $response = Http::get('/api/v1/billing', [
                'tenant_id' => $tenant->id,
            ]);

            if ($response->successful()) {
                return $response->json();
            }

        } catch (\Exception $e) {
            // Return default data if API fails
        }

        return $this->getDefaultBillingData();
    }

    private function getDefaultBillingData(): array
    {
        return [
            'subscription' => [
                'plan_name' => 'Básico',
                'price' => 29.99,
                'status' => 'active',
                'billing_cycle' => 'monthly',
                'days_until_expiration' => 15,
                'on_trial' => false,
            ],
            'next_payment' => [
                'amount' => 29.99,
                'due_date' => now()->addDays(15)->format('Y-m-d'),
                'days_until_due' => 15,
            ],
            'payment_methods' => [
                'bank_transfer' => [
                    'name' => 'Transferencia Bancaria',
                    'details' => 'Banco: XXXXXX - Cuenta: XXXXXX - Titular: Emporio Digital',
                    'enabled' => true,
                ],
                'deposit' => [
                    'name' => 'Depósito Bancario',
                    'details' => 'Banco: XXXXXX - Cuenta: XXXXXX - Titular: Emporio Digital',
                    'enabled' => true,
                ],
            ],
        ];
    }
}
