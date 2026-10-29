<?php

namespace Tests\Unit;

use Modules\Messaging\Support\PhoneNumber;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PhoneNumberTest extends TestCase
{
    /**
     * @return array<string, array{0: ?string, 1: ?string}>
     */
    public static function phoneNumbers(): array
    {
        return [
            'domestic with leading 0' => ['0701740605', '46701740605'],
            'dashes and spaces' => ['070-174 06 05', '46701740605'],
            'brackets and dots' => ['(070) 174.06.05', '46701740605'],
            'plus country code' => ['+46 70 174 06 05', '46701740605'],
            'double zero country code' => ['0046701740605', '46701740605'],
            'country code with domestic 0' => ['+46 (0)70-174 06 05', '46701740605'],
            'country code without plus' => ['46701740605', '46701740605'],
            'missing leading 0' => ['701740605', '46701740605'],
            'swedish landline' => ['08-123 456 78', null],
            'too short' => ['070-123', null],
            'foreign number with plus' => ['+49 151 2345 6789', '4915123456789'],
            'letters only' => ['call me', null],
            'empty' => ['  ', null],
            'null' => [null, null],
        ];
    }

    #[DataProvider('phoneNumbers')]
    public function test_phone_number_is_cleaned_for_sms_and_whatsapp(?string $typed, ?string $expected): void
    {
        $this->assertSame($expected, PhoneNumber::toMsisdn($typed));
    }
}
