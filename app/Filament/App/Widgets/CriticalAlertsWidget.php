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

use Filament\Widgets\Widget;
use App\Services\Dashboard\InsightsEngine;

/**
 * CriticalAlertsWidget: Widget de Alertas Críticas
 * 
 * Este es el widget MÁS IMPORTANTE del dashboard.
 * Solo se muestra cuando hay problemas que requieren atención inmediata.
 * 
 * Características:
 * - Siempre arriba (sort = -1)
 * - Colapsable
 * - Colores según severidad (rojo/naranja)
 * - CTAs claros para cada alerta
 * - Muestra impacto económico cuando es relevante
 */
class CriticalAlertsWidget extends Widget
{
    protected static string $view = 'filament.app.widgets.critical-alerts';

    protected int | string | array $columnSpan = 'full'; // Ancho completo siempre

    protected static ?int $sort = 3; 
    /**
     * Obtener todas las alertas críticas del negocio
     */
    public function getAlerts(): array
    {
        $engine = new InsightsEngine();
        
        // Solo retornar acciones críticas y de alta prioridad
        $allActions = $engine->generateTopActions(limit: 10);
        
        return array_filter($allActions, function ($action) {
            return in_array($action['priority'], ['critical', 'high']);
        });
    }
    
    /**
     * Solo mostrar el widget si hay alertas
     * Filament llama este método para determinar si el widget debe renderizarse
     */
    public static function canView(): bool
    {
        $engine = new InsightsEngine();
        $allActions = $engine->generateTopActions(limit: 10);
        
        $criticalAlerts = array_filter($allActions, function ($action) {
            return in_array($action['priority'], ['critical', 'high']);
        });
        
        return count($criticalAlerts) > 0;
    }
    
    /**
     * Obtener el total de pérdidas estimadas
     */
    public function getTotalEstimatedLoss(): float
    {
        $alerts = $this->getAlerts();
        $total = 0;
        
        foreach ($alerts as $alert) {
            if (isset($alert['estimated_loss'])) {
                $total += $alert['estimated_loss'];
            }
        }
        
        return $total;
    }
    
    /**
     * Obtener contador de alertas por severidad
     */
    public function getAlertCounts(): array
    {
        $alerts = $this->getAlerts();
        $counts = [
            'critical' => 0,
            'high' => 0,
        ];
        
        foreach ($alerts as $alert) {
            if (isset($counts[$alert['priority']])) {
                $counts[$alert['priority']]++;
            }
        }
        
        return $counts;
    }
}
