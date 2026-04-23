<?php

/**
 * Kartenant - Ferretero Ágil
 *
 * Este archivo es parte de Kartenant.
 *
 * @copyright Copyright (c) 2025-2026 Kartenant
 * @license   GNU AGPLv3 <https://www.gnu.org/licenses/agpl-3.0.txt>
 */

namespace App\Filament\App\Pages;

use App\Models\TenantSetting;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class Settings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Configuración';

    protected static ?string $title = 'Configuración del Sistema';

    protected static ?string $navigationGroup = 'Sistema';

    protected static ?int $navigationSort = 99;

    protected static string $view = 'filament.app.pages.settings';

    public ?array $data = [];

    public function mount(): void
    {
        $settings = TenantSetting::getForCurrentTenant();
        $this->form->fill($settings->toArray());
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Políticas del Punto de Venta')
                    ->description('Configura las reglas para las operaciones del POS y devoluciones.')
                    ->icon('heroicon-o-shopping-cart')
                    ->schema([
                        Toggle::make('allow_cashier_void_last_sale')
                            ->label('Permitir a los cajeros anular su última venta')
                            ->helperText('Si se activa, los cajeros verán el botón para anular la última venta que realizaron dentro del límite de tiempo.')
                            ->live()
                            ->default(true),

                        TextInput::make('cashier_void_time_limit_minutes')
                            ->label('Límite de tiempo para anular (minutos)')
                            ->helperText('Los cajeros solo podrán anular ventas realizadas dentro de estos minutos.')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(60)
                            ->default(5)
                            ->suffix('minutos')
                            ->visible(fn ($get) => $get('allow_cashier_void_last_sale')),

                        Toggle::make('cashier_void_requires_same_day')
                            ->label('Anulación solo el mismo día')
                            ->helperText('Si se activa, los cajeros solo pueden anular ventas realizadas el mismo día, independientemente del límite de tiempo.')
                            ->default(true)
                            ->visible(fn ($get) => $get('allow_cashier_void_last_sale')),

                        Toggle::make('cashier_void_requires_own_sale')
                            ->label('Cajero solo puede anular sus propias ventas')
                            ->helperText('Si se activa, los cajeros solo pueden anular ventas que ellos mismos registraron.')
                            ->default(true)
                            ->visible(fn ($get) => $get('allow_cashier_void_last_sale')),
                    ]),

                Section::make('📚 Guía Rápida')
                    ->description('Entiende cómo funcionan los permisos de anulación y devolución')
                    ->collapsed()
                    ->icon('heroicon-o-light-bulb')
                    ->schema([
                        \Filament\Forms\Components\Placeholder::make('info')
                            ->label('')
                            ->content(new \Illuminate\Support\HtmlString('
<div class="space-y-4 text-sm">
    <div class="bg-blue-50 dark:bg-blue-900/20 border-l-4 border-blue-500 p-4 rounded-r">
        <h4 class="font-bold text-blue-900 dark:text-blue-100 mb-2">🔄 Anular Venta (Para Cajeros)</h4>
        <p class="text-blue-800 dark:text-blue-200 mb-2">
            <strong>Propósito:</strong> Corregir errores inmediatos, como cuando el cajero se equivoca al registrar una venta.
        </p>
        <p class="text-blue-800 dark:text-blue-200 mb-2">
            <strong>¿Cuándo?</strong> Solo durante los primeros minutos después de hacer la venta (tiempo configurable arriba).
        </p>
        <p class="text-blue-800 dark:text-blue-200">
            <strong>Requisitos:</strong> Contraseña del usuario + motivo escrito (auditoría completa).
        </p>
    </div>
    
    <div class="bg-purple-50 dark:bg-purple-900/20 border-l-4 border-purple-500 p-4 rounded-r">
        <h4 class="font-bold text-purple-900 dark:text-purple-100 mb-2">📦 Procesar Devolución (Para Gerentes/Supervisores)</h4>
        <p class="text-purple-800 dark:text-purple-200 mb-2">
            <strong>Propósito:</strong> Proceso formal cuando un cliente regresa después (al día siguiente, semana después, etc.).
        </p>
        <p class="text-purple-800 dark:text-purple-200 mb-2">
            <strong>¿Dónde?</strong> En el menú <span class=\"font-mono bg-purple-100 dark:bg-purple-800 px-2 py-0.5 rounded\">Ventas</span>, botón \"Procesar Devolución\" en cada venta.
        </p>
        <p class="text-purple-800 dark:text-purple-200 mb-2">
            <strong>¿Cuándo?</strong> En cualquier momento, sin límite de tiempo.
        </p>
        <p class="text-purple-800 dark:text-purple-200">
            <strong>Requisitos:</strong> Permiso especial "Procesar Devoluciones" (solo personal autorizado).
        </p>
    </div>
    
    <div class="bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg p-4">
        <h4 class="font-bold text-gray-900 dark:text-white mb-3">👥 Permisos por Rol</h4>
        <div class="space-y-2 text-gray-900 dark:text-white">
            <div class="flex items-start gap-3">
                <span class="text-green-600 dark:text-green-400 text-lg">✓</span>
                <div>
                    <strong>Administrador:</strong>
                    <span>Acceso completo a todo</span>
                </div>
            </div>
            <div class="flex items-start gap-3">
                <span class="text-green-600 dark:text-green-400 text-lg">✓</span>
                <div>
                    <strong>Gerente:</strong>
                    <span>Puede anular + procesar devoluciones</span>
                </div>
            </div>
            <div class="flex items-start gap-3">
                <span class="text-green-600 dark:text-green-400 text-lg">✓</span>
                <div>
                    <strong>Supervisor:</strong>
                    <span>Puede anular + procesar devoluciones</span>
                </div>
            </div>
            <div class="flex items-start gap-3">
                <span class="text-yellow-600 dark:text-yellow-400 text-lg">⚠</span>
                <div>
                    <strong>Cajero:</strong>
                    <span>Solo puede anular (según las políticas que configures arriba)</span>
                </div>
            </div>
            <div class="flex items-start gap-3">
                <span class="text-red-600 dark:text-red-400 text-lg">✗</span>
                <div>
                    <strong>Almacenero:</strong>
                    <span>Sin acceso a anulaciones ni devoluciones</span>
                </div>
            </div>
        </div>
    </div>
    
    <div class="bg-green-50 dark:bg-green-900/20 border-l-4 border-green-500 p-4 rounded-r">
        <h4 class="font-bold text-green-900 dark:text-green-100 mb-2">💡 Consejo</h4>
        <p class="text-green-800 dark:text-green-200">
            Si tienes un negocio pequeño con personal de confianza, puedes ser más permisivo con las políticas de arriba.
            Si tienes muchos empleados o quieres más control, desactiva la opción para cajeros y deja que solo los gerentes manejen las anulaciones.
        </p>
    </div>
</div>
                            '))
                            ->columnSpanFull(),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $settings = TenantSetting::getForCurrentTenant();
        $settings->update($data);

        Notification::make()
            ->title('Configuración guardada')
            ->success()
            ->body('Las políticas del sistema han sido actualizadas correctamente.')
            ->send();
    }

    protected function getFormActions(): array
    {
        return [
            \Filament\Actions\Action::make('save')
                ->label('Guardar Configuración')
                ->action('save'),
        ];
    }

    public function hasLogo(): bool
    {
        return false;
    }

    protected function hasFullWidthFormActions(): bool
    {
        return false;
    }
}
