<?php

namespace Tests\Unit;

use App\Support\HtmlSanitizer;
use PHPUnit\Framework\TestCase;

class HtmlSanitizerTest extends TestCase
{
    public function test_it_keeps_formatting_and_removes_executable_markup(): void
    {
        $result = HtmlSanitizer::clean(
            '<p style="text-align: center; background-image: url(javascript:alert(1))">'.
            '<strong>Conteúdo histórico</strong><img src=x onerror=alert(1)>'.
            '<a href="javascript:alert(2)" onclick="alert(3)">link</a>'.
            '<script>alert(4)</script></p>'
        );

        $this->assertStringContainsString('<strong>Conteúdo histórico</strong>', $result);
        $this->assertStringNotContainsString('javascript:', $result);
        $this->assertStringNotContainsString('onclick', $result);
        $this->assertStringNotContainsString('<script', $result);
        $this->assertStringNotContainsString('<img', $result);
    }
}
