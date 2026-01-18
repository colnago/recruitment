<?php

namespace App\Tests\PhpUnit\App\Unit;

use App\Component\Currency\CurrencyConverter;
use PHPUnit\Framework\TestCase;

class CurrencyConverterTest extends TestCase
{
    public function testConvertsPlnToEur(): void
    {
        $converter = new CurrencyConverter();

        self::assertSame(100, $converter->convert(450, 'EUR'));
        self::assertSame(200, $converter->convert(900, 'EUR'));
    }
}