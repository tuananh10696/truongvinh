<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Fortify\Features;

abstract class TestCase extends BaseTestCase
{
    /**
     * Chạy DatabaseSeeder (template thư mục + cây gốc) sau mỗi lần refresh DB.
     * Dữ liệu demo không chạy vì APP_ENV=testing.
     */
    protected bool $seed = true;

    protected function skipUnlessFortifyHas(string $feature, ?string $message = null): void
    {
        if (! Features::enabled($feature)) {
            $this->markTestSkipped($message ?? "Fortify feature [{$feature}] is not enabled.");
        }
    }
}
