<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\AttendanceBreak;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminStaffListTest extends TestCase
{
    use RefreshDatabase;

    private function createAdmin(array $attrs = []): User
    {
        return User::factory()->create(array_merge([
            'role' => 'admin',
            'email_verified_at' => now(),
        ], $attrs));
    }

    private function createUser(array $attrs = []): User
    {
        return User::factory()->create(array_merge([
            'role' => 'user',
            'email_verified_at' => now(),
        ], $attrs));
    }

    public function test_admin_can_view_staff_list()
    {
        $admin = $this->createAdmin();

        $this->createUser([
            'name' => '田中太郎',
            'email' => 'tanaka@example.com',
        ]);

        $this->createUser([
            'name' => '佐藤花子',
            'email' => 'sato@example.com',
        ]);

        $response = $this->actingAs($admin)->get(route('staff.list'));

        $response->assertStatus(200);
        $response->assertSeeText('田中太郎');
        $response->assertSeeText('tanaka@example.com');
        $response->assertSeeText('佐藤花子');
        $response->assertSeeText('sato@example.com');
    }

    public function test_staff_list_does_not_include_admin_users()
    {
        $admin = $this->createAdmin([
            'name' => '管理者A',
            'email' => 'admina@example.com',
        ]);

        $this->createAdmin([
            'name' => '管理者B',
            'email' => 'adminb@example.com',
        ]);

        $this->createUser([
            'name' => '一般ユーザー',
            'email' => 'user@example.com',
        ]);

        $response = $this->actingAs($admin)->get(route('staff.list'));

        $response->assertStatus(200);
        $response->assertSeeText('一般ユーザー');
        $response->assertSeeText('user@example.com');
        $response->assertDontSeeText('管理者B');
        $response->assertDontSeeText('adminb@example.com');
    }

    public function test_admin_can_view_monthly_attendance_for_staff()
    {
        $admin = $this->createAdmin();
        $user = $this->createUser(['name' => '山田太郎']);

        $attendance = Attendance::factory()
            ->for($user)
            ->forDate(Carbon::create(2026, 3, 3))
            ->create([
                'clock_in_at' => Carbon::create(2026, 3, 3, 9, 0),
                'clock_out_at' => Carbon::create(2026, 3, 3, 18, 0),
                'status' => 'done',
            ]);

        AttendanceBreak::factory()->create([
            'attendance_id' => $attendance->id,
            'break_start_at' => Carbon::create(2026, 3, 3, 12, 0),
            'break_end_at' => Carbon::create(2026, 3, 3, 13, 0),
        ]);

        $response = $this->actingAs($admin)->get(
            route('staff.attendance', ['user' => $user->id, 'month' => '2026-03'])
        );

        $response->assertStatus(200);
        $response->assertSeeText('山田太郎');
        $response->assertSeeText('2026/03');
        $response->assertSeeText('09:00');
        $response->assertSeeText('18:00');
    }

    public function test_monthly_attendance_shows_only_target_month_data()
    {
        $admin = $this->createAdmin();
        $user = $this->createUser();

        Attendance::factory()
            ->for($user)
            ->forDate(Carbon::create(2026, 3, 3))
            ->create([
                'clock_in_at' => Carbon::create(2026, 3, 3, 9, 0),
                'clock_out_at' => Carbon::create(2026, 3, 3, 18, 0),
                'status' => 'done',
            ]);

        Attendance::factory()
            ->for($user)
            ->forDate(Carbon::create(2026, 4, 1))
            ->create([
                'clock_in_at' => Carbon::create(2026, 4, 1, 10, 0),
                'clock_out_at' => Carbon::create(2026, 4, 1, 19, 0),
                'status' => 'done',
            ]);

        $response = $this->actingAs($admin)->get(
            route('staff.attendance', ['user' => $user->id, 'month' => '2026-03'])
        );

        $response->assertStatus(200);
        $response->assertSeeText('09:00');
        $response->assertSeeText('18:00');
        $response->assertDontSeeText('10:00');
        $response->assertDontSeeText('19:00');
    }
}