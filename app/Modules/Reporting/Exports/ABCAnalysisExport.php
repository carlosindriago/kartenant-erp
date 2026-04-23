<?php

namespace App\Modules\Reporting\Exports;

use App\Modules\Reporting\Services\ABCAnalysisService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ABCAnalysisExport implements FromCollection, ShouldAutoSize, WithHeadings, WithStyles, WithTitle
{
    protected ABCAnalysisService $service;

    protected ?int $days;

    public function __construct(?int $days = 90)
    {
        $this->service = app(ABCAnalysisService::class);
        $this->days = $days;
    }

    /**
     * Get collection of data to export
     */
    public function collection(): Collection
    {
        $classified = $this->service->classifyProducts($this->days);
        $distribution = $this->service->getABCDistribution($this->days);

        $rows = collect();

        // Add summary rows
        $rows->push([
            'ANÁLISIS ABC - RESUMEN',
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
            $this->days ? "Últimos {$this->days} días" : 'Todo el tiempo',
            '',
            'Total Productos',
            $distribution['total_products'],
            '',
            'Ingresos Totales',
            '$'.number_format($distribution['total_revenue'], 2),
        ]);

        // Empty row
        $rows->push(['', '', '', '', '', '', '', '']);

        // Distribution summary
        $rows->push([
            'DISTRIBUCIÓN ABC',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
        ]);

        $rows->push([
            'Clase A',
            $distribution['class_a']['count'].' productos',
            number_format($distribution['class_a']['percentage'], 1).'% de productos',
            '$'.number_format($distribution['class_a']['total_revenue'], 2),
            number_format($distribution['class_a']['revenue_percentage'], 1).'% de ingresos',
            '',
            '',
            '',
        ]);

        $rows->push([
            'Clase B',
            $distribution['class_b']['count'].' productos',
            number_format($distribution['class_b']['percentage'], 1).'% de productos',
            '$'.number_format($distribution['class_b']['total_revenue'], 2),
            number_format($distribution['class_b']['revenue_percentage'], 1).'% de ingresos',
            '',
            '',
            '',
        ]);

        $rows->push([
            'Clase C',
            $distribution['class_c']['count'].' productos',
            number_format($distribution['class_c']['percentage'], 1).'% de productos',
            '$'.number_format($distribution['class_c']['total_revenue'], 2),
            number_format($distribution['class_c']['revenue_percentage'], 1).'% de ingresos',
            '',
            '',
            '',
        ]);

        // Empty rows
        $rows->push(['', '', '', '', '', '', '', '']);
        $rows->push(['', '', '', '', '', '', '', '']);

        // Detailed product listing header
        $rows->push([
            'PRODUCTOS POR CLASE',
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

        // Product data
        foreach ($classified as $product) {
            $rows->push([
                $product->abc_class,
                $product->name,
                $product->sku,
                $product->category_name ?? 'Sin categoría',
                number_format($product->total_sold),
                '$'.number_format($product->total_revenue, 2),
                number_format($product->revenue_percentage, 2).'%',
                number_format($product->cumulative_percentage, 2).'%',
            ]);
        }

        // Footer
        $rows->push(['', '', '', '', '', '', '', '']);
        $rows->push([
            'Reporte generado el: '.now()->format('d/m/Y H:i'),
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
            'Clase',
            'Producto',
            'SKU',
            'Categoría',
            'Unidades Vendidas',
            'Ingresos',
            '% Ingresos',
            '% Acumulado',
        ];
    }

    /**
     * Apply styles to the worksheet
     */
    public function styles(Worksheet $sheet): array
    {
        return [
            // Header row
            11 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                    'size' => 11,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '6366F1'], // Indigo
                ],
                'alignment' => [
                    'horizontal' => 'center',
                ],
            ],

            // Summary sections
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

            4 => [
                'font' => [
                    'bold' => true,
                    'size' => 12,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'F3F4F6'],
                ],
            ],

            9 => [
                'font' => [
                    'bold' => true,
                    'size' => 12,
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
        return 'Análisis ABC';
    }
}
