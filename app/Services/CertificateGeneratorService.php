<?php

namespace App\Services;

use App\Models\Document;
use setasign\Fpdi\Fpdi;
use Illuminate\Support\Facades\Storage;

class CertificateGeneratorService
{
    /**
     * Generate a Certificate of Completion PDF.
     *
     * @param Document $document
     * @param string $documentHash SHA-256 hash of the flattened document
     * @return string Path to the certificate PDF
     */
    public function generateCertificate(Document $document, string $documentHash): string
    {
        $pdf = new Fpdi();
        $pdf->AddPage();
        $pdf->SetFont('Helvetica', '', 12);

        // --- Header ---
        $pdf->SetFont('Helvetica', 'B', 20);
        $pdf->Cell(0, 10, 'Certificate of Completion', 0, 1, 'C');
        $pdf->Ln(5);

        // --- Document Info ---
        $pdf->SetFont('Helvetica', 'B', 12);
        $pdf->Cell(40, 10, 'Document Name:', 0, 0);
        $pdf->SetFont('Helvetica', '', 12);
        $pdf->Cell(0, 10, $document->name, 0, 1);

        $pdf->SetFont('Helvetica', 'B', 12);
        $pdf->Cell(40, 10, 'Document ID:', 0, 0);
        $pdf->SetFont('Helvetica', '', 12);
        $pdf->Cell(0, 10, $document->id, 0, 1);

        $pdf->SetFont('Helvetica', 'B', 12);
        $pdf->Cell(40, 10, 'Document Hash:', 0, 0);
        $pdf->SetFont('Helvetica', '', 10); // Smaller font for hash
        $pdf->Cell(0, 10, $documentHash, 0, 1);
        $pdf->Ln(5);

        // --- Signers ---
        $pdf->SetFont('Helvetica', 'B', 14);
        $pdf->Cell(0, 10, 'Signatories', 0, 1, 'L');
        $pdf->SetLineWidth(0.5);
        $pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY());
        $pdf->Ln(2);

        $pdf->SetFont('Helvetica', 'B', 10);
        $pdf->Cell(60, 8, 'Name', 1);
        $pdf->Cell(60, 8, 'Email', 1);
        $pdf->Cell(30, 8, 'Status', 1);
        $pdf->Cell(40, 8, 'Date', 1);
        $pdf->Ln();

        $pdf->SetFont('Helvetica', '', 10);
        foreach ($document->signers as $signer) {
            $pdf->Cell(60, 8, $signer->name, 1);
            $pdf->Cell(60, 8, $signer->email, 1);
            $pdf->Cell(30, 8, ucfirst($signer->status), 1);
            $pdf->Cell(40, 8, $signer->updated_at->format('Y-m-d H:i'), 1);
            $pdf->Ln();
        }
        $pdf->Ln(10);

        // --- Audit Log ---
        $pdf->SetFont('Helvetica', 'B', 14);
        $pdf->Cell(0, 10, 'Audit Log', 0, 1, 'L');
        $pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY());
        $pdf->Ln(2);

        $pdf->SetFont('Helvetica', 'B', 10);
        $pdf->Cell(40, 8, 'Date', 1);
        $pdf->Cell(40, 8, 'Action', 1);
        $pdf->Cell(60, 8, 'User', 1);
        $pdf->Cell(50, 8, 'IP Address', 1);
        $pdf->Ln();

        $pdf->SetFont('Helvetica', '', 10);
        foreach ($document->auditLogs as $log) {
            $user = $log->user ? $log->user->name : ($log->metadata['signer_name'] ?? 'System/Guest');

            $pdf->Cell(40, 8, $log->created_at->format('Y-m-d H:i:s'), 1);
            $pdf->Cell(40, 8, $log->action, 1);
            $pdf->Cell(60, 8, substr($user, 0, 30), 1); // Truncate if too long
            $pdf->Cell(50, 8, $log->ip_address ?? 'N/A', 1);
            $pdf->Ln();
        }

        // --- Footer ---
        $pdf->SetY(-30);
        $pdf->SetFont('Helvetica', 'I', 8);
        $pdf->Cell(0, 10, 'Powered by TechSynchronic - Electronically Signed & Sealed', 0, 1, 'C');
        $pdf->Cell(0, 5, 'Reference ID: ' . $document->slug, 0, 1, 'C');


        // Save
        $outputDir = "documents/{$document->business_id}/certificates";
        Storage::disk('public')->makeDirectory($outputDir);
        $filename = "{$outputDir}/{$document->id}_certificate_" . time() . ".pdf";
        $outputPath = Storage::disk('public')->path($filename);

        $pdf->Output('F', $outputPath);

        return $filename;
    }
}
