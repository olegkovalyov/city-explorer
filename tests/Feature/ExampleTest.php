<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Group;
use Tests\NeedsFrontendAssets;
use Tests\TestCase;

#[Group('frontend-assets')]
class ExampleTest extends TestCase
{
    use RefreshDatabase, NeedsFrontendAssets;

    public function test_returns_a_successful_response()
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }
}
