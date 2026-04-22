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

class LandingPageController extends Controller
{
    /**
     * Muestra la página de aterrizaje principal.
     */
    public function show()
    {
        return view('welcome');
    }

    public function showTenantLoginForm()
    {
        return view('find-tenant');
    }

    public function redirectToTenantLogin(Request $request)
    {
        $request->validate(['domain' => 'required|string|alpha_dash']);

        $domain = $request->input('domain');
        
        // Construimos la URL completa del panel de login del tenant
        $url = "https://{$domain}.kartenant.test/app/login";

        return redirect()->to($url);
    }
}