<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use App\Models\Sale;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SaleExcelExporter
{
    private const HEADERS = [
        'NUMÉRO', 'DATE', 'CLIENT', 'ARTICLES', 'SOUS-TOTAL',
        'REMISE', 'TAXE', 'TOTAL', 'PAYÉ', 'RESTE', 'PAIEMENT', 'STATUT', 'VENDEUR'
    ];

    private const COLUMN_WIDTHS = [
        'A' => 15, // Numéro
        'B' => 12, // Date
        'C' => 25, // Client
        'D' => 10, // Articles
        'E' => 15, // Sous-total
        'F' => 12, // Remise
        'G' => 12, // Taxe
        'H' => 15, // Total
        'I' => 15, // Payé
        'J' => 15, // Reste
        'K' => 12, // Paiement
        'L' => 12, // Statut
        'M' => 20, // Vendeur
    ];

    private const COLORS = [
        'payment_status' => [
            'paid' => ['bg' => 'D1FAE5', 'text' => '065F46'],
            'partial' => ['bg' => 'FEF3C7', 'text' => '92400E'],
            'pending' => ['bg' => 'FEE2E2', 'text' => '991B1B'],
            'refunded' => ['bg' => 'E0E7FF', 'text' => '3730A3'],
        ],
        'status' => [
            'completed' => ['bg' => 'D1FAE5', 'text' => '065F46'],
            'pending' => ['bg' => 'FEF3C7', 'text' => '92400E'],
            'cancelled' => ['bg' => 'FEE2E2', 'text' => '991B1B'],
        ],
    ];

    private const PAYMENT_LABELS = [
        'pending' => 'En attente',
        'paid' => 'Payé',
        'partial' => 'Partiel',
        'refunded' => 'Remboursé',
    ];

    private const STATUS_LABELS = [
        'pending' => 'En attente',
        'completed' => 'Complétée',
        'cancelled' => 'Annulée',
    ];

    public function export(Collection $sales, ?string $dateFrom = null, ?string $dateTo = null, ?string $periodLabel = null): StreamedResponse
    {
        $spreadsheet = $this->createSpreadsheet($sales, $dateFrom, $dateTo, $periodLabel);

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, 'ventes_' . date('Y-m-d_His') . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Export to a file path (for email attachments)
     */
    public function exportToFile(Collection $sales, ?string $dateFrom, ?string $dateTo, ?string $periodLabel, string $filePath): void
    {
        $spreadsheet = $this->createSpreadsheet($sales, $dateFrom, $dateTo, $periodLabel);
        $writer = new Xlsx($spreadsheet);
        $writer->save($filePath);
    }

    private function createSpreadsheet(Collection $sales, ?string $dateFrom, ?string $dateTo, ?string $periodLabel): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $this->setDocumentProperties($spreadsheet);
        $sheet->setTitle('Liste des Ventes');

        // Add title and period info
        $startRow = $this->addTitleSection($sheet, $dateFrom, $dateTo, $periodLabel);

        $this->addHeaders($sheet, $startRow);
        $lastRow = $this->addDataRows($sheet, $sales, $startRow + 1);
        $this->addSummarySection($sheet, $sales, $lastRow + 2);
        $this->applyFinalFormatting($sheet, $lastRow, $startRow);

        return $spreadsheet;
    }

    private function setDocumentProperties(Spreadsheet $spreadsheet): void
    {
        $spreadsheet->getProperties()
            ->setCreator("STK System")
            ->setTitle("Rapport des Ventes")
            ->setSubject("Export des ventes")
            ->setDescription("Export Excel du rapport des ventes");
    }

    private function addTitleSection(Worksheet $sheet, ?string $dateFrom, ?string $dateTo, ?string $periodLabel): int
    {
        $sheet->setCellValue('A1', 'RAPPORT DES VENTES');
        $sheet->mergeCells('A1:M1');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 18, 'color' => ['rgb' => '1F2937']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(30);

        $periodText = 'Période : ';
        if ($periodLabel) {
            $periodText .= $periodLabel;
            if ($dateFrom && $dateTo) {
                $periodText .= ' (' . date('d/m/Y', strtotime($dateFrom)) . ' au ' . date('d/m/Y', strtotime($dateTo)) . ')';
            }
        } elseif ($dateFrom && $dateTo) {
            $periodText .= date('d/m/Y', strtotime($dateFrom)) . ' au ' . date('d/m/Y', strtotime($dateTo));
        } elseif ($dateFrom) {
            $periodText .= 'À partir du ' . date('d/m/Y', strtotime($dateFrom));
        } elseif ($dateTo) {
            $periodText .= "Jusqu'au " . date('d/m/Y', strtotime($dateTo));
        } else {
            $periodText .= 'Toutes les dates';
        }

        $sheet->setCellValue('A2', $periodText);
        $sheet->mergeCells('A2:M2');
        $sheet->getStyle('A2')->applyFromArray([
            'font' => ['italic' => true, 'size' => 11, 'color' => ['rgb' => '6B7280']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $sheet->setCellValue('A3', 'Généré le : ' . date('d/m/Y à H:i'));
        $sheet->mergeCells('A3:M3');
        $sheet->getStyle('A3')->applyFromArray([
            'font' => ['size' => 10, 'color' => ['rgb' => '9CA3AF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        return 5; // Headers start at row 5
    }

    private function addHeaders(Worksheet $sheet, int $row): void
    {
        $sheet->fromArray(self::HEADERS, null, 'A' . $row);
        $sheet->getRowDimension($row)->setRowHeight(25);

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11, 'name' => 'Arial'],
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

        $sheet->getStyle("A{$row}:M{$row}")->applyFromArray($headerStyle);
    }

    private function addDataRows(Worksheet $sheet, Collection $sales, int $startRow): int
    {
        $row = $startRow;

        foreach ($sales as $sale) {
            $this->addSaleRow($sheet, $sale, $row);
            $row++;
        }

        return $row - 1;
    }

    private function addSaleRow(Worksheet $sheet, Sale $sale, int $row): void
    {
        $remaining = max(0, $sale->total - $sale->paid_amount);
        $itemCount = $sale->items ? $sale->items->sum('quantity') : 0;

        $sheet->fromArray([
            $sale->sale_number,
            $sale->sale_date ? $sale->sale_date->format('d/m/Y') : 'N/A',
            $sale->client->name ?? 'Client anonyme',
            $itemCount,
            format_currency($sale->subtotal),
            format_currency($sale->discount ?? 0),
            format_currency($sale->tax ?? 0),
            format_currency($sale->total),
            format_currency($sale->paid_amount),
            format_currency($remaining),
            self::PAYMENT_LABELS[$sale->payment_status] ?? $sale->payment_status,
            self::STATUS_LABELS[$sale->status] ?? $sale->status,
            $sale->user->name ?? 'N/A',
        ], null, 'A' . $row);

        $this->applyRowStyles($sheet, $sale, $row);
    }

    private function applyRowStyles(Worksheet $sheet, Sale $sale, int $row): void
    {
        // Base data style
        $dataStyle = [
            'font' => ['size' => 10, 'name' => 'Arial'],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => false]
        ];
        $sheet->getStyle("A{$row}:M{$row}")->applyFromArray($dataStyle);

        // Alternating row colors
        if ($row % 2 == 0) {
            $sheet->getStyle("A{$row}:M{$row}")->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setRGB('F9FAFB');
        }

        // Center align specific columns
        $sheet->getStyle("A{$row}:B{$row}")->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("D{$row}")->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Right align monetary columns
        $sheet->getStyle("E{$row}:J{$row}")->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        // Center align status columns
        $sheet->getStyle("K{$row}:L{$row}")->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $this->applyPaymentStatusColors($sheet, $sale, $row);
        $this->applyStatusColors($sheet, $sale, $row);
    }

    private function applyPaymentStatusColors(Worksheet $sheet, Sale $sale, int $row): void
    {
        $colors = self::COLORS['payment_status'][$sale->payment_status] ?? self::COLORS['payment_status']['pending'];

        $sheet->getStyle("K{$row}")->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB($colors['bg']);
        $sheet->getStyle("K{$row}")->getFont()->getColor()->setRGB($colors['text']);
        $sheet->getStyle("K{$row}")->getFont()->setBold(true);
    }

    private function applyStatusColors(Worksheet $sheet, Sale $sale, int $row): void
    {
        $colors = self::COLORS['status'][$sale->status] ?? self::COLORS['status']['pending'];

        $sheet->getStyle("L{$row}")->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB($colors['bg']);
        $sheet->getStyle("L{$row}")->getFont()->getColor()->setRGB($colors['text']);
        $sheet->getStyle("L{$row}")->getFont()->setBold(true);
    }

    private function addSummarySection(Worksheet $sheet, Collection $sales, int $startRow): void
    {
        $completedSales = $sales->where('status', 'completed');
        $pendingSales = $sales->where('status', 'pending');
        $cancelledSales = $sales->where('status', 'cancelled');

        $sheet->setCellValue('A' . $startRow, 'RÉSUMÉ');
        $sheet->mergeCells('A' . $startRow . ':B' . $startRow);
        $sheet->getStyle('A' . $startRow)->applyFromArray([
            'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => '1F2937']],
        ]);

        $summaryData = [
            ['Total des ventes', $sales->count()],
            ['Ventes complétées', $completedSales->count()],
            ['Ventes en attente', $pendingSales->count()],
            ['Ventes annulées', $cancelledSales->count()],
            ['', ''],
            ['Montant total (complétées)', format_currency($completedSales->sum('total'))],
            ['Montant payé', format_currency($completedSales->sum('paid_amount'))],
            ['Montant en attente', format_currency($pendingSales->sum('total'))],
        ];

        $row = $startRow + 1;
        foreach ($summaryData as $data) {
            $sheet->setCellValue('A' . $row, $data[0]);
            $sheet->setCellValue('B' . $row, $data[1]);

            $sheet->getStyle('A' . $row)->getFont()->setBold(true);
            $sheet->getStyle('B' . $row)->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_RIGHT);

            $row++;
        }

        // Style summary section
        $sheet->getStyle('A' . ($startRow + 1) . ':B' . ($row - 1))->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'D1D5DB']
                ]
            ]
        ]);
    }

    private function applyFinalFormatting(Worksheet $sheet, int $lastRow, int $headerRow): void
    {
        // Set column widths
        foreach (self::COLUMN_WIDTHS as $col => $width) {
            $sheet->getColumnDimension($col)->setWidth($width);
        }

        // Apply borders to data area
        $sheet->getStyle("A{$headerRow}:M{$lastRow}")->applyFromArray([
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

        // Freeze header row
        $sheet->freezePane('A' . ($headerRow + 1));
    }
}
