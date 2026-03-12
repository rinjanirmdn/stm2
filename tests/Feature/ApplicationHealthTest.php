<?php

namespace Tests\Feature;

use Tests\TestCase;

class ApplicationHealthTest extends TestCase
{
    /**
     * Test the application boots without errors.
     */
    public function test_application_boots_successfully(): void
    {
        $this->assertTrue(app()->bound('router'));
        $this->assertTrue(app()->bound('db'));
    }

    /**
     * Test important environment variables are set.
     */
    public function test_required_env_variables_present(): void
    {
        $this->assertNotNull(config('app.key'), 'APP_KEY must be set');
        $this->assertNotEmpty(config('app.name'), 'APP_NAME must be set');
    }

    /**
     * Test key configuration values.
     */
    public function test_app_configuration_is_correct(): void
    {
        $this->assertEquals('testing', config('app.env'));
    }

    /**
     * Test all important routes are registered.
     */
    public function test_critical_routes_exist(): void
    {
        $routeCollection = app('router')->getRoutes();

        // Auth routes
        $this->assertNotNull($routeCollection->getByName('login'), 'Login route must exist');
        $this->assertNotNull($routeCollection->getByName('logout'), 'Logout route must exist');

        // Core app routes
        $this->assertNotNull($routeCollection->getByName('dashboard'), 'Dashboard route must exist');
        $this->assertNotNull($routeCollection->getByName('profile'), 'Profile route must exist');
    }
}
