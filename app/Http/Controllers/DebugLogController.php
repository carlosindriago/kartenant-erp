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

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DebugLogController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->all();

        $message = sprintf(
            "[FRONTEND DEBUG] %s | User: %s | URL: %s | Data: %s",
            $data['message'] ?? 'No message',
            auth()->check() ? auth()->user()->email : 'guest',
            $request->url(),
            json_encode($data['context'] ?? [])
        );

        Log::channel('single')->info($message);

        return response()->json(['logged' => true]);
    }
}
