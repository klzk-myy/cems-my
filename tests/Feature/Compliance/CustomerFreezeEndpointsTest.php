<?php

namespace Tests\Feature\Compliance;

use App\Http\Controllers\CustomerController;
use App\Http\Requests\FreezeCustomerRequest;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class CustomerFreezeEndpointsTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = Customer::factory()->create();
    }

    /**
     * Invoke a controller method with a manually-resolved form request
     * (routes for these endpoints are wired centrally).
     */
    private function callFreeze(User $actor): void
    {
        $request = FreezeCustomerRequest::create('/customers/'.$this->customer->id.'/freeze', 'POST', [
            'reason' => 'AML hold pending STR filing',
        ]);
        $request->setContainer(app());
        $request->setLaravelSession($this->app['session.store']);
        $request->setUserResolver(fn () => $actor);
        // Emulate the framework's automatic form-request resolution.
        $request->validateResolved();

        app(CustomerController::class)->freeze($request, $this->customer);
    }

    #[Test]
    public function compliance_officer_freezes_and_unfreezes_with_audit_trail(): void
    {
        $officer = User::factory()->create(['role' => 'compliance_officer']);

        $this->callFreeze($officer);

        $this->customer->refresh();
        $this->assertTrue($this->customer->is_frozen);
        $this->assertSame('AML hold pending STR filing', $this->customer->freeze_reason);
        $this->assertNotNull($this->customer->frozen_at);

        $this->assertDatabaseHas('system_logs', ['action' => 'customer_frozen']);

        // Unfreeze path.
        $unfreezeRequest = Request::create('/customers/'.$this->customer->id.'/unfreeze', 'POST');
        $unfreezeRequest->setUserResolver(fn () => $officer);

        app(CustomerController::class)->unfreeze($unfreezeRequest, $this->customer);

        $this->customer->refresh();
        $this->assertFalse($this->customer->is_frozen);
        $this->assertNull($this->customer->frozen_at);
        $this->assertDatabaseHas('system_logs', ['action' => 'customer_unfrozen']);
    }

    #[Test]
    public function teller_is_forbidden(): void
    {
        $teller = User::factory()->create(['role' => 'teller']);

        $this->expectException(HttpException::class);

        $this->callFreeze($teller);
    }
}
