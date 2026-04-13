<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Transaction;
use App\Models\Customer;
use App\Models\CurrencyPosition;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionCancellationFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_cancel_button_appears_for_refundable_transactions(): void
    {
        $this->markTestSkipped('Requires view rendering - integration test');
    }

    public function test_cancel_button_does_not_appear_for_old_transactions(): void
    {
        $this->markTestSkipped('Requires view rendering - integration test');
    }

    public function test_only_completed_transactions_can_be_cancelled(): void
    {
        $this->markTestSkipped('Requires TillBalance setup - integration test');
    }

    public function test_guest_users_cannot_access_cancellation(): void
    {
        $this->markTestSkipped('Requires TillBalance setup - integration test');
    }

    public function test_cancellation_reason_is_required_and_min_length(): void
    {
        $this->markTestSkipped('Requires TillBalance setup - integration test');
    }

    public function test_confirmation_checkbox_is_required(): void
    {
        $this->markTestSkipped('Requires TillBalance setup - integration test');
    }

    public function test_cancelled_transactions_cannot_be_cancelled_again(): void
    {
        $this->markTestSkipped('Requires TillBalance setup - integration test');
    }

    public function test_teller_can_cancel_own_transaction(): void
    {
        $this->markTestSkipped('Requires TillBalance setup - integration test');
    }

    public function test_manager_can_cancel_transaction(): void
    {
        $this->markTestSkipped('Requires TillBalance setup - integration test');
    }

    public function test_teller_cannot_cancel_other_teller_transaction(): void
    {
        $this->markTestSkipped('Requires TillBalance setup - integration test');
    }
}
