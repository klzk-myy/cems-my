<?php

namespace App\Services;

class MathService
{
    protected int $scale = 6;

    public function __construct(int $scale = 6)
    {
        $this->scale = $scale;
    }

    public function add(string $a, string $b): string
    {
        return bcadd($a, $b, $this->scale);
    }

    public function subtract(string $a, string $b): string
    {
        return bcsub($a, $b, $this->scale);
    }

    public function multiply(string $a, string $b): string
    {
        return bcmul($a, $b, $this->scale);
    }

    public function divide(string $a, string $b): string
    {
        if (bccomp($b, '0', $this->scale) === 0) {
            throw new \InvalidArgumentException('Division by zero');
        }
        return bcdiv($a, $b, $this->scale);
    }

    public function compare(string $a, string $b): int
    {
        return bccomp($a, $b, $this->scale);
    }

    public function calculateAverageCost(
        string $oldBalance,
        string $oldAvgCost,
        string $transactionAmount,
        string $transactionRate
    ): string {
        $oldValue = $this->multiply($oldBalance, $oldAvgCost);
        $newValue = $this->multiply($transactionAmount, $transactionRate);
        $totalValue = $this->add($oldValue, $newValue);
        $newBalance = $this->add($oldBalance, $transactionAmount);
        return $this->divide($totalValue, $newBalance);
    }

    public function calculateRevaluationPnl(
        string $positionAmount,
        string $oldRate,
        string $newRate
    ): string {
        $rateDiff = $this->subtract($newRate, $oldRate);
        return $this->multiply($positionAmount, $rateDiff);
    }

    public function calculateTransactionAmount(
        string $foreignAmount,
        string $rate,
        string $type = 'Buy'
    ): string {
        $amount = $this->multiply($foreignAmount, $rate);
        if ($type === 'Sell') {
            return $amount;
        }
        return $amount;
    }
}
