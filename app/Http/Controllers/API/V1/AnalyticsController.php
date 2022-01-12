<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources;
use App\Models;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AnalyticsController extends Controller
{
    public function getDailySales(Request $request)
    {
        try {
            $start_date = $request->query('start_date', now()->startOfMonth());
            $end_date = $request->query('end_date', now()->endOfMonth());

            $validator = Validator::make([
                'start_date' => $start_date,
                'end_date' => $end_date,
            ], [
                'start_date' => ['required', 'date',],
                'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            ]);

            if ($validator->fails()) {
                return $this->respondBadRequest('Invalid or missing input fields', $validator->errors()->toArray());
            }

            $sales = Models\Revenue::select(DB::raw('sum(benefactor_share) as share, date(created_at) as created_date'))
                                        ->whereDate('created_at', '>=', $start_date)
                                        ->whereDate('created_at', '<=', $end_date)
                                        ->where('revenue_from', 'sale')
                                        ->where('user_id', $request->user()->id)
                                        ->groupBy('created_date')
                                        ->get()
                                        ->toArray();
            
            $sales_graph = [];
            foreach ($sales as $instance) {
                $sales_graph[$instance['created_date']] = $instance['share'];
            }

            return $this->respondWithSuccess('Sales data retrieved successfully', $sales_graph);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->respondInternalError('Oops, an error occurred. Please try again later.');
        }
    }
}
