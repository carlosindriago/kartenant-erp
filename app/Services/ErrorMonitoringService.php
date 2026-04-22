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

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class ErrorMonitoringService
{
    public function sendCriticalErrorToSlack(Throwable $exception): void
    {
        // PROTECCIÓN: Verificar que la app esté lista
        if (!$this->isAppReady()) {
            return;
        }

        try {
            $this->createAutomaticBugReport($exception);
            
            $slackWebhook = config('services.slack.webhook_url');
            
            if (empty($slackWebhook)) {
                return;
            }

            $message = $this->formatSlackMessage($exception);
            
            Http::post($slackWebhook, $message);
            
        } catch (\Exception $e) {
            // Fallar silenciosamente para no romper la app
            Log::error('Failed to send Slack notification', [
                'error' => $e->getMessage()
            ]);
        }
    }

    protected function createAutomaticBugReport(Throwable $exception): void
    {
        // PROTECCIÓN: Verificar que la app esté lista
        if (!$this->isAppReady()) {
            return;
        }

        try {
            // Verificar que DB esté disponible antes de usarlo
            if (!app()->bound('db')) {
                return;
            }

            $technicalDetails = "File: " . $exception->getFile() . "\n" .
                "Line: " . $exception->getLine() . "\n\n" .
                "Trace:\n" . $exception->getTraceAsString();

            DB::table('bug_reports')->insert([
                'title' => 'Automatic Report: ' . class_basename($exception),
                'description' => $exception->getMessage() . "\n\n" . $technicalDetails,
                'reporter_user_id' => $this->getCurrentUserId(), // Note: migration uses reporter_user_id, let's check that too
                'severity' => 'critical',
                'status' => 'pending', // Migration default is pending, enum values: pending, in_progress, etc.
                'created_at' => now(),
                'updated_at' => now(),
                // Required fields from migration:
                'ticket_number' => 'AUTO-' . now()->format('Ymd-His') . '-' . Str::random(4), // We need to generate this or let model do it? DB::table doesn't use model events.
                'reporter_name' => 'System',
                'reporter_email' => 'system@emporio.digital',
            ]);
        } catch (\Exception $e) {
            // Fallar silenciosamente
            Log::error('Failed to create bug report', [
                'error' => $e->getMessage()
            ]);
        }
    }

    protected function getCurrentUserId(): ?int
    {
        // PROTECCIÓN: Verificar que Auth esté disponible
        if (!app()->bound('auth') || !Auth::check()) {
            return null;
        }

        try {
            return Auth::id();
        } catch (\Exception $e) {
            return null;
        }
    }

    protected function formatSlackMessage(Throwable $exception): array
    {
        $url = request()->fullUrl() ?? 'Console Command';
        $user = $this->getCurrentUserEmail();

        return [
            'text' => '🚨 *Critical Error Detected*',
            'blocks' => [
                [
                    'type' => 'section',
                    'text' => [
                        'type' => 'mrkdwn',
                        'text' => "*Error:* `{$exception->getMessage()}`"
                    ]
                ],
                [
                    'type' => 'section',
                    'fields' => [
                        [
                            'type' => 'mrkdwn',
                            'text' => "*File:*
{$exception->getFile()}"
                        ],
                        [
                            'type' => 'mrkdwn',
                            'text' => "*Line:*
{$exception->getLine()}"
                        ],
                        [
                            'type' => 'mrkdwn',
                            'text' => "*URL:*
{$url}"
                        ],
                        [
                            'type' => 'mrkdwn',
                            'text' => "*User:*
{$user}"
                        ],
                    ]
                ],
            ]
        ];
    }

    protected function getCurrentUserEmail(): string
    {
        // PROTECCIÓN: Verificar que Auth esté disponible
        if (!app()->bound('auth') || !Auth::check()) {
            return 'Guest';
        }

        try {
            return Auth::user()->email ?? 'Guest';
        } catch (\Exception $e) {
            return 'Guest';
        }
    }

    /**
     * Verificar si la aplicación está completamente inicializada
     */
    protected function isAppReady(): bool
    {
        try {
            // Verificar que:
            // 1. La app esté booteada
            // 2. El contenedor tenga el servicio 'db' registrado
            // 3. No estemos en proceso de configuración inicial
            return app()->isBooted() 
                && app()->bound('db') 
                && app()->bound('config');
        } catch (\Exception $e) {
            return false;
        }
    }
}