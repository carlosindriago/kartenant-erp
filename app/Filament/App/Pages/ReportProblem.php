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

use App\Services\BugReportService;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Livewire\WithFileUploads;

class ReportProblem extends Page implements HasForms
{
    use InteractsWithForms;
    use WithFileUploads;

    protected static ?string $navigationIcon = 'heroicon-o-exclamation-triangle';

    protected static ?string $navigationLabel = 'Reportar Problema';

    protected static ?string $title = 'Reportar un Problema';

    protected static ?string $slug = 'report-problem';

    protected static ?string $navigationGroup = null; // Sin grupo, aparece al final

    protected static ?int $navigationSort = 999; // Al final del menú

    protected static string $view = 'filament.app.pages.report-problem';

    // No aparecer en navegación (solo accesible desde el botón del sidebar)
    protected static bool $shouldRegisterNavigation = false;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Información del Problema')
                    ->description('Describe el problema que estás experimentando con el mayor detalle posible.')
                    ->schema([
                        Select::make('severity')
                            ->label('Severidad')
                            ->required()
                            ->options([
                                'critical' => '🔴 Crítico - La aplicación no funciona',
                                'high' => '🟠 Alto - Funcionalidad importante afectada',
                                'medium' => '🟡 Medio - Problema molesto pero no bloqueante',
                                'low' => '🟢 Bajo - Mejora o problema menor',
                            ])
                            ->default('medium')
                            ->native(false)
                            ->helperText('Selecciona qué tan urgente es resolver este problema.'),

                        TextInput::make('title')
                            ->label('Título del Problema')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Ej: Error al guardar una venta')
                            ->helperText('Un resumen breve del problema.'),

                        Textarea::make('description')
                            ->label('Descripción Detallada')
                            ->required()
                            ->rows(4)
                            ->placeholder('Describe qué estaba haciendo cuando ocurrió el problema, qué resultado esperabas y qué ocurrió en su lugar...')
                            ->helperText('Proporciona todos los detalles que consideres relevantes.'),

                        Textarea::make('steps')
                            ->label('Pasos para Reproducir (opcional)')
                            ->rows(3)
                            ->placeholder("1. Ir a...\n2. Click en...\n3. Ver error...")
                            ->helperText('Si conoces los pasos exactos para reproducir el problema, descríbelos aquí.'),
                    ]),

                Section::make('Capturas de Pantalla')
                    ->description('Adjunta hasta 3 capturas de pantalla que muestren el problema.')
                    ->schema([
                        FileUpload::make('screenshots')
                            ->label('Capturas de Pantalla')
                            ->image()
                            ->multiple()
                            ->maxFiles(3)
                            ->maxSize(5120) // 5MB
                            ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/jpg'])
                            ->helperText('Puedes adjuntar hasta 3 capturas de pantalla (máx. 5MB cada una). Formatos: PNG, JPG, JPEG.')
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(false),
            ])
            ->statePath('data');
    }

    public function submit(): void
    {
        $data = $this->form->getState();

        $bugReportService = app(BugReportService::class);

        // Add automatic context
        $data['user_name'] = auth()->user()->name;
        $data['user_email'] = auth()->user()->email;
        $data['tenant_name'] = tenant()?->name ?? 'Unknown';
        $data['url'] = request()->fullUrl();
        $data['user_agent'] = request()->userAgent();

        $result = $bugReportService->submitReport($data);

        if ($result['success']) {
            Notification::make()
                ->title('Reporte Enviado')
                ->body('Gracias por tu reporte. Nuestro equipo lo revisará pronto.')
                ->success()
                ->send();

            // Reset form
            $this->form->fill();
        } else {
            Notification::make()
                ->title('Error al Enviar')
                ->body($result['message'])
                ->danger()
                ->send();
        }
    }

    protected function getFormActions(): array
    {
        return [
            \Filament\Actions\Action::make('submit')
                ->label('Enviar Reporte')
                ->icon('heroicon-o-paper-airplane')
                ->color('danger')
                ->size('lg')
                ->action('submit'),
        ];
    }
}
