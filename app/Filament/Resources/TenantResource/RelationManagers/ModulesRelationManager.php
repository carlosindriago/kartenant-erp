<?php

namespace App\Filament\Resources\TenantResource\RelationManagers;

use App\Models\Module;
use App\Models\ModuleUsageLog;
use App\Models\TenantModule;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ModulesRelationManager extends RelationManager
{
    protected static string $relationship = 'modules';

    protected static ?string $title = 'Módulos y Add-ons';

    protected static ?string $modelLabel = 'Módulo';

    protected static ?string $pluralModelLabel = 'Módulos y Add-ons';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('module_id')
                    ->label('Módulo')
                    ->options(Module::active()->ordered()->pluck('name', 'id'))
                    ->required()
                    ->searchable()
                    ->preload()
                    ->reactive()
                    ->afterStateUpdated(fn ($state, callable $set) => $set('price_override', Module::find($state)?->base_price_monthly))
                    ->disabled(fn ($record) => $record !== null), // Disable when editing

                Forms\Components\TextInput::make('price_override')
                    ->label('Precio Personalizado (Opcional)')
                    ->numeric()
                    ->step(0.01)
                    ->prefix('$')
                    ->helperText('Dejar en blanco para usar el precio base del módulo'),

                Forms\Components\Select::make('status')
                    ->label('Estado')
                    ->options([
                        'active' => 'Activo',
                        'inactive' => 'Inactivo',
                    ])
                    ->default('active')
                    ->required(),

                Forms\Components\DateTimePicker::make('starts_at')
                    ->label('Fecha de Inicio')
                    ->default(now())
                    ->required(),

                Forms\Components\DateTimePicker::make('expires_at')
                    ->label('Fecha de Expiración')
                    ->helperText('Dejar en blanco para acceso ilimitado'),

                Forms\Components\Textarea::make('notes')
                    ->label('Notas')
                    ->rows(3),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Módulo')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('description')
                    ->label('Descripción')
                    ->limit(50)
                    ->wrap(),

                Tables\Columns\TextColumn::make('category')
                    ->label('Categoría')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'inventory' => 'primary',
                        'pos' => 'success',
                        'reporting' => 'warning',
                        'integration' => 'info',
                        'security' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('price_display')
                    ->label('Precio')
                    ->getStateUsing(function ($record) {
                        $pivot = $record->pivot;
                        if ($pivot && $pivot->price_override) {
                            return '$'.number_format($pivot->price_override, 2);
                        }

                        return '$'.number_format($record->base_price_monthly, 2);
                    })
                    ->sortable(),

                Tables\Columns\IconColumn::make('pivot.is_active')
                    ->label('Activo')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('pivot.starts_at')
                    ->label('Inicio')
                    ->dateTime('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('pivot.expires_at')
                    ->label('Expiración')
                    ->dateTime('d/m/Y')
                    ->placeholder('Ilimitado')
                    ->sortable()
                    ->color(fn ($record) => $record->pivot->expires_at && $record->pivot->expires_at->isPast()
                            ? 'danger'
                            : null
                    ),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->label('Categoría')
                    ->options(fn (): array => Module::query()->distinct()->pluck('category', 'category')->toArray()),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Estado Activo')
                    ->placeholder('Todos')
                    ->trueLabel('Activos')
                    ->falseLabel('Inactivos')
                    ->queries(
                        true: fn (Builder $query) => $query->where('tenant_modules.is_active', true),
                        false: fn (Builder $query) => $query->where('tenant_modules.is_active', false),
                        blank: fn (Builder $query) => $query,
                    ),
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->label('Asignar Módulo')
                    ->modalHeading('Asignar Nuevo Módulo')
                    ->form([
                        Forms\Components\Select::make('module_id')
                            ->label('Módulo')
                            ->options(function (RelationManager $livewire) {
                                // Get modules that are not already attached to this tenant
                                $attachedModuleIds = $livewire->getOwnerRecord()->modules()->pluck('module_id');

                                return Module::active()
                                    ->whereNotIn('id', $attachedModuleIds)
                                    ->ordered()
                                    ->pluck('name', 'id');
                            })
                            ->required()
                            ->searchable()
                            ->preload()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                $module = Module::find($state);
                                if ($module) {
                                    $set('price_override', $module->base_price_monthly);
                                }
                            }),

                        Forms\Components\TextInput::make('price_override')
                            ->label('Precio Personalizado (Opcional)')
                            ->numeric()
                            ->step(0.01)
                            ->prefix('$')
                            ->helperText('Dejar en blanco para usar el precio base del módulo'),

                        Forms\Components\Select::make('status')
                            ->label('Estado')
                            ->options([
                                TenantModule::STATUS_ACTIVE => 'Activo',
                                TenantModule::STATUS_INACTIVE => 'Inactivo',
                                TenantModule::STATUS_SUSPENDED => 'Suspendido',
                            ])
                            ->default(TenantModule::STATUS_ACTIVE)
                            ->required(),

                        Forms\Components\DateTimePicker::make('starts_at')
                            ->label('Fecha de Inicio')
                            ->default(now())
                            ->required(),

                        Forms\Components\DateTimePicker::make('expires_at')
                            ->label('Fecha de Expiración')
                            ->helperText('Dejar en blanco para acceso ilimitado'),

                        Forms\Components\Textarea::make('notes')
                            ->label('Notas')
                            ->rows(3),
                    ])
                    ->action(function (array $data, RelationManager $livewire) {
                        $tenant = $livewire->getOwnerRecord();
                        $module = Module::findOrFail($data['module_id']);

                        // Create tenant module assignment
                        $tenantModule = $tenant->tenantModules()->create([
                            'module_id' => $module->id,
                            'status' => $data['status'],
                            'is_active' => $data['status'] === TenantModule::STATUS_ACTIVE,
                            'price_override' => $data['price_override'] ?: null,
                            'starts_at' => $data['starts_at'],
                            'expires_at' => $data['expires_at'] ?: null,
                            'billing_cycle' => $module->billing_cycle,
                            'auto_renew' => $module->auto_renew,
                            'added_by' => auth()->id(),
                            'notes' => $data['notes'] ?? null,
                        ]);

                        // Log the installation
                        ModuleUsageLog::logInstall($tenant, $module, auth()->user(), []);

                        // Increment module installation count
                        $module->incrementInstallations();
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Editar')
                    ->modalHeading('Editar Asignación de Módulo')
                    ->form([
                        Forms\Components\TextInput::make('price_override')
                            ->label('Precio Personalizado (Opcional)')
                            ->numeric()
                            ->step(0.01)
                            ->prefix('$')
                            ->default(fn ($record) => $record->pivot->price_override),

                        Forms\Components\Select::make('status')
                            ->label('Estado')
                            ->options([
                                TenantModule::STATUS_ACTIVE => 'Activo',
                                TenantModule::STATUS_INACTIVE => 'Inactivo',
                                TenantModule::STATUS_SUSPENDED => 'Suspendido',
                            ])
                            ->default(fn ($record) => $record->pivot->status ?? TenantModule::STATUS_ACTIVE)
                            ->required(),

                        Forms\Components\DateTimePicker::make('starts_at')
                            ->label('Fecha de Inicio')
                            ->default(fn ($record) => $record->pivot->starts_at),

                        Forms\Components\DateTimePicker::make('expires_at')
                            ->label('Fecha de Expiración')
                            ->default(fn ($record) => $record->pivot->expires_at),

                        Forms\Components\Textarea::make('notes')
                            ->label('Notas')
                            ->default(fn ($record) => $record->pivot->notes),
                    ])
                    ->action(function (array $data, Tables\Actions\EditAction $action) {
                        $record = $action->getRecord();
                        $pivot = $record->pivot;

                        $pivot->update([
                            'price_override' => $data['price_override'] ?: null,
                            'status' => $data['status'],
                            'is_active' => $data['status'] === TenantModule::STATUS_ACTIVE,
                            'starts_at' => $data['starts_at'],
                            'expires_at' => $data['expires_at'] ?: null,
                            'notes' => $data['notes'] ?? null,
                        ]);

                        // Log configuration change
                        ModuleUsageLog::logConfiguration(
                            $action->getLivewire()->getOwnerRecord(),
                            $record,
                            $data,
                            auth()->user()
                        );
                    }),

                Tables\Actions\DetachAction::make()
                    ->label('Remover')
                    ->modalHeading('Remover Módulo')
                    ->modalDescription('¿Estás seguro de que quieres remover este módulo? Esto desactivará todas sus funciones.')
                    ->modalSubmitActionLabel('Sí, Remover')
                    ->requiresConfirmation()
                    ->action(function (Tables\Actions\DetachAction $action) {
                        $record = $action->getRecord();
                        $tenant = $action->getLivewire()->getOwnerRecord();

                        // Log the uninstallation
                        ModuleUsageLog::logUninstall($tenant, $record, auth()->user(), 'Removido por administrador');

                        // Decrement module installation count
                        $record->decrementInstallations();

                        // Perform the detach
                        $action->process();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make()
                        ->label('Remover Seleccionados')
                        ->modalHeading('Remover Módulos Seleccionados')
                        ->modalDescription('¿Estás seguro de que quieres remover los módulos seleccionados?')
                        ->modalSubmitActionLabel('Sí, Remover Todos')
                        ->requiresConfirmation()
                        ->action(function (Tables\Actions\DetachBulkAction $action) {
                            $records = $action->getRecords();
                            $tenant = $action->getLivewire()->getOwnerRecord();

                            foreach ($records as $record) {
                                // Log the uninstallation
                                ModuleUsageLog::logUninstall($tenant, $record, auth()->user(), 'Removido masivamente por administrador');

                                // Decrement module installation count
                                $record->decrementInstallations();
                            }

                            // Perform the detach
                            $action->process();
                        }),
                ]),
            ])
            ->emptyStateActions([
                Tables\Actions\AttachAction::make()
                    ->label('Asignar Primer Módulo'),
            ]);
    }

    public function isReadOnly(): bool
    {
        return false;
    }
}
