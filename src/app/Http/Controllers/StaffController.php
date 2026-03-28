<?php

namespace App\Http\Controllers;

use App\Models\User;

class StaffController extends Controller
{
    public function index()
    {
        $staffs = User::query()
            ->where('role', '!=', 'admin')
            ->orderBy('id')
            ->get(['id', 'name', 'email', 'role']);

        return view('staff.index', compact('staffs'));
    }
}