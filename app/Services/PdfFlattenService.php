<?php

namespace App\Services;

use App\Models\Document;
use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\Fpdi; // Requires: composer require setasign/fpdf setasign/fpdi

class PdfFlattenService
{
    /**
     * Flatten the document by embedding signatures into the PDF.
     *
     * @param Document $document
     * @return string Path to the flattened PDF
     * @throws \Exception
     */
    public function flatten(Document $document): string
    {
        $originalPdfPath = Storage::disk('public')->path($document->pdf_path);

        if (!file_exists($originalPdfPath)) {
            throw new \Exception("Original PDF not found at {$originalPdfPath}");
        }

        // Initialize FPDI
        $pdf = new Fpdi();

        // Get page count
        $pageCount = $pdf->setSourceFile($originalPdfPath);

        // Group fields by page for efficiency
        $fieldsByPage = $document->fields->groupBy('page_number');

        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
            $templateId = $pdf->importPage($pageNo);

            // Get size of imported page
            $size = $pdf->getTemplateSize($templateId);

            // Add page with same size and orientation
            $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $pdf->useTemplate($templateId);

            // Overlay fields for this page
            if (isset($fieldsByPage[$pageNo])) {
                foreach ($fieldsByPage[$pageNo] as $field) {
                    if ($field->value) {
                        $this->renderField($pdf, $field, $size);
                    }
                }
            }
        }

        // Save output
        $businessId = $document->business_id;
        $outputDir = "documents/{$businessId}/signed";
        Storage::disk('public')->makeDirectory($outputDir);

        $filename = "{$outputDir}/{$document->id}_signed_" . time() . ".pdf";
        $outputPath = Storage::disk('public')->path($filename);

        $pdf->Output('F', $outputPath);

        // Return relative path for storage
        return $filename;
    }

    /**
     * Render a single field onto the PDF.
     *
     * @param Fpdi $pdf
     * @param mixed $field
     * @param array $pageSize
     */
    private function renderField(Fpdi $pdf, $field, $pageSize)
    {
        \Log::info("Rendering field on PDF", [
            'field_id' => $field->id,
            'x_position' => $field->x_position,
            'y_position' => $field->y_position,
            'width' => $field->width,
            'height' => $field->height,
            'page_width' => $pageSize['width'],
            'page_height' => $pageSize['height']
        ]);

        // COORDINATE SYSTEM FIX (V3 - Final):
        // ==================================
        // 1. Origin: FPDF uses Top-Left origin (0,0) and Millimeters (mm).
        //    Y increases downward. We DO NOT need to invert Y.
        //
        // 2. Units: 
        //    - Page Size: mm (A4 = 210x297)
        //    - Position: % (0-100)
        //    - Dimensions: CSS Pixels (at 96 DPI)
        //
        // 3. Conversion Formula (Exact Pixel Match):
        //    - 1 inch = 25.4 mm = 96 pixels
        //    - Scaling Factor = 25.4 / 96 ≈ 0.264583

        $mm_per_pixel = 25.4 / 96;

        // Calculate Coordinates in MM (Relative to Top-Left)
        $x = ($field->x_position / 100) * $pageSize['width'];
        $y = ($field->y_position / 100) * $pageSize['height'];

        // Calculate Dimensions in MM (Absolute)
        $w = $field->width * $mm_per_pixel;
        $h = $field->height * $mm_per_pixel;

        \Log::info("Calculated PDF coordinates (mm)", [
            'x' => $x,
            'y' => $y,
            'w' => $w,
            'h' => $h
        ]);

        // Handle Image Signatures (Base64)
        if ($this->isBase64Image($field->value)) {
            $this->renderImage($pdf, $field->value, $x, $y, $w, $h);
        } else {
            // Text Signature
            $pdf->SetFont('Helvetica', 'I', 14);
            $pdf->SetTextColor(0, 0, 139);
            $pdf->SetXY($x, $y);
            $pdf->Cell($w, $h, $field->value, 0, 0, 'L');
        }
    }

    private function renderImage(Fpdi $pdf, $base64, $x, $y, $w, $h)
    {
        // Extract image data
        if (preg_match('/^data:image\/(\w+);base64,/', $base64, $type)) {
            $data = substr($base64, strpos($base64, ',') + 1);
            $data = base64_decode($data);
            $type = strtolower($type[1]);

            // FPDF supports JPG, JPEG, PNG, GIF
            if (in_array($type, ['jpg', 'jpeg', 'png', 'gif'])) {
                // Format for mem image: "data://text/plain;base64," . base64_encode($data) ?
                // FDPF needs a file path or a special helper URL for memory streams.
                // Actually FPDF's Image() supports a 'data' URI wrapper since standard PHP stream wrappers can be used,
                // BUT standard fpdf `Image` takes a file path.
                // We can save temp file or use mem stream wrapper.

                $tempFile = tempnam(sys_get_temp_dir(), 'sig') . '.' . $type;
                file_put_contents($tempFile, $data);

                $pdf->Image($tempFile, $x, $y, $w, $h, $type);

                unlink($tempFile);
            }
        }
    }

    private function isBase64Image($value)
    {
        return preg_match('/^data:image\/(\w+);base64,/', $value);
    }

    /**
     * Merge the certificate PDF with the signed document PDF.
     *
     * @param string $signedPdfPath Relative path to signed PDF
     * @param string $certificatePath Relative path to certificate PDF
     * @return string Path to the final merged PDF
     */
    public function mergeCertificate(string $signedPdfPath, string $certificatePath): string
    {
        // Absolute paths
        $signedAbs = Storage::disk('public')->path($signedPdfPath);
        $certAbs = Storage::disk('public')->path($certificatePath);

        if (!file_exists($signedAbs) || !file_exists($certAbs)) {
            throw new \Exception("One of the files to merge does not exist.");
        }

        $pdf = new Fpdi();

        // Import Signed Document Pages
        $pageCount = $pdf->setSourceFile($signedAbs);
        for ($i = 1; $i <= $pageCount; $i++) {
            $tplId = $pdf->importPage($i);
            $size = $pdf->getTemplateSize($tplId);
            $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $pdf->useTemplate($tplId);
        }

        // Import Certificate Pages
        $certPageCount = $pdf->setSourceFile($certAbs);
        for ($i = 1; $i <= $certPageCount; $i++) {
            $tplId = $pdf->importPage($i);
            $size = $pdf->getTemplateSize($tplId);
            $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $pdf->useTemplate($tplId);
        }

        // Save Final Output
        $pathInfo = pathinfo($signedPdfPath);
        $finalFilename = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '_final.pdf';
        $finalAbsPath = Storage::disk('public')->path($finalFilename);

        $pdf->Output('F', $finalAbsPath);

        return $finalFilename;
    }
}
