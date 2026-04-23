<?php

/**
 * Kartenant - Ferretero Ágil
 *
 * Este archivo es parte de Kartenant.
 *
 * @copyright Copyright (c) 2025-2026 Kartenant
 * @license   GNU AGPLv3 <https://www.gnu.org/licenses/agpl-3.0.txt>
 */

namespace App\Filament\Resources\BugReportResource\Pages;

use App\Filament\Resources\BugReportResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBugReport extends CreateRecord
{
    protected static string $resource = BugReportResource::class;
}
