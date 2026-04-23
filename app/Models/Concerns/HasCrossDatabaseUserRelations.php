<?php

/**
 * Kartenant - Ferretero Ágil
 *
 * Este archivo es parte de Kartenant.
 *
 * @copyright Copyright (c) 2025-2026 Kartenant
 * @license   GNU AGPLv3 <https://www.gnu.org/licenses/agpl-3.0.txt>
 */

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Support\Facades\Cache;

/**
 * Trait para manejar relaciones con el modelo User que vive en la base de datos landlord
 * mientras el modelo actual vive en la base de datos tenant.
 *
 * Este trait resuelve el problema arquitectónico de referencias cruzadas entre bases de datos
 * en arquitecturas multi-tenant database-per-tenant.
 *
 * @property int|null $user_id
 * @property int|null $cancelled_by
 * @property int|null $processed_by_user_id
 * @property int|null $authorized_by
 */
trait HasCrossDatabaseUserRelations
{
    /**
     * Cache temporal de usuarios para evitar múltiples consultas
     */
    protected array $userCache = [];

    /**
     * Obtener el usuario principal (user_id)
     */
    public function getUserAttribute(): ?User
    {
        $userId = $this->attributes['user_id'] ?? null;

        if (! $userId) {
            return null;
        }

        return $this->getCachedUser('user_id', $userId);
    }

    /**
     * Obtener el usuario que canceló (cancelled_by)
     */
    public function getCancelledByAttribute(): ?User
    {
        $cancelledBy = $this->attributes['cancelled_by'] ?? null;

        if (! $cancelledBy) {
            return null;
        }

        return $this->getCachedUser('cancelled_by', $cancelledBy);
    }

    /**
     * Obtener el usuario que procesó (processed_by_user_id)
     */
    public function getProcessedByUserAttribute(): ?User
    {
        $processedBy = $this->attributes['processed_by_user_id'] ?? null;

        if (! $processedBy) {
            return null;
        }

        return $this->getCachedUser('processed_by_user_id', $processedBy);
    }

    /**
     * Obtener el usuario que autorizó (authorized_by)
     */
    public function getAuthorizedByAttribute(): ?User
    {
        $authorizedBy = $this->attributes['authorized_by'] ?? null;

        if (! $authorizedBy) {
            return null;
        }

        return $this->getCachedUser('authorized_by', $authorizedBy);
    }

    /**
     * Obtener el usuario que abrió (opened_by_user_id)
     */
    public function getOpenedByAttribute(): ?User
    {
        $openedBy = $this->attributes['opened_by_user_id'] ?? null;

        if (! $openedBy) {
            return null;
        }

        return $this->getCachedUser('opened_by_user_id', $openedBy);
    }

    /**
     * Obtener el usuario que cerró (closed_by_user_id)
     */
    public function getClosedByAttribute(): ?User
    {
        $closedBy = $this->attributes['closed_by_user_id'] ?? null;

        if (! $closedBy) {
            return null;
        }

        return $this->getCachedUser('closed_by_user_id', $closedBy);
    }

    /**
     * Obtener el usuario que forzó el cierre (forced_by_user_id)
     */
    public function getForcedByAttribute(): ?User
    {
        $forcedBy = $this->attributes['forced_by_user_id'] ?? null;

        if (! $forcedBy) {
            return null;
        }

        return $this->getCachedUser('forced_by_user_id', $forcedBy);
    }

    /**
     * Obtener usuario de la base de datos landlord con cache
     *
     * @param  string  $cacheKey  Clave para el cache interno
     * @param  int  $userId  ID del usuario
     */
    protected function getCachedUser(string $cacheKey, int $userId): ?User
    {
        // Cache en memoria del modelo
        if (isset($this->userCache[$cacheKey])) {
            return $this->userCache[$cacheKey];
        }

        // Cache de Laravel (5 minutos)
        $cacheKeyFull = "user_{$userId}";

        $user = Cache::remember($cacheKeyFull, 300, function () use ($userId) {
            return User::on('landlord')->find($userId);
        });

        // Guardar en cache de memoria
        $this->userCache[$cacheKey] = $user;

        return $user;
    }

    /**
     * Eager load de usuarios en batch para evitar N+1
     *
     * Uso:
     * $sales = Sale::with('items')->get();
     * Sale::eagerLoadUsers($sales, ['user_id', 'cancelled_by']);
     *
     * @param  \Illuminate\Support\Collection  $models
     * @param  array  $fields  Campos a cargar (user_id, cancelled_by, etc.)
     */
    public static function eagerLoadUsers($models, array $fields = ['user_id']): void
    {
        // Recolectar todos los IDs únicos
        $userIds = collect();

        foreach ($fields as $field) {
            $ids = $models->pluck($field)->filter()->unique();
            $userIds = $userIds->merge($ids);
        }

        if ($userIds->isEmpty()) {
            return;
        }

        // Cargar todos los usuarios en una sola query
        $users = User::on('landlord')
            ->whereIn('id', $userIds->unique()->values())
            ->get()
            ->keyBy('id');

        // Asignar a los modelos
        foreach ($models as $model) {
            foreach ($fields as $field) {
                if ($model->{$field}) {
                    $cacheKey = $field;
                    $model->userCache[$cacheKey] = $users->get($model->{$field});
                }
            }
        }
    }

    /**
     * Limpiar cache de usuarios
     */
    public function clearUserCache(): void
    {
        $this->userCache = [];

        $userFields = [
            'user_id',
            'cancelled_by',
            'processed_by_user_id',
            'authorized_by',
            'opened_by_user_id',
            'closed_by_user_id',
            'forced_by_user_id',
        ];

        foreach ($userFields as $field) {
            if (isset($this->{$field}) && $this->{$field}) {
                Cache::forget("user_{$this->{$field}}");
            }
        }
    }
}
