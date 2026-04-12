<?php

namespace App\Services;

use App\Models\SanctionEntry;
use App\Models\SanctionList;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SanctionsImportService
{
    public function __construct(
        protected AuditService $auditService,
    ) {}

    /**
     * Import sanctions entries from CSV file.
     *
     * @param  string  $filepath  Path to CSV file
     * @param  int  $listId  Sanction list ID
     * @param  bool  $fullRefresh  If true, removes entries not in new file
     * @return array Import statistics with change detection
     */
    public function importFromCsv(string $filepath, int $listId, bool $fullRefresh = false): array
    {
        $list = SanctionList::findOrFail($listId);
        $previousCount = $list->entry_count;

        // Get existing entry names for change detection
        $existingEntries = $this->getExistingEntries($listId);
        $importedEntries = [];

        $handle = fopen($filepath, 'r');
        if (! $handle) {
            throw new \RuntimeException("Cannot open file: {$filepath}");
        }

        $headers = fgetcsv($handle);
        $imported = 0;
        $updated = 0;

        DB::beginTransaction();

        try {
            while (($row = fgetcsv($handle)) !== false) {
                $data = array_combine($headers, $row);

                if ($data === false) {
                    Log::warning('Failed to parse CSV row', ['row' => $row]);

                    continue;
                }

                $entryData = $this->normalizeEntryData($data);
                $entryKey = $this->getEntryKey($entryData);

                // Check if entry already exists
                if (isset($existingEntries[$entryKey])) {
                    // Update existing entry
                    SanctionEntry::where('id', $existingEntries[$entryKey])->update([
                        'aliases' => $entryData['aliases'],
                        'nationality' => $entryData['nationality'],
                        'date_of_birth' => $entryData['date_of_birth'],
                        'details' => json_encode($data),
                    ]);
                    $updated++;
                } else {
                    // Create new entry
                    SanctionEntry::create([
                        'list_id' => $listId,
                        'entity_name' => $entryData['entity_name'],
                        'entity_type' => $entryData['entity_type'],
                        'aliases' => $entryData['aliases'],
                        'nationality' => $entryData['nationality'],
                        'date_of_birth' => $entryData['date_of_birth'],
                        'details' => json_encode($data),
                    ]);
                    $imported++;
                }

                $importedEntries[] = $entryKey;
            }

            fclose($handle);

            // Handle removed entries in full refresh mode
            $removed = 0;
            if ($fullRefresh) {
                $removed = $this->removeStaleEntries($listId, $importedEntries);
            }

            // Update list metadata
            $newCount = SanctionEntry::where('list_id', $listId)->count();
            $checksum = hash_file('sha256', $filepath);

            $list->update([
                'entry_count' => $newCount,
                'last_checksum' => $checksum,
            ]);

            // Calculate change statistics
            $changeStats = $this->calculateChangeStats($previousCount, $newCount, $imported, $removed);

            // Log the import
            $this->auditService->logWithSeverity(
                'sanctions_list_imported',
                [
                    'entity_type' => 'SanctionList',
                    'entity_id' => $listId,
                    'new_values' => [
                        'imported' => $imported,
                        'updated' => $updated,
                        'removed' => $removed,
                        'previous_count' => $previousCount,
                        'new_count' => $newCount,
                        'change_percentage' => $changeStats['percentage'],
                        'is_significant' => $changeStats['is_significant'],
                    ],
                ],
                $changeStats['is_significant'] ? 'WARNING' : 'INFO'
            );

            DB::commit();

            return [
                'imported' => $imported,
                'updated' => $updated,
                'removed' => $removed,
                'previous_count' => $previousCount,
                'new_count' => $newCount,
                'change_percentage' => $changeStats['percentage'],
                'is_significant_change' => $changeStats['is_significant'],
                'new_entries_detected' => $imported,
                'changes' => [
                    'new' => $imported > 0 ? $this->getNewEntriesDetails($listId, $imported) : [],
                    'removed' => $removed > 0 ? $this->getRemovedEntriesDetails($removed) : [],
                ],
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Sanctions import failed', [
                'list_id' => $listId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Import sanctions from XML (UN/OFAC format).
     */
    public function importFromXml(string $filepath, int $listId, string $format = 'UN'): array
    {
        $xml = simplexml_load_file($filepath);
        if ($xml === false) {
            throw new \RuntimeException("Failed to parse XML file: {$filepath}");
        }

        $entries = match ($format) {
            'UN' => $this->parseUnXml($xml),
            'OFAC' => $this->parseOfacXml($xml),
            default => throw new \InvalidArgumentException("Unknown XML format: {$format}"),
        };

        return $this->importEntries($entries, $listId, $filepath);
    }

    /**
     * Import sanctions from JSON.
     */
    public function importFromJson(string $filepath, int $listId): array
    {
        $content = file_get_contents($filepath);
        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Failed to parse JSON: '.json_last_error_msg());
        }

        $entries = $this->parseJsonData($data);

        return $this->importEntries($entries, $listId, $filepath);
    }

    /**
     * Get new entries for rescreening notification.
     */
    public function getNewEntriesForRescreening(int $listId, int $limit = 100): array
    {
        return SanctionEntry::where('list_id', $listId)
            ->orderBy('id', 'desc')
            ->limit($limit)
            ->get(['entity_name', 'entity_type', 'aliases', 'nationality'])
            ->toArray();
    }

    /**
     * Calculate change statistics and determine if significant.
     */
    protected function calculateChangeStats(int $previousCount, int $newCount, int $imported, int $removed): array
    {
        if ($previousCount === 0) {
            return ['percentage' => 0.0, 'is_significant' => $newCount > 100];
        }

        $netChange = abs($newCount - $previousCount);
        $percentage = ($netChange / $previousCount) * 100;

        $isSignificant = $percentage > config('sanctions.change_thresholds.significant_percentage', 10.0)
            || $imported >= config('sanctions.change_thresholds.minimum_new_entries', 5)
            || $removed >= config('sanctions.change_thresholds.minimum_removed_entries', 5);

        return [
            'percentage' => round($percentage, 2),
            'is_significant' => $isSignificant,
        ];
    }

    protected function getExistingEntries(int $listId): array
    {
        return SanctionEntry::where('list_id', $listId)
            ->get(['id', 'entity_name', 'entity_type'])
            ->keyBy(fn ($entry) => $this->getEntryKey([
                'entity_name' => $entry->entity_name,
                'entity_type' => $entry->entity_type,
            ]))
            ->map(fn ($entry) => $entry->id)
            ->toArray();
    }

    protected function getEntryKey(array $data): string
    {
        return strtolower(trim($data['entity_name'])).'|'.strtolower(trim($data['entity_type'] ?? 'Individual'));
    }

    protected function normalizeEntryData(array $data): array
    {
        return [
            'entity_name' => trim($data['name'] ?? $data['entity_name'] ?? ''),
            'entity_type' => $data['entity_type'] ?? 'Individual',
            'aliases' => isset($data['aliases']) && ! empty($data['aliases']) ? json_encode(explode(',', $data['aliases'])) : null,
            'nationality' => $data['nationality'] ?? null,
            'date_of_birth' => ! empty($data['date_of_birth']) ? $data['date_of_birth'] : null,
        ];
    }

    protected function removeStaleEntries(int $listId, array $currentEntries): int
    {
        // Get all entries for this list
        $existingEntries = SanctionEntry::where('list_id', $listId)->get(['id', 'entity_name', 'entity_type']);

        // Convert current entries to a lookup set for faster checking
        $currentEntriesSet = array_flip(array_map('strtolower', $currentEntries));

        $toRemove = [];
        foreach ($existingEntries as $entry) {
            $key = strtolower(trim($entry->entity_name)).'|'.strtolower(trim($entry->entity_type));
            if (! isset($currentEntriesSet[$key])) {
                $toRemove[] = $entry->id;
            }
        }

        $count = count($toRemove);

        if ($count > 0) {
            SanctionEntry::whereIn('id', $toRemove)->delete();
        }

        return $count;
    }

    protected function getNewEntriesDetails(int $listId, int $count): array
    {
        return SanctionEntry::where('list_id', $listId)
            ->orderBy('id', 'desc')
            ->limit(min($count, 10)) // Return first 10
            ->pluck('entity_name')
            ->toArray();
    }

    protected function getRemovedEntriesDetails(int $count): array
    {
        return ["{$count} entries removed"];
    }

    protected function importEntries(array $entries, int $listId, string $filepath): array
    {
        // Create temporary CSV for unified processing
        $csvPath = sys_get_temp_dir().'/sanctions_import_'.uniqid().'.csv';
        $handle = fopen($csvPath, 'w');

        fputcsv($handle, ['name', 'entity_type', 'aliases', 'nationality', 'date_of_birth']);

        foreach ($entries as $entry) {
            fputcsv($handle, [
                $entry['entity_name'],
                $entry['entity_type'] ?? 'Individual',
                $entry['aliases'] ?? '',
                $entry['nationality'] ?? '',
                $entry['date_of_birth'] ?? '',
            ]);
        }

        fclose($handle);

        $result = $this->importFromCsv($csvPath, $listId, true);

        unlink($csvPath);

        return $result;
    }

    protected function parseUnXml(\SimpleXMLElement $xml): array
    {
        $entries = [];
        // UN format parsing - adjust based on actual UN schema
        foreach ($xml->xpath('//INDIVIDUAL') as $individual) {
            $entries[] = [
                'entity_name' => trim((string) ($individual->NAME ?? $individual->FIRST_NAME.' '.$individual->SECOND_NAME)),
                'entity_type' => 'Individual',
                'nationality' => (string) ($individual->NATIONALITY ?? ''),
                'aliases' => '',
            ];
        }
        foreach ($xml->xpath('//ENTITY') as $entity) {
            $entries[] = [
                'entity_name' => trim((string) $entity->NAME),
                'entity_type' => 'Entity',
                'nationality' => '',
                'aliases' => '',
            ];
        }

        return $entries;
    }

    protected function parseOfacXml(\SimpleXMLElement $xml): array
    {
        $entries = [];
        // OFAC SDN format
        foreach ($xml->publishInformation->children() as $child) {
            // OFAC has a different structure
        }

        // Parse publish information
        foreach ($xml->xpath('//sdnEntry') as $entry) {
            $name = (string) ($entry->lastName ?? '');
            if (isset($entry->firstName)) {
                $name = (string) $entry->firstName.' '.$name;
            }
            if (empty($name) && isset($entry->lastName)) {
                $name = (string) $entry->lastName;
            }

            $entries[] = [
                'entity_name' => trim($name),
                'entity_type' => ((string) ($entry->sdnType ?? '')) === 'Individual' ? 'Individual' : 'Entity',
                'nationality' => '',
                'aliases' => '',
            ];
        }

        return $entries;
    }

    protected function parseJsonData(array $data): array
    {
        $entries = [];

        // Handle EU format
        if (isset($data['result'])) {
            foreach ($data['result'] as $item) {
                $entries[] = [
                    'entity_name' => $item['name'] ?? '',
                    'entity_type' => ($item['type'] ?? '') === 'person' ? 'Individual' : 'Entity',
                    'nationality' => $item['country'] ?? '',
                    'aliases' => '',
                ];
            }
        }

        return $entries;
    }
}
