<?php

namespace App\View\Components;

use App\Services\TenantUsageService;
use Illuminate\View\Component;
use Illuminate\View\View;

class UsageWarningBanner extends Component
{
    public array $usageStatus;

    public bool $showBanner;

    public string $bannerType;

    public string $title;

    public string $message;

    public array $actions;

    public function __construct()
    {
        $this->usageStatus = app(TenantUsageService::class)->getUsageStatus(tenant()->id);
        $this->showBanner = $this->shouldShowBanner();
        $this->bannerType = $this->getBannerType();
        $this->title = $this->getBannerTitle();
        $this->message = $this->getBannerMessage();
        $this->actions = $this->getBannerActions();
    }

    public function render(): View
    {
        return view('components.usage-warning-banner');
    }

    private function shouldShowBanner(): bool
    {
        $status = $this->usageStatus['status'];

        // Don't show for normal status
        if ($status === 'normal') {
            return false;
        }

        // Don't show if user has dismissed it recently (session-based)
        if (session()->has("usage_banner_dismissed_{$status}")) {
            $dismissedAt = session("usage_banner_dismissed_{$status}");
            if (now()->diffInHours($dismissedAt) < 1) { // Don't show for 1 hour after dismissal
                return false;
            }
        }

        return true;
    }

    private function getBannerType(): string
    {
        return match ($this->usageStatus['status']) {
            'warning' => 'warning',
            'overdraft' => 'danger',
            'critical' => 'danger',
            default => 'info',
        };
    }

    private function getBannerTitle(): string
    {
        return match ($this->usageStatus['status']) {
            'warning' => '⚠️ Advertencia de Uso',
            'overdraft' => '🔴 Límites Excedidos',
            'critical' => '🚨 Uso Crítico - Acción Requerida',
            default => 'Información de Uso',
        };
    }

    private function getBannerMessage(): string
    {
        $status = $this->usageStatus['status'];
        $daysRemaining = $this->usageStatus['days_remaining'];
        $metrics = $this->usageStatus['metrics'];

        // Find the metric with highest percentage
        $criticalMetrics = [];
        foreach ($metrics as $metric => $data) {
            if ($data['percentage'] >= 80) {
                $criticalMetrics[] = $data;
            }
        }

        // Sort by percentage descending
        usort($criticalMetrics, function ($a, $b) {
            return $b['percentage'] <=> $a['percentage'];
        });

        $mainMetric = $criticalMetrics[0] ?? null;

        if (! $mainMetric) {
            return 'Revisa el estado de tu plan de suscripción.';
        }

        $metricName = match ($mainMetric['zone']) {
            'sales' => 'ventas mensuales',
            'products' => 'productos',
            'users' => 'usuarios',
            'storage' => 'almacenamiento',
            default => 'recursos',
        };

        $baseMessage = match ($status) {
            'warning' => "Estás cerca del límite de {$metricName} ({$mainMetric['percentage']}%).",
            'overdraft' => "Has excedido el límite de {$metricName} en un ".($mainMetric['percentage'] - 100).'%.',
            'critical' => "Has excedido significativamente el límite de {$metricName}. Algunas funciones están limitadas.",
            default => "Revisa tu uso actual de {$metricName}.",
        };

        $timeMessage = $daysRemaining <= 3
            ? " Quedan solo {$daysRemaining} días en este período."
            : '';

        return $baseMessage.$timeMessage;
    }

    private function getBannerActions(): array
    {
        $actions = [];

        if ($this->usageStatus['upgrade_required']) {
            $actions[] = [
                'label' => 'Actualizar Plan',
                'url' => route('billing.index'),
                'type' => 'primary',
            ];
        }

        $actions[] = [
            'label' => 'Ver Detalles',
            'url' => route('filament.app.resources.tenant-usages.index'),
            'type' => 'secondary',
        ];

        $actions[] = [
            'label' => 'Ocultar (1 hora)',
            'action' => 'dismiss',
            'type' => 'ghost',
        ];

        return $actions;
    }

    public function getBannerClasses(): string
    {
        $baseClasses = 'border-l-4 p-4 mb-6 rounded-lg shadow-sm';

        return match ($this->bannerType) {
            'warning' => $baseClasses.' bg-yellow-50 border-yellow-400',
            'danger' => $baseClasses.' bg-red-50 border-red-400',
            'info' => $baseClasses.' bg-blue-50 border-blue-400',
            default => $baseClasses.' bg-gray-50 border-gray-400',
        };
    }

    public function getTitleClasses(): string
    {
        return match ($this->bannerType) {
            'warning' => 'text-yellow-800',
            'danger' => 'text-red-800',
            'info' => 'text-blue-800',
            default => 'text-gray-800',
        };
    }

    public function getTextClasses(): string
    {
        return match ($this->bannerType) {
            'warning' => 'text-yellow-700',
            'danger' => 'text-red-700',
            'info' => 'text-blue-700',
            default => 'text-gray-700',
        };
    }

    public function getActionButtonClasses(string $type): string
    {
        $baseClasses = 'px-4 py-2 rounded-md text-sm font-medium transition-colors';

        return match ($type) {
            'primary' => $baseClasses.' bg-blue-600 text-white hover:bg-blue-700',
            'secondary' => $baseClasses.' bg-gray-200 text-gray-800 hover:bg-gray-300',
            'ghost' => $baseClasses.' text-gray-600 hover:text-gray-800',
            default => $baseClasses,
        };
    }
}
