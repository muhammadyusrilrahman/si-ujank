<?php

namespace App\Support;

class MoneyFormatter
{
    /**
     * Format numeric value into Indonesian Rupiah string.
     */
    public static function rupiah(mixed $amount, int $decimals = 2, bool $withSymbol = true): string
    {
        if ($amount === null || $amount === '') {
            $amount = 0;
        }

        if (! is_numeric($amount)) {
            $amount = (float) $amount;
        }

        $formatted = number_format((float) $amount, $decimals, ',', '.');

        return $withSymbol ? 'Rp ' . $formatted : $formatted;
    }
}
