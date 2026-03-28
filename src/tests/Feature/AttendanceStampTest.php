<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\AttendanceBreak;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceStampTest extends TestCase
{
    use RefreshDatabase;

    private function createVerifiedUser(array $attrs = []): User
    {
        return User::factory()->create(array_merge([
            'role' => 'user',
            'email_verified_at' => now(),
        ], $attrs));
    }

    public function test_user_can_clock_in()
    {
        $user = $this->createVerifiedUser();

        $response = $this->actingAs($user)->post(route('attendance.clockIn'));

        $response->assertRedirect(route('attendance.index'));

        $today = Carbon::today()->toDateString();

        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'work_date' => $today,
            'status' => 'working',
        ]);
    }

    public function test_user_can_start_and_end_break()
    {
        $user = $this->createVerifiedUser();

        $attendance = Attendance::factory()
            ->for($user)
            ->forDate(Carbon::today())
            ->create([
                'status' => 'working',
                'clock_in_at' => Carbon::today()->setTime(9, 0),
                'clock_out_at' => null,
            ]);

        $breakInResponse = $this->actingAs($user)->post(route('attendance.breakIn'));
        $breakInResponse->assertRedirect(route('attendance.index'));

        $attendance->refresh();

        $this->assertEquals('breaking', $attendance->status);
        $this->assertDatabaseHas('attendance_breaks', [
            'attendance_id' => $attendance->id,
        ]);

        $break = AttendanceBreak::where('attendance_id', $attendance->id)->latest('id')->first();
        $this->assertNotNull($break);
        $this->assertNull($break->break_end_at);

        $breakOutResponse = $this->actingAs($user)->post(route('attendance.breakOut'));
        $breakOutResponse->assertRedirect(route('attendance.index'));

        $attendance->refresh();
        $break->refresh();

        $this->assertEquals('working', $attendance->status);
        $this->assertNotNull($break->break_end_at);
    }

    public function test_user_can_clock_out()
    {
        $user = $this->createVerifiedUser();

        $attendance = Attendance::factory()
            ->for($user)
            ->forDate(Carbon::today())
            ->create([
                'status' => 'working',
                'clock_in_at' => Carbon::today()->setTime(9, 0),
                'clock_out_at' => null,
            ]);

        $response = $this->actingAs($user)->post(route('attendance.clockOut'));

        $response->assertRedirect(route('attendance.index'));

        $attendance->refresh();

        $this->assertEquals('done', $attendance->status);
        $this->assertNotNull($attendance->clock_out_at);
    }
    
    public function test_user_cannot_clock_in_twice_in_same_day()
    {
        $user = $this->createVerifiedUser();
        
        Attendance::factory()
          ->for($user)
          ->forDate(Carbon::today())
          ->create([
              'clock_in_at' => Carbon::today()->setTime(9, 0),
              'status' => 'working',
            ]);
            
        $response = $this->actingAs($user)
           ->from(route('attendance.index'))
           ->post(route('attendance.clockIn'));
           
        $response->assertRedirect(route('attendance.index'));
        $response->assertSessionHasErrors();
        $this->assertEquals(
            1,
            Attendance::where('user_id', $user->id)
               ->whereDate('work_date', Carbon::today())
               ->count()
        );
    }
    
    public function test_user_cannot_start_break_before_clock_in()
    {
        $user = $this->createVerifiedUser();
        
        $response = $this->actingAs($user)
           ->from(route('attendance.index'))
           ->post(route('attendance.breakIn'));
           
        $response->assertRedirect(route('attendance.index'));
        $response->assertSessionHasErrors();
    }
    
    public function test_user_cannot_start_break_after_clock_out()
    {
        $user = $this->createVerifiedUser();
        
        Attendance::factory()
           ->for($user)
           ->forDate(Carbon::today())
           ->create([
               'clock_in_at' => Carbon::today()->setTime(9, 0),
               'clock_out_at' => Carbon::today()->setTime(18, 0),
               'status' => 'done',
            ]);
            
        $response = $this->actingAs($user)
           ->from(route('attendance.index'))
           ->post(route('attendance.breakIn'));
           
        $response->assertRedirect(route('attendance.index'));
        $response->assertSessionHasErrors();
    }
    
    public function test_user_cannot_clock_out_while_breaking()
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
            
        $response = $this->actingAs($user)
           ->from(route('attendance.index'))
           ->post(route('attendance.clockOut'));
           
        $response->assertRedirect(route('attendance.index'));
        $response->assertSessionHasErrors();
        
        $attendance->refresh();
        $this->assertNull($attendance->clock_out_at);
        $this->assertEquals('breaking', $attendance->status);
    }
    
    public function test_user_can_take_break_multiple_times_in_a_day()
    {
        $user = $this->createVerifiedUser();
        
        $attendance = Attendance::factory()
           ->for($user)
           ->forDate(Carbon::today())
           ->create([
               'status' => 'working',
               'clock_in_at' => Carbon::today()->setTime(9, 0),
               'clock_out_at' => null,
            ]);
            
        $this->actingAs($user)->post(route('attendance.breakIn'));
        $this->actingAs($user)->post(route('attendance.breakOut'));
        $this->actingAs($user)->post(route('attendance.breakIn'));
        $this->actingAs($user)->post(route('attendance.breakOut'));
        
        $attendance->refresh();
        
        $this->assertEquals('working', $attendance->status);
        $this->assertEquals(2, AttendanceBreak::where('attendance_id', $attendance->id)->count());
    }
}