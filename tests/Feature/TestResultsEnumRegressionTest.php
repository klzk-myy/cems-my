<?php

namespace Tests\Feature;

use App\Enums\TestResultStatus;
use App\Http\Controllers\TestResultsController;
use App\Models\TestResult;
use App\Services\System\TestRunnerService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Artisan;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TestResultsEnumRegressionTest extends TestCase
{
    use DatabaseTransactions;

    #[Test]
    public function run_tests_command_returns_failure_exit_code_when_status_is_failed_enum(): void
    {
        // Regression: $result->status is a TestResultStatus enum; comparing it
        // to the string 'failed' was always false, so failed runs returned
        // exit code 0 (broken CI exit codes).
        $testResult = TestResult::factory()->create(['status' => TestResultStatus::Failed]);

        $runner = $this->createMock(TestRunnerService::class);
        $runner->method('runTests')->willReturn($testResult);
        $this->app->instance(TestRunnerService::class, $runner);

        $exitCode = Artisan::call('test:run', ['suite' => 'Navigation']);

        $this->assertSame(1, $exitCode);
    }

    #[Test]
    public function run_tests_command_returns_success_exit_code_when_passed(): void
    {
        $testResult = TestResult::factory()->create(['status' => TestResultStatus::Passed]);

        $runner = $this->createMock(TestRunnerService::class);
        $runner->method('runTests')->willReturn($testResult);
        $this->app->instance(TestRunnerService::class, $runner);

        $exitCode = Artisan::call('test:run', ['suite' => 'Navigation']);

        $this->assertSame(0, $exitCode);
    }

    #[Test]
    public function latest_status_serializes_enum_backing_value_in_json(): void
    {
        // Regression: status is enum-cast, so json_encode of the raw enum
        // produced {} — the widget never saw "passed"/"failed".
        TestResult::factory()->create(['status' => TestResultStatus::Passed]);

        $controller = new TestResultsController(app(TestRunnerService::class));
        $response = $controller->latestStatus();

        $this->assertSame('passed', $response->getData()->status);
    }
}
