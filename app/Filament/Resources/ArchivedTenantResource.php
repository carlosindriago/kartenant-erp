<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ArchivedTenantResource\Pages;
use App\Models\Tenant;
use App\Services\TenantBackupService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\HtmlString;

class ArchivedTenantResource extends Resource
{
    protected static ?string $model = Tenant::class;

    protected static ?string $navigationIcon = 'heroicon-o-archive-box-arrow-down';

    protected static ?string $navigationLabel = 'Tenants Archivados';

    protected static ?string $modelLabel = 'Tenant Archivado';

    protected static ?string $pluralModelLabel = 'Tenants Archivados';

    protected static ?string $navigationGroup = null;

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Archived tenants are read-only - no editing allowed
                Forms\Components\Section::make('Información del Tenant Archivado')
                    ->description('Los tenants archivados son de solo lectura. Para modificar, debes restaurar el tenant primero.')
                    ->schema([
                        Forms\Components\Placeholder::make('name')
                            ->label('Nombre de la Tienda')
                            ->content(fn ($record): string => $record->name ?? 'N/A'),

                        Forms\Components\Placeholder::make('domain')
                            ->label('Dominio')
                            ->content(fn ($record): string => $record->domain ?? 'N/A'),

                        Forms\Components\Placeholder::make('status')
                            ->label('Estado')
                            ->content(fn ($record): HtmlString => new HtmlString(sprintf(
                                '<span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-%s-100 text-%s-800">%s</span>',
                                $record->status_color === 'success' ? 'green' : ($record->status_color === 'danger' ? 'red' : ($record->status_color === 'warning' ? 'yellow' : 'gray')),
                                $record->status_color === 'success' ? 'green' : ($record->status_color === 'danger' ? 'red' : ($record->status_color === 'warning' ? 'yellow' : 'gray')),
                                $record->status_label
                            ))),

                        Forms\Components\Placeholder::make('deleted_at')
                            ->label('Fecha de Archivado')
                            ->content(fn ($record): string => $record->deleted_at?->format('d/m/Y H:i:s') ?? 'N/A'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Components\Section::make('Información del Tenant Archivado')
                    ->description('Detalles completos del tenant archivado')
                    ->schema([
                        Components\Grid::make(4)
                            ->schema([
                                Components\TextEntry::make('name')
                                    ->label('Nombre de la Tienda')
                                    ->size('lg')
                                    ->weight('bold')
                                    ->columnSpan(2),

                                Components\TextEntry::make('status')
                                    ->label('Estado')
                                    ->badge()
                                    ->color(fn ($record): string => $record->status_color)
                                    ->formatStateUsing(fn ($state): string => match ($state) {
                                        'archived' => 'Archivado 📦',
                                        default => $state,
                                    }),
                            ]),

                        Components\Grid::make(4)
                            ->schema([
                                Components\TextEntry::make('domain')
                                    ->label('Dominio')
                                    ->copyable()
                                    ->copyMessage('Dominio copiado')
                                    ->icon('heroicon-o-link'),

                                Components\TextEntry::make('deleted_at')
                                    ->label('Archivado el')
                                    ->dateTime('d/m/Y H:i')
                                    ->icon('heroicon-o-calendar'),

                                Components\TextEntry::make('created_at')
                                    ->label('Creado el')
                                    ->dateTime('d/m/Y H:i')
                                    ->icon('heroicon-o-clock'),

                                Components\TextEntry::make('contact_email')
                                    ->label('Email de Contacto')
                                    ->copyable()
                                    ->icon('heroicon-o-envelope'),
                            ]),

                        Components\Grid::make(3)
                            ->schema([
                                Components\TextEntry::make('cuit')
                                    ->label('CUIT / RUT / RFC')
                                    ->placeholder('No especificado'),

                                Components\TextEntry::make('phone')
                                    ->label('Teléfono')
                                    ->placeholder('No especificado'),

                                Components\TextEntry::make('timezone')
                                    ->label('Zona Horaria')
                                    ->formatStateUsing(fn (string $state): string => str_replace(['America/', '_'], ['', ' '], $state)
                                    ),
                            ]),
                    ])
                    ->columns(1),

                Components\Section::make('Estadísticas del Tenant')
                    ->description('Métricas y datos históricos')
                    ->schema([
                        Components\Grid::make(4)
                            ->schema([
                                Components\Section::make('Usuarios')
                                    ->schema([
                                        Components\TextEntry::make('user_count')
                                            ->label('Total Usuarios')
                                            ->formatStateUsing(fn ($record) => self::getTenantUserCount($record->id))
                                            ->icon('heroicon-o-users')
                                            ->size('lg')
                                            ->color('primary'),

                                        Components\TextEntry::make('last_active')
                                            ->label('Última Actividad')
                                            ->formatStateUsing(fn ($record) => self::getTenantLastActivity($record->id))
                                            ->icon('heroicon-o-clock')
                                            ->placeholder('Sin actividad'),
                                    ])
                                    ->compact(),

                                Components\Section::make('Datos')
                                    ->schema([
                                        Components\TextEntry::make('product_count')
                                            ->label('Productos')
                                            ->formatStateUsing(fn ($record) => self::getTenantProductCount($record->database))
                                            ->icon('heroicon-o-cube')
                                            ->size('lg')
                                            ->color('info'),

                                        Components\TextEntry::make('sales_count')
                                            ->label('Ventas Totales')
                                            ->formatStateUsing(fn ($record) => self::getTenantSalesCount($record->database))
                                            ->icon('heroicon-o-currency-dollar'),
                                    ])
                                    ->compact(),

                                Components\Section::make('Almacenamiento')
                                    ->schema([
                                        Components\TextEntry::make('storage_used')
                                            ->label('Espacio Usado')
                                            ->formatStateUsing(fn ($record) => self::getTenantStorageUsage($record->database))
                                            ->icon('heroicon-o-server')
                                            ->size('lg')
                                            ->color('warning'),

                                        Components\TextEntry::make('file_count')
                                            ->label('Archivos')
                                            ->formatStateUsing(fn ($record) => self::getTenantFileCount($record->id))
                                            ->icon('heroicon-o-document'),
                                    ])
                                    ->compact(),

                                Components\Section::make('Backups')
                                    ->schema([
                                        Components\TextEntry::make('last_backup')
                                            ->label('Último Backup')
                                            ->formatStateUsing(fn ($record) => self::getLastBackupStatus($record->id))
                                            ->icon('heroicon-o-archive-box')
                                            ->size('lg')
                                            ->color('success'),

                                        Components\TextEntry::make('backup_count')
                                            ->label('Backups Totales')
                                            ->formatStateUsing(fn ($record) => self::getBackupCount($record->id))
                                            ->icon('heroicon-o-circle-stack'),
                                    ])
                                    ->compact(),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed()
                    ->columns(1),

                Components\Section::make('Registro de Archivado')
                    ->description('Información detallada sobre el archivado')
                    ->schema([
                        Components\Grid::make(2)
                            ->schema([
                                Components\TextEntry::make('archive_info.archived_by')
                                    ->label('Archivado por')
                                    ->formatStateUsing(fn ($state) => self::getAdminName($state))
                                    ->icon('heroicon-o-user')
                                    ->placeholder('Desconocido'),

                                Components\TextEntry::make('archive_info.archive_reason')
                                    ->label('Motivo del Archivado')
                                    ->formatStateUsing(fn ($state) => $state ?? 'No especificado')
                                    ->placeholder('No especificado'),
                            ]),

                        Components\Grid::make(3)
                            ->schema([
                                Components\TextEntry::make('archive_info.backup_path')
                                    ->label('Path del Backup')
                                    ->formatStateUsing(fn ($state) => $state ? basename($state) : 'N/A')
                                    ->placeholder('N/A'),

                                Components\TextEntry::make('archive_info.backup_size')
                                    ->label('Tamaño del Backup')
                                    ->formatStateUsing(fn ($state) => $state ? round($state / 1024 / 1024, 2).' MB' : 'N/A')
                                    ->placeholder('N/A'),

                                Components\TextEntry::make('archive_info.ip_address')
                                    ->label('IP del Archivado')
                                    ->formatStateUsing(fn ($state) => $state ?? 'N/A')
                                    ->placeholder('N/A'),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed()
                    ->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // ESSENTIAL COLUMNS - Optimized layout
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->grow(true)
                    ->tooltip('Nombre de la tienda archivada')
                    ->description(fn ($record) => $record->company_display_name ?? null),

                TextColumn::make('domain')
                    ->label('Dominio')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Dominio copiado')
                    ->tooltip('Click para copiar el dominio')
                    ->icon('heroicon-o-link')
                    ->size('sm'),

                TextColumn::make('contact_email')
                    ->label('Email')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Email copiado')
                    ->tooltip('Click para copiar email de contacto')
                    ->icon('heroicon-o-envelope')
                    ->size('sm')
                    ->toggleable(),

                // ARCHIVAL STATUS COLUMNS
                TextColumn::make('deleted_at')
                    ->label('Archivado')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->description(function ($record) {
                        $diff = $record->deleted_at->diffForHumans(now());

                        return "Hace {$diff}";
                    })
                    ->tooltip('Fecha de archivado')
                    ->size('sm'),

                BadgeColumn::make('status_label')
                    ->label('Estado')
                    ->color(fn ($record): string => $record->status_color)
                    ->tooltip('Estado actual del tenant archivado')
                    ->size('sm'),

                // HEALTH SCORE FOR ARCHIVED TENANTS
                TextColumn::make('archived_health_score')
                    ->label('Health Score')
                    ->formatStateUsing(fn ($record): string => "{$record->archived_health_score}/100")
                    ->alignCenter()
                    ->weight('bold')
                    ->color(fn ($record) => $record->health_score_color)
                    ->tooltip(fn ($record) => $record->health_score_tooltip)
                    ->icon(fn ($record) => match (true) {
                        $record->archived_health_score >= 90 => 'heroicon-o-face-smile',
                        $record->archived_health_score >= 75 => 'heroicon-o-face-smile',
                        $record->archived_health_score >= 60 => 'heroicon-o-face-frown',
                        $record->archived_health_score >= 40 => 'heroicon-o-exclamation-triangle',
                        default => 'heroicon-o-x-circle',
                    })
                    ->size('sm'),

                // COMPACT STATISTICS
                TextColumn::make('user_count')
                    ->label('Usuarios')
                    ->formatStateUsing(fn ($record) => self::getTenantUserCount($record->id))
                    ->alignCenter()
                    ->tooltip('Total de usuarios registrados')
                    ->icon('heroicon-o-users')
                    ->size('sm')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('storage_used')
                    ->label('Almacenamiento')
                    ->formatStateUsing(fn ($record) => self::getTenantStorageUsage($record->database))
                    ->alignCenter()
                    ->tooltip('Espacio utilizado por el tenant')
                    ->icon('heroicon-o-server')
                    ->size('sm')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('last_backup')
                    ->label('Backup')
                    ->formatStateUsing(fn ($record) => self::getLastBackupStatus($record->id))
                    ->badge()
                    ->color(fn ($record) => self::getBackupStatusColor($record->id))
                    ->tooltip('Estado del último backup')
                    ->size('sm')
                    ->toggleable(isToggledHiddenByDefault: false),
            ])
            ->filters([
                Filters\Filter::make('archived_recently')
                    ->label('Archivados Recientemente (últimos 30 días)')
                    ->query(fn (Builder $query) => $query->where('deleted_at', '>=', now()->subDays(30))
                    )
                    ->toggle(),

                Filters\Filter::make('archived_long_ago')
                    ->label('Archivados Antiguos (+90 días)')
                    ->query(fn (Builder $query) => $query->where('deleted_at', '<=', now()->subDays(90))
                    )
                    ->toggle(),

                Filters\SelectFilter::make('has_backups')
                    ->label('Estado de Backups')
                    ->options([
                        'has_backups' => 'Con Backups',
                        'no_backups' => 'Sin Backups',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (! isset($data['value'])) {
                            return $query;
                        }

                        if ($data['value'] === 'has_backups') {
                            return $query->whereHas('backupLogs');
                        } elseif ($data['value'] === 'no_backups') {
                            return $query->whereDoesntHave('backupLogs');
                        }

                        return $query;
                    }),

                Filters\Filter::make('large_storage')
                    ->label('Almacenamiento > 100MB')
                    ->query(function (Builder $query) {
                        return $query->whereRaw('
                            pg_database_size(database) > 100 * 1024 * 1024
                        ');
                    })
                    ->toggle(),
            ])
            ->actions([
                // PRIMARY ACTIONS - Optimized UX
                Tables\Actions\ViewAction::make('view')
                    ->label('')
                    ->icon('heroicon-o-eye')
                    ->color('primary')
                    ->size('sm')
                    ->tooltip('Ver detalles del tenant archivado')
                    ->url(fn ($record) => ArchivedTenantResource::getUrl('view', ['record' => $record->id]))
                    ->openUrlInNewTab(false),

                // ADVANCED ACTIONS GROUP - Simplified for testing
                ActionGroup::make([
                    Action::make('restore')
                        ->label('🔄 Restaurar')
                        ->icon('heroicon-o-arrow-uturn-left')
                        ->color('success')
                        ->tooltip('Restaurar tenant')
                        ->visible(fn (): bool => auth('superadmin')->user()?->is_super_admin ?? false),

                    Action::make('backup')
                        ->label('Backup')
                        ->icon('heroicon-o-circle-stack')
                        ->color('info')
                        ->tooltip('Crear backup')
                        ->visible(fn (): bool => auth('superadmin')->user()?->is_super_admin ?? false),
                ])
                    ->label('')
                    ->icon('heroicon-o-ellipsis-horizontal')
                    ->color('gray')
                    ->tooltip('Más acciones'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // ARCHIVED TENANT SPECIFIC ACTIONS
                    Tables\Actions\BulkAction::make('bulk_restore')
                        ->label('🔄 Restaurar Seleccionados')
                        ->icon('heroicon-o-arrow-uturn-left')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('🔄 Restaurar Tenants Archivados')
                        ->modalDescription(fn ($records) => "**¿Estás seguro de restaurar {$records->count()} tenant(s) archivado(s)?**\n\n".
                            "Esta acción:\n".
                            "• Reactivará todos los tenants seleccionados\n".
                            "• Restaurará el acceso para los usuarios\n".
                            "• Generará backups previos a la restauración\n".
                            '• Procesará cada tenant individualmente'
                        )
                        ->modalSubmitActionLabel('Restaurar Tenants')
                        ->form([
                            Forms\Components\Textarea::make('bulk_restore_reason')
                                ->label('Motivo de la Restauración en Lote')
                                ->required()
                                ->rows(3)
                                ->placeholder('Describe por qué estás restaurando estos tenants...'),

                            Forms\Components\Checkbox::make('backup_before_bulk_restore')
                                ->label('Crear backups antes de restaurar (recomendado)')
                                ->default(true),

                            Forms\Components\Checkbox::make('understand_bulk_consequences')
                                ->label('Entiendo las consecuencias de restaurar múltiples tenants.')
                                ->required(),
                        ])
                        ->action(function ($records, array $data) {
                            $backupService = app(TenantBackupService::class);
                            $successCount = 0;
                            $failedCount = 0;
                            $errorDetails = [];

                            foreach ($records as $tenant) {
                                try {
                                    // Create backup before restore if requested
                                    if ($data['backup_before_bulk_restore']) {
                                        $backupResult = $backupService->backupDatabase(
                                            $tenant->database,
                                            $tenant->id,
                                            'pre_bulk_restore'
                                        );

                                        if (! $backupResult['success']) {
                                            $errorDetails[] = "Backup fallido para {$tenant->name}: {$backupResult['error']}";
                                            $failedCount++;

                                            continue;
                                        }
                                    }

                                    // Restore tenant
                                    $tenant->status = Tenant::STATUS_ACTIVE;
                                    $tenant->restore();

                                    // Log the restoration
                                    activity()
                                        ->causedBy(auth('superadmin')->user())
                                        ->performedOn($tenant)
                                        ->withProperties([
                                            'action' => 'bulk_restore',
                                            'restore_reason' => $data['bulk_restore_reason'],
                                            'backup_before_restore' => $data['backup_before_bulk_restore'],
                                            'ip' => request()->ip(),
                                        ])
                                        ->log('Tenant restaurado desde estado archivado (lote)');

                                    $successCount++;

                                } catch (\Exception $e) {
                                    $errorDetails[] = "Error restaurando {$tenant->name}: {$e->getMessage()}";
                                    $failedCount++;
                                }
                            }

                            // Send appropriate notification
                            if ($failedCount === 0) {
                                Notification::make()
                                    ->title('✅ Restauración en Lote Exitosa')
                                    ->body("Se han restaurado {$successCount} tenant(s) exitosamente.")
                                    ->success()
                                    ->duration(15000)
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title('⚠️ Restauración en Lote con Errores')
                                    ->body("Exitosos: {$successCount} | Fallidos: {$failedCount}")
                                    ->warning()
                                    ->duration(15000)
                                    ->actions([
                                        Tables\Actions\Action::make('view_errors')
                                            ->label('Ver Errores')
                                            ->color('danger')
                                            ->action(function () {
                                                // TODO: Show error details in a modal or log
                                            }),
                                    ])
                                    ->send();
                            }
                        })
                        ->visible(fn (): bool => auth('superadmin')->user()?->is_super_admin ?? false),

                    // STANDARD BULK ACTIONS
                    Tables\Actions\BulkAction::make('bulk_backup')
                        ->label('Crear Backups en Lote')
                        ->icon('heroicon-o-circle-stack')
                        ->color('info')
                        ->requiresConfirmation()
                        ->modalHeading('Crear Backups de Tenants Archivados')
                        ->modalDescription(fn ($records) => "Se crearán backups de {$records->count()} tenant(s) archivado(s).\n\n".
                            "Los backups serán guardados con la etiqueta 'archived_bulk'."
                        )
                        ->modalSubmitActionLabel('Crear Backups')
                        ->action(function ($records) {
                            $backupService = app(TenantBackupService::class);
                            $successCount = 0;
                            $failedCount = 0;

                            foreach ($records as $tenant) {
                                try {
                                    $result = $backupService->backupDatabase($tenant->database, $tenant->id, 'archived_bulk');

                                    if ($result['success']) {
                                        $successCount++;
                                    } else {
                                        $failedCount++;
                                    }
                                } catch (\Exception $e) {
                                    $failedCount++;
                                }
                            }

                            if ($failedCount === 0) {
                                Notification::make()
                                    ->title('✅ Backups Completados Exitosamente')
                                    ->body("Se han creado {$successCount} backup(s) exitosamente.")
                                    ->success()
                                    ->duration(10000)
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title('⚠️ Backups Completados con Errores')
                                    ->body("Exitosos: {$successCount} | Fallidos: {$failedCount}")
                                    ->warning()
                                    ->duration(10000)
                                    ->send();
                            }
                        })
                        ->visible(fn (): bool => auth('superadmin')->user()?->is_super_admin ?? false),

                    Tables\Actions\BulkAction::make('bulk_export')
                        ->label('Exportar Datos en Lote')
                        ->icon('heroicon-o-document-arrow-down')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Exportar Datos de Tenants Archivados')
                        ->modalDescription(fn ($records) => "Se iniciará la exportación de datos de {$records->count()} tenant(s) archivado(s).\n\n".
                            "Esta acción puede tardar varios minutos dependiendo del volumen de datos.\n".
                            'Recibirás notificaciones cuando cada exportación esté lista.'
                        )
                        ->modalSubmitActionLabel('Iniciar Exportación en Lote')
                        ->action(function ($records) {
                            // TODO: Implement bulk export functionality
                            Notification::make()
                                ->title('📋 Exportación en Lote Iniciada')
                                ->body("Se ha iniciado la exportación de {$records->count()} tenant(s) archivado(s). Recibirás notificaciones cuando estén listas.")
                                ->info()
                                ->duration(10000)
                                ->send();
                        }),

                    // CRITICAL BULK ACTIONS
                    Tables\Actions\BulkAction::make('bulk_force_delete')
                        ->label('🗑️ Eliminar Permanentemente')
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('⚠️ ELIMINAR PERMANENTEMENTE EN LOTE')
                        ->modalDescription(fn ($records) => "**ADVERTENCIA: Esta acción es IRREVERSIBLE**\n\n".
                            "Eliminar permanentemente {$records->count()} tenant(s) archivado(s) significa:\n\n".
                            "• Todos los datos serán borrados para siempre\n".
                            "• No se podrá recuperar ninguna información\n".
                            "• Los backups podrían ser eliminados también\n".
                            '• Esta acción no puede deshacerse bajo ninguna circunstancia'
                        )
                        ->modalSubmitActionLabel('Entiendo, Eliminar Permanentemente')
                        ->form([
                            Forms\Components\Textarea::make('bulk_delete_reason')
                                ->label('Motivo de la Eliminación en Lote')
                                ->required()
                                ->rows(3)
                                ->placeholder('Describe exhaustivamente el motivo de esta eliminación masiva...'),

                            Forms\Components\TextInput::make('admin_password')
                                ->label('Contraseña de Administrador')
                                ->required()
                                ->password()
                                ->revealable()
                                ->helperText('Confirma tu identidad para realizar esta acción crítica.'),

                            Forms\Components\TextInput::make('confirm_bulk_delete_keyword')
                                ->label('Palabra Clave de Confirmación')
                                ->required()
                                ->placeholder('Escribe: DELETE_PERMANENTLY'),

                            Forms\Components\Checkbox::make('understand_bulk_permanent')
                                ->label('Entiendo que esta acción es PERMANENTE e IRREVERSIBLE.')
                                ->required(),

                            Forms\Components\Checkbox::make('bulk_legal_compliance')
                                ->label('Confirmo cumplir con todas las obligaciones legales de retención de datos.')
                                ->required(),
                        ])
                        ->action(function ($records, array $data) {
                            $admin = auth('superadmin')->user();
                            $successCount = 0;
                            $failedCount = 0;

                            // Validate admin password
                            if (! Hash::check($data['admin_password'], $admin->password)) {
                                Notification::make()
                                    ->title('Error de Autenticación')
                                    ->body('La contraseña de administrador es incorrecta.')
                                    ->danger()
                                    ->send();

                                return;
                            }

                            // Validate delete keyword
                            if ($data['confirm_bulk_delete_keyword'] !== 'DELETE_PERMANENTLY') {
                                Notification::make()
                                    ->title('Error de Confirmación')
                                    ->body('La palabra clave es incorrecta. Eliminación en lote cancelada.')
                                    ->danger()
                                    ->send();

                                return;
                            }

                            foreach ($records as $tenant) {
                                try {
                                    // Log the final deletion
                                    activity()
                                        ->causedBy($admin)
                                        ->performedOn($tenant)
                                        ->withProperties([
                                            'action' => 'bulk_force_delete',
                                            'delete_reason' => $data['bulk_delete_reason'],
                                            'final_deletion' => true,
                                            'ip' => request()->ip(),
                                            'user_agent' => request()->userAgent(),
                                        ])
                                        ->log('Tenant eliminado permanentemente (lote)');

                                    // Force delete tenant
                                    $tenant->forceDelete();
                                    $successCount++;

                                } catch (\Exception $e) {
                                    $failedCount++;
                                }
                            }

                            Notification::make()
                                ->title('🗑️ Eliminación en Lote Completada')
                                ->body("Se han eliminado permanentemente {$successCount} tenant(s). Fallidos: {$failedCount}")
                                ->danger()
                                ->duration(20000)
                                ->send();
                        })
                        ->visible(fn (): bool => auth('superadmin')->user()?->is_super_admin ?? false),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScope(SoftDeletingScope::class) // Remove the default soft-deletes global scope
            ->whereNotNull('deleted_at') // Only show archived (soft-deleted) tenants
            ->where('status', Tenant::STATUS_ARCHIVED);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListArchivedTenants::route('/'),
            'view' => Pages\ViewArchivedTenant::route('/{record}'),
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        $user = auth('superadmin')->user();

        return $user?->is_super_admin || ($user?->hasPermissionTo('admin.archived_tenants.view', 'superadmin') ?? false);
    }

    public static function canViewAny(): bool
    {
        return self::shouldRegisterNavigation();
    }

    /**
     * Helper methods for tenant statistics
     */
    private static function getTenantUserCount(int $tenantId): int
    {
        try {
            return Cache::remember("archived_tenant_users_{$tenantId}", 300, function () use ($tenantId) {
                return \App\Models\User::where('tenant_id', $tenantId)->count();
            });
        } catch (\Exception $e) {
            return 0;
        }
    }

    private static function getTenantStorageUsage(string $database): string
    {
        try {
            return Cache::remember("archived_storage_{$database}", 600, function () use ($database) {
                $result = \Illuminate\Support\Facades\DB::select('
                    SELECT pg_size_pretty(pg_database_size(?)) as size
                    FROM pg_database WHERE datname = ?
                ', [$database, $database]);

                return $result[0]->size ?? '0 MB';
            });
        } catch (\Exception $e) {
            return 'Error';
        }
    }

    private static function getTenantProductCount(string $database): int
    {
        try {
            return Cache::remember("archived_products_{$database}", 600, function () {
                // Switch to tenant connection temporarily
                $originalConnection = config('database.default');
                config(['database.default' => 'tenant']);

                try {
                    $count = \App\Modules\Inventory\Models\Product::count();
                    config(['database.default' => $originalConnection]);

                    return $count;
                } catch (\Exception $e) {
                    config(['database.default' => $originalConnection]);

                    return 0;
                }
            });
        } catch (\Exception $e) {
            return 0;
        }
    }

    private static function getTenantSalesCount(string $database): int
    {
        try {
            return Cache::remember("archived_sales_{$database}", 600, function () {
                // Switch to tenant connection temporarily
                $originalConnection = config('database.default');
                config(['database.default' => 'tenant']);

                try {
                    $count = \App\Modules\POS\Models\Sale::count();
                    config(['database.default' => $originalConnection]);

                    return $count;
                } catch (\Exception $e) {
                    config(['database.default' => $originalConnection]);

                    return 0;
                }
            });
        } catch (\Exception $e) {
            return 0;
        }
    }

    private static function getTenantLastActivity(int $tenantId): string
    {
        try {
            return Cache::remember("archived_activity_{$tenantId}", 300, function () use ($tenantId) {
                $activity = \App\Models\TenantActivity::where('tenant_id', $tenantId)
                    ->latest('created_at')
                    ->first();

                if (! $activity) {
                    return 'Sin actividad';
                }

                $diff = $activity->created_at->diffForHumans(now());

                return "Hace {$diff}";
            });
        } catch (\Exception $e) {
            return 'Error';
        }
    }

    private static function getTenantFileCount(int $tenantId): int
    {
        try {
            return Cache::remember("archived_files_{$tenantId}", 600, function () use ($tenantId) {
                $storagePath = storage_path("app/tenant-uploads/{$tenantId}");
                if (! is_dir($storagePath)) {
                    return 0;
                }

                $fileCount = 0;
                $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($storagePath));
                foreach ($iterator as $file) {
                    if ($file->isFile()) {
                        $fileCount++;
                    }
                }

                return $fileCount;
            });
        } catch (\Exception $e) {
            return 0;
        }
    }

    private static function getLastBackupStatus(int $tenantId): string
    {
        try {
            $latestBackup = \App\Models\BackupLog::where('tenant_id', $tenantId)
                ->latest('created_at')
                ->first();

            if (! $latestBackup) {
                return 'Nunca';
            }

            if ($latestBackup->status === 'failed') {
                return 'Error';
            }

            return $latestBackup->created_at->diffForHumans();
        } catch (\Exception $e) {
            return 'Error';
        }
    }

    private static function getBackupStatusColor(int $tenantId): string
    {
        try {
            $latestBackup = \App\Models\BackupLog::where('tenant_id', $tenantId)
                ->latest('created_at')
                ->first();

            if (! $latestBackup) {
                return 'gray';
            }

            if ($latestBackup->status === 'failed') {
                return 'danger';
            }

            $hoursAgo = $latestBackup->created_at->diffInHours(now());

            if ($hoursAgo < 24) {
                return 'success';
            } elseif ($hoursAgo < 72) {
                return 'warning';
            }

            return 'danger';
        } catch (\Exception $e) {
            return 'gray';
        }
    }

    private static function getBackupCount(int $tenantId): int
    {
        try {
            return \App\Models\BackupLog::where('tenant_id', $tenantId)->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    private static function getAdminName(?int $adminId): string
    {
        if (! $adminId) {
            return 'Desconocido';
        }

        try {
            $admin = \App\Models\User::find($adminId);

            return $admin ? $admin->name : 'Desconocido';
        } catch (\Exception $e) {
            return 'Desconocido';
        }
    }
}
