<?php

namespace App\Services;

use App\Models\TenantUsage;
use App\Models\UsageAlert;
use App\Models\Tenant;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;

class UsageAlertService
{
    private const SLACK_WEBHOOK_KEY = 'usage_alerts';

    /**
     * Process pending alerts for a tenant usage record
     */
    public function processAlerts(TenantUsage $usage): void
    {
        $alertsToSend = $usage->getAlertsToSend();

        foreach ($alertsToSend as $alertType) {
            $this->sendAlert($usage, $alertType);
        }
    }

    /**
     * Send specific alert type
     */
    public function sendAlert(TenantUsage $usage, string $alertType): void
    {
        try {
            $tenant = $usage->tenant;
            if (!$tenant) {
                return;
            }

            // Create alert record
            $alert = $this->createAlert($usage, $alertType);

            // Determine delivery channels
            $channels = $this->getDeliveryChannels($alertType);

            // Send to each channel
            foreach ($channels as $channel) {
                $this->sendToChannel($alert, $channel);
            }

            // Mark as sent
            $usage->markAlertSent($alertType);

            // Log successful alert
            Log::info('Usage alert sent', [
                'tenant_id' => $tenant->id,
                'tenant_name' => $tenant->name,
                'alert_type' => $alertType,
                'status' => $usage->status,
                'channels' => $channels,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send usage alert', [
                'tenant_id' => $usage->tenant_id,
                'alert_type' => $alertType,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Create alert record
     */
    private function createAlert(TenantUsage $usage, string $alertType): UsageAlert
    {
        // Find the metric with highest percentage for this alert type
        $metricType = $this->getPrimaryMetricForAlert($usage, $alertType);
        $percentage = $usage->getPercentage($metricType);
        $currentValue = $usage->getCurrent($metricType);
        $limitValue = $usage->getLimit($metricType);

        return UsageAlert::create([
            'tenant_id' => $usage->tenant_id,
            'tenant_usage_id' => $usage->id,
            'alert_type' => $alertType,
            'metric_type' => $metricType,
            'current_value' => $currentValue,
            'limit_value' => $limitValue,
            'percentage' => $percentage,
            'delivery_channels' => $this->getDeliveryChannels($alertType),
            'delivery_status' => [],
            'message' => $this->generateAlertMessage($alertType, $metricType, $percentage, $currentValue, $limitValue),
            'metadata' => [
                'period' => $usage->getPeriodLabel(),
                'days_remaining' => $usage->getDaysRemainingInPeriod(),
                'overall_status' => $usage->status,
            ],
        ]);
    }

    /**
     * Get primary metric for alert type
     */
    private function getPrimaryMetricForAlert(TenantUsage $usage, string $alertType): string
    {
        $metrics = ['sales', 'products', 'users', 'storage'];
        $highestPercentage = 0;
        $primaryMetric = 'overall';

        foreach ($metrics as $metric) {
            $percentage = $usage->getPercentage($metric);
            if ($percentage > $highestPercentage) {
                $highestPercentage = $percentage;
                $primaryMetric = $metric;
            }
        }

        return $primaryMetric;
    }

    /**
     * Get delivery channels for alert type
     */
    private function getDeliveryChannels(string $alertType): array
    {
        $channels = ['in_app']; // Always show in app

        if ($alertType === 'warning') {
            $channels[] = 'email';
        } elseif ($alertType === 'overdraft') {
            $channels[] = 'email';
            if (config('services.slack.webhooks.' . self::SLACK_WEBHOOK_KEY)) {
                $channels[] = 'slack';
            }
        } elseif ($alertType === 'critical') {
            $channels[] = 'email';
            $channels[] = 'slack'; // Always send critical to Slack
        }

        return $channels;
    }

    /**
     * Send alert to specific channel
     */
    private function sendToChannel(UsageAlert $alert, string $channel): void
    {
        try {
            switch ($channel) {
                case 'email':
                    $this->sendEmailAlert($alert);
                    break;
                case 'slack':
                    $this->sendSlackAlert($alert);
                    break;
                case 'in_app':
                    // In-app alerts are handled by frontend components
                    $alert->markDelivered('in_app');
                    break;
            }
        } catch (\Exception $e) {
            $alert->markFailed($channel, $e->getMessage());
            Log::error("Failed to send {$channel} alert", [
                'alert_id' => $alert->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send email alert
     */
    private function sendEmailAlert(UsageAlert $alert): void
    {
        $tenant = $alert->tenant;

        // Send to tenant owner
        if ($tenant->owner && $tenant->owner->email) {
            Mail::to($tenant->owner->email)->send(
                new \App\Mail\UsageAlertMail($alert)
            );
        }

        // Send to sales team for overdraft/critical
        if (in_array($alert->alert_type, ['overdraft', 'critical'])) {
            $salesEmail = config('usage.sales_team_email', 'sales@emporiodigital.com');
            Mail::to($salesEmail)->send(
                new \App\Mail\SalesTeamAlertMail($alert)
            );
        }

        $alert->markDelivered('email');
    }

    /**
     * Send Slack alert
     */
    private function sendSlackAlert(UsageAlert $alert): void
    {
        $webhook = config('services.slack.webhooks.' . self::SLACK_WEBHOOK_KEY);

        if (!$webhook) {
            return;
        }

        $payload = $this->buildSlackPayload($alert);

        $response = \Http::post($webhook, $payload);

        if ($response->successful()) {
            $alert->markDelivered('slack');
        } else {
            $alert->markFailed('slack', 'HTTP ' . $response->status());
        }
    }

    /**
     * Build Slack payload
     */
    private function buildSlackPayload(UsageAlert $alert): array
    {
        $color = match($alert->alert_type) {
            'warning' => 'warning',
            'overdraft' => 'danger',
            'critical' => 'danger',
            default => 'good',
        };

        $emoji = match($alert->alert_type) {
            'warning' => '⚠️',
            'overdraft' => '🔴',
            'critical' => '🚨',
            default => 'ℹ️',
        };

        return [
            'attachments' => [
                [
                    'color' => $color,
                    'title' => "{$emoji} Alerta de Uso - {$alert->tenant->name}",
                    'fields' => [
                        [
                            'title' => 'Tipo de Alerta',
                            'value' => ucfirst($alert->alert_type),
                            'short' => true,
                        ],
                        [
                            'title' => 'Métrica',
                            'value' => $alert->getMetricDisplayName(),
                            'short' => true,
                        ],
                        [
                            'title' => 'Uso Actual',
                            'value' => number_format($alert->current_value),
                            'short' => true,
                        ],
                        [
                            'title' => 'Límite',
                            'value' => number_format($alert->limit_value),
                            'short' => true,
                        ],
                        [
                            'title' => 'Porcentaje',
                            'value' => number_format($alert->percentage, 1) . '%',
                            'short' => true,
                        ],
                        [
                            'title' => 'Período',
                            'value' => $alert->metadata['period'] ?? 'N/A',
                            'short' => true,
                        ],
                    ],
                    'text' => $alert->message,
                    'footer' => 'Emporio Digital Usage Monitor',
                    'ts' => $alert->created_at->timestamp,
                ],
            ],
        ];
    }

    /**
     * Generate alert message
     */
    private function generateAlertMessage(
        string $alertType,
        string $metricType,
        float $percentage,
        int $currentValue,
        ?int $limitValue
    ): string {
        $metricName = match($metricType) {
            'sales' => 'Ventas Mensuales',
            'products' => 'Productos',
            'users' => 'Usuarios',
            'storage' => 'Almacenamiento',
            default => 'Uso General',
        };

        $current = number_format($currentValue);
        $limit = $limitValue ? number_format($limitValue) : 'Ilimitado';
        $percentDisplay = number_format($percentage, 1);

        $baseMessage = "{$metricName}: {$current} / {$limit} ({$percentDisplay}%)";

        return match($alertType) {
            'warning' => "⚠️ **Advertencia de Uso**\n\n{$baseMessage}\n\nEstás acercándote al límite de tu plan. Considera actualizar pronto para evitar interrupciones.",
            'overdraft' => "🔴 **Exceso de Uso Detectado**\n\n{$baseMessage}\n\nHas excedido el límite de tu plan. Se requiere actualizar el plan en el próximo ciclo de facturación.\n\n💡 **Acción Recomendada:** Contacta a nuestro equipo de ventas para una actualización inmediata.",
            'critical' => "🚨 **Uso Crítico - Acción Requerida**\n\n{$baseMessage}\n\nHas excedido significativamente el límite de tu plan. Algunas funciones pueden estar limitadas.\n\n⚡ **Acción Inmediata Requerida:** Actualiza tu plan ahora para restaurar todas las funcionalidades.",
            default => $baseMessage,
        };
    }

    /**
     * Process all pending alerts system-wide
     */
    public function processAllPendingAlerts(): void
    {
        TenantUsage::whereHas('tenant')
            ->where(function ($query) {
                $query->where(function ($q) {
                    $q->where('status', 'warning')->where('warning_sent', false);
                })
                ->orWhere(function ($q) {
                    $q->where('status', 'overdraft')->where('overdraft_sent', false);
                })
                ->orWhere(function ($q) {
                    $q->where('status', 'critical')->where('critical_sent', false);
                });
            })
            ->chunk(50, function ($usages) {
                foreach ($usages as $usage) {
                    Queue::push(function () use ($usage) {
                        $this->processAlerts($usage);
                    });
                }
            });
    }

    /**
     * Send test alert for development
     */
    public function sendTestAlert(int $tenantId, string $alertType = 'warning'): void
    {
        $usage = TenantUsage::getOrCreateCurrentUsage($tenantId);
        $this->sendAlert($usage, $alertType);
    }

    /**
     * Get alert statistics for admin dashboard
     */
    public function getAlertStatistics(): array
    {
        $today = now()->startOfDay();
        $weekAgo = now()->subDays(7)->startOfDay();
        $monthAgo = now()->subMonth()->startOfDay();

        return [
            'alerts_today' => UsageAlert::where('created_at', '>=', $today)->count(),
            'alerts_this_week' => UsageAlert::where('created_at', '>=', $weekAgo)->count(),
            'alerts_this_month' => UsageAlert::where('created_at', '>=', $monthAgo)->count(),
            'warning_alerts' => UsageAlert::warning()->count(),
            'overdraft_alerts' => UsageAlert::overdraft()->count(),
            'critical_alerts' => UsageAlert::critical()->count(),
            'email_delivery_rate' => $this->getDeliveryRate('email'),
            'slack_delivery_rate' => $this->getDeliveryRate('slack'),
        ];
    }

    /**
     * Get delivery rate for channel
     */
    private function getDeliveryRate(string $channel): float
    {
        $total = UsageAlert::whereJsonContains('delivery_channels', $channel)->count();

        if ($total === 0) {
            return 100.0;
        }

        $delivered = UsageAlert::whereJsonContains('delivery_channels', $channel)
            ->whereJsonContains('delivery_status->' . $channel, 'sent')
            ->count();

        return round(($delivered / $total) * 100, 2);
    }
}