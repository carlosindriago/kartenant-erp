<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Caja - {{ $report['register_info']['register_number'] }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
            color: #333;
            line-height: 1.4;
        }
        
        .container {
            padding: 20px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 3px solid #6366f1;
            padding-bottom: 15px;
        }
        
        .header h1 {
            color: #6366f1;
            font-size: 22px;
            margin-bottom: 5px;
        }
        
        .header .subtitle {
            color: #666;
            font-size: 12px;
        }
        
        .section {
            margin-bottom: 20px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 12px;
            background-color: #f9fafb;
        }
        
        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: #374151;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 2px solid #d1d5db;
        }
        
        .info-grid {
            display: table;
            width: 100%;
            margin-bottom: 10px;
        }
        
        .info-row {
            display: table-row;
        }
        
        .info-label {
            display: table-cell;
            font-weight: bold;
            padding: 5px 10px 5px 0;
            width: 40%;
            color: #6b7280;
        }
        
        .info-value {
            display: table-cell;
            padding: 5px 0;
            color: #111827;
        }
        
        .highlighted {
            background-color: #10b981;
            color: white;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            margin: 15px 0;
        }
        
        .highlighted .label {
            font-size: 12px;
            margin-bottom: 5px;
            opacity: 0.9;
        }
        
        .highlighted .value {
            font-size: 24px;
            font-weight: bold;
        }
        
        .stats-grid {
            display: table;
            width: 100%;
            margin-top: 10px;
        }
        
        .stats-row {
            display: table-row;
        }
        
        .stats-cell {
            display: table-cell;
            width: 25%;
            padding: 10px;
            text-align: center;
            border: 1px solid #e5e7eb;
            background-color: white;
        }
        
        .stats-cell .label {
            font-size: 9px;
            color: #6b7280;
            margin-bottom: 5px;
        }
        
        .stats-cell .value {
            font-size: 16px;
            font-weight: bold;
            color: #111827;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: bold;
        }
        
        .status-open {
            background-color: #d1fae5;
            color: #065f46;
        }
        
        .status-closed {
            background-color: #e5e7eb;
            color: #374151;
        }
        
        .difference-box {
            padding: 12px;
            border-radius: 8px;
            text-align: center;
            margin-top: 10px;
        }
        
        .difference-positive {
            background-color: #d1fae5;
            color: #065f46;
            border: 2px solid #10b981;
        }
        
        .difference-negative {
            background-color: #fee2e2;
            color: #991b1b;
            border: 2px solid #ef4444;
        }
        
        .difference-exact {
            background-color: #ecfdf5;
            color: #047857;
            border: 2px solid #10b981;
        }
        
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            font-size: 9px;
            color: #9ca3af;
        }
        
        .notes {
            background-color: #fffbeb;
            border-left: 4px solid #f59e0b;
            padding: 10px;
            margin-top: 10px;
            font-size: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        {{-- Header --}}
        <div class="header">
            <h1>📄 REPORTE DE CAJA</h1>
            <div class="subtitle">Generado el {{ now()->format('d/m/Y H:i:s') }}</div>
        </div>
        
        {{-- Información de la Caja --}}
        <div class="section">
            <div class="section-title">📋 Información de la Caja</div>
            <div class="info-grid">
                <div class="info-row">
                    <div class="info-label">Número de Caja:</div>
                    <div class="info-value"><strong>{{ $report['register_info']['register_number'] }}</strong></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Cajero:</div>
                    <div class="info-value">{{ $report['register_info']['opened_by'] }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Estado:</div>
                    <div class="info-value">
                        @if($report['register_info']['status'] === 'open')
                            <span class="status-badge status-open">🟢 ABIERTA</span>
                        @else
                            <span class="status-badge status-closed">🔒 CERRADA</span>
                        @endif
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-label">Apertura:</div>
                    <div class="info-value">{{ $report['register_info']['opened_at']->format('d/m/Y H:i') }}</div>
                </div>
                @if($report['register_info']['closed_at'])
                <div class="info-row">
                    <div class="info-label">Cierre:</div>
                    <div class="info-value">{{ $report['register_info']['closed_at']->format('d/m/Y H:i') }}</div>
                </div>
                @endif
            </div>
        </div>
        
        {{-- Total Esperado en Efectivo - Destacado --}}
        <div class="highlighted">
            <div class="label">💰 TOTAL ESPERADO EN EFECTIVO</div>
            <div class="value">${{ number_format($report['cash_flow']['expected_cash_total'], 0) }}</div>
            <div class="label" style="margin-top: 5px; font-size: 10px;">(Inicial + Ventas Completadas)</div>
        </div>
        
        {{-- Flujo de Efectivo --}}
        <div class="section">
            <div class="section-title">💵 Flujo de Efectivo</div>
            <div class="stats-grid">
                <div class="stats-row">
                    <div class="stats-cell">
                        <div class="label">Fondo Inicial</div>
                        <div class="value">${{ number_format($report['cash_flow']['initial_amount'], 0) }}</div>
                    </div>
                    <div class="stats-cell">
                        <div class="label">+ Ventas Efectivo</div>
                        <div class="value" style="color: #10b981;">${{ number_format($report['cash_flow']['cash_sales'], 0) }}</div>
                    </div>
                    @if($report['register_info']['status'] === 'closed')
                    <div class="stats-cell">
                        <div class="label">= Total Esperado</div>
                        <div class="value">${{ number_format($report['cash_flow']['expected_amount'], 0) }}</div>
                    </div>
                    <div class="stats-cell">
                        <div class="label">💰 Contado Real</div>
                        <div class="value">${{ number_format($report['cash_flow']['actual_amount'], 0) }}</div>
                    </div>
                    @endif
                </div>
            </div>
            
            @if(($report['cash_flow']['cash_returns'] ?? 0) > 0)
            <div style="margin-top: 10px; padding: 8px; background-color: #f3f4f6; border-radius: 4px; font-size: 10px;">
                <strong>ℹ️ Ventas Canceladas (dinero devuelto, no está en caja):</strong> 
                ${{ number_format($report['cash_flow']['cash_returns'], 0) }}
            </div>
            @endif
            
            {{-- Diferencia --}}
            @if($report['register_info']['status'] === 'closed')
                @php
                    $difference = $report['cash_flow']['difference'] ?? 0;
                @endphp
                <div class="difference-box @if($difference > 0) difference-positive @elseif($difference < 0) difference-negative @else difference-exact @endif">
                    <div style="font-weight: bold; margin-bottom: 5px;">
                        @if($difference > 0)
                            ✅ SOBRANTE
                        @elseif($difference < 0)
                            ⚠️ FALTANTE
                        @else
                            🎯 ¡EXACTO!
                        @endif
                    </div>
                    <div style="font-size: 20px; font-weight: bold;">
                        {{ $difference >= 0 ? '+' : '' }}${{ number_format(abs($difference), 0) }}
                    </div>
                </div>
            @endif
        </div>
        
        {{-- Resumen de Ventas --}}
        <div class="section">
            <div class="section-title">📊 Resumen de Ventas</div>
            <div class="stats-grid">
                <div class="stats-row">
                    <div class="stats-cell">
                        <div class="label">Total de Ventas</div>
                        <div class="value">{{ $report['sales_summary']['total_sales'] ?? 0 }}</div>
                    </div>
                    <div class="stats-cell">
                        <div class="label">Monto Total</div>
                        <div class="value">${{ number_format($report['sales_summary']['total_amount'] ?? 0, 0) }}</div>
                    </div>
                    <div class="stats-cell">
                        <div class="label">Ventas Anuladas</div>
                        <div class="value" style="color: #ef4444;">{{ $report['sales_summary']['cancelled_sales'] ?? 0 }}</div>
                    </div>
                </div>
            </div>
        </div>
        
        {{-- Desglose por Método de Pago --}}
        <div class="section">
            <div class="section-title">💳 Desglose por Método de Pago</div>
            <div class="stats-grid">
                <div class="stats-row">
                    <div class="stats-cell">
                        <div class="label">💵 Efectivo</div>
                        <div class="value" style="color: #10b981;">${{ number_format($report['payment_methods']['cash'] ?? 0, 0) }}</div>
                    </div>
                    <div class="stats-cell">
                        <div class="label">💳 Tarjeta</div>
                        <div class="value" style="color: #3b82f6;">${{ number_format($report['payment_methods']['card'] ?? 0, 0) }}</div>
                    </div>
                    <div class="stats-cell">
                        <div class="label">🏦 Transferencia</div>
                        <div class="value" style="color: #8b5cf6;">${{ number_format($report['payment_methods']['transfer'] ?? 0, 0) }}</div>
                    </div>
                </div>
            </div>
        </div>
        
        {{-- Observaciones --}}
        @if(!empty($report['notes']['opening']) || !empty($report['notes']['closing']))
        <div class="section">
            <div class="section-title">📝 Observaciones</div>
            @if(!empty($report['notes']['opening']))
            <div class="notes">
                <strong>Apertura:</strong><br>
                {{ $report['notes']['opening'] }}
            </div>
            @endif
            @if(!empty($report['notes']['closing']))
            <div class="notes" style="background-color: #fee2e2; border-left-color: #ef4444;">
                <strong>Cierre:</strong><br>
                {{ $report['notes']['closing'] }}
            </div>
            @endif
        </div>
        @endif
        
        {{-- Footer --}}
        <div class="footer">
            <div>Este documento fue generado automáticamente por el sistema de Punto de Venta</div>
            <div>{{ config('app.name') }} - {{ now()->format('d/m/Y H:i:s') }}</div>
        </div>
    </div>
</body>
</html>
