<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Vite;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Skip Vite asset loading in tests
        if (env('VITE_SKIP_ASSETS') === 'true') {
            $this->mockViteFacade();
        }
    }
    
    protected function mockViteFacade(): void
    {
        // Create a simple mock for the Vite facade that returns the asset path unchanged
        Facade::clearResolvedInstance('vite');
        
        // Mock the Vite __invoke method to handle @vite() calls in Blade templates
        Vite::shouldReceive('__invoke')
            ->withAnyArgs()
            ->andReturn('');
            
        // Mock the asset method to handle vite_asset() calls
        Vite::shouldReceive('asset')
            ->withAnyArgs()
            ->andReturnUsing(function ($asset) {
                return $asset;
            });
            
        // Mock chunk to avoid ViteManifestNotFoundException
        Vite::shouldReceive('chunk')
            ->withAnyArgs()
            ->andReturn([]);
    }
}
