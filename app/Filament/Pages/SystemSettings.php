<?php

namespace App\Filament\Pages;

use App\Models\SystemSetting;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class SystemSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Configuración Global';

    protected static ?string $title = 'Configuración Global del Sistema';

    protected static string $view = 'filament.pages.system-settings';

    protected static ?string $navigationGroup = 'Configuración del Sistema';

    protected static ?int $navigationSort = 1;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'trial_auto_enable' => SystemSetting::get('trial.auto_enable', true),
            'trial_duration_days' => SystemSetting::get('trial.duration_days', 7),
            'registration_enabled' => SystemSetting::get('registration.enabled', true),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Configuración de Trial')
                    ->description('Controla el comportamiento del período de prueba gratuito')
                    ->schema([
                        Forms\Components\Toggle::make('trial_auto_enable')
                            ->label('Habilitar Trial Automático')
                            ->helperText('Si está activado, los usuarios obtienen automáticamente un período de prueba al registrarse. Si está desactivado, deben pagar inmediatamente.')
                            ->reactive()
                            ->inline(false),

                        Forms\Components\TextInput::make('trial_duration_days')
                            ->label('Duración del Trial (días)')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(90)
                            ->suffix('días')
                            ->helperText('Número de días del período de prueba gratuito')
                            ->required()
                            ->visible(fn ($get) => $get('trial_auto_enable')),
                    ])
                    ->columns(2),

                Section::make('Configuración de Registro')
                    ->description('Controla quién puede registrarse en el sistema')
                    ->schema([
                        Forms\Components\Toggle::make('registration_enabled')
                            ->label('Habilitar Registro Público')
                            ->helperText('Si está desactivado, solo los SuperAdmins pueden crear nuevos tenants')
                            ->inline(false),
                    ]),

                Section::make('Estado Actual')
                    ->description('Resumen de la configuración actual')
                    ->schema([
                        Forms\Components\Placeholder::make('current_status')
                            ->label('')
                            ->content(function () {
                                $trialEnabled = SystemSetting::get('trial.auto_enable', true);
                                $trialDays = SystemSetting::get('trial.duration_days', 7);
                                $activeGateway = SystemSetting::get('payments.active_gateway', 'manual_transfer');

                                if ($trialEnabled) {
                                    $message = "✅ **Trial Automático ACTIVADO**\n\n";
                                    $message .= "Los usuarios obtienen {$trialDays} días de prueba gratis al registrarse.\n\n";
                                } else {
                                    $message = "💰 **Venta Directa ACTIVADA**\n\n";
                                    $message .= "Los usuarios deben pagar inmediatamente usando: **{$activeGateway}**\n\n";
                                }

                                return $message;
                            })
                            ->columnSpanFull(),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        SystemSetting::set('trial.auto_enable', $data['trial_auto_enable'], 'boolean', 'trial');
        SystemSetting::set('trial.duration_days', $data['trial_duration_days'], 'integer', 'trial');
        SystemSetting::set('registration.enabled', $data['registration_enabled'], 'boolean', 'registration');

        // Clear cache
        SystemSetting::clearAllCache();

        Notification::make()
            ->title('Configuración guardada')
            ->success()
            ->body('Los cambios se han aplicado correctamente.')
            ->send();
    }

    public function canView(): bool
    {
        $user = auth('superadmin')->user();

        return $user?->is_super_admin ?? false;
    }
}
