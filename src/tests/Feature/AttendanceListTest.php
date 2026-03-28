<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\AttendanceBreak;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceListTest extends TestCase
{
    use RefreshDatabase;

    private function createUser(array $attrs = []): User
    {
        return User::factory()->create(array_merge([
            'role' => 'user',
            'email_verified_at' => now(),
        ], $attrs));
    }

    private function createAdmin(array $attrs = []): User
    {
        return User::factory()->create(array_merge([
            'role' => 'admin',
            'email_verified_at' => now(),
        ], $attrs));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_user_can_view_own_monthly_attendance_list()
    {
        $user = $this->createUser(['name' => '山田太郎']);

        Attendance::factory()
            ->for($user)
            ->forDate(Carbon::create(2026, 3, 3))
            ->create([
                'clock_in_at' => Carbon::create(2026, 3, 3, 9, 0),
                'clock_out_at' => Carbon::create(2026, 3, 3, 18, 0),
                'status' => 'done',
            ]);

        $response = $this->actingAs($user)
            ->get(route('attendance.list', ['month' => '2026-03']));

        $response->assertStatus(200);
        $response->assertSeeText('2026/03');
        $response->assertSeeText('03/03');
        $response->assertSeeText('09:00');
        $response->assertSeeText('18:00');
    }

    public function test_user_monthly_list_shows_only_target_month_data()
    {
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

        $response = $this->actingAs($user)
            ->get(route('attendance.list', ['month' => '2026-03']));

        $response->assertStatus(200);
        $response->assertSeeText('09:00');
        $response->assertSeeText('18:00');
        $response->assertDontSeeText('10:00');
        $response->assertDontSeeText('19:00');
    }

    public function test_user_can_view_attendance_detail()
    {
        $user = $this->createUser(['name' => '山田太郎']);

        $attendance = Attendance::factory()
            ->for($user)
            ->forDate(Carbon::create(2026, 3, 3))
            ->create([
                'clock_in_at' => Carbon::create(2026, 3, 3, 9, 0),
                'clock_out_at' => Carbon::create(2026, 3, 3, 18, 0),
                'note' => '通常勤務',
                'status' => 'done',
            ]);

        AttendanceBreak::factory()->create([
            'attendance_id' => $attendance->id,
            'break_start_at' => Carbon::create(2026, 3, 3, 12, 0),
            'break_end_at' => Carbon::create(2026, 3, 3, 13, 0),
        ]);

        $response = $this->actingAs($user)
            ->get(route('attendance.detail', $attendance->id));

        $response->assertStatus(200);
        $response->assertSeeText('山田太郎');
        $response->assertSeeText('2026年');
        $response->assertSeeText('3月3日');
        $response->assertSee('value="09:00"', false);
        $response->assertSee('value="18:00"', false);
        $response->assertSee('value="12:00"', false);
        $response->assertSee('value="13:00"', false);
        $response->assertSeeText('通常勤務');
    }

    public function test_user_cannot_view_other_users_attendance_detail()
    {
        $user = $this->createUser();
        $other = $this->createUser();

        $attendance = Attendance::factory()
            ->for($other)
            ->forDate(Carbon::create(2026, 3, 3))
            ->create();

        $response = $this->actingAs($user)
            ->get(route('attendance.detail', $attendance->id));

        $response->assertStatus(404);
    }

    public function test_admin_can_view_daily_attendance_list()
    {
        $admin = $this->createAdmin();
        $user1 = $this->createUser(['name' => '田中太郎']);
        $user2 = $this->createUser(['name' => '佐藤花子']);

        Attendance::factory()
            ->for($user1)
            ->forDate(Carbon::create(2026, 3, 3))
            ->create([
                'clock_in_at' => Carbon::create(2026, 3, 3, 9, 0),
                'clock_out_at' => Carbon::create(2026, 3, 3, 18, 0),
                'status' => 'done',
            ]);

        Attendance::factory()
            ->for($user2)
            ->forDate(Carbon::create(2026, 3, 3))
            ->create([
                'clock_in_at' => Carbon::create(2026, 3, 3, 10, 0),
                'clock_out_at' => Carbon::create(2026, 3, 3, 19, 0),
                'status' => 'done',
            ]);

        $response = $this->actingAs($admin)
            ->get(route('attendance.list', ['date' => '2026-03-03']));

        $response->assertStatus(200);
        $response->assertSeeText('2026年3月3日');
        $response->assertSeeText('田中太郎');
        $response->assertSeeText('佐藤花子');
        $response->assertSeeText('09:00');
        $response->assertSeeText('10:00');
    }

    public function test_admin_can_view_user_attendance_detail()
    {
        $admin = $this->createAdmin();
        $user = $this->createUser(['name' => '山田太郎']);

        $attendance = Attendance::factory()
            ->for($user)
            ->forDate(Carbon::create(2026, 3, 3))
            ->create([
                'clock_in_at' => Carbon::create(2026, 3, 3, 9, 0),
                'clock_out_at' => Carbon::create(2026, 3, 3, 18, 0),
                'note' => '管理者確認用',
                'status' => 'done',
            ]);

        $response = $this->actingAs($admin)
            ->get(route('attendance.detail', $attendance->id));

        $response->assertStatus(200);
        $response->assertSeeText('山田太郎');
        $response->assertSee('value="09:00"', false);
        $response->assertSee('value="18:00"', false);
        $response->assertSeeText('管理者確認用');
    }
}