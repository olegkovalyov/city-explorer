<?php

namespace Tests;

use Illuminate\Support\Facades\Vite;
use Illuminate\Support\Str;
use Mockery;

trait WithoutViteTest
{
    /**
     * Set up the test environment to work without Vite assets.
     */
    protected function withoutVite(): void
    {
        // Mock the Vite facade for testing without checking for existing mocks
        Vite::shouldReceive('useCspNonce')
            ->andReturn('test-nonce');
            
        Vite::shouldReceive('asset')
            ->withAnyArgs()
            ->andReturnUsing(function ($path) {
                return "/{$path}";
            });

        Vite::shouldReceive('__invoke')
            ->withAnyArgs()
            ->andReturn('');
            
        Vite::shouldReceive('reactRefresh')
            ->andReturn('');
            
        Vite::shouldReceive('chunk')
            ->withAnyArgs()
            ->andReturn(['', '']);
            
        Vite::shouldReceive('manifestHash')
            ->andReturn(Str::random(8));
            
        // Add support for preloadedAssets method - this is critical for all auth tests
        Vite::shouldReceive('preloadedAssets')
            ->withAnyArgs()
            ->andReturn([]);
    }
} 