<?php

/**
 * Kartenant - Ferretero Ágil
 *
 * Este archivo es parte de Kartenant.
 *
 * @copyright Copyright (c) 2025-2026 Kartenant
 * @license   GNU AGPLv3 <https://www.gnu.org/licenses/agpl-3.0.txt>
 */

namespace App\Filament\Resources\ActivityResource\Pages;

use App\Filament\Resources\ActivityResource;
use App\Models\Activity;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\ListRecords;

class ListActivities extends ListRecords
{
    protected static string $resource = ActivityResource::class;

    protected function authorizeAccess(): void
    {
        $u = auth('superadmin')->user();
        abort_unless(($u?->can('admin.audit.view') ?? false), 403);
    }

    protected function getHeaderActions(): array
    {
        $eventOptions = [
            'login' => 'login',
            'logout' => 'logout',
            'two_factor_sent' => 'two_factor_sent',
            'two_factor_verified' => 'two_factor_verified',
            'two_factor_invalid' => 'two_factor_invalid',
            'login_failed' => 'login_failed',
            'account_locked' => 'account_locked',
        ];

        return [
            Actions\Action::make('export')
                ->label('Exportar a Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->visible(fn () => auth('superadmin')->user()?->can('admin.audit.view') ?? false)
                ->modalHeading('Exportar Auditoría')
                ->modalSubmitActionLabel('Descargar')
                ->form([
                    Forms\Components\Select::make('guard')
                        ->label('Guard')
                        ->native(false)
                        ->options([
                            'superadmin' => 'superadmin',
                            'tenant' => 'tenant',
                            'web' => 'web',
                        ])
                        ->placeholder('Todos'),
                    Forms\Components\Select::make('event')
                        ->label('Evento')
                        ->native(false)
                        ->options($eventOptions)
                        ->multiple()
                        ->placeholder('Todos'),
                    Forms\Components\DatePicker::make('from')->label('Desde'),
                    Forms\Components\DatePicker::make('until')->label('Hasta'),
                ])
                ->action(function (array $data) {
                    $query = Activity::query()
                        ->where('log_name', 'auth')
                        ->when($data['guard'] ?? null, fn ($q, $g) => $q->where('guard', $g))
                        ->when(($data['event'] ?? []) !== [] && $data['event'] !== null, fn ($q) => $q->whereIn('event', $data['event']))
                        ->when($data['from'] ?? null, fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
                        ->when($data['until'] ?? null, fn ($q, $date) => $q->whereDate('created_at', '<=', $date))
                        ->orderBy('created_at', 'desc');

                    $fileName = 'auditoria_'.now()->format('Ymd_His').'.csv';

                    return response()->streamDownload(function () use ($query) {
                        $handle = fopen('php://output', 'w');
                        // UTF-8 BOM para Excel
                        fwrite($handle, chr(0xEF).chr(0xBB).chr(0xBF));
                        $delimiter = ';';
                        fputcsv($handle, [
                            'Fecha', 'Evento', 'Descripción', 'Usuario', 'Guard', 'IP', 'Ruta', 'Método', 'Tenant', 'URL',
                        ], $delimiter);

                        foreach ($query->cursor() as $row) {
                            fputcsv($handle, [
                                optional($row->created_at)->format('Y-m-d H:i:s'),
                                $row->event,
                                $row->description,
                                optional($row->causer)->email,
                                $row->guard,
                                $row->ip,
                                $row->route,
                                $row->method,
                                $row->tenant_id,
                                data_get($row->properties, 'url'),
                            ], $delimiter);
                        }

                        fclose($handle);
                    }, $fileName, [
                        'Content-Type' => 'text/csv; charset=UTF-8',
                    ]);
                }),
        ];
    }
}
