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

use App\Models\User;
use App\Models\UserStatusChange;
use Illuminate\Support\Facades\DB;

class EmployeeEventService
{
    /**
     * Registrar alta de empleado con verificación
     */
    public function registerEmployeeCreation(User $user, User $createdBy, ?string $notes = null): UserStatusChange
    {
        return DB::transaction(function () use ($user, $createdBy, $notes) {
            $event = UserStatusChange::create([
                'user_id' => $user->id,
                'action' => 'registered',
                'reason' => 'Alta de empleado en el sistema',
                'changed_by' => $createdBy->id,
                'changed_at' => now(),
                'additional_notes' => $notes,
            ]);
            
            // Generar número de documento
            $event->document_number = $event->generateDocumentNumber();
            
            // Generar hash de verificación
            $event->ensureVerificationHash();
            
            $event->save();
            
            // Registrar en activity log
            activity()
                ->causedBy($createdBy)
                ->performedOn($user)
                ->withProperties([
                    'event_id' => $event->id,
                    'document_number' => $event->document_number,
                    'verification_hash' => $event->verification_hash,
                ])
                ->log('Empleado registrado con comprobante verificable');
            
            return $event->fresh(['user', 'changedBy']);
        });
    }
    
    /**
     * Registrar desactivación de empleado con verificación
     */
    public function registerEmployeeDeactivation(
        User $user,
        User $deactivatedBy,
        string $reason,
        ?string $notes = null
    ): UserStatusChange {
        return DB::transaction(function () use ($user, $deactivatedBy, $reason, $notes) {
            // Actualizar usuario
            $user->update([
                'is_active' => false,
                'deactivated_at' => now(),
                'deactivated_by' => $deactivatedBy->id,
                'deactivation_reason' => $reason,
            ]);
            
            // Crear evento de desactivación
            $event = UserStatusChange::create([
                'user_id' => $user->id,
                'action' => 'deactivated',
                'reason' => $reason,
                'changed_by' => $deactivatedBy->id,
                'changed_at' => now(),
                'additional_notes' => $notes,
            ]);
            
            // Generar número de documento
            $event->document_number = $event->generateDocumentNumber();
            
            // Generar hash de verificación
            $event->ensureVerificationHash();
            
            $event->save();
            
            // Registrar en activity log
            activity()
                ->causedBy($deactivatedBy)
                ->performedOn($user)
                ->withProperties([
                    'event_id' => $event->id,
                    'document_number' => $event->document_number,
                    'verification_hash' => $event->verification_hash,
                    'reason' => $reason,
                ])
                ->log('Empleado desactivado con comprobante verificable');
            
            return $event->fresh(['user', 'changedBy']);
        });
    }
    
    /**
     * Registrar reactivación de empleado con verificación
     */
    public function registerEmployeeActivation(
        User $user,
        User $activatedBy,
        string $reason,
        ?string $notes = null
    ): UserStatusChange {
        return DB::transaction(function () use ($user, $activatedBy, $reason, $notes) {
            // Actualizar usuario
            $user->update([
                'is_active' => true,
                'reactivated_at' => now(),
                'reactivated_by' => $activatedBy->id,
                'reactivation_reason' => $reason,
            ]);
            
            // Crear evento de activación
            $event = UserStatusChange::create([
                'user_id' => $user->id,
                'action' => 'activated',
                'reason' => $reason,
                'changed_by' => $activatedBy->id,
                'changed_at' => now(),
                'additional_notes' => $notes,
            ]);
            
            // Generar número de documento
            $event->document_number = $event->generateDocumentNumber();
            
            // Generar hash de verificación
            $event->ensureVerificationHash();
            
            $event->save();
            
            // Registrar en activity log
            activity()
                ->causedBy($activatedBy)
                ->performedOn($user)
                ->withProperties([
                    'event_id' => $event->id,
                    'document_number' => $event->document_number,
                    'verification_hash' => $event->verification_hash,
                    'reason' => $reason,
                ])
                ->log('Empleado reactivado con comprobante verificable');
            
            return $event->fresh(['user', 'changedBy']);
        });
    }
    
    /**
     * Generar y descargar PDF del evento
     */
    public function downloadEventPdf(UserStatusChange $event): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $pdf = $event->generatePdf();
        
        $filename = strtolower(str_replace(' ', '-', $event->event_type)) . '-' . $event->document_number . '.pdf';
        
        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, $filename);
    }
}
