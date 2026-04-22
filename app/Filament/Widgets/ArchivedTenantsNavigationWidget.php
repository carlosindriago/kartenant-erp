<?php

namespace App\Filament\Widgets;

use App\Models\Tenant;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Cache;

class ArchivedTenantsNavigationWidget extends Widget
{
    protected static string $view = 'filament.widgets.archived-tenants-navigation-widget';

    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    public function getViewData(): array
    {
        try {
            $archivedCount = Cache::remember('archived_tenants_count', 300, function () {
                return Tenant::onlyTrashed()
                    ->where('status', Tenant::STATUS_ARCHIVED)
                    ->count();
            });

            $recentlyArchived = Cache::remember('recently_archived_count', 300, function () {
                return Tenant::onlyTrashed()
                    ->where('status', Tenant::STATUS_ARCHIVED)
                    ->where('deleted_at', '>=', now()->subDays(7))
                    ->count();
            });

            $archiveWithIssues = Cache::remember('archived_with_issues_count', 600, function () {
                return Tenant::onlyTrashed()
                    ->where('status', Tenant::STATUS_ARCHIVED)
                    ->whereDoesntHave('backupLogs')
                    ->count();
            });

            return [
                'archived_count' => $archivedCount,
                'recently_archived' => $recentlyArchived,
                'archive_with_issues' => $archiveWithIssues,
                'navigation_url' => \App\Filament\Resources\ArchivedTenantResource::getUrl('index'),
            ];
        } catch (\Exception $e) {
            return [
                'archived_count' => 0,
                'recently_archived' => 0,
                'archive_with_issues' => 0,
                'navigation_url' => '#',
                'error' => $e->getMessage(),
            ];
        }
    }
}