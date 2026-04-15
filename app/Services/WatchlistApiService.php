<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\SanctionEntry;
use App\Models\ScreeningResult;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class WatchlistApiService
{
    protected float $blockThreshold = 0.90;

    protected float $flagThreshold = 0.75;

    public function __construct(
        protected SanctionScreeningService $sanctionScreeningService,
    ) {
        $this->blockThreshold = (float) config('sanctions.screening_block_threshold', 0.90);
        $this->flagThreshold = (float) config('sanctions.screening_flag_threshold', 0.75);
    }

    public function screenNameEnhanced(string $name): array
    {
        $name = trim($name);
        $results = [];

        $levenshteinResult = $this->screenNameLevenshtein($name);
        if ($levenshteinResult['score'] >= $this->flagThreshold) {
            $results[] = $levenshteinResult;
        }

        $soundexResult = $this->screenNameSoundex($name);
        if ($soundexResult['score'] >= $this->flagThreshold) {
            $results[] = $soundexResult;
        }

        $metaphoneResult = $this->screenNameMetaphone($name);
        if ($metaphoneResult['score'] >= $this->flagThreshold) {
            $results[] = $metaphoneResult;
        }

        $tokenResult = $this->screenNameToken($name);
        if ($tokenResult['score'] >= $this->flagThreshold) {
            $results[] = $tokenResult;
        }

        if (empty($results)) {
            return [
                'action' => 'clear',
                'score' => 0.0,
                'match_type' => null,
                'match_info' => null,
            ];
        }

        usort($results, fn ($a, $b) => $b['score'] <=> $a['score']);
        $bestMatch = $results[0];

        return [
            'action' => $this->determineAction($bestMatch['score']),
            'score' => $bestMatch['score'],
            'match_type' => $bestMatch['match_type'],
            'match_info' => $bestMatch['match_info'],
            'all_matches' => $results,
        ];
    }

    public function screenNameLevenshtein(string $name): array
    {
        $sanctionEntries = SanctionEntry::where('status', 'active')
            ->whereNotNull('normalized_name')
            ->get();

        $normalizedName = $this->normalizeName($name);
        $bestMatch = ['score' => 0, 'entry' => null, 'matched_fields' => null];

        foreach ($sanctionEntries as $entry) {
            $score = $this->calculateLevenshteinScore($normalizedName, $entry->normalized_name);
            if ($score > $bestMatch['score']) {
                $bestMatch = [
                    'score' => $score,
                    'entry' => $entry,
                    'matched_fields' => ['normalized_name' => $entry->normalized_name],
                ];
            }
        }

        return [
            'match_type' => 'levenshtein',
            'score' => $bestMatch['score'],
            'entry' => $bestMatch['entry'],
            'match_info' => $bestMatch['score'] > 0 ? $bestMatch : null,
        ];
    }

    public function screenNameSoundex(string $name): array
    {
        $sanctionEntries = SanctionEntry::where('status', 'active')
            ->whereNotNull('soundex_code')
            ->get();

        $soundex = soundex($name);
        $bestMatch = ['score' => 0, 'entry' => null, 'matched_fields' => null];

        foreach ($sanctionEntries as $entry) {
            if ($entry->soundex_code === $soundex) {
                $score = 1.0;
                if ($score > $bestMatch['score']) {
                    $bestMatch = [
                        'score' => $score,
                        'entry' => $entry,
                        'matched_fields' => ['soundex' => $soundex],
                    ];
                }
            }
        }

        return [
            'match_type' => 'soundex',
            'score' => $bestMatch['score'],
            'entry' => $bestMatch['entry'],
            'match_info' => $bestMatch['score'] > 0 ? $bestMatch : null,
        ];
    }

    public function screenNameMetaphone(string $name): array
    {
        $sanctionEntries = SanctionEntry::where('status', 'active')
            ->whereNotNull('metaphone_code')
            ->get();

        $metaphone = metaphone($name);
        $bestMatch = ['score' => 0, 'entry' => null, 'matched_fields' => null];

        foreach ($sanctionEntries as $entry) {
            if ($entry->metaphone_code === $metaphone) {
                $score = 1.0;
                if ($score > $bestMatch['score']) {
                    $bestMatch = [
                        'score' => $score,
                        'entry' => $entry,
                        'matched_fields' => ['metaphone' => $metaphone],
                    ];
                }
            }
        }

        return [
            'match_type' => 'metaphone',
            'score' => $bestMatch['score'],
            'entry' => $bestMatch['entry'],
            'match_info' => $bestMatch['score'] > 0 ? $bestMatch : null,
        ];
    }

    public function screenNameToken(string $name): array
    {
        $sanctionEntries = SanctionEntry::where('status', 'active')
            ->get();

        $tokens = $this->getNameTokens($name);
        $normalizedName = $this->normalizeName($name);
        $bestMatch = ['score' => 0, 'entry' => null, 'matched_fields' => null];

        foreach ($sanctionEntries as $entry) {
            $entryTokens = $this->getNameTokens($entry->normalized_name ?? $entry->sanctioned_name);
            $matchedTokens = array_intersect($tokens, $entryTokens);
            $tokenScore = count($matchedTokens) / max(count($tokens), count($entryTokens));

            $directScore = $this->calculateLevenshteinScore($normalizedName, $entry->normalized_name ?? '');

            $combinedScore = ($tokenScore * 0.4) + ($directScore * 0.6);

            if ($combinedScore > $bestMatch['score']) {
                $bestMatch = [
                    'score' => $combinedScore,
                    'entry' => $entry,
                    'matched_fields' => [
                        'matched_tokens' => array_values($matchedTokens),
                        'query_tokens' => $tokens,
                        'entry_tokens' => $entryTokens,
                    ],
                ];
            }
        }

        return [
            'match_type' => 'token',
            'score' => $bestMatch['score'],
            'entry' => $bestMatch['entry'],
            'match_info' => $bestMatch['score'] > 0 ? $bestMatch : null,
        ];
    }

    public function screenCustomer(Customer $customer): array
    {
        $namesToScreen = $this->getCustomerNamesToScreen($customer);

        $results = [];
        foreach ($namesToScreen as $nameData) {
            $result = $this->screenNameEnhanced($nameData['name']);
            if ($result['score'] > 0) {
                $result['name_type'] = $nameData['type'];
                $results[] = $result;
            }
        }

        if (empty($results)) {
            return [
                'action' => 'clear',
                'score' => 0.0,
                'match_type' => null,
                'match_info' => null,
            ];
        }

        usort($results, fn ($a, $b) => $b['score'] <=> $a['score']);
        $bestMatch = $results[0];

        $screeningResult = $this->saveScreeningResult(
            customer: $customer,
            screenedName: $customer->full_name,
            result: $bestMatch,
            sanctionEntry: $bestMatch['match_info']['entry'] ?? null
        );

        return [
            'action' => $bestMatch['action'],
            'score' => $bestMatch['score'],
            'match_type' => $bestMatch['match_type'],
            'match_info' => $bestMatch['match_info'],
            'screening_result_id' => $screeningResult?->id,
        ];
    }

    public function screenTransaction(Transaction $transaction): array
    {
        $customer = $transaction->customer;
        $result = $this->screenCustomer($customer);

        if ($result['action'] === 'block' && isset($result['screening_result_id'])) {
            $this->freezeTransactionIfMatched($transaction, $result);
        }

        return $result;
    }

    public function freezeTransactionIfMatched(Transaction $transaction, array $result): void
    {
        if ($result['action'] !== 'block') {
            return;
        }

        DB::transaction(function () use ($transaction) {
            $transaction->status = 'pending_review';
            $transaction->compliance_flags = array_merge(
                $transaction->compliance_flags ?? [],
                ['sanction_match_block' => true]
            );
            $transaction->save();
        });
    }

    public function getScreeningHistory(Customer $customer): Collection
    {
        return ScreeningResult::where('customer_id', $customer->id)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    protected function normalizeName(string $name): string
    {
        $name = mb_strtolower(trim($name));
        $name = preg_replace('/[^a-z\s\'-]/i', '', $name);
        $name = preg_replace('/\s+/', ' ', $name);

        return $name;
    }

    protected function calculateLevenshteinScore(string $name1, string $name2): float
    {
        if ($name1 === $name2) {
            return 1.0;
        }

        if (empty($name1) || empty($name2)) {
            return 0.0;
        }

        $maxLen = max(strlen($name1), strlen($name2));
        $distance = levenshtein($name1, $name2);

        return 1.0 - ($distance / $maxLen);
    }

    protected function getNameTokens(string $name): array
    {
        $normalized = $this->normalizeName($name);
        $tokens = explode(' ', $normalized);
        $tokens = array_filter($tokens, fn ($t) => strlen($t) > 1);

        return array_values($tokens);
    }

    protected function getCustomerNamesToScreen(Customer $customer): array
    {
        $names = [
            ['name' => $customer->full_name, 'type' => 'primary'],
        ];

        if ($customer->alternative_names) {
            foreach ((array) $customer->alternative_names as $altName) {
                $names[] = ['name' => $altName, 'type' => 'alternative'];
            }
        }

        return $names;
    }

    protected function determineAction(float $score): string
    {
        if ($score >= $this->blockThreshold) {
            return 'block';
        }

        if ($score >= $this->flagThreshold) {
            return 'flag';
        }

        return 'clear';
    }

    protected function saveScreeningResult(
        ?Customer $customer = null,
        ?Transaction $transaction = null,
        string $screenedName = '',
        array $result = [],
        ?SanctionEntry $sanctionEntry = null
    ): ?ScreeningResult {
        if ($result['score'] < $this->flagThreshold) {
            return null;
        }

        return ScreeningResult::create([
            'customer_id' => $customer?->id,
            'transaction_id' => $transaction?->id,
            'screened_name' => $screenedName,
            'sanction_entry_id' => $sanctionEntry?->id,
            'match_type' => $result['match_type'],
            'match_score' => $result['score'],
            'action_taken' => $result['action'],
            'result' => $result['action'],
            'matched_fields' => $result['match_info']['matched_fields'] ?? null,
        ]);
    }
}
