<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class AttendanceCorrectionRequestTest extends TestCase
{
    use RefreshDatabase;

    private function createVerifiedUser(array $attrs = []): User
    {
        return User::factory()->create(array_merge([
            'role' => 'user',
            'email_verified_at' => now(),
        ], $attrs));
    }

    public function test_note_is_required_for_correction_request()
    {
        $user = $this->createVerifiedUser();

        $attendance = Attendance::factory()
            ->for($user)
            ->forDate(Carbon::create(2026, 3, 3))
            ->create();

        $response = $this->actingAs($user)->post(
            route('attendance.requestCorrection', $attendance->id),
            [
                'requested_clock_in_at' => '09:00',
                'requested_clock_out_at' => '18:00',
                'requested_note' => '',
                'breaks' => [
                    ['start' => '12:00', 'end' => '13:00'],
                ],
            ]
        );

        $response->assertSessionHasErrors(['requested_note']);
    }

    public function test_clock_in_after_clock_out_returns_validation_error()
    {
        $user = $this->createVerifiedUser();

        $attendance = Attendance::factory()
            ->for($user)
            ->forDate(Carbon::create(2026, 3, 3))
            ->create();

        $response = $this->actingAs($user)->post(
            route('attendance.requestCorrection', $attendance->id),
            [
                'requested_clock_in_at' => '21:30',
                'requested_clock_out_at' => '18:00',
                'requested_note' => '電車遅延のため',
                'breaks' => [
                    ['start' => '12:00', 'end' => '13:00'],
                ],
            ]
        );

        $response->assertSessionHasErrors(['requested_clock_in_at']);
    }

    public function test_user_can_create_pending_correction_request()
    {
        $user = $this->createVerifiedUser();

        $attendance = Attendance::factory()
            ->for($user)
            ->forDate(Carbon::create(2026, 3, 3))
            ->create();

        $response = $this->actingAs($user)->post(
            route('attendance.requestCorrection', $attendance->id),
            [
                'requested_clock_in_at' => '09:30',
                'requested_clock_out_at' => '18:30',
                'requested_note' => '電車遅延のため',
                'breaks' => [
                    ['start' => '12:15', 'end' => '12:45'],
                    ['start' => '15:00', 'end' => '15:15'],
                ],
            ]
        );

        $response->assertRedirect(route('attendance.detail', $attendance->id));

        $this->assertDatabaseHas('stamp_correction_requests', [
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'requested_note' => '電車遅延のため',
            'status' => 'pending',
        ]);
    }
    
    public function test_break_start_after_clock_out_returns_validation_error()
    {
        $user = $this->createVerifiedUser();
        
        $attendance = Attendance::factory()
           ->for($user)
           ->forDate(Carbon::create(2026, 3, 3))
           ->create();
           
        $response = $this->actingAs($user)->post(
            route('attendance.requestCorrection', $attendance->id),
            [
                'requested_clock_in_at' => '09:00',
                'requested_clock_out_at' => '18:00',
                'requested_note' => '修正理由',
                'breaks' => [
                    ['start' => '18:30', 'end' => '18:45'],
                ],
            ]
        );
        
        $response->assertSessionHasErrors(['breaks.0.start']);
    }
    
    public function test_break_end_after_clock_out_returns_validation_error()
    {
        $user = $this->createVerifiedUser();
        
        $attendance = Attendance::factory()
           ->for($user)
           ->forDate(Carbon::create(2026, 3, 3))
           ->create();
           
        $response = $this->actingAs($user)->post(
            route('attendance.requestCorrection', $attendance->id),
            [
                'requested_clock_in_at' => '09:00',
                'requested_clock_out_at' => '18:00',
                'requested_note' => '修正理由',
                'breaks' => [
                    ['start' => '17:30', 'end' => '18:30'],
                ],
            ]
        );
        
        $response->assertSessionHasErrors(['breaks.0.end']);
    }
    
    public function test_break_start_after_break_end_returns_validation_error()
    {
        $user = $this->createVerifiedUser();
        
        $attendance = Attendance::factory()
           ->for($user)
           ->forDate(Carbon::create(2026, 3, 3))
           ->create();
           
        $response = $this->actingAs($user)->post(
            route('attendance.requestCorrection', $attendance->id),
            [
                'requested_clock_in_at' => '09:00',
                'requested_clock_out_at' => '18:00',
                'requested_note' => '修正理由',
                'breaks' => [
                    ['start' => '13:00', 'end' => '12:00'],
                ],
            ]
        );
        
        $response->assertSessionHasErrors(['breaks.0.start']);
    }
    
    public function test_user_cannot_request_correction_twice_while_pending_exists()
    {
        $user = $this->createVerifiedUser();
        
        $attendance = Attendance::factory()
           ->for($user)
           ->forDate(Carbon::create(2026, 3, 3))
           ->create();
           
        \App\Models\StampCorrectionRequest::factory()->create([
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'requested_clock_in_at' => Carbon::create(2026, 3, 3, 9, 30),
            'requested_clock_out_at' => Carbon::create(2026, 3, 3, 18, 30),
            'requested_note' => '先に申請済み',
            'status' => 'pending',
        ]);
        
        $response = $this->actingAs($user)
           ->from(route('attendance.detail', $attendance->id))
           ->post(route('attendance.requestCorrection', $attendance->id), [
               'requested_clock_in_at' => '09:15',
               'requested_clock_out_at' => '18:15',
               'requested_note' => '再申請',
               'breaks' => [
                   ['start' => '12:00', 'end' => '13:00'],
                ],
            ]);
            
        $response->assertRedirect(route('attendance.detail', $attendance->id));
        $response->assertSessionHasErrors();
    }
    
    public function test_user_can_view_own_pending_correction_requests()
    {
        $user = $this->createVerifiedUser();
        $other = $this->createVerifiedUser();
        
        $attendance1 = Attendance::factory()
           ->for($user)
           ->forDate(Carbon::create(2026, 3, 3))
           ->create();
           
        $attendance2 = Attendance::factory()
           ->for($other)
           ->forDate(Carbon::create(2026, 3, 4))
           ->create();
           
        \App\Models\StampCorrectionRequest::factory()->create([
            'attendance_id' => $attendance1->id,
            'user_id' => $user->id,
            'requested_note' => '自分の承認待ち',
            'status' => 'pending',
        ]);
        
        \App\Models\StampCorrectionRequest::factory()->create([
            'attendance_id' => $attendance2->id,
            'user_id' => $other->id,
            'requested_note' => '他人の承認待ち',
            'status' => 'pending',
        ]);
        
        $response = $this->actingAs($user)
           ->get(route('stamp_correction_request.list', ['status' => 'pending']));
           
        $response->assertStatus(200);
        $response->assertSeeText('自分の承認待ち');
        $response->assertDontSeeText('他人の承認待ち');
    }
    
    public function test_user_can_view_own_approved_correction_requests()
    {
        $user = $this->createVerifiedUser();
        $other = $this->createVerifiedUser();
        
        $attendance1 = Attendance::factory()
           ->for($user)
           ->forDate(Carbon::create(2026, 3, 3))
           ->create();
           
        $attendance2 = Attendance::factory()
           ->for($other)
           ->forDate(Carbon::create(2026, 3, 4))
           ->create();
           
        \App\Models\StampCorrectionRequest::factory()->create([
            'attendance_id' => $attendance1->id,
            'user_id' => $user->id,
            'requested_note' => '自分の承認済み',
            'status' => 'approved',
        ]);
        
        \App\Models\StampCorrectionRequest::factory()->create([
            'attendance_id' => $attendance2->id,
            'user_id' => $other->id,
            'requested_note' => '他人の承認済み',
            'status' => 'approved',
        ]);
        
        $response = $this->actingAs($user)
           ->get(route('stamp_correction_request.list', ['status' => 'approved']));
           
        $response->assertStatus(200);
        $response->assertSeeText('自分の承認済み');
        $response->assertDontSeeText('他人の承認済み');
    }
    
    public function test_user_can_view_correction_request_detail()
    {
        $user = $this->createVerifiedUser(['name' => '山田太郎']);
        
        $attendance = Attendance::factory()
           ->for($user)
           ->forDate(Carbon::create(2026, 3, 3))
           ->create();
           
        $request = \App\Models\StampCorrectionRequest::factory()->create([
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'requested_clock_in_at' => Carbon::create(2026, 3, 3, 9, 30),
            'requested_clock_out_at' => Carbon::create(2026, 3, 3, 18, 30),
            'requested_note' => '電車遅延のため',
            'status' => 'pending',
        ]);
        
        $response = $this->actingAs($user)
           ->get(route('stamp_correction_request.show', $request));
           
        $response->assertStatus(200);
        $response->assertSeeText('山田太郎');
        $response->assertSeeText('電車遅延のため');
    }
}