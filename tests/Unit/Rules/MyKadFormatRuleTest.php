<?php

namespace Tests\Unit\Rules;

use App\Rules\MyKadFormatRule;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MyKadFormatRuleTest extends TestCase
{
    private MyKadFormatRule $rule;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rule = new MyKadFormatRule;
    }

    private function assertPasses(string $id, ?string $idType = 'MyKad'): void
    {
        $this->mockRequestInput(['id_type' => $idType]);

        $failCalled = false;

        $this->rule->validate('id_number', $id, function () use (&$failCalled): void {
            $failCalled = true;
        });

        $this->assertFalse($failCalled, "Expected MyKad {$id} to pass validation.");
    }

    private function assertFails(string $id, string $expectedMessage, ?string $idType = 'MyKad'): void
    {
        $this->mockRequestInput(['id_type' => $idType]);

        $caught = null;

        $this->rule->validate('id_number', $id, function (string $failMessage) use (&$caught): void {
            $caught = $failMessage;
        });

        $this->assertSame($expectedMessage, $caught, "Expected failure with: {$expectedMessage}; got: {$caught}");
    }

    private function mockRequestInput(array $input): void
    {
        $app = app();

        $original = $app->get('request');

        $request = Request::create('/', 'GET', $input);

        $app->instance('request', $request);

        $this->beforeApplicationDestroyed(function () use ($app, $original): void {
            $app->instance('request', $original);
        });
    }

    #[Test]
    #[DataProvider('validMyKadProvider')]
    public function it_accepts_valid_mykad_numbers(string $id, ?string $idType = 'MyKad'): void
    {
        $this->assertPasses($id, $idType);
    }

    public static function validMyKadProvider(): array
    {
        return [
            'january first' => ['900101-01-1234'],
            'march fifteenth' => ['750315-05-6789'],
            'december thirty first' => ['991231-14-0000'],
            'february twenty ninth' => ['650229-02-1234'],
            'passport type is not validated' => ['1234567-89-0000', 'Passport'],
            'others type is not validated' => ['000000-99-0000', 'Others'],
        ];
    }

    #[Test]
    #[DataProvider('invalidMyKadProvider')]
    public function it_rejects_invalid_mykad_numbers(string $id, string $expectedMessage): void
    {
        $this->assertFails($id, $expectedMessage);
    }

    public static function invalidMyKadProvider(): array
    {
        return [
            'wrong format' => [
                '900123-01-234',
                'MyKad ID must be in format XXXXXX-XX-XXXX (e.g., 900123-01-2345)',
            ],
            'month out of range' => [
                '901313-01-2345',
                'MyKad ID contains invalid month in birthdate.',
            ],
            'day out of range' => [
                '900100-01-2345',
                'MyKad ID contains invalid day in birthdate.',
            ],
            'april thirty one (invalid)' => [
                '900431-01-2345',
                'MyKad ID contains invalid day for month 4.',
            ],
            'february thirty (invalid)' => [
                '900230-01-2345',
                'MyKad ID contains invalid day for month 2.',
            ],
        ];
    }

    #[Test]
    public function it_rejects_non_string_values(): void
    {
        $this->mockRequestInput(['id_type' => 'MyKad']);

        $caught = null;

        $this->rule->validate('id_number', null, function (string $failMessage) use (&$caught): void {
            $caught = $failMessage;
        });

        $this->assertSame('MyKad ID must be a string.', $caught);
    }
}
