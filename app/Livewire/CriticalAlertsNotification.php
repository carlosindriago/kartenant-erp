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

use App\Services\Dashboard\InsightsEngine;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;

/**
 * CriticalAlertsNotification: Componente de notificaciones globales
 *
 * Se muestra en el topbar de todas las páginas del panel tenant.
 * Estilo Facebook: icono de campana con contador de alertas críticas.
 *
 * Características:
 * - Contador dinámico de alertas
 * - Dropdown con lista de alertas
 * - Click en alerta navega al recurso correspondiente
 * - Cache de 5 minutos para performance
 */
class CriticalAlertsNotification extends Component
{
    public bool $showDropdown = false;

    /**
     * Toggle del dropdown
     */
    public function toggleDropdown()
    {
        $this->showDropdown = ! $this->showDropdown;
    }

    /**
     * Cerrar dropdown
     */
    public function closeDropdown()
    {
        $this->showDropdown = false;
    }

    /**
     * Obtener alertas críticas y de alta prioridad
     */
    public function getAlertsProperty()
    {
        return Cache::remember('critical_alerts_notification', 300, function () {
            $engine = new InsightsEngine;
            $allActions = $engine->generateTopActions(limit: 10);

            // Solo alertas críticas y de alta prioridad
            return array_filter($allActions, function ($action) {
                return in_array($action['priority'], ['critical', 'high']);
            });
        });
    }

    /**
     * Obtener contador de alertas
     */
    public function getCountProperty()
    {
        return count($this->alerts);
    }

    /**
     * Obtener alertas por severidad
     */
    public function getCriticalCountProperty()
    {
        return count(array_filter($this->alerts, fn ($a) => $a['priority'] === 'critical'));
    }

    public function getHighCountProperty()
    {
        return count(array_filter($this->alerts, fn ($a) => $a['priority'] === 'high'));
    }

    /**
     * Limpiar cache manualmente (útil después de resolver una alerta)
     */
    public function refreshAlerts()
    {
        Cache::forget('critical_alerts_notification');
        Cache::forget('insights.top_actions');
        $this->dispatch('alerts-refreshed');
    }

    public function render()
    {
        return view('livewire.critical-alerts-notification');
    }
}
