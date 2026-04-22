<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class TimeFormattingService
{
    /**
     * Formatea tiempo restante en formato "Días y Horas" para Ernesto
     *
     * @param Carbon $endDate Fecha de vencimiento
     * @return string Formato "Faltan X días y Y horas" o "Vencido hace X días"
     */
    public static function formatRemainingTime(Carbon $endDate): string
    {
        try {
            $now = now();

            // Si ya venció
            if ($endDate->isPast()) {
                return self::formatPastTime($endDate);
            }

            // Calcular diferencia exacta
            $diff = $now->diff($endDate);

            // Casos especiales
            if ($diff->days === 0) {
                return self::formatHoursOnly($diff->h);
            }

            if ($diff->days === 1) {
                return self::formatOneDay($diff->h);
            }

            // Formato estándar para múltiples días
            return self::formatMultipleDays($diff->days, $diff->h);

        } catch (\Exception $e) {
            Log::error('Error en TimeFormattingService::formatRemainingTime', [
                'end_date' => $endDate->toISOString(),
                'error' => $e->getMessage()
            ]);

            return 'Fecha no disponible';
        }
    }

    /**
     * Formatea tiempo pasado (cuando ya venció)
     */
    private static function formatPastTime(Carbon $endDate): string
    {
        $diff = $endDate->diff(now());

        if ($diff->days === 0) {
            return "Vencido hace {$diff->h} horas";
        }

        if ($diff->days === 1) {
            return $diff->h > 0
                ? "Vencido hace 1 día y {$diff->h} horas"
                : "Vencido hace 1 día";
        }

        return $diff->h > 0
            ? "Vencido hace {$diff->days} días y {$diff->h} horas"
            : "Vencido hace {$diff->days} días";
    }

    /**
     * Formatea solo horas (cuando faltan menos de 1 día)
     */
    private static function formatHoursOnly(int $hours): string
    {
        return match(true) {
            $hours === 0 => "Menos de 1 hora",
            $hours === 1 => "Falta 1 hora",
            $hours <= 6 => "Faltan {$hours} horas",
            default => "Faltan {$hours} horas"
        };
    }

    /**
     * Formatea 1 día con horas
     */
    private static function formatOneDay(int $hours): string
    {
        return match(true) {
            $hours === 0 => "Falta 1 día",
            $hours === 1 => "Falta 1 día y 1 hora",
            $hours <= 6 => "Falta 1 día y {$hours} horas",
            default => "Falta 1 día y {$hours} horas"
        };
    }

    /**
     * Formatea múltiples días
     */
    private static function formatMultipleDays(int $days, int $hours): string
    {
        $dayText = match(true) {
            $days === 0 => '',
            $days === 1 => '1 día',
            $days <= 6 => "{$days} días",
            default => "{$days} días"
        };

        if ($hours === 0) {
            return "Faltan {$dayText}";
        }

        if ($hours === 1) {
            return "Faltan {$dayText} y 1 hora";
        }

        return "Faltan {$dayText} y {$hours} horas";
    }

    /**
     * Formato simplificado para widgets de dashboard
     *
     * @param Carbon $endDate
     * @return string Formato corto "Xd Yh"
     */
    public static function formatCompactRemainingTime(Carbon $endDate): string
    {
        try {
            $now = now();

            if ($endDate->isPast()) {
                return 'Vencido';
            }

            $diff = $now->diff($endDate);

            if ($diff->days === 0) {
                return "{$diff->h}h";
            }

            if ($diff->h === 0) {
                return "{$diff->days}d";
            }

            return "{$diff->days}d {$diff->h}h";

        } catch (\Exception $e) {
            Log::error('Error en TimeFormattingService::formatCompactRemainingTime', [
                'end_date' => $endDate->toISOString(),
                'error' => $e->getMessage()
            ]);

            return 'N/A';
        }
    }

    /**
     * Formatea diferencia relativa en español business-friendly
     *
     * @param Carbon $date
     * @return string "Hoy", "Ayer", "Hace X días", etc.
     */
    public static function formatRelativeDate(Carbon $date): string
    {
        try {
            $now = now();

            if ($date->isToday()) {
                return "Hoy";
            }

            if ($date->isYesterday()) {
                return "Ayer";
            }

            if ($date->isTomorrow()) {
                return "Mañana";
            }

            $diffDays = $now->diffInDays($date, false);

            if (abs($diffDays) <= 7) {
                return $diffDays > 0
                    ? "En {$diffDays} días"
                    : "Hace " . abs($diffDays) . " días";
            }

            if (abs($diffDays) <= 30) {
                $weeks = round(abs($diffDays) / 7);
                return $diffDays > 0
                    ? "En {$weeks} semanas"
                    : "Hace {$weeks} semanas";
            }

            if (abs($diffDays) <= 365) {
                $months = round(abs($diffDays) / 30);
                return $diffDays > 0
                    ? "En {$months} meses"
                    : "Hace {$months} meses";
            }

            $years = round(abs($diffDays) / 365);
            return $diffDays > 0
                ? "En {$years} años"
                : "Hace {$years} años";

        } catch (\Exception $e) {
            Log::error('Error en TimeFormattingService::formatRelativeDate', [
                'date' => $date->toISOString(),
                'error' => $e->getMessage()
            ]);

            return 'Fecha desconocida';
        }
    }

    /**
     * Formatea fecha completa en formato latinoamericano
     *
     * @param Carbon $date
     * @return string "d de mes de año, H:MM"
     */
    public static function formatFullDate(Carbon $date): string
    {
        try {
            $months = [
                'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio',
                'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'
            ];

            return $date->format('d') . ' de ' . $months[$date->month - 1] . ' de ' . $date->format('Y, H:i');

        } catch (\Exception $e) {
            Log::error('Error en TimeFormattingService::formatFullDate', [
                'date' => $date->toISOString(),
                'error' => $e->getMessage()
            ]);

            return $date->format('d/m/Y H:i');
        }
    }

    /**
     * Verifica si una fecha está próxima a vencer (alerta naranja)
     *
     * @param Carbon $endDate
     * @param int $daysThreshold Umbral de días (default: 7)
     * @return bool
     */
    public static function isExpiringSoon(Carbon $endDate, int $daysThreshold = 7): bool
    {
        try {
            return $endDate->isFuture() &&
                   $endDate->diffInDays(now()) <= $daysThreshold;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Formatea días decimales a formato "X días Y horas" para dashboard
     *
     * @param float $decimalDays Días con decimales (ej: 6.8758198285301)
     * @param bool $isPast Si es tiempo pasado o futuro
     * @return string Formato legible "Hace X días Y horas" o "Faltan X días Y horas"
     */
    public static function formatDecimalDays(float $decimalDays, bool $isPast = false): string
    {
        try {
            // Extraer días enteros y horas fraccionales
            $days = floor(abs($decimalDays));
            $remainingDecimal = abs($decimalDays) - $days;
            $hours = round($remainingDecimal * 24);

            // Corregir si las horas son 24
            if ($hours >= 24) {
                $days += 1;
                $hours = 0;
            }

            // Singular o plural
            $dayText = $days === 1 ? 'día' : 'días';
            $hourText = $hours === 1 ? 'hora' : 'horas';

            // Construir el texto
            if ($days === 0 && $hours === 0) {
                return $isPast ? 'Vencido hace momentos' : 'Vence en momentos';
            }

            if ($days === 0) {
                return $isPast
                    ? "Vencido hace {$hours} {$hourText}"
                    : "Faltan {$hours} {$hourText}";
            }

            if ($hours === 0) {
                return $isPast
                    ? "Vencido hace {$days} {$dayText}"
                    : "Faltan {$days} {$dayText}";
            }

            return $isPast
                ? "Vencido hace {$days} {$dayText} y {$hours} {$hourText}"
                : "Faltan {$days} {$dayText} y {$hours} {$hourText}";

        } catch (\Exception $e) {
            Log::error('Error en TimeFormattingService::formatDecimalDays', [
                'decimal_days' => $decimalDays,
                'is_past' => $isPast,
                'error' => $e->getMessage()
            ]);

            return 'Tiempo no disponible';
        }
    }

    /**
     * Formatea días decimales de forma compacta para dashboard
     *
     * @param float $decimalDays Días con decimales
     * @param bool $isPast Si es tiempo pasado
     * @return string Formato "Xd Yh" o "Hace Xd Yh"
     */
    public static function formatDecimalDaysCompact(float $decimalDays, bool $isPast = false): string
    {
        try {
            $days = floor(abs($decimalDays));
            $remainingDecimal = abs($decimalDays) - $days;
            $hours = round($remainingDecimal * 24);

            if ($hours >= 24) {
                $days += 1;
                $hours = 0;
            }

            $prefix = $isPast ? 'Hace ' : '';

            if ($days === 0 && $hours === 0) {
                return $isPast ? 'Ahora' : 'Ahora';
            }

            if ($days === 0) {
                return $prefix . "{$hours}h";
            }

            if ($hours === 0) {
                return $prefix . "{$days}d";
            }

            return $prefix . "{$days}d {$hours}h";

        } catch (\Exception $e) {
            return 'N/A';
        }
    }

    /**
     * Obtiene el color de estado según tiempo restante
     *
     * @param Carbon $endDate
     * @return string 'success', 'warning', 'danger'
     */
    public static function getTimeStatusColor(Carbon $endDate): string
    {
        try {
            if ($endDate->isPast()) {
                return 'danger';
            }

            $daysLeft = $endDate->diffInDays(now());

            return match(true) {
                $daysLeft > 30 => 'success',
                $daysLeft > 7 => 'warning',
                default => 'danger'
            };

        } catch (\Exception $e) {
            return 'gray';
        }
    }
}