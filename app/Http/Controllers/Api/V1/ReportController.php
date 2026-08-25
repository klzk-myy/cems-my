<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Traits\ApiResponse;
use App\Http\Controllers\Concerns\EnsuresManagerOrAdmin;
use App\Http\Controllers\Controller;
use App\Services\System\DocumentStorageService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    use ApiResponse;
    use EnsuresManagerOrAdmin;

    public function __construct(
        protected DocumentStorageService $documentStorageService
    ) {}

    /**
     * Download a generated report.
     */
    public function download(string $filename): BinaryFileResponse|StreamedResponse|JsonResponse
    {
        if ($response = $this->requireManagerOrAdminResponse()) {
            return $response;
        }

        // Sanitize filename to prevent path traversal. basename() strips any
        // directory component, so a relative "../../etc/passwd" becomes
        // "passwd" and is confined to the reports directory.
        $filename = basename($filename);

        $filepath = "reports/{$filename}";

        if (! $this->documentStorageService->exists($filepath)) {
            return $this->notFoundResponse('Report not found.');
        }

        return $this->documentStorageService->download($filepath);
    }
}
