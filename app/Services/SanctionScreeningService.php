<?php

namespace App\Services;

use App\Models\Customer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SanctionScreeningService
{
    protected float $matchThreshold = 0.80;

    public function __construct(
        protected AuditService $auditService,
    ) {}

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

                // Log sanction hit
                $this->auditService->logSanctionEvent('sanction_screening_hit', $entry->id, [
                    'entity_type' => $entry->entity_type,
                    'new' => [
                        'entity_name' => $entry->entity_name,
                        'match_score' => round($maxScore, 2),
                        'match_type' => $score > $aliasScore ? 'Name' : 'Alias',
                    ],
                ]);
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

    /**
     * Log a sanction block override event.
     *
     * Called when a user overrides a sanction block/flag on a customer or transaction.
     *
     * @param  int  $entityId  Entity ID (customer or transaction ID)
     * @param  string  $entityType  Entity type (Customer, Transaction, etc.)
     * @param  array  $data  Override data including reason
     */
    public function logBlockOverride(int $entityId, string $entityType, array $data = []): void
    {
        $this->auditService->logSanctionEvent('sanction_block_overridden', $entityId, [
            'entity_type' => $entityType,
            'new' => $data,
        ]);
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

    /**
     * Check customer for sanctions - returns structured result
     */
    public function checkCustomer(Customer $customer): SanctionCheckResult
    {
        $fullName = $customer->full_name;
        
        // Escape LIKE wildcards
        $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $fullName);
        $pattern = '%' . $escaped . '%';
        
        // Check against sanction entries
        $matches = DB::table('sanction_entries')
            ->whereRaw("entity_name LIKE ?", [$pattern])
            ->orWhereRaw("aliases LIKE ?", [$pattern])
            ->get();
        
        foreach ($matches as $match) {
            $similarity = $this->calculateSimilarity(
                strtolower($fullName),
                strtolower($match->entity_name)
            );
            
            // Block if similarity > 80%
            if ($similarity >= 0.80) {
                Log::warning('Sanctions match detected', [
                    'customer_id' => $customer->id,
                    'customer_name' => $fullName,
                    'matched_entity' => $match->entity_name,
                    'similarity' => $similarity,
                    'list_name' => $match->list_name ?? 'Unknown',
                ]);
                
                // Audit log
                $this->auditService->logSanctionEvent('sanction_screening_hit', $customer->id, [
                    'customer_name' => $fullName,
                    'matched_entity' => $match->entity_name,
                    'similarity' => $similarity,
                    'action' => 'blocked',
                ]);
                
                return SanctionCheckResult::blocked(
                    'Sanctions list match detected. Transaction blocked.',
                    $similarity,
                    $match->entity_name
                );
            }
            
            // Flag for review if similarity > 60%
            if ($similarity >= 0.60) {
                $this->auditService->logSanctionEvent('sanction_screening_flag', $customer->id, [
                    'customer_name' => $fullName,
                    'matched_entity' => $match->entity_name,
                    'similarity' => $similarity,
                    'action' => 'flagged',
                ]);
                
                // Create compliance flag
                \App\Models\FlaggedTransaction::create([
                    'customer_id' => $customer->id,
                    'flag_type' => \App\Enums\ComplianceFlagType::SanctionMatch,
                    'severity' => 'warning',
                    'description' => "Possible sanctions match: {$match->entity_name} ({$similarity}% similar)",
                    'status' => 'open',
                ]);
            }
        }
        
        return SanctionCheckResult::passed();
    }
}
