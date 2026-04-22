@extends('tenant.layouts.app')

@section('title', 'Vista Previa de la Tienda')

@section('content')
<div class="min-h-screen" style="{{ $settings->css_variables }}">
    <style>
        body {
            font-family: '{{ $settings->effective_primary_font }}', -apple-system, BlinkMacSystemFont, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f8fafc;
        }

        .preview-container {
            background: {{ $settings->show_background_image && $settings->background_image_path ?
                "url('" . $settings->getBackgroundPublicUrl() . "')" : '#ffffff' }};
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            min-height: 100vh;
            position: relative;
        }

        .preview-overlay {
            background: rgba(255, 255, 255, 0.92);
            min-height: 100vh;
        }

        .brand-header {
            background: linear-gradient(135deg, {{ $settings->effective_brand_color }} 0%, {{ \App\Models\StoreSetting::adjustBrightness($settings->effective_brand_color, -20) }} 100%);
            color: white;
            padding: 3rem 2rem;
            text-align: center;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        .brand-logo {
            width: 120px;
            height: 120px;
            margin: 0 auto 2rem;
            background: white;
            border-radius: 50%;
            padding: 1rem;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .brand-logo img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        .brand-name {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .brand-slogan {
            font-size: 1.25rem;
            opacity: 0.9;
            margin-bottom: 2rem;
        }

        .welcome-message {
            font-size: 1.125rem;
            line-height: 1.6;
            max-width: 600px;
            margin: 0 auto;
            opacity: 0.95;
        }

        .features-section {
            padding: 4rem 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 3rem;
        }

        .feature-card {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
        }

        .feature-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        .feature-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, {{ $settings->effective_brand_color }} 0%, {{ \App\Models\StoreSetting::adjustBrightness($settings->effective_brand_color, -20) }} 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.5rem;
            color: white;
        }

        .feature-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 0.75rem;
        }

        .feature-description {
            color: #6b7280;
            line-height: 1.6;
        }

        .cta-section {
            background: linear-gradient(135deg, {{ $settings->effective_brand_color }} 0%, {{ \App\Models\StoreSetting::adjustBrightness($settings->effective_brand_color, -20) }} 100%);
            color: white;
            padding: 4rem 2rem;
            text-align: center;
            margin-top: 2rem;
        }

        .cta-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .cta-description {
            font-size: 1.125rem;
            opacity: 0.9;
            margin-bottom: 2rem;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        .cta-button {
            background: white;
            color: {{ $settings->effective_brand_color }};
            padding: 1rem 2rem;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
        }

        .cta-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.2);
            text-decoration: none;
            color: {{ $settings->effective_brand_color }};
        }

        .social-links {
            display: flex;
            justify-content: center;
            space-x: 1rem;
            margin-top: 2rem;
        }

        .social-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            color: white;
            margin: 0 0.5rem;
            text-decoration: none;
            transition: background-color 0.2s ease-in-out;
        }

        .social-link:hover {
            background: rgba(255, 255, 255, 0.2);
            text-decoration: none;
            color: white;
        }

        .preview-header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid #e5e7eb;
            padding: 1rem 2rem;
            z-index: 50;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .preview-badge {
            background: #f59e0b;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 600;
        }

        .preview-actions {
            display: flex;
            gap: 1rem;
        }

        .preview-button {
            background: white;
            border: 1px solid #d1d5db;
            color: #374151;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-size: 0.875rem;
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            transition: all 0.2s ease-in-out;
        }

        .preview-button:hover {
            background: #f9fafb;
            border-color: #9ca3af;
            text-decoration: none;
            color: #374151;
        }

        .preview-content {
            margin-top: 80px;
        }

        @media (max-width: 768px) {
            .brand-name {
                font-size: 2rem;
            }

            .brand-slogan {
                font-size: 1.125rem;
            }

            .features-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }

            .preview-header {
                padding: 0.75rem 1rem;
            }

            .preview-actions {
                flex-direction: column;
                gap: 0.5rem;
            }

            .preview-content {
                margin-top: 120px;
            }
        }
    </style>

    <!-- Preview Header -->
    <div class="preview-header">
        <div class="preview-badge">
            🎨 Vista Previa de la Tienda
        </div>
        <div class="preview-actions">
            <a href="{{ route('tenant.settings.index') }}" class="preview-button">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12"></path>
                </svg>
                Volver a Configuración
            </a>
            <button onclick="window.print()" class="preview-button">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                </svg>
                Imprimir
            </button>
        </div>
    </div>

    <!-- Preview Content -->
    <div class="preview-content">
        <div class="preview-container">
            @if($settings->show_background_image && $settings->background_image_path)
            <div class="preview-overlay">
                @endif

                <!-- Brand Header -->
                <div class="brand-header">
                    @if($settings->hasLogo())
                    <div class="brand-logo">
                        <img src="{{ $settings->getLogoPublicUrl() }}" alt="{{ $settings->effective_store_name }}">
                    </div>
                    @endif

                    <h1 class="brand-name">{{ $settings->effective_store_name }}</h1>

                    @if($settings->effective_store_slogan)
                    <p class="brand-slogan">{{ $settings->effective_store_slogan }}</p>
                    @endif

                    <div class="welcome-message">
                        <p>{{ $settings->effective_welcome_message }}</p>
                    </div>
                </div>

                <!-- Features Section -->
                <div class="features-section">
                    <h2 style="font-size: 2rem; font-weight: 700; text-align: center; color: #1f2937; margin-bottom: 1rem;">
                        Características Principales
                    </h2>
                    <p style="text-align: center; color: #6b7280; font-size: 1.125rem; max-width: 600px; margin: 0 auto;">
                        Todo lo que necesitas para gestionar tu ferretería de manera eficiente
                    </p>

                    <div class="features-grid">
                        <!-- Inventory Feature -->
                        <div class="feature-card">
                            <div class="feature-icon">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                </svg>
                            </div>
                            <h3 class="feature-title">Control de Inventario</h3>
                            <p class="feature-description">
                                Gestiona tu stock en tiempo real, recibe alertas de productos bajos y mantén un control preciso de tus existencias.
                            </p>
                        </div>

                        <!-- Sales Feature -->
                        <div class="feature-card">
                            <div class="feature-icon">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <h3 class="feature-title">Punto de Venta</h3>
                            <p class="feature-description">
                                Procesa ventas rápidamente, gestiona múltiples formas de pago y genera tickets automáticamente para tus clientes.
                            </p>
                        </div>

                        <!-- Customers Feature -->
                        <div class="feature-card">
                            <div class="feature-icon">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                </svg>
                            </div>
                            <h3 class="feature-title">Gestión de Clientes</h3>
                            <p class="feature-description">
                                Mantén un registro completo de tus clientes, su historial de compras y preferencias para ofrecer un mejor servicio.
                            </p>
                        </div>

                        <!-- Reports Feature -->
                        <div class="feature-card">
                            <div class="feature-icon">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                </svg>
                            </div>
                            <h3 class="feature-title">Reportes y Análisis</h3>
                            <p class="feature-description">
                                Genera reportes detallados de ventas, inventario y finanzas para tomar decisiones informadas sobre tu negocio.
                            </p>
                        </div>

                        <!-- Security Feature -->
                        <div class="feature-card">
                            <div class="feature-icon">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                </svg>
                            </div>
                            <h3 class="feature-title">Seguridad Total</h3>
                            <p class="feature-description">
                                Protección de datos con cifrado avanzado, respaldos automáticos y control de acceso para tu tranquilidad.
                            </p>
                        </div>

                        <!-- Support Feature -->
                        <div class="feature-card">
                            <div class="feature-icon">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                </svg>
                            </div>
                            <h3 class="feature-title">Soporte Técnico</h3>
                            <p class="feature-description">
                                Asistencia técnica especializada disponible para ayudarte a sacar el máximo provecho del sistema.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Call to Action Section -->
                <div class="cta-section">
                    <h2 class="cta-title">¿Listo para transformar tu ferretería?</h2>
                    <p class="cta-description">
                        Únete a cientos de ferreterías que ya están optimizando sus operaciones con nuestro sistema.
                    </p>

                    @if($settings->whatsapp_number)
                    <a href="{{ $settings->whatsapp_url }}" target="_blank" class="cta-button">
                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"></path>
                            <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"></path>
                        </svg>
                        Contactar por WhatsApp
                    </a>
                    @endif

                    @if($settings->hasSocialMedia())
                    <div class="social-links">
                        @if($settings->facebook_url)
                        <a href="{{ $settings->facebook_url }}" target="_blank" class="social-link">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                            </svg>
                        </a>
                        @endif

                        @if($settings->instagram_url)
                        <a href="{{ $settings->instagram_url }}" target="_blank" class="social-link">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zM5.838 12a6.162 6.162 0 1112.324 0 6.162 6.162 0 01-12.324 0zM12 16a4 4 0 110-8 4 4 0 010 8zm4.965-10.405a1.44 1.44 0 112.881.001 1.44 1.44 0 01-2.881-.001z"/>
                            </svg>
                        </a>
                        @endif

                        @if($settings->contact_email)
                        <a href="mailto:{{ $settings->contact_email }}" class="social-link">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                        </a>
                        @endif
                    </div>
                    @endif
                </div>

                @if($settings->show_background_image && $settings->background_image_path)
            </div>
        </div>
        @endif
    </div>
</div>
@endsection

@php
    // Helper function to adjust brightness (moved from StoreSetting since we need it here)
    function adjustBrightness($hex, $percent) {
        $hex = str_replace('#', '', $hex);
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        $r = max(0, min(255, $r + ($r * $percent / 100)));
        $g = max(0, min(255, $g + ($g * $percent / 100)));
        $b = max(0, min(255, $b + ($b * $percent / 100)));
        return '#' . str_pad(dechex($r), 2, '0', STR_PAD_LEFT) .
               str_pad(dechex($g), 2, '0', STR_PAD_LEFT) .
               str_pad(dechex($b), 2, '0', STR_PAD_LEFT);
    }
@endphp