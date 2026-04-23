<?php

/**
 * Kartenant - Ferretero Ágil
 *
 * Este archivo es parte de Kartenant.
 *
 * @copyright Copyright (c) 2025-2026 Kartenant
 * @license   GNU AGPLv3 <https://www.gnu.org/licenses/agpl-3.0.txt>
 */

namespace App\Services;

use App\Models\BugReport;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * BugReportService
 *
 * Handles user-submitted bug reports from the application
 * - Uploads screenshots to temporary storage
 * - Sends formatted reports to Slack
 * - Includes full context (user, tenant, URL, browser, etc.)
 */
class BugReportService
{
    /**
     * Submit a bug report to Slack and Database
     */
    public function submitReport(array $data): array
    {
        $webhookUrl = config('logging.channels.slack.url');

        try {
            // Upload screenshots if provided
            $screenshotUrls = [];
            if (! empty($data['screenshots'])) {
                $screenshotUrls = $this->uploadScreenshots($data['screenshots']);
            }

            // Save to database
            $bugReport = BugReport::create([
                'severity' => $data['severity'] ?? 'medium',
                'title' => $data['title'],
                'description' => $data['description'],
                'steps_to_reproduce' => $data['steps'] ?? null,
                'status' => 'pending',
                'priority' => $this->determinePriority($data['severity'] ?? 'medium'),
                'reporter_name' => $data['user_name'] ?? 'Desconocido',
                'reporter_email' => $data['user_email'] ?? null,
                'reporter_user_id' => $data['user_id'] ?? null,
                'reporter_ip' => $data['ip'] ?? null,
                'tenant_id' => $data['tenant_id'] ?? null,
                'tenant_name' => $data['tenant_name'] ?? 'Desconocido',
                'url' => $data['url'] ?? null,
                'user_agent' => $data['user_agent'] ?? null,
                'screenshots' => $screenshotUrls,
            ]);

            Log::info('[BugReport] Report saved to database', [
                'ticket_number' => $bugReport->ticket_number,
                'user' => $data['user_name'] ?? 'Unknown',
                'tenant' => $data['tenant_name'] ?? 'Unknown',
            ]);

            // Send to Slack if configured
            if ($webhookUrl) {
                try {
                    $payload = $this->buildSlackPayload($data, $screenshotUrls, $bugReport->ticket_number);
                    $response = Http::timeout(10)->post($webhookUrl, $payload);

                    if ($response->successful()) {
                        Log::info('[BugReport] Report sent to Slack', [
                            'ticket_number' => $bugReport->ticket_number,
                        ]);
                    }
                } catch (\Throwable $e) {
                    Log::warning('[BugReport] Failed to send to Slack (report still saved): '.$e->getMessage());
                }
            }

            return [
                'success' => true,
                'message' => 'Reporte enviado exitosamente',
                'ticket_number' => $bugReport->ticket_number,
            ];

        } catch (\Throwable $e) {
            Log::error('[BugReport] Failed to save report: '.$e->getMessage());

            return [
                'success' => false,
                'message' => 'Error al procesar el reporte: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Determine priority based on severity
     */
    protected function determinePriority(string $severity): string
    {
        return match ($severity) {
            'critical' => 'urgent',
            'high' => 'high',
            'medium' => 'normal',
            'low' => 'low',
            default => 'normal',
        };
    }

    /**
     * Upload screenshots to storage and return paths
     */
    protected function uploadScreenshots(array $files): array
    {
        $paths = [];

        foreach ($files as $index => $file) {
            if ($file instanceof UploadedFile) {
                try {
                    // Generate unique filename with original extension
                    $uniqueName = uniqid().'-'.$index.'.'.$file->extension();
                    $directory = 'bug-reports/'.now()->format('Y-m-d');

                    // Store in public disk: storage/app/public/bug-reports/2025-10-19/xxxxx-0.png
                    $path = $file->storeAs($directory, $uniqueName, 'public');

                    // Save the path relative to storage/app (for database)
                    // This will be: public/bug-reports/2025-10-19/xxxxx-0.png
                    $paths[] = $path;

                    Log::info('[BugReport] Screenshot uploaded', [
                        'original_name' => $file->getClientOriginalName(),
                        'stored_path' => $path,
                    ]);

                } catch (\Throwable $e) {
                    Log::error('[BugReport] Failed to upload screenshot', [
                        'file' => $file->getClientOriginalName(),
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        return $paths;
    }

    /**
     * Build Slack message payload for bug report
     */
    protected function buildSlackPayload(array $data, array $screenshotUrls, ?string $ticketNumber = null): array
    {
        $severity = $data['severity'] ?? 'medium';
        $title = $data['title'] ?? 'Reporte de Error';
        $description = $data['description'] ?? 'Sin descripción';
        $stepsToReproduce = $data['steps'] ?? 'No especificados';

        // Determine color based on severity
        $color = match ($severity) {
            'critical' => 'danger',
            'high' => '#ff9900',
            'medium' => 'warning',
            'low' => '#36a64f',
            default => '#cccccc',
        };

        // Build fields array
        $fields = [
            [
                'title' => 'Reportado por',
                'value' => $data['user_name'] ?? 'Desconocido',
                'short' => true,
            ],
            [
                'title' => 'Tenant',
                'value' => $data['tenant_name'] ?? 'Desconocido',
                'short' => true,
            ],
            [
                'title' => 'Email',
                'value' => $data['user_email'] ?? 'No disponible',
                'short' => true,
            ],
            [
                'title' => 'Severidad',
                'value' => $this->getSeverityLabel($severity),
                'short' => true,
            ],
            [
                'title' => 'IP Address',
                'value' => $data['ip'] ?? 'No disponible',
                'short' => true,
            ],
            [
                'title' => 'Fecha/Hora',
                'value' => now()->format('Y-m-d H:i:s'),
                'short' => true,
            ],
            [
                'title' => 'URL',
                'value' => '<'.($data['url'] ?? 'No disponible').'|Ver página>',
                'short' => false,
            ],
            [
                'title' => 'Navegador',
                'value' => $data['user_agent'] ?? 'No disponible',
                'short' => false,
            ],
        ];

        // Add steps to reproduce if provided
        if (! empty($stepsToReproduce) && $stepsToReproduce !== 'No especificados') {
            $fields[] = [
                'title' => 'Pasos para Reproducir',
                'value' => $stepsToReproduce,
                'short' => false,
            ];
        }

        // Build attachment
        $attachment = [
            'color' => $color,
            'title' => '🐛 '.($ticketNumber ? "[{$ticketNumber}] " : '').$title,
            'text' => $description,
            'fields' => $fields,
            'footer' => 'Reporte de Usuario • '.config('app.name').($ticketNumber ? " • Ticket: {$ticketNumber}" : ''),
            'footer_icon' => 'https://platform.slack-edge.com/img/default_application_icon.png',
            'ts' => time(),
        ];

        // Add screenshot URLs if available
        if (! empty($screenshotUrls)) {
            $screenshotText = 'Se adjuntaron '.count($screenshotUrls)." captura(s) de pantalla.\n";
            $screenshotText .= '_Nota: Las imágenes están almacenadas localmente. ';
            $screenshotText .= "En producción con un dominio público, Slack podrá mostrarlas directamente._\n\n";
            $screenshotText .= "Rutas:\n";

            foreach ($screenshotUrls as $index => $url) {
                $screenshotText .= '• Screenshot '.($index + 1).': `'.basename($url)."`\n";
            }

            $attachment['fields'][] = [
                'title' => '📸 Capturas de Pantalla ('.count($screenshotUrls).')',
                'value' => $screenshotText,
                'short' => false,
            ];
        }

        return [
            'username' => config('logging.channels.slack.username', 'Kartenant Bug Reports'),
            'icon_emoji' => ':bug:',
            'text' => '*Nuevo Reporte de Error desde la Aplicación*',
            'attachments' => [$attachment],
        ];
    }

    /**
     * Get human-readable severity label
     */
    protected function getSeverityLabel(string $severity): string
    {
        return match ($severity) {
            'critical' => '🔴 Crítico - La aplicación no funciona',
            'high' => '🟠 Alto - Funcionalidad importante afectada',
            'medium' => '🟡 Medio - Problema molesto pero no bloqueante',
            'low' => '🟢 Bajo - Mejora o problema menor',
            default => '⚪ Sin clasificar',
        };
    }
}
