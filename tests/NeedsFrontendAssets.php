<?php

namespace Tests;

use PHPUnit\Framework\Attributes\Group;

/**
 * This trait marks tests that need frontend assets (Vite/Vue)
 * Add this trait to tests that depend on frontend assets to exclude them in CI
 */
trait NeedsFrontendAssets
{
    // The trait has no code - it's just used for marking tests
} 