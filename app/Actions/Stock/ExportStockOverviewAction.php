<?php

namespace App\Actions\Stock;

use App\Services\StockOverviewService;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;

class ExportStockOverviewAction
{
    /**
     * Export stock overview to Excel.
     */
    public function toExcel(Collection $variants, array $kpis): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('État du Stock');

        // Title
        $sheet->setCellValue('A1', 'État du Stock - ' . now()->format('d/m/Y H:i'));
        $sheet->mergeCells('A1:H1');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 18,
                'color' => ['argb' => 'FFFFFFFF'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF4F46E5'],
            ],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(30);

        // KPIs Summary Section
        $sheet->setCellValue('A3', 'Résumé Financier');
        $sheet->mergeCells('A3:H3');
        $sheet->getStyle('A3')->applyFromArray([
            'font' => ['bold' => true, 'size' => 13, 'color' => ['argb' => 'FF1E293B']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF1F5F9']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
        ]);

        // KPI Cards
        $kpiData = [
            ['label' => 'Valeur totale du stock:', 'value' => format_currency($kpis['total_stock_value']), 'color' => 'FF3B82F6'],
            ['label' => 'Valeur de vente potentielle:', 'value' => format_currency($kpis['total_retail_value']), 'color' => 'FF10B981'],
            ['label' => 'Profit potentiel:', 'value' => format_currency($kpis['potential_profit']), 'color' => 'FF8B5CF6'],
            ['label' => 'Marge bénéficiaire:', 'value' => $kpis['profit_margin_percentage'] . '%', 'color' => 'FFF59E0B'],
        ];

        $row = 4;
        foreach ($kpiData as $kpi) {
            $sheet->setCellValue('A' . $row, $kpi['label']);
            $sheet->setCellValue('B' . $row, $kpi['value']);
            $sheet->mergeCells('B' . $row . ':D' . $row);

            $sheet->getStyle('A' . $row)->applyFromArray([
                'font' => ['bold' => true, 'size' => 11],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF8FAFC']],
            ]);

            $sheet->getStyle('B' . $row . ':D' . $row)->applyFromArray([
                'font' => ['bold' => true, 'size' => 12, 'color' => ['argb' => $kpi['color']]],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
            ]);
            $row++;
        }

        // Border around KPI section
        $sheet->getStyle('A3:H' . ($row - 1))->applyFromArray([
            'borders' => [
                'outline' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['argb' => 'FFE2E8F0']],
            ],
        ]);

        // Headers
        $headerRow = $row + 1;
        $headers = ['Produit', 'SKU', 'Stock', 'Seuil', 'Valeur Unit.', 'Valeur Tot.', 'Statut', 'Catégorie'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . $headerRow, $header);
            $col++;
        }

        $sheet->getStyle('A' . $headerRow . ':H' . $headerRow)->applyFromArray([
            'font' => ['bold' => true, 'size' => 11, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF475569']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFFFFFFF']],
            ],
        ]);
        $sheet->getRowDimension($headerRow)->setRowHeight(25);

        // Data rows
        $dataStartRow = $headerRow + 1;
        $currentRow = $dataStartRow;

        foreach ($variants as $variant) {
            $sheet->setCellValue('A' . $currentRow, $variant->product->name . ($variant->getVariantName() !== 'Standard' ? ' - ' . $variant->getVariantName() : ''));
            $sheet->setCellValue('B' . $currentRow, $variant->sku ?? 'N/A');
            $sheet->setCellValue('C' . $currentRow, $variant->stock_quantity);
            $sheet->setCellValue('D' . $currentRow, $variant->low_stock_threshold);
            $sheet->setCellValue('E' . $currentRow, number_format($variant->product->cost_price, 0, ',', ' '));
            $sheet->setCellValue('F' . $currentRow, number_format($variant->stock_quantity * $variant->product->cost_price, 0, ',', ' '));

            // Status with color
            $status = $variant->isOutOfStock() ? 'Rupture' : ($variant->isLowStock() ? 'Stock faible' : 'En stock');
            $sheet->setCellValue('G' . $currentRow, $status);

            // Apply status color
            if ($variant->isOutOfStock()) {
                $statusColor = 'FFFEE2E2';
                $textColor = 'FF991B1B';
            } elseif ($variant->isLowStock()) {
                $statusColor = 'FFFEF3C7';
                $textColor = 'FF92400E';
            } else {
                $statusColor = 'FFDCFCE7';
                $textColor = 'FF166534';
            }

            $sheet->getStyle('G' . $currentRow)->applyFromArray([
                'font' => ['bold' => true, 'color' => ['argb' => $textColor]],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $statusColor]],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);

            $sheet->setCellValue('H' . $currentRow, $variant->product->category->name ?? 'N/A');

            // Alternate row colors
            if ($currentRow % 2 == 0) {
                $sheet->getStyle('A' . $currentRow . ':H' . $currentRow)->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF8FAFC']],
                ]);
            }

            $currentRow++;
        }

        // Borders for data section
        $lastRow = $currentRow - 1;
        $sheet->getStyle('A' . $dataStartRow . ':H' . $lastRow)->applyFromArray([
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFE2E8F0']],
            ],
        ]);

        // Alignment for numeric columns
        $sheet->getStyle('C' . $dataStartRow . ':D' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('E' . $dataStartRow . ':F' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        // Auto-size columns
        foreach (range('A', 'H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Add footer
        $footerRow = $lastRow + 2;
        $sheet->setCellValue('A' . $footerRow, 'Généré le ' . now()->format('d/m/Y à H:i:s'));
        $sheet->mergeCells('A' . $footerRow . ':H' . $footerRow);
        $sheet->getStyle('A' . $footerRow)->applyFromArray([
            'font' => ['italic' => true, 'size' => 9, 'color' => ['argb' => 'FF64748B']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // Save and download
        $writer = new Xlsx($spreadsheet);
        $filename = 'etat-stock-' . now()->format('Y-m-d-His') . '.xlsx';
        $tempFile = storage_path('app/temp/' . $filename);

        if (!file_exists(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }

        $writer->save($tempFile);

        return response()->download($tempFile, $filename)->deleteFileAfterSend(true);
    }

    /**
     * Export stock overview to PDF.
     */
    public function toPdf(Collection $variants, array $kpis): \Illuminate\Http\Response
    {
        $pdf = Pdf::loadView('exports.stock-overview', [
            'variants' => $variants,
            'kpis' => $kpis,
            'date' => now()->format('d/m/Y'),
        ]);

        return $pdf->download('etat-stock-' . now()->format('Y-m-d-His') . '.pdf');
    }
}
