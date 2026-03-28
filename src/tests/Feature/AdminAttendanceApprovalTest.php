<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\AttendanceBreak;
use App\Models\StampCorrectionRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAttendanceApprovalTest extends TestCase
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

    public function test_non_admin_cannot_approve_correction_request()
    {
        $user = $this->createUser();

        $attendance = Attendance::factory()
            ->for($user)
            ->forDate(Carbon::create(2026, 3, 3))
            ->create([
                'clock_in_at' => Carbon::create(2026, 3, 3, 9, 0),
                'clock_out_at' => Carbon::create(2026, 3, 3, 18, 0),
                'status' => 'done',
            ]);

        $request = StampCorrectionRequest::factory()->create([
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'requested_clock_in_at' => Carbon::create(2026, 3, 3, 9, 30),
            'requested_clock_out_at' => Carbon::create(2026, 3, 3, 18, 30),
            'requested_break1_start' => Carbon::create(2026, 3, 3, 12, 15),
            'requested_break1_end' => Carbon::create(2026, 3, 3, 12, 45),
            'requested_note' => '電車遅延のため',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($user)->post(
            route('stamp_correction_request.approve', ['stampCorrectionRequest' => $request->id])
        );

        $response->assertStatus(403);
    }

    public function test_admin_can_approve_and_reflect_attendance()
    {
        $admin = $this->createAdmin();
        $user = $this->createUser();

        $attendance = Attendance::factory()
            ->for($user)
            ->forDate(Carbon::create(2026, 3, 3))
            ->create([
                'clock_in_at' => Carbon::create(2026, 3, 3, 9, 0),
                'clock_out_at' => Carbon::create(2026, 3, 3, 18, 0),
                'note' => null,
                'status' => 'done',
            ]);

        AttendanceBreak::factory()->create([
            'attendance_id' => $attendance->id,
            'break_start_at' => Carbon::create(2026, 3, 3, 12, 0),
            'break_end_at' => Carbon::create(2026, 3, 3, 13, 0),
        ]);

        $request = StampCorrectionRequest::factory()->create([
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'requested_clock_in_at' => Carbon::create(2026, 3, 3, 9, 30),
            'requested_clock_out_at' => Carbon::create(2026, 3, 3, 18, 30),
            'requested_break1_start' => Carbon::create(2026, 3, 3, 12, 15),
            'requested_break1_end' => Carbon::create(2026, 3, 3, 12, 45),
            'requested_break2_start' => Carbon::create(2026, 3, 3, 15, 0),
            'requested_break2_end' => Carbon::create(2026, 3, 3, 15, 15),
            'requested_note' => '電車遅延のため',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin)->post(
            route('stamp_correction_request.approve', ['stampCorrectionRequest' => $request->id])
        );

        $response->assertRedirect(route('stamp_correction_request.list', ['status' => 'approved']));

        $attendance->refresh();
        $request->refresh();

        $this->assertEquals('approved', $request->status);
        $this->assertEquals($admin->id, $request->approved_by);
        $this->assertNotNull($request->approved_at);

        $this->assertEquals('09:30', $attendance->clock_in_at->format('H:i'));
        $this->assertEquals('18:30', $attendance->clock_out_at->format('H:i'));
        $this->assertEquals('電車遅延のため', $attendance->note);
        $this->assertEquals('done', $attendance->status);

        $this->assertCount(2, $attendance->breaks()->get());
        $this->assertDatabaseHas('attendance_breaks', [
            'attendance_id' => $attendance->id,
            'break_start_at' => Carbon::create(2026, 3, 3, 12, 15)->toDateTimeString(),
            'break_end_at' => Carbon::create(2026, 3, 3, 12, 45)->toDateTimeString(),
        ]);
        $this->assertDatabaseHas('attendance_breaks', [
            'attendance_id' => $attendance->id,
            'break_start_at' => Carbon::create(2026, 3, 3, 15, 0)->toDateTimeString(),
            'break_end_at' => Carbon::create(2026, 3, 3, 15, 15)->toDateTimeString(),
        ]);
    }
    
    public function test_admin_can_view_all_pending_requests()
    {
        $admin = $this->createAdmin();
        $user1 = $this->createUser(['name' => 'ユーザーA']);
        $user2 = $this->createUser(['name' => 'ユーザーB']);
        
        $attendance1 = Attendance::factory()
           ->for($user1)
           ->forDate(Carbon::create(2026, 3, 3))
           ->create();
           
        $attendance2 = Attendance::factory()
           ->for($user2)
           ->forDate(Carbon::create(2026, 3, 4))
           ->create();
           
        StampCorrectionRequest::factory()->create([
            'attendance_id' => $attendance1->id,
            'user_id' => $user1->id,
            'requested_note' => '申請A',
            'status' => 'pending',
        ]);
        
        StampCorrectionRequest::factory()->create([
            'attendance_id' => $attendance2->id,
            'user_id' => $user2->id,
            'requested_note' => '申請B',
            'status' => 'pending',
        ]);
        
        $response = $this->actingAs($admin)
           ->get(route('stamp_correction_request.list', ['status' => 'pending']));
           
        $response->assertStatus(200);
        $response->assertSeeText('申請A');
        $response->assertSeeText('申請B');
    }
    
    public function test_admin_can_view_all_approved_requests()
    {
        $admin = $this->createAdmin();
        $user = $this->createUser();
        
        $attendance = Attendance::factory()
           ->for($user)
           ->forDate(Carbon::create(2026, 3, 3))
           ->create();
           
        StampCorrectionRequest::factory()->create([
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'requested_note' => '承認済み申請',
            'status' => 'approved',
        ]);
        
        $response = $this->actingAs($admin)
           ->get(route('stamp_correction_request.list', ['status' => 'approved']));
           
        $response->assertStatus(200);
        $response->assertSeeText('承認済み申請');
    }
    
    public function test_admin_can_view_correction_request_detail()
    {
        $admin = $this->createAdmin();
        $user = $this->createUser(['name' => '山田花子']);
        
        $attendance = Attendance::factory()
           ->for($user)
           ->forDate(Carbon::create(2026, 3, 3))
           ->create();
           
        $request = StampCorrectionRequest::factory()->create([
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'requested_clock_in_at' => Carbon::create(2026, 3, 3, 9, 30),
            'requested_clock_out_at' => Carbon::create(2026, 3, 3, 18, 30),
            'requested_note' => '管理者確認用',
            'status' => 'pending',
        ]);
        
        $response = $this->actingAs($admin)
           ->get(route('stamp_correction_request.show', $request));
           
        $response->assertStatus(200);
        $response->assertSeeText('山田花子');
        $response->assertSeeText('管理者確認用');
    }
    
    public function test_admin_cannot_approve_already_processed_request()
    {
        $admin = $this->createAdmin();
        $user1 = $this->createUser();
        
        $attendance = Attendance::factory()
           ->for($user1)
           ->forDate(Carbon::create(2026, 3, 3))
           ->create([
               'clock_in_at' => Carbon::create(2026, 3, 3, 9, 0),
               'clock_out_at' => Carbon::create(2026, 3, 3, 18, 0),
               'status' => 'done',
            ]);

        $request = StampCorrectionRequest::factory()->create([
            'attendance_id' => $attendance->id,
            'user_id' => $user1->id,
            'requested_note' => '再承認不可確認',
            'status' => 'approve',
        ]);
        
        $response = $this->actingAs($admin)
           ->from(route('stamp_correction_request.show', $request))
           ->post(route('stamp_correction_request.approve', $request));

        $response->assertRedirect(route('stamp_correction_request.show', $request));
        $response->assertSessionHas('error', 'この申請は既に処理済みです。');
    }
}