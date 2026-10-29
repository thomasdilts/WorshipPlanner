<?php

namespace Modules\Messaging\Support;

/**
 * Turns a phone number typed in any format into the digits-only form SMS / WhatsApp need (e.g. 46701234567).
 * The stored phone number is never changed; call this every time a number is used for sending.
 */
final class PhoneNumber
{
    public const SWEDEN = '46';

    /**
     * Swedish numbers must be mobile numbers (07x → 467x, 11 digits). Numbers typed with + or 00 and
     * another country code are kept as international numbers. Returns null when no usable number remains.
     */
    public static function toMsisdn(?string $phone): ?string
    {
        if ($phone === null || trim($phone) === '') {
            return null;
        }

        $typed = trim($phone);
        $digits = preg_replace('/\D+/', '', $typed) ?? '';
        $isInternational = str_starts_with($typed, '+') || str_starts_with($digits, '00');

        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        } elseif (! $isInternational) {
            $digits = ltrim($digits, '0');

            if (! str_starts_with($digits, self::SWEDEN)) {
                $digits = self::SWEDEN.$digits;
            }
        }

        if (str_starts_with($digits, self::SWEDEN.'0')) {
            // "+46 (0)70 …": the domestic 0 must not follow the country code.
            $digits = self::SWEDEN.substr($digits, 3);
        }

        if (str_starts_with($digits, self::SWEDEN)) {
            return preg_match('/^467\d{8}$/', $digits) === 1 ? $digits : null;
        }

        return strlen($digits) >= 8 && strlen($digits) <= 15 ? $digits : null;
    }
}
