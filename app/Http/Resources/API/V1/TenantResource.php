<?php

/**
 * Kartenant - Ferretero Ágil
 * 
 * Este archivo es parte de Kartenant.
 * 
 * @copyright Copyright (c) 2025-2026 Kartenant
 * @license   GNU AGPLv3 <https://www.gnu.org/licenses/agpl-3.0.txt>
 */

namespace App\Http\Resources\API\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Tenant API Resource
 *
 * Transforms Tenant model for API responses.
 */
class TenantResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'domain' => $this->domain,
            'database' => $this->database,
            'status' => $this->status,
            'locale' => $this->locale,
            'currency' => $this->currency,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),

            // Conditionally include settings if loaded
            'settings' => $this->when(
                $this->relationLoaded('settings'),
                fn() => $this->settings
            ),

            // Include subscription plan info if exists
            'plan' => $this->when(
                isset($this->plan),
                fn() => $this->plan
            ),
        ];
    }
}
