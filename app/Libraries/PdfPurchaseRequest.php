<?php

namespace App\Libraries;

use Fpdf\Fpdf;

class PdfPurchaseRequest extends Fpdf
{
    private $headerData = [];

    public function setHeaderData($data)
    {
        $this->headerData = $data;
    }

    // Page header
    function Header()
    {
        $logoPath = FCPATH . 'public/images/pdf-logo/serenity-local.png';
        $ttdPath = FCPATH . 'public/images/pdf-logo/TTdku.jpg';

        $rowHeight = 7;
        $totalHeight = $rowHeight * 4;

        $startX = $this->GetX();
        $startY = $this->GetY();

        // Kolom 1. Logo
        $this->Cell(35, $totalHeight, '', 1, 0, 'C');
        if (file_exists($logoPath)) {
            $this->Image($logoPath, $startX + 4, $startY + 8, 27);
        }

        // Kolom 2. Judul
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(60, $totalHeight, 'Purchase Request', 1, 0, 'C');

        // Kolom 3. Dokumen info
        $this->SetFont('Arial', '', 9);

        $this->Cell(30, 6, 'Dokumen', 1, 0, 'L');
        $this->Cell(25, 6, $this->headerData['transcode'] ?? '-', 1, 1, 'L');

        $this->SetX($startX + 95);
        $this->Cell(30, $rowHeight, 'Revisi', 1, 0, 'L');
        $this->Cell(25, $rowHeight, '001', 1, 1, 'L');

        $this->SetX($startX + 95);
        $this->Cell(30, $rowHeight, 'Tanggal Terbit', 1, 0, 'L');
        $this->Cell(
            25,
            $rowHeight,
            $this->formatDate($this->headerData['transdate'] ?? date('Y-m-d')),
            1,
            1,
            'L'
        );

        $this->SetX($startX + 95);
        $this->Cell(30, 8, 'Halaman', 1, 0, 'L');
        $this->Cell(25, 8, '1', 1, 1);

        // Kolom 4. Approval
        $approvalX = $startX + 150;

        $this->SetXY($approvalX, $startY);
        $this->MultiCell(45, 3, "Disetujui oleh:\nManager Mutu", 1, 'C');

        $this->Ln();

        // Baris 2. Sejajar Revisi
        $signStartY = $startY + $rowHeight - 1; //sejajar dengan revisi 
        $signHeight = $rowHeight * 2;

        $this->SetXY($approvalX, $signStartY);
        $this->Cell(45, $signHeight, '', 1, 1, 'C');

        // Gambar tanda tangan di tengah cell
        if (file_exists($ttdPath)) {
            $imgWidth = 30;
            $imgHeight = 10;

            $imgX = $approvalX + (45 - $imgWidth) / 2;
            $imgY = $signStartY + ($signHeight - $imgHeight) / 2 ;

            $this->Image($ttdPath, $imgX, $imgY, $imgWidth, $imgHeight);
        }

        // Baris 4. Sejajar Halaman
        $this->SetX($approvalX);
        $this->Cell(45, 8, 'Neycela Erizka P', 1, 1, 'C');

        // Garis pemisah setelah header
        $leftMargin = 10;
        $tableWidth = array_sum([10, 80, 40, 30, 35]); // samakan dengan width tabel

        $this->Ln(2);
        $this->Line(
            $leftMargin,
            $this->GetY(),
            $leftMargin + $tableWidth,
            $this->GetY()
        );
        $this->Ln(4);
    }



    // Page footer
    function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'Page ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }

    // Format date to Indonesian format
    private function formatDate($date)
    {
        $bulanIndo = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember'
        ];

        $timestamp = strtotime($date);
        return date('d', $timestamp) . ' ' . $bulanIndo[(int) date('n', $timestamp)] . ' ' . date('Y', $timestamp);
    }

    // Better table with borders
    function ImprovedTable($header, $data)
    {
        // Column widths
        $w = [10, 80, 55, 50];

        // Header
        $this->SetFont('Arial', 'B', 10);
        $this->SetFillColor(230, 230, 230);

        for ($i = 0; $i < count($header); $i++) {
            $this->Cell($w[$i], 7, $header[$i], 1, 0, 'C', true);
        }
        $this->Ln();

        // Data
        $this->SetFont('Arial', '', 9);
        $no = 1;
        foreach ($data as $row) {
            $this->Cell($w[0], 6, $no++, 1, 0, 'C');
            $this->Cell($w[1], 6, $row['productname'] ?? '-', 1, 0, 'L');
            $this->Cell($w[2], 6, $row['uomnm'] ?? '-', 1, 0, 'C');

            // Format qty
            $qty = $row['qty'] ?? 0;
            $qtyFormatted = (floor($qty) == $qty)
                ? number_format($qty, 0, ',', '.')
                : number_format($qty, 2, ',', '.');

            $this->Cell($w[3], 6, $qtyFormatted, 1, 0, 'C');

            $this->Ln();
        }
    }
}