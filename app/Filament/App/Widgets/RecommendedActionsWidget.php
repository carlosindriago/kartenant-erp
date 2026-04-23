<?php

/**
 * Kartenant - Ferretero Ágil
 *
 * Este archivo es parte de Kartenant.
 *
 * @copyright Copyright (c) 2025-2026 Kartenant
 * @license   GNU AGPLv3 <https://www.gnu.org/licenses/agpl-3.0.txt>
 */

namespace App\Filament\App\Widgets;

use App\Services\Dashboard\InsightsEngine;
use Filament\Widgets\Widget;

/**
 * RecommendedActionsWidget: Widget de Acciones Recomendadas
 *
 * Este widget responde la pregunta clave: "¿Qué debo hacer HOY?"
 *
 * Muestra las top 5 acciones priorizadas por el InsightsEngine:
 * - Reordenes urgentes
 * - Productos a liquidar
 * - Clientes a contactar
 * - Ajustes de precio
 *
 * Cada acción incluye:
 * - Prioridad (crítico/alto/medio)
 * - Impacto económico estimado
 * - CTAs claros
 */
class RecommendedActionsWidget extends Widget
{
    protected static string $view = 'filament.app.widgets.recommended-actions';

    protected int|string|array $columnSpan = 'full'; // Full width always

    protected static ?int $sort = 11; // After critical alerts

    /**
     * Obtener las top 5 acciones recomendadas
     */
    public function getActions(): array
    {
        $engine = new InsightsEngine;

        // Filtrar solo acciones de prioridad media o inferior
        // (Las críticas/altas ya están en CriticalAlertsWidget)
        $allActions = $engine->generateTopActions(limit: 10);

        return array_filter($allActions, function ($action) {
            return in_array($action['priority'], ['medium', 'low']);
        });
    }

    /**
     * Obtener color del badge según prioridad
     */
    public function getPriorityColor(string $priority): string
    {
        return match ($priority) {
            'critical' => 'danger',
            'high' => 'warning',
            'medium' => 'info',
            'low' => 'gray',
            default => 'gray',
        };
    }

    /**
     * Obtener label del badge según prioridad
     */
    public function getPriorityLabel(string $priority): string
    {
        return match ($priority) {
            'critical' => 'URGENTE',
            'high' => 'IMPORTANTE',
            'medium' => 'Moderado',
            'low' => 'Sugerencia',
            default => $priority,
        };
    }
}
