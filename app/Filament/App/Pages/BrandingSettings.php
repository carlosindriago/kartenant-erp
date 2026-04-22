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

use App\Models\Tenant;
use App\Services\LogoOptimizationService;
use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Storage;

class BrandingSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-paint-brush';

    protected static string $view = 'filament.app.pages.branding-settings';

    protected static ?string $navigationLabel = 'Branding';

    protected static ?string $title = 'Configuración de Branding';

    protected static ?string $navigationGroup = 'Configuración';

    protected static ?int $navigationSort = 10;

    public ?array $data = [];

    public function mount(): void
    {
        $tenant = Tenant::current();

        if (!$tenant) {
            abort(404, 'Tenant not found');
        }

        $this->form->fill([
            'logo_type' => $tenant->logo_type ?? 'text',
            'company_display_name' => $tenant->company_display_name ?? $tenant->name,
            'logo_background_color' => $tenant->logo_background_color ?? '#ffffff',
            'logo_text_color' => $tenant->logo_text_color ?? '#1f2937',
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Configuración de Logo')
                    ->description('Personaliza cómo se muestra tu marca en el sistema')
                    ->schema([
                        Radio::make('logo_type')
                            ->label('Tipo de Logo')
                            ->options([
                                'text' => 'Texto (Nombre de la empresa)',
                                'image' => 'Imagen (Logo personalizado)',
                            ])
                            ->default('text')
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state) {
                                // Limpiar preview cuando cambia el tipo
                                $this->dispatch('logoTypeChanged', type: $state);
                            })
                            ->columnSpanFull(),

                        TextInput::make('company_display_name')
                            ->label('Nombre a Mostrar')
                            ->placeholder('Ej: Mi Empresa S.A.')
                            ->helperText('Este nombre aparecerá en el logo cuando uses modo texto')
                            ->maxLength(255)
                            ->reactive()
                            ->visible(fn (Forms\Get $get) => $get('logo_type') === 'text')
                            ->columnSpanFull(),

                        ColorPicker::make('logo_text_color')
                            ->label('Color del Texto')
                            ->helperText('Color que se usará para el texto del logo')
                            ->visible(fn (Forms\Get $get) => $get('logo_type') === 'text')
                            ->columnSpan(1),

                        ColorPicker::make('logo_background_color')
                            ->label('Color de Fondo (Opcional)')
                            ->helperText('Deja en blanco para fondo transparente')
                            ->visible(fn (Forms\Get $get) => $get('logo_type') === 'text')
                            ->columnSpan(1),

                        FileUpload::make('logo_image')
                            ->label('Archivo de Logo')
                            ->image()
                            ->maxSize(2048) // 2MB
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/svg+xml', 'image/webp'])
                            ->disk('public')
                            ->directory('temp') // Temporal, se moverá en el proceso
                            ->visibility('public')
                            ->imageResizeMode('contain')
                            ->imageCropAspectRatio('4:1')
                            ->imageResizeTargetWidth('300')
                            ->imageResizeTargetHeight('100')
                            ->helperText(function () {
                                $restrictions = LogoOptimizationService::getRestrictions();
                                return "Formatos: {$restrictions['allowed_formats']} | Tamaño máx: {$restrictions['max_size']} | Dimensiones recomendadas: {$restrictions['recommended_dimensions']}";
                            })
                            ->visible(fn (Forms\Get $get) => $get('logo_type') === 'image')
                            ->columnSpanFull(),

                        Placeholder::make('current_logo_preview')
                            ->label('Logo Actual')
                            ->content(function () {
                                $tenant = Tenant::current();

                                if (!$tenant) {
                                    return 'No disponible';
                                }

                                if ($tenant->usesImageLogo() && $tenant->logo_url) {
                                    return view('filament.components.logo-preview', [
                                        'type' => 'image',
                                        'url' => $tenant->logo_url,
                                    ]);
                                }

                                return view('filament.components.logo-preview', [
                                    'type' => 'text',
                                    'text' => $tenant->display_name,
                                    'textColor' => $tenant->logo_text_color ?? '#1f2937',
                                    'backgroundColor' => $tenant->logo_background_color ?? '#ffffff',
                                ]);
                            })
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Información')
                    ->description('Detalles técnicos y mejores prácticas')
                    ->schema([
                        Placeholder::make('info')
                            ->label('')
                            ->content(function () {
                                return view('filament.components.branding-info');
                            })
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $tenant = Tenant::current();

        if (!$tenant) {
            Notification::make()
                ->title('Error')
                ->body('No se pudo identificar el tenant')
                ->danger()
                ->send();
            return;
        }

        try {
            $logoService = new LogoOptimizationService();

            // Si cambió a tipo imagen y se subió un archivo
            if ($data['logo_type'] === 'image' && !empty($data['logo_image'])) {
                // Obtener el archivo temporal
                $tempPath = $data['logo_image'];
                $fullPath = Storage::disk('public')->path($tempPath);

                if (file_exists($fullPath)) {
                    $file = new \Illuminate\Http\UploadedFile(
                        $fullPath,
                        basename($tempPath),
                        Storage::disk('public')->mimeType($tempPath),
                        null,
                        true
                    );

                    // Procesar y optimizar el logo
                    $result = $logoService->processLogo($file, $tenant);

                    // Eliminar archivo temporal
                    Storage::disk('public')->delete($tempPath);

                    if (!$result['success']) {
                        Notification::make()
                            ->title('Error al procesar el logo')
                            ->body($result['message'])
                            ->danger()
                            ->send();
                        return;
                    }

                    // Actualizar tenant con nueva ruta
                    $tenant->update([
                        'logo_type' => 'image',
                        'logo_path' => $result['path'],
                        'company_display_name' => $data['company_display_name'] ?? $tenant->name,
                    ]);

                    Notification::make()
                        ->title('Logo actualizado')
                        ->body($result['message'])
                        ->success()
                        ->send();

                    $this->redirect(static::getUrl());
                    return;
                }
            }

            // Si es tipo texto, actualizar solo los datos
            if ($data['logo_type'] === 'text') {
                // Eliminar logo anterior si existe
                if ($tenant->logo_path) {
                    $logoService->deleteLogo($tenant);
                }

                $tenant->update([
                    'logo_type' => 'text',
                    'company_display_name' => $data['company_display_name'] ?? $tenant->name,
                    'logo_text_color' => $data['logo_text_color'] ?? '#1f2937',
                    'logo_background_color' => $data['logo_background_color'] ?? '#ffffff',
                    'logo_path' => null,
                ]);

                Notification::make()
                    ->title('Branding actualizado')
                    ->body('La configuración de branding se ha guardado correctamente')
                    ->success()
                    ->send();

                $this->redirect(static::getUrl());
                return;
            }

            // Si solo cambió colores (imagen ya existe)
            $tenant->update([
                'company_display_name' => $data['company_display_name'] ?? $tenant->name,
                'logo_text_color' => $data['logo_text_color'] ?? null,
                'logo_background_color' => $data['logo_background_color'] ?? null,
            ]);

            Notification::make()
                ->title('Configuración guardada')
                ->body('Los cambios se han guardado correctamente')
                ->success()
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Error al guardar')
                ->body('Ocurrió un error: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function resetBranding(): void
    {
        $tenant = Tenant::current();

        if (!$tenant) {
            return;
        }

        try {
            $logoService = new LogoOptimizationService();
            $logoService->deleteLogo($tenant);

            $tenant->update([
                'logo_type' => 'text',
                'company_display_name' => $tenant->name,
                'logo_text_color' => '#1f2937',
                'logo_background_color' => '#ffffff',
            ]);

            Notification::make()
                ->title('Branding restablecido')
                ->body('Se ha restaurado la configuración predeterminada')
                ->success()
                ->send();

            $this->redirect(static::getUrl());

        } catch (\Exception $e) {
            Notification::make()
                ->title('Error')
                ->body('No se pudo restablecer el branding: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }
}
