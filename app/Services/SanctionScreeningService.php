<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class SanctionScreeningService
{
    protected float $matchThreshold = 0.80;

    public function screenName(string $name): array
    {
        $matches = [];
        $name = strtolower(trim($name));
        $nameParts = explode(' ', $name);

        // Query sanction entries
        $entries = DB::table('sanction_entries')
            ->select('id', 'entity_name', 'aliases', 'entity_type')
            ->get();

        foreach ($entries as $entry) {
            $entryName = strtolower($entry->entity_name);
            $aliases = $entry->aliases ? strtolower($entry->aliases) : '';

            $score = $this->calculateSimilarity($name, $entryName);
            $aliasScore = $this->checkAliases($name, $aliases);

            $maxScore = max($score, $aliasScore);

            if ($maxScore >= $this->matchThreshold) {
                $matches[] = [
                    'entry_id' => $entry->id,
                    'entity_name' => $entry->entity_name,
                    'entity_type' => $entry->entity_type,
                    'match_score' => round($maxScore, 2),
                    'match_type' => $score > $aliasScore ? 'Name' : 'Alias',
                ];
            }
        }

        // Sort by match score descending
        usort($matches, fn ($a, $b) => $b['match_score'] <=> $a['match_score']);

        return $matches;
    }

    protected function calculateSimilarity(string $str1, string $str2): float
    {
        $distance = levenshtein($str1, $str2);
        $maxLen = max(strlen($str1), strlen($str2));

        if ($maxLen === 0) {
            return 1.0;
        }

        return 1 - ($distance / $maxLen);
    }

    protected function checkAliases(string $name, string $aliases): float
    {
        if (empty($aliases)) {
            return 0.0;
        }

        $aliasList = array_map('trim', explode(',', $aliases));
        $maxScore = 0.0;

        foreach ($aliasList as $alias) {
            $score = $this->calculateSimilarity($name, $alias);
            $maxScore = max($maxScore, $score);
        }

        return $maxScore;
    }

    public function importSanctionList(string $filePath, int $uploadedBy): int
    {
        $listId = DB::table('sanction_lists')->insertGetId([
            'name' => basename($filePath),
            'list_type' => $this->detectListType($filePath),
            'source_file' => basename($filePath),
            'uploaded_by' => $uploadedBy,
            'uploaded_at' => now(),
        ]);

        $count = $this->processCsvFile($filePath, $listId);

        return $count;
    }

    protected function detectListType(string $filePath): string
    {
        $name = strtolower($filePath);
        if (str_contains($name, 'unscr')) {
            return 'UNSCR';
        }
        if (str_contains($name, 'moha')) {
            return 'MOHA';
        }

        return 'Internal';
    }

    protected function processCsvFile(string $filePath, int $listId): int
    {
        $handle = fopen($filePath, 'r');
        if (! $handle) {
            throw new \RuntimeException('Cannot open file: '.$filePath);
        }

        $headers = fgetcsv($handle);
        $count = 0;

        while (($row = fgetcsv($handle)) !== false) {
            $data = array_combine($headers, $row);

            DB::table('sanction_entries')->insert([
                'list_id' => $listId,
                'entity_name' => $data['name'] ?? '',
                'entity_type' => $data['entity_type'] ?? 'Individual',
                'aliases' => $data['aliases'] ?? null,
                'nationality' => $data['nationality'] ?? null,
                'date_of_birth' => $data['date_of_birth'] ?? null,
                'details' => json_encode($data),
            ]);

            $count++;
        }

        fclose($handle);

        return $count;
    }
}
