<?php

namespace Database\Factories;

use App\Models\StampCorrectionRequest;
use App\Models\Attendance;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

class StampCorrectionRequestFactory extends Factory
{
    protected $model = StampCorrectionRequest::class;

    public function definition()
    {
        $date = Carbon::today();

        return [
            'attendance_id' => Attendance::factory(),
            'user_id' => User::factory(),
            'requested_clock_in_at' => (clone $date)->setTime(9, 30),
            'requested_clock_out_at' => (clone $date)->setTime(18, 30),
            'requested_note' => '電車遅延のため',
            'status' => 'pending',
            'approved_by' => null,
            'approved_at' => null,
        ];
    }

    public function approved(User $admin)
    {
        return $this->state(function () use ($admin) {
            return [
                'status' => 'approved',
                'approved_by' => $admin->id,
                'approved_at' => now(),
            ];
        });
    }
}
