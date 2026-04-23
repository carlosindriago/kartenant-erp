<?php

/**
 * Kartenant - Ferretero Ágil
 *
 * Este archivo es parte de Kartenant.
 *
 * @copyright Copyright (c) 2025-2026 Kartenant
 * @license   GNU AGPLv3 <https://www.gnu.org/licenses/agpl-3.0.txt>
 */

namespace App\Livewire;

use App\Services\BugReportService;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

class BugReportModal extends Component implements HasForms
{
    use InteractsWithForms;
    use WithFileUploads;

    public bool $isOpen = false;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
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
                    ->native(false),

                TextInput::make('title')
                    ->label('Título del Problema')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('Ej: Error al guardar una venta'),

                Textarea::make('description')
                    ->label('Descripción Detallada')
                    ->required()
                    ->rows(4)
                    ->placeholder('Describe qué estaba haciendo cuando ocurrió el problema...'),

                Textarea::make('steps')
                    ->label('Pasos para Reproducir (opcional)')
                    ->rows(3)
                    ->placeholder("1. Ir a...\n2. Click en...\n3. Ver error..."),

                FileUpload::make('screenshots')
                    ->label('Capturas de Pantalla (opcional)')
                    ->image()
                    ->multiple()
                    ->maxFiles(3)
                    ->maxSize(5120) // 5MB
                    ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/jpg'])
                    ->helperText('Puedes adjuntar hasta 3 capturas de pantalla (máx. 5MB cada una)'),
            ])
            ->statePath('data');
    }

    #[On('openBugReportModal')]
    public function openModal(): void
    {
        $this->isOpen = true;
        \Log::info('[BugReport] Modal opened from navigation');
    }

    public function closeModal(): void
    {
        $this->isOpen = false;
        $this->form->fill();
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

            $this->closeModal();
        } else {
            Notification::make()
                ->title('Error al Enviar')
                ->body($result['message'])
                ->danger()
                ->send();
        }
    }

    public function render()
    {
        return view('livewire.bug-report-modal');
    }
}
