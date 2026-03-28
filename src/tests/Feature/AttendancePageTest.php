<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\AttendanceBreak;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendancePageTest extends TestCase
{
    use RefreshDatabase;

    private function createVerifiedUser(array $attrs = []): User
    {
        return User::factory()->create(array_merge([
            'role' => 'user',
            'email_verified_at' => now(),
        ], $attrs));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_attendance_page_displays_current_datetime()
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 23, 9, 30, 0));

        $user = $this->createVerifiedUser();

        $response = $this->actingAs($user)->get(route('attendance.index'));

        $response->assertStatus(200);
        $response->assertSeeText('2026');
        $response->assertSeeText('09:30');
    }

    public function test_off_status_shows_clock_in_button()
    {
        $user = $this->createVerifiedUser();

        $response = $this->actingAs($user)->get(route('attendance.index'));

        $response->assertStatus(200);
        $response->assertSeeText('出勤');
    }

    public function test_working_status_is_displayed()
    {
        $user = $this->createVerifiedUser();

        Attendance::factory()
            ->for($user)
            ->forDate(Carbon::today())
            ->create([
                'status' => 'working',
                'clock_in_at' => Carbon::today()->setTime(9, 0),
                'clock_out_at' => null,
            ]);

        $response = $this->actingAs($user)->get(route('attendance.index'));

        $response->assertStatus(200);
        $response->assertSeeText('出勤中');
        $response->assertSeeText('休憩入');
        $response->assertSeeText('退勤');
    }

    public function test_breaking_status_is_displayed()
    {
        $user = $this->createVerifiedUser();

        $attendance = Attendance::factory()
            ->for($user)
            ->forDate(Carbon::today())
            ->create([
                'status' => 'breaking',
                'clock_in_at' => Carbon::today()->setTime(9, 0),
                'clock_out_at' => null,
            ]);

        AttendanceBreak::factory()->create([
            'attendance_id' => $attendance->id,
            'break_start_at' => Carbon::today()->setTime(12, 0),
            'break_end_at' => null,
        ]);

        $response = $this->actingAs($user)->get(route('attendance.index'));

        $response->assertStatus(200);
        $response->assertSeeText('休憩中');
        $response->assertSeeText('休憩戻');
    }

    public function test_done_status_is_displayed()
    {
        $user = $this->createVerifiedUser();

        Attendance::factory()
            ->for($user)
            ->forDate(Carbon::today())
            ->create([
                'status' => 'done',
                'clock_in_at' => Carbon::today()->setTime(9, 0),
                'clock_out_at' => Carbon::today()->setTime(18, 0),
            ]);

        $response = $this->actingAs($user)->get(route('attendance.index'));

        $response->assertStatus(200);
        $response->assertSeeText('退勤済');
    }
}