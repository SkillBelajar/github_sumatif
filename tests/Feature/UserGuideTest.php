<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserGuideTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role): User
    {
        return User::create([
            'name' => $role, 'email' => $role.uniqid().'@t.local', 'password' => 'password',
            'role' => $role, 'school_name' => 'S', 'email_verified_at' => now(),
        ]);
    }

    public function test_teacher_sees_teacher_guide(): void
    {
        $this->actingAs($this->user('teacher'))
            ->get('/panduan')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('UserGuide', false)->where('role', 'teacher'));
    }

    public function test_admin_sees_admin_guide(): void
    {
        $this->actingAs($this->user('admin'))
            ->get('/panduan')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('UserGuide', false)->where('role', 'admin'));
    }

    public function test_guest_redirected_from_guide(): void
    {
        $this->get('/panduan')->assertRedirect('/login');
    }
}
