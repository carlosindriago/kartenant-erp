<?php

namespace App\Modules\Reporting\Exports;

use App\Modules\Reporting\Services\ProfitabilityService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProfitabilityExport implements FromCollection, ShouldAutoSize, WithHeadings, WithStyles, WithTitle
{
    protected ProfitabilityService $service;

    protected ?array $dateRange;

    public function __construct(?array $dateRange = null)
    {
        $this->service = app(ProfitabilityService::class);
        $this->dateRange = $dateRange ?? [
            'start' => now()->subDays(30),
            'end' => now(),
        ];
    }

    /**
     * Get collection of data to export
     */
    public function collection(): Collection
    {
        $summary = $this->service->getProfitabilitySummary($this->dateRange);
        $mostProfitable = $this->service->getMostProfitableProducts(20, $this->dateRange);
        $leastProfitable = $this->service->getLeastProfitableProducts(20, $this->dateRange);

        $rows = collect();

        // Summary section
        $rows->push([
            'ANÁLISIS DE RENTABILIDAD - RESUMEN',
            '',
            '',
            '',
            '',
            '',
            '',
        ]);

        $rows->push([
            'Período',
            $this->dateRange['start']->format('d/m/Y').' - '.$this->dateRange['end']->format('d/m/Y'),
            '',
            'Ingresos Totales',
            '$'.number_format($summary['total_revenue'], 2),
            '',
            '',
        ]);

        $rows->push([
            'Ganancia Total',
            '$'.number_format($summary['total_profit'], 2),
            '',
            'Margen General',
            number_format($summary['overall_margin'], 2).'%',
            '',
            '',
        ]);

        $rows->push([
            'Productos Rentables',
            $summary['profitable_products'],
            '',
            'Productos No Rentables',
            $summary['unprofitable_products'],
            '',
            '',
        ]);

        // Empty rows
        $rows->push(['', '', '', '', '', '', '']);
        $rows->push(['', '', '', '', '', '', '']);

        // Most Profitable section
        $rows->push([
            'TOP PRODUCTOS MÁS RENTABLES',
            '',
            '',
            '',
            '',
            '',
            '',
        ]);

        // Empty row
        $rows->push(['', '', '', '', '', '', '']);

        foreach ($mostProfitable as $product) {
            $rows->push([
                $product->name,
                $product->sku,
                $product->category_name ?? 'Sin categoría',
                number_format($product->total_sold),
                '$'.number_format($product->total_revenue, 2),
                '$'.number_format($product->profit, 2),
                number_format($product->profit_margin, 2).'%',
            ]);
        }

        // Empty rows
        $rows->push(['', '', '', '', '', '', '']);
        $rows->push(['', '', '', '', '', '', '']);

        // Least Profitable section
        $rows->push([
            'TOP PRODUCTOS MENOS RENTABLES',
            '',
            '',
            '',
            '',
            '',
            '',
        ]);

        // Empty row
        $rows->push(['', '', '', '', '', '', '']);

        foreach ($leastProfitable as $product) {
            $rows->push([
                $product->name,
                $product->sku,
                $product->category_name ?? 'Sin categoría',
                number_format($product->total_sold),
                '$'.number_format($product->total_revenue, 2),
                '$'.number_format($product->profit, 2),
                number_format($product->profit_margin, 2).'%',
            ]);
        }

        // Footer
        $rows->push(['', '', '', '', '', '', '']);
        $rows->push([
            'Reporte generado el: '.now()->format('d/m/Y H:i'),
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
            'Unidades Vendidas',
            'Ingresos',
            'Ganancia',
            'Margen %',
        ];
    }

    /**
     * Apply styles to the worksheet
     */
    public function styles(Worksheet $sheet): array
    {
        // Calculate row numbers dynamically
        $mostProfitableHeaderRow = 8;
        $leastProfitableHeaderRow = $mostProfitableHeaderRow + 20 + 4; // +20 products, +4 spacing

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

            // Most Profitable header
            7 => [
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

            $mostProfitableHeaderRow => [
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

            // Least Profitable section
            $leastProfitableHeaderRow - 1 => [
                'font' => [
                    'bold' => true,
                    'size' => 12,
                    'color' => ['rgb' => '991B1B'], // Red
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'FEE2E2'], // Light red
                ],
            ],

            $leastProfitableHeaderRow => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                    'size' => 11,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'EF4444'], // Red
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
        return 'Rentabilidad';
    }
}
