<?php

/**
 * Kartenant - Ferretero Ágil
 *
 * Este archivo es parte de Kartenant.
 *
 * @copyright Copyright (c) 2025-2026 Kartenant
 * @license   GNU AGPLv3 <https://www.gnu.org/licenses/agpl-3.0.txt>
 */

namespace App\Filament\Resources;

use App\Filament\Resources\SecurityQuestionResource\Pages;
use App\Models\SecurityQuestion;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SecurityQuestionResource extends Resource
{
    protected static ?string $model = SecurityQuestion::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationLabel = 'Preguntas de Seguridad';

    protected static ?string $modelLabel = 'Pregunta de Seguridad';

    protected static ?string $pluralModelLabel = 'Preguntas de Seguridad';

    protected static ?string $navigationGroup = 'Configuración del Sistema';

    protected static ?int $navigationSort = 10;

    public static function canViewAny(): bool
    {
        $user = auth('superadmin')->user();

        return $user?->is_super_admin || ($user?->hasPermissionTo('admin.security_questions.view', 'superadmin') ?? false);
    }

    public static function canCreate(): bool
    {
        $user = auth('superadmin')->user();

        return $user?->is_super_admin || ($user?->hasPermissionTo('admin.security_questions.create', 'superadmin') ?? false);
    }

    public static function canEdit($record): bool
    {
        $user = auth('superadmin')->user();

        return $user?->is_super_admin || ($user?->hasPermissionTo('admin.security_questions.update', 'superadmin') ?? false);
    }

    public static function canDelete($record): bool
    {
        $user = auth('superadmin')->user();

        return $user?->is_super_admin || ($user?->hasPermissionTo('admin.security_questions.delete', 'superadmin') ?? false);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('question')
                    ->label('Pregunta')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('¿Cuál es el nombre de tu primera mascota?')
                    ->helperText('Escribe una pregunta clara y fácil de recordar'),

                Forms\Components\Toggle::make('is_active')
                    ->label('Activa')
                    ->helperText('Solo las preguntas activas aparecerán en la configuración de usuarios')
                    ->default(true),

                Forms\Components\TextInput::make('sort_order')
                    ->label('Orden de Visualización')
                    ->numeric()
                    ->default(fn () => SecurityQuestion::max('sort_order') + 1)
                    ->helperText('Número que determina el orden en que aparece la pregunta'),
            ])
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Orden')
                    ->numeric()
                    ->sortable()
                    ->width('80px'),

                Tables\Columns\TextColumn::make('question')
                    ->label('Pregunta')
                    ->searchable()
                    ->wrap(),

                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Activa')
                    ->sortable(),

                Tables\Columns\TextColumn::make('userSecurityAnswers_count')
                    ->label('Usuarios')
                    ->counts('userSecurityAnswers')
                    ->sortable()
                    ->tooltip('Cantidad de usuarios que han configurado esta pregunta'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creada')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('sort_order')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Estado')
                    ->placeholder('Todas')
                    ->trueLabel('Solo activas')
                    ->falseLabel('Solo inactivas'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Editar'),
                Tables\Actions\DeleteAction::make()
                    ->label('Eliminar')
                    ->before(function (Tables\Actions\DeleteAction $action, SecurityQuestion $record) {
                        if ($record->userSecurityAnswers()->exists()) {
                            \Filament\Notifications\Notification::make()
                                ->title('No se puede eliminar')
                                ->body('Esta pregunta está siendo utilizada por usuarios. Desactívala en su lugar.')
                                ->danger()
                                ->send();
                            $action->cancel();
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activar seleccionadas')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(fn ($records) => $records->each->update(['is_active' => true]))
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Desactivar seleccionadas')
                        ->icon('heroicon-o-x-circle')
                        ->color('warning')
                        ->action(fn ($records) => $records->each->update(['is_active' => false]))
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->emptyStateHeading('Sin preguntas de seguridad')
            ->emptyStateDescription('Crea preguntas de seguridad para que los usuarios puedan configurar la recuperación de contraseñas.')
            ->emptyStateIcon('heroicon-o-shield-check');
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
            'index' => Pages\ListSecurityQuestions::route('/'),
            'create' => Pages\CreateSecurityQuestion::route('/create'),
            'edit' => Pages\EditSecurityQuestion::route('/{record}/edit'),
        ];
    }
}
