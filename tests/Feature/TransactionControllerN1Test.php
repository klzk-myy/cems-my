<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TransactionControllerN1Test extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function transaction_index_eager_loads_journal_entry(): void
    {
        $branch = Branch::factory()->create();
        $user = User::factory()->for($branch, 'branch')->create(['role' => UserRole::Teller]);

        Transaction::factory()->count(5)->create([
            'branch_id' => $branch->id,
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->get('/transactions');

        $response->assertStatus(200);
        $view = $response->viewData('transactions');

        foreach ($view->items() as $transaction) {
            $this->assertInstanceOf(Transaction::class, $transaction);
            $this->assertTrue(
                $transaction->relationLoaded('journalEntry'),
                "Transaction #{$transaction->id} should have journalEntry eager-loaded"
            );
        }
    }
}
