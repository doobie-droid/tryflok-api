<?php

namespace App\Http\Controllers\API\V1\Admin;

use App\Constants\Roles;
use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AdministratorController extends Controller
{
    public function dashboard(Request $request)
    {
        try {
            return $this->respondWithSuccess("Dashboard details retrieved successfully.", [
                'users_total' => User::all()->count(),
                'users_today' => User::whereDate('created_at', today())->count(),
                'users_month' => User::whereMonth('created_at', now()->month)->count(),
                'creators_total' => User::role(Roles::CREATOR)->count(),
                'creators_today' => User::role(Roles::CREATOR)->whereDate('created_at', today())->count(),
                'creators_month' => User::role(Roles::CREATOR)->whereMonth('created_at', now()->month)->count(),
                'sales_total' => Sale::all()->sum('amount'),
                'sales_today' => Sale::whereDate('created_at', today())->sum('amount'),
                'sales_month' => Sale::whereMonth('created_at', now()->month)->sum('amount'),
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->respondInternalError("Oops, an error occurred. Please try again later.");
        }
    }
}
