<?php

namespace Database\Factories;

use App\Models\StampCorrectionRequestBreak;
use App\Models\StampCorrectionRequest;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

class StampCorrectionRequestBreakFactory extends Factory
{
    protected $model = StampCorrectionRequestBreak::class;

    public function definition()
    {
        $date = Carbon::today();

        return [
            'stamp_correction_request_id' => StampCorrectionRequest::factory(),
            'break_start_at' => (clone $date)->setTime(12, 15),
            'break_end_at' => (clone $date)->setTime(13, 15),
        ];
    }

    public function forRequest(StampCorrectionRequest $req, int $startH, int $startM, int $endH, int $endM)
    {
        $date = Carbon::parse($req->attendance->work_date);
        return $this->state(function () use ($req, $date, $startH, $startM, $endH, $endM) {
            return [
                'stamp_correction_request_id' => $req->id,
                'break_start_at' => (clone $date)->setTime($startH, $startM),
                'break_end_at' => (clone $date)->setTime($endH, $endM),
            ];
        });
    }
}
