<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\SanctionScreeningService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SanctionController extends Controller
{
    public function __construct(
        protected SanctionScreeningService $screeningService
    ) {}

    /**
     * Search sanctions list by name.
     */
    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|min:3',
        ]);

        $matches = $this->screeningService->screenName($validated['name']);

        return response()->json([
            'success' => true,
            'query' => $validated['name'],
            'matches' => $matches,
            'count' => count($matches),
        ]);
    }

    /**
     * Upload sanctions list file.
     */
    public function upload(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:10240',
        ]);

        $file = $request->file('file');
        $path = $file->store('sanction_lists');

        $count = $this->screeningService->importSanctionList(
            storage_path('app/'.$path),
            auth()->id()
        );

        return response()->json([
            'success' => true,
            'message' => 'Sanction list imported successfully.',
            'entries_imported' => $count,
        ]);
    }
}
