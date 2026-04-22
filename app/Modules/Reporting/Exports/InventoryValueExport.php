<?php

namespace App\Modules\Reporting\Exports;

use App\Modules\Reporting\Services\InventoryReportService;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Illuminate\Support\Collection;

class InventoryValueExport implements
    FromCollection,
    WithHeadings,
    WithStyles,
    WithTitle,
    ShouldAutoSize
{
    protected InventoryReportService $service;
    protected array $summary;

    public function __construct()
    {
        $this->service = app(InventoryReportService::class);
        $this->summary = $this->service->calculateTotalInventoryValue();
    }

    /**
     * Get collection of data to export
     */
    public function collection(): Collection
    {
        $byCategory = $this->service->getInventoryValueByCategory();

        $rows = collect();

        // Add summary row
        $rows->push([
            'RESUMEN GENERAL',
            '',
            '',
            '',
            '',
            '',
        ]);

        $rows->push([
            'Valor Total del Inventario',
            '$' . number_format($this->summary['total_value'], 2),
            'Costo Total',
            '$' . number_format($this->summary['total_cost'], 2),
            'Ganancia Potencial',
            '$' . number_format($this->summary['potential_profit'], 2),
        ]);

        $rows->push([
            'Total Unidades',
            number_format($this->summary['total_units']),
            'Total Productos',
            number_format($this->summary['product_count']),
            'Margen Promedio',
            number_format($this->summary['profit_margin'], 2) . '%',
        ]);

        $rows->push([
            'Productos Activos',
            number_format($this->summary['active_products']),
            'Productos Inactivos',
            number_format($this->summary['inactive_products']),
            'Tendencia 7 días',
            number_format($this->summary['trend_7_days'], 2) . '%',
        ]);

        // Empty row
        $rows->push(['', '', '', '', '', '']);

        // Category breakdown header
        $rows->push([
            'DESGLOSE POR CATEGORÍA',
            '',
            '',
            '',
            '',
            '',
        ]);

        // Empty row
        $rows->push(['', '', '', '', '', '']);

        // Category data
        foreach ($byCategory as $category) {
            $rows->push([
                $category->category_name,
                number_format($category->product_count),
                number_format($category->total_units),
                '$' . number_format($category->total_value, 2),
                '$' . number_format($category->total_cost, 2),
                number_format($category->profit_margin, 2) . '%',
            ]);
        }

        // Footer
        $rows->push(['', '', '', '', '', '']);
        $rows->push([
            'Reporte generado el: ' . now()->format('d/m/Y H:i'),
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
            'Categoría',
            'Productos',
            'Unidades',
            'Valor Total',
            'Costo Total',
            'Margen %',
        ];
    }

    /**
     * Apply styles to the worksheet
     */
    public function styles(Worksheet $sheet): array
    {
        return [
            // Style for header row
            7 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                    'size' => 12,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4F46E5'], // Indigo
                ],
                'alignment' => [
                    'horizontal' => 'center',
                ],
            ],

            // Style for summary section
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

            // Style for category breakdown header
            6 => [
                'font' => [
                    'bold' => true,
                    'size' => 12,
                    'color' => ['rgb' => '1F2937'],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'F3F4F6'],
                ],
            ],
        ];
    }

    /**
     * Get title for the sheet
     */
    public function title(): string
    {
        return 'Valor de Inventario';
    }
}
