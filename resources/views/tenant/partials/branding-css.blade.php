{{-- Dynamic Branding CSS from StoreSettings --}}
@php
    // Use settings passed from view, with fallbacks to prevent errors
    if (!isset($settings) || !$settings) {
        $brandColor = '#2563eb';
        $primaryFont = 'Inter';
        $cssVariables = '--primary-color: #2563eb; --primary-hover: #1e40af;';
    } else {
        $brandColor = $settings->effective_brand_color ?? '#2563eb';
        $primaryFont = $settings->effective_primary_font ?? 'Inter';

        // Generate CSS variables safely
        try {
            $cssVariables = method_exists($settings, 'getCssVariablesAttribute')
                ? $settings->css_variables
                : '--primary-color: ' . $brandColor . '; --primary-hover: ' . $brandColor . ';';
        } catch (Exception $e) {
            $cssVariables = '--primary-color: ' . $brandColor . '; --primary-hover: #1e40af;';
        }
    }

    // Font import URLs
    $fontImports = [
        'Inter' => 'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap',
        'Roboto' => 'https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap',
        'Open Sans' => 'https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;600;700&display=swap',
        'Montserrat' => 'https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap',
        'Poppins' => 'https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap',
    ];
@endphp

{{-- Google Font Import --}}
@if(isset($fontImports[$primaryFont]))
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="{{ $fontImports[$primaryFont] }}" rel="stylesheet">
@endif

<style>
    /* CSS Custom Properties for Dynamic Theming */
    :root {
        {{ $cssVariables }}

        /* Typography */
        --font-family-primary: '{{ $primaryFont }}', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        --font-family-mono: 'JetBrains Mono', 'Fira Code', Consolas, monospace;

        /* Base Colors */
        --color-white: #ffffff;
        --color-black: #000000;
        --color-gray-50: #f9fafb;
        --color-gray-100: #f3f4f6;
        --color-gray-200: #e5e7eb;
        --color-gray-300: #d1d5db;
        --color-gray-400: #9ca3af;
        --color-gray-500: #6b7280;
        --color-gray-600: #4b5563;
        --color-gray-700: #374151;
        --color-gray-800: #1f2937;
        --color-gray-900: #111827;

        /* Semantic Colors */
        --color-success: #10b981;
        --color-success-light: #34d399;
        --color-warning: #f59e0b;
        --color-warning-light: #fbbf24;
        --color-error: #ef4444;
        --color-error-light: #f87171;
        --color-info: #3b82f6;
        --color-info-light: #60a5fa;

        /* Shadows */
        --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        --shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
        --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);

        /* Border Radius */
        --radius-sm: 0.25rem;
        --radius: 0.375rem;
        --radius-md: 0.5rem;
        --radius-lg: 0.75rem;
        --radius-xl: 1rem;

        /* Transitions */
        --transition-fast: 150ms ease-in-out;
        --transition: 200ms ease-in-out;
        --transition-slow: 300ms ease-in-out;
    }

    /* Dark Theme Variables */
    @media (prefers-color-scheme: dark) {
        :root {
            --color-white: #ffffff;
            --color-black: #000000;
            --color-gray-50: #1f2937;
            --color-gray-100: #374151;
            --color-gray-200: #4b5563;
            --color-gray-300: #6b7280;
            --color-gray-400: #9ca3af;
            --color-gray-500: #d1d5db;
            --color-gray-600: #e5e7eb;
            --color-gray-700: #f3f4f6;
            --color-gray-800: #f9fafb;
            --color-gray-900: #ffffff;
        }
    }

    /* Apply Brand Font to Body */
    body {
        font-family: var(--font-family-primary);
    }

    /* Utility Classes with Dynamic Colors */
    .bg-primary {
        background-color: var(--primary-color);
    }

    .bg-primary-hover {
        background-color: var(--primary-hover);
    }

    .text-primary {
        color: var(--primary-color);
    }

    .border-primary {
        border-color: var(--primary-color);
    }

    .btn-primary {
        background-color: var(--primary-color);
        color: var(--color-white);
        font-weight: 600;
        padding: 0.75rem 1.5rem;
        border-radius: var(--radius-md);
        transition: background-color var(--transition);
        border: none;
        cursor: pointer;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 44px; /* WCAG touch target minimum */
    }

    .btn-primary:hover {
        background-color: var(--primary-hover);
    }

    .btn-primary:focus {
        outline: 2px solid var(--primary-color);
        outline-offset: 2px;
    }

    /* Card Components */
    .card {
        background: var(--color-white);
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow);
        border: 1px solid var(--color-gray-200);
        overflow: hidden;
    }

    .card-header {
        padding: 1.5rem;
        border-bottom: 1px solid var(--color-gray-200);
        background: var(--color-gray-50);
    }

    .card-body {
        padding: 1.5rem;
    }

    .card-footer {
        padding: 1rem 1.5rem;
        border-top: 1px solid var(--color-gray-200);
        background: var(--color-gray-50);
    }

    /* Metric Cards */
    .metric-card {
        background: var(--color-white);
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow);
        padding: 1.5rem;
        border-left: 4px solid var(--primary-color);
        transition: transform var(--transition), box-shadow var(--transition);
    }

    .metric-card:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-md);
    }

    .metric-value {
        font-size: 2rem;
        font-weight: 700;
        color: var(--primary-color);
        line-height: 1.2;
    }

    .metric-label {
        font-size: 0.875rem;
        color: var(--color-gray-600);
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.025em;
    }

    .metric-icon {
        font-size: 2.5rem;
        opacity: 0.8;
    }

    /* Navigation Components */
    .nav-link {
        color: var(--color-gray-700);
        text-decoration: none;
        padding: 0.5rem 1rem;
        border-radius: var(--radius);
        transition: all var(--transition);
        font-weight: 500;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        min-height: 44px;
    }

    .nav-link:hover {
        background-color: var(--color-gray-100);
        color: var(--primary-color);
    }

    .nav-link.active {
        background-color: var(--primary-color);
        color: var(--color-white);
    }

    /* Loading States */
    .loading {
        opacity: 0.6;
        pointer-events: none;
    }

    .spinner {
        border: 2px solid var(--color-gray-200);
        border-top: 2px solid var(--primary-color);
        border-radius: 50%;
        width: 1.5rem;
        height: 1.5rem;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    /* Accessibility Focus Styles */
    .focus-visible {
        outline: 2px solid var(--primary-color);
        outline-offset: 2px;
    }

    /* Print Styles */
    @media print {
        .no-print {
            display: none !important;
        }

        .card {
            box-shadow: none;
            border: 1px solid #000;
        }
    }

    /* Mobile Optimizations */
    @media (max-width: 768px) {
        .metric-card {
            padding: 1rem;
        }

        .metric-value {
            font-size: 1.5rem;
        }

        .card-body,
        .card-header,
        .card-footer {
            padding: 1rem;
        }

        .btn-primary {
            padding: 0.875rem 1.25rem;
            font-size: 1rem;
        }
    }

    /* High Contrast Mode Support */
    @media (prefers-contrast: high) {
        .btn-primary {
            border: 2px solid var(--color-black);
        }

        .card {
            border: 2px solid var(--color-gray-700);
        }
    }

    /* Reduced Motion Support */
    @media (prefers-reduced-motion: reduce) {
        *,
        *::before,
        *::after {
            animation-duration: 0.01ms !important;
            animation-iteration-count: 1 !important;
            transition-duration: 0.01ms !important;
        }
    }
</style>