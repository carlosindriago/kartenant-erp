<?php

namespace App\Modules\Reporting\Exports;

use App\Modules\Reporting\Services\TurnoverService;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Illuminate\Support\Collection;

class TurnoverExport implements
    FromCollection,
    WithHeadings,
    WithStyles,
    WithTitle,
    ShouldAutoSize
{
    protected TurnoverService $service;
    protected int $days;

    public function __construct(int $days = 30)
    {
        $this->service = app(TurnoverService::class);
        $this->days = $days;
    }

    /**
     * Get collection of data to export
     */
    public function collection(): Collection
    {
        $summary = $this->service->getTurnoverSummary($this->days);
        $fastMovers = $this->service->getFastMovingProducts($this->days, 30);
        $slowMovers = $this->service->getSlowMovingProducts(90, 30);

        $rows = collect();

        // Summary section
        $rows->push([
            'ANÁLISIS DE ROTACIÓN DE INVENTARIO',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
        ]);

        $rows->push([
            'Período analizado',
            "{$this->days} días",
            '',
            'Productos analizados',
            $summary['total_products_analyzed'],
            '',
            '',
            '',
        ]);

        $rows->push([
            'Tasa de Rotación Promedio',
            number_format($summary['average_turnover_rate'], 2) . 'x',
            '',
            'DSI Promedio',
            number_format($summary['average_dsi'], 1) . ' días',
            '',
            '',
            '',
        ]);

        $rows->push([
            'Alta Rotación',
            $summary['fast_movers'] . ' productos',
            '',
            'Rotación Normal',
            $summary['normal_movers'] . ' productos',
            '',
            '',
            '',
        ]);

        $rows->push([
            'Baja Rotación',
            $summary['slow_movers'] . ' productos',
            '',
            'Estancados (0 ventas)',
            $summary['stagnant'] . ' productos',
            '',
            '',
            '',
        ]);

        // Empty rows
        $rows->push(['', '', '', '', '', '', '', '']);
        $rows->push(['', '', '', '', '', '', '', '']);

        // Fast Movers section
        $rows->push([
            'PRODUCTOS DE ALTA ROTACIÓN (Se venden rápido)',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
        ]);

        // Empty row
        $rows->push(['', '', '', '', '', '', '', '']);

        foreach ($fastMovers as $item) {
            $product = $item['product'];
            $turnoverData = $item['turnover_data'];

            $rows->push([
                $product->name,
                $product->sku,
                $product->category->name ?? 'Sin categoría',
                number_format($product->stock),
                number_format($turnoverData['units_sold']),
                number_format($turnoverData['turnover_rate'], 2) . 'x',
                number_format($turnoverData['days_sales_of_inventory'], 1) . ' días',
                $turnoverData['recommendation'],
            ]);
        }

        // Empty rows
        $rows->push(['', '', '', '', '', '', '', '']);
        $rows->push(['', '', '', '', '', '', '', '']);

        // Slow Movers section
        $rows->push([
            'PRODUCTOS DE BAJA ROTACIÓN (Se venden lento o estancados)',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
        ]);

        // Empty row
        $rows->push(['', '', '', '', '', '', '', '']);

        foreach ($slowMovers as $item) {
            $product = $item['product'];
            $turnoverData = $item['turnover_data'];

            $rows->push([
                $product->name,
                $product->sku,
                $product->category->name ?? 'Sin categoría',
                number_format($product->stock),
                number_format($turnoverData['units_sold']),
                number_format($turnoverData['turnover_rate'], 2) . 'x',
                $turnoverData['days_sales_of_inventory'] > 999
                    ? '+999 días'
                    : number_format($turnoverData['days_sales_of_inventory'], 1) . ' días',
                $turnoverData['recommendation'],
            ]);
        }

        // Footer
        $rows->push(['', '', '', '', '', '', '', '']);
        $rows->push([
            'Reporte generado el: ' . now()->format('d/m/Y H:i'),
            '',
            '',
            '',
            '',
            '',
            '',
            'Kartenant',
        ]);

        return $rows;
    }

    /**
     * Get headings for the export
     */
    public function headings(): array
    {
        return [
            'Producto',
            'SKU',
            'Categoría',
            'Stock Actual',
            'Unidades Vendidas',
            'Tasa de Rotación',
            'DSI (Días)',
            'Recomendación',
        ];
    }

    /**
     * Apply styles to the worksheet
     */
    public function styles(Worksheet $sheet): array
    {
        $fastMoversHeaderRow = 10;
        $slowMoversHeaderRow = $fastMoversHeaderRow + 30 + 4; // +30 products, +4 spacing

        return [
            // Summary section
            1 => [
                'font' => [
                    'bold' => true,
                    'size' => 14,
                    'color' => ['rgb' => '1F2937'],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E5E7EB'],
                ],
            ],

            // Fast Movers section
            8 => [
                'font' => [
                    'bold' => true,
                    'size' => 12,
                    'color' => ['rgb' => '065F46'], // Green
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'D1FAE5'], // Light green
                ],
            ],

            $fastMoversHeaderRow => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                    'size' => 11,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '10B981'], // Green
                ],
                'alignment' => [
                    'horizontal' => 'center',
                ],
            ],

            // Slow Movers section
            $slowMoversHeaderRow - 1 => [
                'font' => [
                    'bold' => true,
                    'size' => 12,
                    'color' => ['rgb' => 'D97706'], // Amber
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'FEF3C7'], // Light amber
                ],
            ],

            $slowMoversHeaderRow => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                    'size' => 11,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'F59E0B'], // Amber
                ],
                'alignment' => [
                    'horizontal' => 'center',
                ],
            ],
        ];
    }

    /**
     * Get title for the sheet
     */
    public function title(): string
    {
        return 'Rotación de Inventario';
    }
}
