<?php

namespace Tests\Feature;

use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    /**
     * Test login page is accessible.
     */
    public function test_login_page_is_accessible(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    /**
     * Test unauthenticated user is redirected to login.
     */
    public function test_unauthenticated_user_redirected_to_login(): void
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect('/login');
    }

    /**
     * Test root URL redirects to login for guests.
     */
    public function test_root_redirects_to_login_for_guests(): void
    {
        $response = $this->get('/');

        $response->assertRedirect(route('login'));
    }

    /**
     * Test forgot password page is accessible.
     */
    public function test_forgot_password_page_is_accessible(): void
    {
        $response = $this->get('/forgot-password');

        $response->assertStatus(200);
    }

    /**
     * Test login with empty credentials returns validation error.
     */
    public function test_login_with_empty_credentials_fails(): void
    {
        $response = $this->post('/login', []);

        $response->assertSessionHasErrors();
    }
}
