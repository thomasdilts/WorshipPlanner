<?php

namespace App\Support;

use Illuminate\Support\Str;

final class HtmlMessage
{
    public static function isHtml(?string $content): bool
    {
        if ($content === null || $content === '') {
            return false;
        }

        return $content !== strip_tags($content);
    }

    /**
     * Safe-ish HTML for email/preview: allow common formatting tags only.
     */
    public static function sanitize(?string $html): string
    {
        if ($html === null || $html === '') {
            return '';
        }

        $allowed = '<p><br><br/><strong><b><em><i><u><ul><ol><li><a><h1><h2><h3><h4><blockquote><span><div><table><thead><tbody><tr><th><td><hr><img>';

        return strip_tags($html, $allowed);
    }

    public static function toPlainText(?string $content): string
    {
        if ($content === null || $content === '') {
            return '';
        }

        $text = html_entity_decode(strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>'], ["\n", "\n", "\n", "\n\n"], $content)));

        return trim(preg_replace("/[ \t]+/", ' ', preg_replace("/\n{3,}/", "\n\n", $text)) ?? $text);
    }

    public static function forEmailBody(?string $content): string
    {
        $content ??= '';

        if (! self::isHtml($content)) {
            return nl2br(e($content));
        }

        return self::sanitize($content);
    }

    public static function excerpt(?string $content, int $limit = 80): string
    {
        return Str::limit(self::toPlainText($content), $limit);
    }
}
