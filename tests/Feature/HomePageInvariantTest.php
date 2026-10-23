<?php

namespace Tests\Feature;

use App\Models\Pagina;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomePageInvariantTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_prevents_two_home_pages_even_outside_the_controller(): void
    {
        Pagina::factory()->home()->create();
        $this->expectException(UniqueConstraintViolationException::class);
        Pagina::factory()->home()->create();
    }
}
