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

use App\Filament\Resources\AdminUserResource\Pages;
use App\Http\Requests\Admin\UserStoreRequest;
use App\Http\Requests\Admin\UserUpdateRequest;
use App\Http\Requests\Admin\UserDestroyRequest;
use App\Models\User;
use App\Models\UserAuditLog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class AdminUserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationLabel = 'Administradores';
    protected static ?string $navigationGroup = 'Seguridad';

    protected static ?string $modelLabel = 'Administrador';
    protected static ?string $pluralModelLabel = 'Administradores';

    public static function form(Form $form): Form
    {
        $currentUser = Auth::guard('superadmin')->user();
        $isSuperAdmin = $currentUser?->is_super_admin ?? false;

        return $form
            ->schema([
                Forms\Components\Section::make('Información Básica')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->label('Nombre')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->required()
                            ->email()
                            ->unique(ignoreRecord: true)
                            ->label('Correo Electrónico')
                            ->maxLength(255),
                    ]),

                Forms\Components\Section::make('Seguridad')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Toggle::make('is_super_admin')
                            ->label('Super Admin')
                            ->visible($isSuperAdmin)
                            ->helperText('Solo los superadministradores pueden modificar este campo'),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Usuario Activo')
                            ->visible($isSuperAdmin)
                            ->helperText('Controla si el usuario puede acceder al sistema'),

                        Forms\Components\Toggle::make('force_renew_password')
                            ->label('Forzar cambio de contraseña')
                            ->visible($isSuperAdmin)
                            ->helperText('El usuario deberá cambiar su contraseña en el próximo inicio de sesión'),

                        Forms\Components\TextInput::make('password')
                            ->password()
                            ->revealable()
                            ->dehydrateStateUsing(fn ($state) => filled($state) ? Hash::make($state) : null)
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $operation) => $operation === 'create')
                            ->label('Contraseña')
                            ->helperText(fn (string $operation) =>
                                $operation === 'create'
                                    ? 'Mínimo 8 caracteres, debe incluir mayúsculas, minúsculas y números'
                                    : 'Dejar en blanco para mantener la contraseña actual'
                            ),
                    ]),

                Forms\Components\Section::make('Permisos y Roles')
                    ->columns(2)
                    ->visible($isSuperAdmin)
                    ->schema([
                        Forms\Components\Select::make('roles')
                            ->label('Roles')
                            ->relationship('roles', 'name')
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->helperText('Selecciona los roles que tendrá el usuario'),

                        Forms\Components\Select::make('permissions')
                            ->label('Permisos Específicos')
                            ->relationship('permissions', 'name')
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->helperText('Permisos adicionales más allá de los roles asignados'),
                    ]),

                Forms\Components\Section::make('Notas de Administración')
                    ->visible(fn (string $operation) => $operation === 'edit' && $isSuperAdmin)
                    ->schema([
                        Forms\Components\Textarea::make('deactivation_reason')
                            ->label('Motivo de desactivación')
                            ->rows(3)
                            ->visible(fn (callable $get) => !$get('is_active'))
                            ->helperText('Explica por qué este usuario está siendo desactivado'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->label('Nombre'),
                Tables\Columns\TextColumn::make('email')->searchable(),
                Tables\Columns\IconColumn::make('is_super_admin')->boolean()->label('Super Admin'),
                Tables\Columns\TagsColumn::make('roles.name')->label('Roles'),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_super_admin')->label('Super Admin')->boolean(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn () => auth('superadmin')->user()?->can('admin.users.update') ?? false),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn () => auth('superadmin')->user()?->can('admin.users.delete') ?? false),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => auth('superadmin')->user()?->can('admin.users.delete') ?? false),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        return $query->where(function ($q) {
            $q->where('is_super_admin', true)
                ->orWhereHas('roles')
                ->orWhereHas('permissions');
        });
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAdminUsers::route('/'),
            'create' => Pages\CreateAdminUser::route('/create'),
            'edit' => Pages\EditAdminUser::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        $user = auth('superadmin')->user();
        return $user?->is_super_admin || ($user?->hasPermissionTo('admin.users.view', 'superadmin') ?? false);
    }

    public static function canCreate(): bool
    {
        $user = auth('superadmin')->user();
        return $user?->is_super_admin || ($user?->hasPermissionTo('admin.users.create', 'superadmin') ?? false);
    }

    public static function canEdit($record): bool
    {
        $user = auth('superadmin')->user();
        return $user?->is_super_admin || ($user?->hasPermissionTo('admin.users.update', 'superadmin') ?? false);
    }

    public static function canDelete($record): bool
    {
        $user = auth('superadmin')->user();
        return $user?->is_super_admin || ($user?->hasPermissionTo('admin.users.delete', 'superadmin') ?? false);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return self::canViewAny();
    }

    /**
     * SECURE: Create user with proper validation and authorization
     */
    public static function create(array $data): User
    {
        $request = new UserStoreRequest();
        $request->merge($data);

        // Validate and authorize
        $validated = $request->validated();

        // Create user using secure methods
        $user = User::create($validated);

        // Handle role assignments (only for superadmins)
        if (isset($validated['roles']) && Auth::guard('superadmin')->user()?->is_super_admin) {
            $user->syncRoles($validated['roles']);
        }

        // Handle permission assignments (only for superadmins)
        if (isset($validated['permissions']) && Auth::guard('superadmin')->user()?->is_super_admin) {
            $user->syncPermissions($validated['permissions']);
        }

        // Handle tenant assignments (only for superadmins)
        if (isset($validated['tenants']) && Auth::guard('superadmin')->user()?->is_super_admin) {
            $user->tenants()->sync($validated['tenants']);
        }

        // Log the creation
        UserAuditLog::logUserAction(
            $user,
            Auth::guard('superadmin')->user(),
            'user_created',
            null,
            json_encode([
                'name' => $user->name,
                'email' => $user->email,
                'is_super_admin' => $user->is_super_admin,
                'is_active' => $user->is_active,
            ]),
            'Usuario creado desde panel de administración'
        );

        return $user;
    }

    /**
     * SECURE: Update user with proper validation and authorization
     */
    public static function update(User $record, array $data): User
    {
        $request = new UserUpdateRequest();
        $request->merge($data);
        $request->setRouteResolver(function () use ($record) {
            $route = new \Illuminate\Routing\Route('PUT', 'users/{user}', []);
            $route->bind('user', $record);
            return $route;
        });

        // Validate and authorize
        $validated = $request->validated();
        $sanitized = $request->sanitized();

        // Use secure update method
        $record->secureUpdate($sanitized, Auth::guard('superadmin')->user());

        // Handle role assignments (only for superadmins)
        if (isset($sanitized['roles']) && Auth::guard('superadmin')->user()?->is_super_admin) {
            $oldRoles = $record->roles->pluck('name')->toArray();
            $record->syncRoles($sanitized['roles']);
            $newRoles = $record->roles->pluck('name')->toArray();

            if ($oldRoles !== $newRoles) {
                UserAuditLog::logUserAction(
                    $record,
                    Auth::guard('superadmin')->user(),
                    'roles_assigned',
                    json_encode($oldRoles),
                    json_encode($newRoles),
                    'Roles actualizados desde panel de administración'
                );
            }
        }

        // Handle permission assignments (only for superadmins)
        if (isset($sanitized['permissions']) && Auth::guard('superadmin')->user()?->is_super_admin) {
            $oldPermissions = $record->permissions->pluck('name')->toArray();
            $record->syncPermissions($sanitized['permissions']);
            $newPermissions = $record->permissions->pluck('name')->toArray();

            if ($oldPermissions !== $newPermissions) {
                UserAuditLog::logUserAction(
                    $record,
                    Auth::guard('superadmin')->user(),
                    'permissions_assigned',
                    json_encode($oldPermissions),
                    json_encode($newPermissions),
                    'Permisos actualizados desde panel de administración'
                );
            }
        }

        // Handle tenant assignments (only for superadmins)
        if (isset($sanitized['tenants']) && Auth::guard('superadmin')->user()?->is_super_admin) {
            $oldTenants = $record->tenants->pluck('id')->toArray();
            $record->tenants()->sync($sanitized['tenants']);
            $newTenants = $record->tenants->pluck('id')->toArray();

            if ($oldTenants !== $newTenants) {
                UserAuditLog::logUserAction(
                    $record,
                    Auth::guard('superadmin')->user(),
                    'tenants_assigned',
                    json_encode($oldTenants),
                    json_encode($newTenants),
                    'Tenants actualizados desde panel de administración'
                );
            }
        }

        return $record->refresh();
    }

    /**
     * SECURE: Delete user with proper validation and authorization
     */
    public static function delete(User $record): bool
    {
        $request = new UserDestroyRequest();
        $request->merge([
            'confirm_delete' => 'DELETE',
            'delete_reason' => 'Usuario eliminado desde panel de administración',
        ]);

        // Validate and authorize
        $request->validated();

        // Get audit data before deletion
        $auditData = $request->getAuditData();

        // Log the deletion
        UserAuditLog::logUserAction(
            $record,
            Auth::guard('superadmin')->user(),
            'user_deleted',
            json_encode([
                'name' => $record->name,
                'email' => $record->email,
                'is_super_admin' => $record->is_super_admin,
                'is_active' => $record->is_active,
            ]),
            null,
            $auditData['delete_reason'],
            $auditData
        );

        return $record->delete();
    }
}
