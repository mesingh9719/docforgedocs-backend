<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Document;
use Illuminate\Http\Request;
use App\Http\Resources\Api\V1\DocumentResource;

class PublicDocumentController extends Controller
{
    public function show(Request $request, $token)
    {
        $document = Document::with('documentType')->where('public_token', $token)->firstOrFail();

        // Log View
        $this->logActivity($request, $document, 'view');

        return new DocumentResource($document);
    }

    public function download(Request $request, $token)
    {
        $document = Document::where('public_token', $token)->firstOrFail();

        if (!$document->pdf_path || !\Illuminate\Support\Facades\Storage::disk('public')->exists($document->pdf_path)) {
            return response()->json(['message' => 'PDF not available.'], 404);
        }

        // Log Download
        $this->logActivity($request, $document, 'download');

        return \Illuminate\Support\Facades\Storage::disk('public')->download($document->pdf_path, $document->slug . '.pdf');
    }

    public function preview(Request $request, $token)
    {
        $document = Document::where('public_token', $token)->firstOrFail();

        \Illuminate\Support\Facades\Log::info("Preview requested for doc ID: {$document->id}, Path: {$document->pdf_path}");

        if (!$document->pdf_path || !\Illuminate\Support\Facades\Storage::disk('public')->exists($document->pdf_path)) {
            \Illuminate\Support\Facades\Log::error("PDF not found at path: " . ($document->pdf_path ?? 'NULL'));
            return response()->json(['message' => 'PDF not available.'], 404);
        }

        // Log View (Optional: Log explicit preview fetch? Or rely on the 'show' log?)
        // Since 'show' is called to load the page, we don't necessarily need to log 'view' again here to avoid doubles.
        // But if someone accesses URL directly... 
        // Let's stick to page load log for now.

        return \Illuminate\Support\Facades\Storage::disk('public')->response($document->pdf_path, $document->slug . '.pdf', [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $document->slug . '.pdf"'
        ]);
    }

    private function logActivity(Request $request, Document $document, string $action)
    {
        // Simple User Agent Parsing (Can use a library like jenssegers/agent in future for better accuracy)
        $userAgent = $request->userAgent();

        // Basic detection
        $device = 'Desktop';
        if (preg_match('/(tablet|ipad|playbook)|(android(?!.*(mobi|opera mini)))/i', $userAgent)) {
            $device = 'Tablet';
        } elseif (preg_match('/(up.browser|up.link|mmp|symbian|smartphone|midp|wap|phone|android|iemobile)/i', $userAgent)) {
            $device = 'Mobile';
        }

        $platform = 'Unknown';
        if (preg_match('/linux/i', $userAgent))
            $platform = 'Linux';
        elseif (preg_match('/macintosh|mac os x/i', $userAgent))
            $platform = 'Mac';
        elseif (preg_match('/windows|win32/i', $userAgent))
            $platform = 'Windows';
        elseif (preg_match('/android/i', $userAgent))
            $platform = 'Android';
        elseif (preg_match('/iphone|ipad|ipod/i', $userAgent))
            $platform = 'iOS';

        $browser = 'Unknown';
        if (preg_match('/MSIE/i', $userAgent) && !preg_match('/Opera/i', $userAgent))
            $browser = 'Internet Explorer';
        elseif (preg_match('/Firefox/i', $userAgent))
            $browser = 'Firefox';
        elseif (preg_match('/Chrome/i', $userAgent))
            $browser = 'Chrome';
        elseif (preg_match('/Safari/i', $userAgent))
            $browser = 'Safari';
        elseif (preg_match('/Opera/i', $userAgent))
            $browser = 'Opera';

        \App\Models\DocumentUsageLog::create([
            'document_id' => $document->id,
            'action' => $action,
            'ip_address' => $request->ip(),
            'user_agent' => $userAgent,
            'location' => null, // GeoIP can be added here later
            'device' => $device,
            'platform' => $platform,
            'browser' => $browser
        ]);
    }
}
