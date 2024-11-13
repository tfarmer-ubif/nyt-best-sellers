<?php

namespace Tests\Unit;

use App\Rules\IsbnLengthRule;
use Exception;
use Tests\TestCase;

class IsbnLengthRuleTest extends TestCase
{
    public static function dataProvider(): array
    {
        return [
            ['1234567890', true],
            ['1234567890123', true],
            ['123456789', false],
            ['12345678901234', false],
            ['12345678901', false],
            ['123456789012345', false],
        ];
    }

    /**
     * @dataProvider dataProvider
     */
    public function testIsbnLengthRule(string $value, bool $isValid): void
    {
        $fail = fn() => throw new Exception();

        if(!$isValid) {
            $this->expectException(Exception::class);
        }

        if($isValid) {
            $this->assertTrue(strlen($value) === 10 || strlen($value) === 13);
        }

        (new IsbnLengthRule())->validate('isbn', $value, $fail);
    }
}
