<?php

/**
 * Kartenant - Ferretero Ágil
 *
 * Este archivo es parte de Kartenant.
 *
 * @copyright Copyright (c) 2025-2026 Kartenant
 * @license   GNU AGPLv3 <https://www.gnu.org/licenses/agpl-3.0.txt>
 */

namespace App\Http\Controllers;

use App\Models\Tenant;
use Illuminate\Http\Request;

class TenantLandingController extends Controller
{
    public function show(Request $request)
    {
        $host = $request->getHost();
        $tenant = Tenant::query()->where('domain', $host)->first();

        // Fallback: resolve by subdomain slug (first label)
        if (! $tenant) {
            $parts = explode('.', $host);
            if (count($parts) >= 3) {
                $slug = $parts[0];
                $tenant = Tenant::query()->where('domain', $slug)->first();
            }
        }

        return view('tenant.landing', [
            'tenant' => $tenant,
        ]);
    }
}
