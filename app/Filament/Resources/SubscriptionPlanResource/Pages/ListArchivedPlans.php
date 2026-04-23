<?php

namespace App\Filament\Resources\SubscriptionPlanResource\Pages;

use App\Filament\Resources\SubscriptionPlanResource;
use App\Models\SubscriptionPlan;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ListArchivedPlans extends ListRecords
{
    protected static string $resource = SubscriptionPlanResource::class;

    protected static ?string $navigationIcon = 'heroicon-o-archive-box';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Planes Archivados';

    protected static ?string $navigationGroup = 'Suscripciones';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ])
            ->where(function ($query) {
                $query->where('is_active', false)
                    ->orWhereNotNull('deleted_at');
            })
            ->ordered();
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Plan')
                    ->searchable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->getStateUsing(fn (SubscriptionPlan $record): string => $record->trashed() ? 'Archivado' : 'Inactivo')
                    ->color(fn (SubscriptionPlan $record): string => $record->trashed() ? 'danger' : 'warning'),

                Tables\Columns\TextColumn::make('subscriptions_count')
                    ->label('Suscripciones')
                    ->getStateUsing(fn (SubscriptionPlan $record) => $record->subscriptions()->count())
                    ->badge()
                    ->color(fn ($record) => $record->subscriptions()->count() > 0 ? 'danger' : 'success'),
            ])
            ->actions([
                Tables\Actions\Action::make('activate')
                    ->label('Activar Plan')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (SubscriptionPlan $record) => ! $record->is_active && ! $record->trashed())
                    ->requiresConfirmation()
                    ->modalHeading('Activar Plan Inactivo')
                    ->modalDescription('¿Estás seguro de que quieres activar este plan? Establecerá el plan como activo y aceptará nuevas suscripciones.')
                    ->modalSubmitActionLabel('Sí, Activar')
                    ->action(fn (SubscriptionPlan $record) => $record->update(['is_active' => true]))
                    ->successNotificationTitle('Plan activado correctamente'),

                Tables\Actions\Action::make('restore')
                    ->label('Restaurar Archivado')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->visible(fn (SubscriptionPlan $record) => $record->trashed())
                    ->requiresConfirmation()
                    ->modalHeading('Restaurar Plan Archivado')
                    ->modalDescription('⚠️ Este plan fue eliminado anteriormente. ¿Estás seguro de que quieres restaurarlo? Recuperará el plan con su configuración anterior.')
                    ->modalSubmitActionLabel('Sí, Restaurar')
                    ->action(fn (SubscriptionPlan $record) => $record->restore())
                    ->successNotificationTitle('Plan archivado restaurado'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('activate')
                        ->label(function ($records) {
                            $eligibleCount = collect($records)->filter(fn ($record) => ! $record->trashed() && ! $record->is_active)->count();
                            $totalCount = count($records);

                            return "Activar Seleccionados ({$eligibleCount}/{$totalCount})";
                        })
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(function ($records) {
                            if (empty($records)) {
                                return false;
                            }
                            $inactiveCount = collect($records)->filter(fn ($record) => ! $record->trashed() && ! $record->is_active)->count();

                            return $inactiveCount > 0;
                        })
                        ->requiresConfirmation()
                        ->modalHeading(function ($records) {
                            $eligibleCount = collect($records)->filter(fn ($record) => ! $record->trashed() && ! $record->is_active)->count();

                            return "Activar {$eligibleCount} Plan(es) Inactivo(s)";
                        })
                        ->modalDescription('¿Estás seguro de que quieres activar los planes seleccionados? Solo afectará a los planes inactivos (no archivados).')
                        ->modalSubmitActionLabel('Sí, Activar')
                        ->action(function ($records) {
                            // Filter to only affect inactive (non-trashed) records
                            $eligibleRecords = collect($records)->filter(fn ($record) => ! $record->trashed() && ! $record->is_active);
                            $trashedCount = collect($records)->filter(fn ($record) => $record->trashed())->count();

                            if ($eligibleRecords->count() === 0) {
                                Notification::make()
                                    ->warning()
                                    ->title('No hay planes inactivos para activar')
                                    ->body("Todos los planes seleccionados están archivados. Use 'Restaurar Seleccionados' para los planes eliminados.")
                                    ->send();

                                return;
                            }

                            $eligibleRecords->each->update(['is_active' => true]);

                            if ($trashedCount > 0) {
                                Notification::make()
                                    ->warning()
                                    ->title("Se activaron {$eligibleRecords->count()} planes")
                                    ->body("Se ignoraron {$trashedCount} planes archivados. Use 'Restaurar' para los planes eliminados.")
                                    ->send();
                            }
                        })
                        ->deselectRecordsAfterCompletion()
                        ->successNotificationTitle('Planes activados correctamente'),

                    Tables\Actions\BulkAction::make('restore')
                        ->label(function ($records) {
                            $eligibleCount = collect($records)->filter(fn ($record) => $record->trashed())->count();
                            $totalCount = count($records);

                            return "Restaurar Seleccionados ({$eligibleCount}/{$totalCount})";
                        })
                        ->icon('heroicon-o-arrow-path')
                        ->color('warning')
                        ->visible(function ($records) {
                            if (empty($records)) {
                                return false;
                            }
                            $trashedCount = collect($records)->filter(fn ($record) => $record->trashed())->count();

                            return $trashedCount > 0;
                        })
                        ->requiresConfirmation()
                        ->modalHeading(function ($records) {
                            $eligibleCount = collect($records)->filter(fn ($record) => $record->trashed())->count();

                            return "Restaurar {$eligibleCount} Plan(es) Archivado(s)";
                        })
                        ->modalDescription('⚠️ ¿Estás seguro de que quieres restaurar los planes seleccionados? Solo afectará a los planes archivados (eliminados).')
                        ->modalSubmitActionLabel('Sí, Restaurar')
                        ->action(function ($records) {
                            // Filter to only affect trashed records
                            $eligibleRecords = collect($records)->filter(fn ($record) => $record->trashed());
                            $inactiveCount = collect($records)->filter(fn ($record) => ! $record->trashed() && ! $record->is_active)->count();

                            if ($eligibleRecords->count() === 0) {
                                Notification::make()
                                    ->warning()
                                    ->title('No hay planes archivados para restaurar')
                                    ->body("Los planes seleccionados están inactivos pero no eliminados. Use 'Activar Seleccionados' para planes inactivos.")
                                    ->send();

                                return;
                            }

                            $eligibleRecords->each->restore();

                            if ($inactiveCount > 0) {
                                Notification::make()
                                    ->warning()
                                    ->title("Se restauraron {$eligibleRecords->count()} planes")
                                    ->body("Se ignoraron {$inactiveCount} planes inactivos. Use 'Activar' para los planes desactivados.")
                                    ->send();
                            }
                        })
                        ->deselectRecordsAfterCompletion()
                        ->successNotificationTitle('Planes archivados restaurados'),

                    Tables\Actions\DeleteBulkAction::make()
                        ->before(function ($records, Tables\Actions\DeleteBulkAction $action) {
                            $blockedPlans = [];
                            $totalBlockedSubscriptions = 0;

                            foreach ($records as $record) {
                                if ($record->subscriptions()->count() > 0) {
                                    $subscriptionCount = $record->subscriptions()->count();
                                    $blockedPlans[] = "{$record->name} ({$subscriptionCount} suscripciones)";
                                    $totalBlockedSubscriptions += $subscriptionCount;
                                }
                            }

                            if (! empty($blockedPlans)) {
                                // Lanzar excepción para prevenir la eliminación (método correcto en Filament)
                                throw new \Exception('No se pueden eliminar planes con suscripciones activas. Planes bloqueados: '.implode(' | ', $blockedPlans).". Total: {$totalBlockedSubscriptions} suscripciones afectadas.");
                            }
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Eliminar Permanentemente Planes Seleccionados')
                        ->modalDescription('⚠️ ¡ADVERTENCIA! Esta acción eliminará permanentemente los planes seleccionados. No podrán ser recuperados.')
                        ->modalSubmitActionLabel('Sí, Eliminar Permanentemente')
                        ->deselectRecordsAfterCompletion()
                        ->successNotificationTitle('Planes eliminados permanentemente'),
                ]),
            ]);
    }
}
