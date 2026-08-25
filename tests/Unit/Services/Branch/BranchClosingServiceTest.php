<?php

namespace Tests\Unit\Services\Branch;

use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\BranchClosureWorkflow;
use App\Models\User;
use App\Services\Branch\BranchClosingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BranchClosingServiceTest extends TestCase
{
    use RefreshDatabase;

    private BranchClosingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(BranchClosingService::class);
    }

    #[Test]
    public function initiate_closure_creates_workflow(): void
    {
        $branch = Branch::factory()->create();
        $user = User::factory()->create(['role' => UserRole::Manager]);

        $before = BranchClosureWorkflow::count();

        $workflow = $this->service->initiateClosure($branch, $user);

        $this->assertInstanceOf(BranchClosureWorkflow::class, $workflow);
        $this->assertSame($branch->id, $workflow->branch_id);
        $this->assertSame($before + 1, BranchClosureWorkflow::count());
    }

    #[Test]
    public function get_active_workflow_returns_null_when_no_active_closure(): void
    {
        $branch = Branch::factory()->create();

        $this->assertNull($this->service->getActiveWorkflow($branch));
    }
}
