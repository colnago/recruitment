<?php

declare(strict_types=1);

namespace App\Component\Currency;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class CurrencyConverter
{
    public const PLN = 'PLN';
    public const EUR = 'EUR';

    private const RATE = [
        self::PLN => 100,
        self::EUR => 450,
    ];

    public function normalize(?string $currency): string
    {
        $currency = strtoupper((string) $currency);
        if ($currency === '') {
            return self::PLN;
        }

        if (!isset(self::RATE[$currency])) {
            throw new BadRequestHttpException('Unsupported currency.');
        }

        return $currency;
    }

    public function convert(int $amount, string $currency): int
    {
        $currency = $this->normalize($currency);

        return intdiv($amount * 100, self::RATE[$currency]);
    }
}