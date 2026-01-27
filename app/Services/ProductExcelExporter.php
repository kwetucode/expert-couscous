<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use App\Models\Product;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductExcelExporter
{
    private const HEADERS = [
        'RÉFÉRENCE', 'NOM', 'CATÉGORIE', 'PRIX DE VENTE',
        'PRIX DE COÛT', 'MARGE %', 'STOCK TOTAL', 'STATUT', 'DATE DE CRÉATION'
    ];

    private const COLUMN_WIDTHS = [
        'A' => 15, // Référence
        'B' => 30, // Nom
        'C' => 20, // Catégorie
        'F' => 13, // Marge %
        'G' => 12, // Stock
        'H' => 12, // Statut
    ];

    private const COLORS = [
        'margin' => [
            'low' => ['bg' => 'FEE2E2', 'text' => '991B1B'],
            'medium' => ['bg' => 'FEF3C7', 'text' => '92400E'],
            'high' => ['bg' => 'D1FAE5', 'text' => '065F46'],
        ],
        'stock' => [
            'empty' => ['bg' => 'FEE2E2', 'text' => '991B1B'],
            'low' => ['bg' => 'FED7AA', 'text' => 'C2410C'],
            'normal' => ['bg' => 'D1FAE5', 'text' => '065F46'],
        ],
        'status' => [
            'active' => ['bg' => 'D1FAE5', 'text' => '065F46'],
            'inactive' => ['bg' => 'F3F4F6', 'text' => '6B7280'],
        ],
    ];

    public function export(Collection $products): StreamedResponse
    {
        $spreadsheet = $this->createSpreadsheet($products);

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, 'produits_' . date('Y-m-d_His') . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function createSpreadsheet(Collection $products): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $this->setDocumentProperties($spreadsheet);
        $sheet->setTitle('Liste des Produits');

        $this->addHeaders($sheet);
        $lastRow = $this->addDataRows($sheet, $products);
        $this->applyFinalFormatting($sheet, $lastRow);

        return $spreadsheet;
    }

    private function setDocumentProperties(Spreadsheet $spreadsheet): void
    {
        $spreadsheet->getProperties()
            ->setCreator("STK System")
            ->setTitle("Liste des Produits")
            ->setSubject("Export des produits")
            ->setDescription("Export Excel de la liste des produits");
    }

    private function addHeaders(Worksheet $sheet): void
    {
        $sheet->fromArray(self::HEADERS, null, 'A1');
        $sheet->getRowDimension(1)->setRowHeight(25);

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 13, 'name' => 'Arial'],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F46E5']],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_MEDIUM,
                    'color' => ['rgb' => '312E81']
                ]
            ]
        ];

        $sheet->getStyle('A1:I1')->applyFromArray($headerStyle);
    }

    private function addDataRows(Worksheet $sheet, Collection $products): int
    {
        $row = 2;

        foreach ($products as $product) {
            $this->addProductRow($sheet, $product, $row);
            $row++;
        }

        return $row - 1;
    }

    private function addProductRow(Worksheet $sheet, Product $product, int $row): void
    {
        $totalStock = $product->variants->sum('stock_quantity');

        $sheet->fromArray([
            $product->reference,
            $product->name,
            $product->category->name ?? 'N/A',
            format_currency($product->price),
            $product->cost_price ? format_currency($product->cost_price) : 'N/A',
            $product->getProfitMarginFormatted(),
            $totalStock,
            $product->status === 'active' ? 'Actif' : 'Inactif',
            $product->created_at->format('d/m/Y H:i')
        ], null, 'A' . $row);

        $this->applyRowStyles($sheet, $product, $row, $totalStock);
    }

    private function applyRowStyles(Worksheet $sheet, Product $product, int $row, int $totalStock): void
    {
        // Base data style
        $dataStyle = [
            'font' => ['size' => 11, 'name' => 'Arial'],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => false]
        ];
        $sheet->getStyle("A{$row}:I{$row}")->applyFromArray($dataStyle);

        // Alternating row colors
        if ($row % 2 == 0) {
            $sheet->getStyle("A{$row}:I{$row}")->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setRGB('F9FAFB');
        }

        // Center align numeric columns
        $sheet->getStyle("F{$row}:H{$row}")->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $this->applyMarginColors($sheet, $product, $row);
        $this->applyStatusColors($sheet, $product, $row);
        $this->applyStockColors($sheet, $product, $row, $totalStock);
    }

    private function applyMarginColors(Worksheet $sheet, Product $product, int $row): void
    {
        $margin = $product->getProfitMargin();

        if ($margin === null) {
            return;
        }

        $colors = $this->getMarginColors($margin);

        $sheet->getStyle("F{$row}")->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB($colors['bg']);
        $sheet->getStyle("F{$row}")->getFont()->getColor()->setRGB($colors['text']);
        $sheet->getStyle("F{$row}")->getFont()->setBold(true);
    }

    private function applyStatusColors(Worksheet $sheet, Product $product, int $row): void
    {
        $colors = self::COLORS['status'][$product->status === 'active' ? 'active' : 'inactive'];

        $sheet->getStyle("H{$row}")->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB($colors['bg']);
        $sheet->getStyle("H{$row}")->getFont()->getColor()->setRGB($colors['text']);
        $sheet->getStyle("H{$row}")->getFont()->setBold(true);
    }

    private function applyStockColors(Worksheet $sheet, Product $product, int $row, int $totalStock): void
    {
        $colors = $this->getStockColors($totalStock, $product->stock_alert_threshold);

        $sheet->getStyle("G{$row}")->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB($colors['bg']);
        $sheet->getStyle("G{$row}")->getFont()->getColor()->setRGB($colors['text']);
        $sheet->getStyle("G{$row}")->getFont()->setBold(true);
    }

    private function getMarginColors(float $margin): array
    {
        if ($margin < 10) {
            return self::COLORS['margin']['low'];
        } elseif ($margin < 30) {
            return self::COLORS['margin']['medium'];
        }

        return self::COLORS['margin']['high'];
    }

    private function getStockColors(int $totalStock, int $threshold): array
    {
        if ($totalStock == 0) {
            return self::COLORS['stock']['empty'];
        } elseif ($totalStock <= $threshold) {
            return self::COLORS['stock']['low'];
        }

        return self::COLORS['stock']['normal'];
    }

    private function applyFinalFormatting(Worksheet $sheet, int $lastRow): void
    {
        // Auto-size and set minimum widths
        foreach (range('A', 'I') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        foreach (self::COLUMN_WIDTHS as $col => $width) {
            $sheet->getColumnDimension($col)->setWidth($width);
        }

        // Apply borders
        $sheet->getStyle("A1:I{$lastRow}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'D1D5DB']
                ],
                'outline' => [
                    'borderStyle' => Border::BORDER_MEDIUM,
                    'color' => ['rgb' => '9CA3AF']
                ]
            ]
        ]);

        // Freeze first row
        $sheet->freezePane('A2');
    }
}
