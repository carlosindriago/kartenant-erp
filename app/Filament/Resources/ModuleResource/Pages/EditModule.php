<?php

namespace App\Filament\Resources\ModuleResource\Pages;

use App\Filament\Resources\ModuleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditModule extends EditRecord
{
    protected static string $resource = ModuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make()
                ->before(function (Actions\DeleteAction $action) {
                    $installationCount = $this->record->activeTenants()->count();
                    if ($installationCount > 0) {
                        $action->cancel();
                        $action->failureNotificationTitle("No se puede eliminar el módulo '{$this->record->name}'");
                        $action->failureNotificationDescription("Tiene {$installationCount} instalaciones activas.");
                    }
                }),
        ];
    }

    protected function afterSave(): void
    {
        // Log module update
        \Log::info('Module updated', [
            'module_id' => $this->record->id,
            'module_name' => $this->record->name,
            'updated_by' => auth()->id(),
        ]);
    }
}
