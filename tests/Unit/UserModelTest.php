<?php

namespace Tests\Unit;

use App\Models\User;
use Tests\TestCase;

class UserModelTest extends TestCase
{
    /**
     * Test that User model has correct table name.
     */
    public function test_user_model_uses_correct_table(): void
    {
        $user = new User();
        $this->assertEquals('md_users', $user->getTable());
    }

    /**
     * Test that fillable attributes are correctly defined.
     */
    public function test_user_has_correct_fillable_attributes(): void
    {
        $user = new User();
        $expected = [
            'full_name',
            'nik',
            'username',
            'email',
            'vendor_code',
            'password',
            'is_active',
            'role_id',
        ];

        $this->assertEquals($expected, $user->getFillable());
    }

    /**
     * Test password is hidden in serialization.
     */
    public function test_password_is_hidden(): void
    {
        $user = new User();
        $this->assertContains('password', $user->getHidden());
    }

    /**
     * Test isVendor returns true for users with vendor_code.
     */
    public function test_is_vendor_returns_true_with_vendor_code(): void
    {
        $user = new User();
        $user->vendor_code = 'V001';

        $this->assertTrue($user->isVendor());
    }

    /**
     * Test isVendor returns false for users without vendor_code.
     */
    public function test_is_vendor_returns_false_without_vendor_code(): void
    {
        $user = new User();
        $user->vendor_code = null;

        $this->assertFalse($user->isVendor());
    }

    /**
     * Test name accessor maps to full_name.
     */
    public function test_name_accessor_returns_full_name(): void
    {
        $user = new User();
        $user->full_name = 'John Doe';

        $this->assertEquals('John Doe', $user->name);
    }

    /**
     * Test name mutator sets full_name.
     */
    public function test_name_mutator_sets_full_name(): void
    {
        $user = new User();
        $user->name = 'Jane Doe';

        $this->assertEquals('Jane Doe', $user->full_name);
    }

    /**
     * Test is_active is cast to boolean.
     */
    public function test_is_active_cast_to_boolean(): void
    {
        $user = new User();
        $casts = $user->getCasts();

        $this->assertArrayHasKey('is_active', $casts);
        $this->assertEquals('boolean', $casts['is_active']);
    }
}
