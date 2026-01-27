<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use App\Models\Purchase;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PurchaseExcelExporter
{
    private const HEADERS = [
        'NUMÉRO', 'DATE', 'FOURNISSEUR', 'ARTICLES', 'TOTAL', 'STATUT', 'NOTES'
    ];

    private const COLUMN_WIDTHS = [
        'A' => 18, // Numéro
        'B' => 12, // Date
        'C' => 30, // Fournisseur
        'D' => 10, // Articles
        'E' => 18, // Total
        'F' => 15, // Statut
        'G' => 35, // Notes
    ];

    private const COLORS = [
        'status' => [
            'received' => ['bg' => 'D1FAE5', 'text' => '065F46'],
            'pending' => ['bg' => 'FEF3C7', 'text' => '92400E'],
            'cancelled' => ['bg' => 'FEE2E2', 'text' => '991B1B'],
        ],
    ];

    private const STATUS_LABELS = [
        'pending' => 'En attente',
        'received' => 'Réceptionné',
        'cancelled' => 'Annulé',
    ];

    public function export(Collection $purchases, ?string $dateFrom = null, ?string $dateTo = null, ?string $periodLabel = null): StreamedResponse
    {
        $spreadsheet = $this->createSpreadsheet($purchases, $dateFrom, $dateTo, $periodLabel);

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, 'achats_' . date('Y-m-d_His') . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Export to a file path (for email attachments)
     */
    public function exportToFile(Collection $purchases, ?string $dateFrom, ?string $dateTo, ?string $periodLabel, string $filePath): void
    {
        $spreadsheet = $this->createSpreadsheet($purchases, $dateFrom, $dateTo, $periodLabel);
        $writer = new Xlsx($spreadsheet);
        $writer->save($filePath);
    }

    private function createSpreadsheet(Collection $purchases, ?string $dateFrom, ?string $dateTo, ?string $periodLabel): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $this->setDocumentProperties($spreadsheet);
        $sheet->setTitle('Liste des Achats');

        // Add title and period info
        $startRow = $this->addTitleSection($sheet, $dateFrom, $dateTo, $periodLabel);

        $this->addHeaders($sheet, $startRow);
        $lastRow = $this->addDataRows($sheet, $purchases, $startRow + 1);
        $this->addSummarySection($sheet, $purchases, $lastRow + 2);
        $this->applyFinalFormatting($sheet, $lastRow, $startRow);

        return $spreadsheet;
    }

    private function setDocumentProperties(Spreadsheet $spreadsheet): void
    {
        $spreadsheet->getProperties()
            ->setCreator("STK System")
            ->setTitle("Rapport des Achats")
            ->setSubject("Export des achats")
            ->setDescription("Export Excel du rapport des achats");
    }

    private function addTitleSection(Worksheet $sheet, ?string $dateFrom, ?string $dateTo, ?string $periodLabel): int
    {
        $sheet->setCellValue('A1', 'RAPPORT DES ACHATS');
        $sheet->mergeCells('A1:G1');
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
        $sheet->mergeCells('A2:G2');
        $sheet->getStyle('A2')->applyFromArray([
            'font' => ['italic' => true, 'size' => 11, 'color' => ['rgb' => '6B7280']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $sheet->setCellValue('A3', 'Généré le : ' . date('d/m/Y à H:i'));
        $sheet->mergeCells('A3:G3');
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

        $sheet->getStyle("A{$row}:G{$row}")->applyFromArray($headerStyle);
    }

    private function addDataRows(Worksheet $sheet, Collection $purchases, int $startRow): int
    {
        $row = $startRow;

        foreach ($purchases as $purchase) {
            $this->addPurchaseRow($sheet, $purchase, $row);
            $row++;
        }

        return $row - 1;
    }

    private function addPurchaseRow(Worksheet $sheet, Purchase $purchase, int $row): void
    {
        $itemCount = $purchase->items ? $purchase->items->sum('quantity') : 0;

        $sheet->fromArray([
            $purchase->purchase_number,
            $purchase->purchase_date ? $purchase->purchase_date->format('d/m/Y') : 'N/A',
            $purchase->supplier->name ?? 'Fournisseur inconnu',
            $itemCount,
            format_currency($purchase->total),
            self::STATUS_LABELS[$purchase->status] ?? $purchase->status,
            $purchase->notes ?? '',
        ], null, 'A' . $row);

        $this->applyRowStyles($sheet, $purchase, $row);
    }

    private function applyRowStyles(Worksheet $sheet, Purchase $purchase, int $row): void
    {
        // Base data style
        $dataStyle = [
            'font' => ['size' => 10, 'name' => 'Arial'],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => false]
        ];
        $sheet->getStyle("A{$row}:G{$row}")->applyFromArray($dataStyle);

        // Alternating row colors
        if ($row % 2 == 0) {
            $sheet->getStyle("A{$row}:G{$row}")->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setRGB('F9FAFB');
        }

        // Center align specific columns
        $sheet->getStyle("A{$row}:B{$row}")->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("D{$row}")->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Right align monetary columns
        $sheet->getStyle("E{$row}")->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        // Center align status column
        $sheet->getStyle("F{$row}")->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $this->applyStatusColors($sheet, $purchase, $row);
    }

    private function applyStatusColors(Worksheet $sheet, Purchase $purchase, int $row): void
    {
        $colors = self::COLORS['status'][$purchase->status] ?? self::COLORS['status']['pending'];

        $sheet->getStyle("F{$row}")->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB($colors['bg']);
        $sheet->getStyle("F{$row}")->getFont()->getColor()->setRGB($colors['text']);
        $sheet->getStyle("F{$row}")->getFont()->setBold(true);
    }

    private function addSummarySection(Worksheet $sheet, Collection $purchases, int $startRow): void
    {
        $receivedPurchases = $purchases->where('status', 'received');
        $pendingPurchases = $purchases->where('status', 'pending');
        $cancelledPurchases = $purchases->where('status', 'cancelled');

        $sheet->setCellValue('A' . $startRow, 'RÉSUMÉ');
        $sheet->mergeCells('A' . $startRow . ':B' . $startRow);
        $sheet->getStyle('A' . $startRow)->applyFromArray([
            'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => '1F2937']],
        ]);

        $summaryData = [
            ['Total des achats', $purchases->count()],
            ['Achats réceptionnés', $receivedPurchases->count()],
            ['Achats en attente', $pendingPurchases->count()],
            ['Achats annulés', $cancelledPurchases->count()],
            ['', ''],
            ['Montant total (réceptionnés)', format_currency($receivedPurchases->sum('total'))],
            ['Montant en attente', format_currency($pendingPurchases->sum('total'))],
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
        $sheet->getStyle("A{$headerRow}:G{$lastRow}")->applyFromArray([
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
