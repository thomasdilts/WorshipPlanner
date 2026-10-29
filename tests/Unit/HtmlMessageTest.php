<?php

namespace Tests\Unit;

use App\Support\HtmlMessage;
use Tests\TestCase;

class HtmlMessageTest extends TestCase
{
    public function test_for_email_body_keeps_safe_html(): void
    {
        $html = '<p>Hello <strong>team</strong></p><script>alert(1)</script>';

        $result = HtmlMessage::forEmailBody($html);

        $this->assertStringContainsString('<strong>team</strong>', $result);
        $this->assertStringNotContainsString('<script>', $result);
    }

    public function test_for_email_body_escapes_plain_text(): void
    {
        $result = HtmlMessage::forEmailBody("Line 1\nLine 2");

        $this->assertStringContainsString('Line 1<br />', $result);
        $this->assertStringContainsString('Line 2', $result);
    }

    public function test_excerpt_strips_tags(): void
    {
        $this->assertSame(
            'Hello world',
            HtmlMessage::excerpt('<p>Hello <em>world</em></p>')
        );
    }
}
