<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use App\Models\Invoice;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InvoiceExcelExporter
{
    private const HEADERS = [
        'N° FACTURE', 'DATE', 'ÉCHÉANCE', 'CLIENT', 'N° VENTE',
        'MONTANT HT', 'TVA', 'MONTANT TTC', 'STATUT'
    ];

    private const COLUMN_WIDTHS = [
        'A' => 18, // N° Facture
        'B' => 12, // Date
        'C' => 12, // Échéance
        'D' => 25, // Client
        'E' => 15, // N° Vente
        'F' => 15, // Montant HT
        'G' => 12, // TVA
        'H' => 15, // Montant TTC
        'I' => 12, // Statut
    ];

    private const COLORS = [
        'status' => [
            'draft' => ['bg' => 'E5E7EB', 'text' => '374151'],
            'sent' => ['bg' => 'DBEAFE', 'text' => '1E40AF'],
            'paid' => ['bg' => 'D1FAE5', 'text' => '065F46'],
            'cancelled' => ['bg' => 'FEE2E2', 'text' => '991B1B'],
            'overdue' => ['bg' => 'FEF3C7', 'text' => '92400E'],
        ],
    ];

    private const STATUS_LABELS = [
        'draft' => 'Brouillon',
        'sent' => 'Envoyée',
        'paid' => 'Payée',
        'cancelled' => 'Annulée',
        'overdue' => 'En retard',
    ];

    public function export(Collection $invoices, ?string $dateFrom = null, ?string $dateTo = null, ?string $periodLabel = null): StreamedResponse
    {
        $spreadsheet = $this->createSpreadsheet($invoices, $dateFrom, $dateTo, $periodLabel);

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, 'factures_' . date('Y-m-d_His') . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function createSpreadsheet(Collection $invoices, ?string $dateFrom, ?string $dateTo, ?string $periodLabel): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $this->setDocumentProperties($spreadsheet);
        $sheet->setTitle('Liste des Factures');

        // Add title and period info
        $startRow = $this->addTitleSection($sheet, $dateFrom, $dateTo, $periodLabel);

        $this->addHeaders($sheet, $startRow);
        $lastRow = $this->addDataRows($sheet, $invoices, $startRow + 1);
        $this->addSummarySection($sheet, $invoices, $lastRow + 2);
        $this->applyFinalFormatting($sheet, $lastRow, $startRow);

        return $spreadsheet;
    }

    private function setDocumentProperties(Spreadsheet $spreadsheet): void
    {
        $spreadsheet->getProperties()
            ->setCreator("STK System")
            ->setTitle("Rapport des Factures")
            ->setSubject("Export des factures")
            ->setDescription("Export Excel du rapport des factures");
    }

    private function addTitleSection(Worksheet $sheet, ?string $dateFrom, ?string $dateTo, ?string $periodLabel): int
    {
        $sheet->setCellValue('A1', 'RAPPORT DES FACTURES');
        $sheet->mergeCells('A1:I1');
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
        $sheet->mergeCells('A2:I2');
        $sheet->getStyle('A2')->applyFromArray([
            'font' => ['italic' => true, 'size' => 11, 'color' => ['rgb' => '6B7280']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $sheet->setCellValue('A3', 'Généré le : ' . date('d/m/Y à H:i'));
        $sheet->mergeCells('A3:I3');
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

        $sheet->getStyle("A{$row}:I{$row}")->applyFromArray($headerStyle);
    }

    private function addDataRows(Worksheet $sheet, Collection $invoices, int $startRow): int
    {
        $row = $startRow;

        foreach ($invoices as $invoice) {
            $this->addInvoiceRow($sheet, $invoice, $row);
            $row++;
        }

        return $row - 1;
    }

    private function addInvoiceRow(Worksheet $sheet, Invoice $invoice, int $row): void
    {
        $subtotal = $invoice->sale ? $invoice->sale->subtotal : 0;
        $tax = $invoice->sale ? ($invoice->sale->tax ?? 0) : 0;
        $total = $invoice->sale ? $invoice->sale->total : 0;

        $sheet->fromArray([
            $invoice->invoice_number,
            $invoice->invoice_date ? $invoice->invoice_date->format('d/m/Y') : 'N/A',
            $invoice->due_date ? $invoice->due_date->format('d/m/Y') : 'N/A',
            $invoice->sale?->client?->name ?? 'Client anonyme',
            $invoice->sale?->sale_number ?? 'N/A',
            format_currency($subtotal),
            format_currency($tax),
            format_currency($total),
            self::STATUS_LABELS[$invoice->status] ?? $invoice->status,
        ], null, 'A' . $row);

        $this->applyRowStyles($sheet, $invoice, $row);
    }

    private function applyRowStyles(Worksheet $sheet, Invoice $invoice, int $row): void
    {
        $baseStyle = [
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'E5E7EB']
                ]
            ]
        ];

        // Apply alternating row color
        if ($row % 2 === 0) {
            $baseStyle['fill'] = [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'F9FAFB']
            ];
        }

        $sheet->getStyle("A{$row}:I{$row}")->applyFromArray($baseStyle);

        // Style status column (I)
        $statusColors = self::COLORS['status'][$invoice->status] ?? ['bg' => 'E5E7EB', 'text' => '374151'];
        $sheet->getStyle("I{$row}")->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $statusColors['bg']]],
            'font' => ['color' => ['rgb' => $statusColors['text']], 'bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
        ]);

        // Align amounts to right
        $sheet->getStyle("F{$row}:H{$row}")->applyFromArray([
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT]
        ]);

        // Center dates
        $sheet->getStyle("B{$row}:C{$row}")->applyFromArray([
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
        ]);

        $sheet->getRowDimension($row)->setRowHeight(22);
    }

    private function addSummarySection(Worksheet $sheet, Collection $invoices, int $startRow): void
    {
        $row = $startRow;

        // Summary title
        $sheet->setCellValue("A{$row}", 'RÉSUMÉ');
        $sheet->mergeCells("A{$row}:I{$row}");
        $sheet->getStyle("A{$row}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => '1F2937']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E5E7EB']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '9CA3AF']]]
        ]);

        $row++;

        // Calculate totals
        $totalInvoices = $invoices->count();
        $paidInvoices = $invoices->where('status', 'paid')->count();
        $unpaidInvoices = $invoices->whereIn('status', ['draft', 'sent'])->count();
        $cancelledInvoices = $invoices->where('status', 'cancelled')->count();
        $totalAmount = $invoices->sum(fn($i) => $i->sale ? $i->sale->total : 0);
        $paidAmount = $invoices->where('status', 'paid')->sum(fn($i) => $i->sale ? $i->sale->total : 0);
        $unpaidAmount = $invoices->whereIn('status', ['draft', 'sent'])->sum(fn($i) => $i->sale ? $i->sale->total : 0);

        $summaryData = [
            ['Total factures', $totalInvoices],
            ['Factures payées', $paidInvoices],
            ['Factures impayées', $unpaidInvoices],
            ['Factures annulées', $cancelledInvoices],
            ['Montant total', format_currency($totalAmount)],
            ['Montant payé', format_currency($paidAmount)],
            ['Montant impayé', format_currency($unpaidAmount)],
        ];

        foreach ($summaryData as $data) {
            $sheet->setCellValue("A{$row}", $data[0]);
            $sheet->setCellValue("B{$row}", $data[1]);
            $sheet->getStyle("A{$row}")->applyFromArray([
                'font' => ['bold' => true],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT]
            ]);
            $sheet->getStyle("B{$row}")->applyFromArray([
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
                'font' => ['bold' => true]
            ]);
            $row++;
        }
    }

    private function applyFinalFormatting(Worksheet $sheet, int $lastDataRow, int $headerRow): void
    {
        // Set column widths
        foreach (self::COLUMN_WIDTHS as $col => $width) {
            $sheet->getColumnDimension($col)->setWidth($width);
        }

        // Freeze header row
        $sheet->freezePane('A' . ($headerRow + 1));

        // Set print area
        $sheet->getPageSetup()
            ->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE)
            ->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4)
            ->setFitToWidth(1)
            ->setFitToHeight(0);
    }
}
