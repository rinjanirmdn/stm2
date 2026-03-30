<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class GoogleTest extends DuskTestCase
{
    public function test_google(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('https://www.google.com')
                ->assertSee('Google');
        });
    }
}
