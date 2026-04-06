<?php

namespace App\Http\Controllers;

use App\Services\SanctionScreeningService;
use Illuminate\Http\Request;

class SanctionController extends Controller
{
    protected SanctionScreeningService $screeningService;

    public function __construct(SanctionScreeningService $screeningService)
    {
        $this->screeningService = $screeningService;
    }

    public function search(Request $request)
    {
        $request->validate([
            'name' => 'required|string|min:3',
        ]);

        $matches = $this->screeningService->screenName($request->name);

        return response()->json([
            'query' => $request->name,
            'matches' => $matches,
            'count' => count($matches),
        ]);
    }

    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:10240',
        ]);

        $file = $request->file('file');
        $path = $file->store('sanction_lists');

        $count = $this->screeningService->importSanctionList(
            storage_path('app/'.$path),
            auth()->id()
        );

        return response()->json([
            'message' => 'Sanction list imported successfully',
            'entries_imported' => $count,
        ]);
    }
}
