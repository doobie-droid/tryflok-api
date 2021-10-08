<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Constants\Constants;
use App\Models\Review;
use Illuminate\Support\Facades\Cache;
use App\Http\Resources\TrendingResource;
use Illuminate\Database\Eloquent\Builder;

class AnalyticsController extends Controller
{
    public function trending(Request $request)
    {
        try {
            $page = ctype_digit(strval($request->query('page', 1))) ? $request->query('page', 1) : 1;
            $limit = ctype_digit(strval($request->query('limit', 10))) ? $request->query('limit', 10) : 1;

            if ($request->user() == NULL || $request->user()->id == NULL) {
                $user_id = 0;
            } else {
                $user_id = $request->user()->id;
            }

            $reviews = Review::select(DB::raw('avg(rating) as rating, reviewable_type, reviewable_id'))
            ->whereHas('reviewable', function(Builder $query) {
                $query
                ->where('is_available', 1)
                ->where('show_only_in_collections', 0)
                ->where('approved_by_admin', 1);
            })
            ->with([
                'reviewable' => function($query) use ($user_id) {
                    $query
                    ->with('categories', 'owner', 'prices', 'prices.continent', 'prices.country', 'cover')
                    ->withCount([
                        'ratings' => function ($query) {
                            $query->where('rating', '>', 0);
                        }
                    ])->withAvg([
                        'ratings' => function($query)
                        {
                            $query->where('rating', '>', 0);
                        }
                    ], 'rating')
                    ->with([
                        'userables' => function ($query) use ($user_id) {
                            $query->where('user_id',  $user_id)->where('status', 'available');
                        },
                    ]);
                }
            ])
            ->groupBy('reviewable_type','reviewable_id')
            ->orderBy('rating', 'DESC')
            ->paginate($limit, array('*'), 'page', $page);

            return $this->respondWithSuccess("Trending retrieved successfully", [
                'trending' => TrendingResource::collection($reviews),
                'current_page' => $reviews->currentPage(),
                'items_per_page' => $reviews->perPage(),
                'total' => $reviews->total(),
            ]);
        } catch(\Exception $exception) {
            Log::error($exception);
			return $this->respondInternalError("Oops, an error occurred. Please try again later.");
		} 
    }
}
