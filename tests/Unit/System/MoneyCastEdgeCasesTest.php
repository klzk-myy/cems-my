<?php

namespace Tests\Unit\System;

use App\Casts\MoneyCast;
use App\Exceptions\Domain\MathValidationException;
use App\Services\System\MathService;
use App\Support\BcmathHelper;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Pure logic tests for defect fixes. These tests do not touch the database,
 * so they run in any environment (no RefreshDatabase required).
 */
class MoneyCastEdgeCasesTest extends TestCase
{
    #[Test]
    public function negative_values_rounding_to_zero_become_positive_zero(): void
    {
        $cast = new MoneyCast(4);

        $this->assertSame('0.0000', $cast->set(null, 'amount', '-0.00001', []));
        $this->assertSame('0.0000', $cast->set(null, 'amount', -0.000001, []));
        $this->assertSame('0', (new MoneyCast(0))->set(null, 'quantity', '-0.4', []));
    }

    #[Test]
    public function scientific_notation_inputs_are_normalized(): void
    {
        $cast = new MoneyCast(4);

        $this->assertSame('1500.0000', $cast->set(null, 'amount', '1.5E+3', []));
        $this->assertSame('0.0250', $cast->set(null, 'amount', 2.5E-2, []));
        $this->assertSame('-150.0000', $cast->set(null, 'amount', '-1.5E+2', []));
    }

    #[Test]
    public function scale_variants_still_round_correctly(): void
    {
        $this->assertSame('3.123457', (new MoneyCast(6))->set(null, 'rate', '3.123456789', []));
        $this->assertSame('13', (new MoneyCast(0))->set(null, 'quantity', '12.6', []));
    }

    #[Test]
    public function bcmath_helper_matches_math_service_outputs(): void
    {
        $math = new MathService(4);

        $this->assertSame($math->add('1.2345', '2.3456'), BcmathHelper::add('1.2345', '2.3456'));
        $this->assertSame($math->subtract('1.0000', '2.3456'), BcmathHelper::subtract('1.0000', '2.3456'));
        $this->assertSame($math->multiply('1.2345', '2.0000'), BcmathHelper::multiply('1.2345', '2.0000'));
        $this->assertSame($math->divide('1.0000', '8.0000'), BcmathHelper::divide('1.0000', '8.0000'));
        $this->assertSame($math->compare('2.0000', '1.9999'), BcmathHelper::compare('2.0000', '1.9999'));
        $this->assertSame($math->abs('-5.0000'), BcmathHelper::abs('-5.0000'));
    }

    #[Test]
    public function bcmath_helper_comparisons_and_scale(): void
    {
        $this->assertTrue(BcmathHelper::gt('2.0000', '1.9999'));
        $this->assertTrue(BcmathHelper::lte('1.0000', '1.0000'));
        $this->assertFalse(BcmathHelper::lt('1.0000', '1.0000'));
        $this->assertSame('3.00', BcmathHelper::add('1.5', '1.5', 2));

        BcmathHelper::setScale(6);
        $this->assertSame('3.000001', BcmathHelper::add('1.000001', '2'));
        BcmathHelper::setScale(4);
    }

    #[Test]
    public function bcmath_helper_divide_by_zero_throws(): void
    {
        $this->expectException(MathValidationException::class);

        BcmathHelper::divide('1', '0');
    }
}
