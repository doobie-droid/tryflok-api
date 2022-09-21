<?php

namespace App\Jobs\Users;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models;
use App\Models\Revenue;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Mail\User\WeeklyValidationMail;
use Illuminate\Support\Facades\Mail;

class CompileWeeklyAnalytics implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    private $user;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($user)
    {
        $this->user = $user;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try{
            Log::info("here".$this->user);
            $digiverses = Models\Collection::where('user_id', $this->user->id)
            ->where('is_available', 1)
            ->where('approved_by_admin', 1)
            ->whereNull('archived_at')
            ->whereNull('deleted_at')
            ->where('type', 'digiverse')
            ->with([
                'contents' => function ($query) {
                    $query->orderBy('created_at', 'desc');
                }
                ])
            ->get();
            foreach ($digiverses as $digiverse)
            {   Log::info("here");
                $user_analytics = array();
                $previous_week = array();
                $current_week = array(); 
                $current_week_content_with_highest_engagements = $this->getDigiverseCurrentWeekContentWithHighestEngagements($digiverse);               
                $previous_week_content_with_highest_engagements = $this->getDigiversePreviousWeekContentWithHighestEngagements($digiverse);               
                if ( ! is_null($current_week_content_with_highest_engagements))
                {
                    $current_week = [
                        "current_week" => [
                            "digiverse_id" => $digiverse->id,
                            "week_revenue" => $this->getDigiverseCurrentWeekRevenues($digiverse),
                            "week_subscribers" => $this->getDigiverseCurrentWeekSubscribers($digiverse),
                            "content_with_highest_engagements" => [                
                                "content_id" => $current_week_content_with_highest_engagements->id,
                                "content_type" => $current_week_content_with_highest_engagements->type,
                                "title" => $current_week_content_with_highest_engagements->title,
                                "cover" => $this->getContentCover($current_week_content_with_highest_engagements)['url'],
                                "views" => $this->getContentCurrentWeekViews($current_week_content_with_highest_engagements),
                                "purchases" => $this->getContentCurrentWeekPurchases($current_week_content_with_highest_engagements),
                                "revenue" => $this->getContentCurrentWeekRevenues($current_week_content_with_highest_engagements),
                                "likes" => $this->getContentCurrentWeekLikes($current_week_content_with_highest_engagements),
                                ]
                            ]
                        ];
                array_push($user_analytics, $current_week);
                }
                if ( ! is_null($previous_week_content_with_highest_engagements))
                {
                    $previous_week = [
                        "previous_week" => [
                            "digiverse_id" => $digiverse->id,
                            "week_revenue" => $this->getDigiversePreviousWeekRevenues($digiverse),
                            "week_subscribers" => $this->getDigiversePreviousWeekSubscribers($digiverse),
                            "content_with_highest_engagements" => [                
                                "content_id" => $previous_week_content_with_highest_engagements->id,
                                "content_type" => $previous_week_content_with_highest_engagements->type,
                                "title" => $previous_week_content_with_highest_engagements->title,
                                "cover" => $this->getContentCover($previous_week_content_with_highest_engagements)['url'],
                                "views" => $this->getContentPreviousWeekViews($previous_week_content_with_highest_engagements),
                                "purchases" => $this->getContentPreviousWeekPurchases($previous_week_content_with_highest_engagements),
                                "revenue" => $this->getContentPreviousWeekRevenues($previous_week_content_with_highest_engagements),
                                "likes" => $this->getContentPreviousWeekLikes($previous_week_content_with_highest_engagements),
                                ]   
                        ]
                        ];
                array_push($user_analytics, $previous_week);
                }    
            Cache::put("analytics:{$digiverse->id}", $user_analytics);
            $this->sendWeeklyValidationMail($digiverse->id); 
            }
        } catch (\Exception $exception) {
            throw $exception;
            Log::error($exception);
        }
    }

    public function getContentCurrentWeekRevenues($content) {
        return $content->revenues()
        ->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])
        ->sum('amount');
    }

    public function getContentPreviousWeekRevenues($content) {
        return $content->revenues()
        ->whereBetween('created_at', [Carbon::now()->startOfWeek()->subDays(7), Carbon::now()->endOfWeek()->subDays(7)])
        ->sum('amount');
    }

    public function getContentCurrentWeekViews($content) {
        return $content->views()
        ->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])
        ->count();
    }

    public function getContentPreviousWeekViews($content) {
        return $content->views()
        ->whereBetween('created_at', [Carbon::now()->startOfWeek()->subDays(7), Carbon::now()->endOfWeek()->subDays(7)])
        ->count();
    }

    public function getContentCurrentWeekPurchases($content) {
        return $content->revenues()
        ->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])
        ->count();
    }

    public function getContentPreviousWeekPurchases($content) {
        return $content->revenues()
        ->whereBetween('created_at', [Carbon::now()->startOfWeek()->subDays(7), Carbon::now()->endOfWeek()->subDays(7)])
        ->count();
    }

    public function getContentCurrentWeekLikes($content) {
        return $content->likes()
        ->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])
        ->count();
    }

    public function getContentPreviousWeekLikes($content) {
        return $content->likes()
        ->whereBetween('created_at', [Carbon::now()->startOfWeek()->subDays(7), Carbon::now()->endOfWeek()->subDays(7)])
        ->count();
    }

    public function getContentCover($content) {
        return $content->cover()->first();
    }

    public function getDigiverseCurrentWeekRevenues($digiverse) {
        $contents = $digiverse->contents;
        $digiverse_revenues = array();
        foreach ($contents as $content) {
            $revenues = $content->revenues()
            ->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])
            ->sum('amount');
            array_push($digiverse_revenues, $revenues);
        }
        return array_sum($digiverse_revenues);
    }

    public function getDigiversePreviousWeekRevenues($digiverse) {
        $contents = $digiverse->contents;
        $digiverse_revenues = array();
        foreach ($contents as $content) {
            $revenues = $content->revenues()
            ->whereBetween('created_at', [Carbon::now()->startOfWeek()->subDays(7), Carbon::now()->endOfWeek()->subDays(7)])
            ->sum('amount');
            array_push($digiverse_revenues, $revenues);
        }
        return array_sum($digiverse_revenues);
    }

    public function getDigiverseCurrentWeekSubscribers($digiverse) {
        return $digiverse->subscriptions()
        ->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])
        ->count();
    }

    public function getDigiversePreviousWeekSubscribers($digiverse) {
        return $digiverse->subscriptions()
        ->whereBetween('created_at', [Carbon::now()->startOfWeek()->subDays(7), Carbon::now()->endOfWeek()->subDays(7)])
        ->count();
    }

    public function getDigiverseCurrentWeekContentWithHighestEngagements($digiverse) {
        $contents = $digiverse->contents;
        $highest_total_engagements = 0;
        $content_with_highest_engagement = null;
        $latest_content_created_at = '2020-09-05 19:00:12';
        foreach ($contents as $content) {
            $content_views = $this->getContentCurrentWeekViews($content) * 0.001;
            $content_likes = $this->getContentCurrentWeekLikes($content) * 0.1;
            $content_purchases = $this->getContentCurrentWeekPurchases($content) * 1;
            $content_revenues = $this->getContentCurrentWeekRevenues($content) * 1.1;

            $total_content_engagements_count = $content_likes + $content_views + $content_purchases + $content_revenues;

            if ($total_content_engagements_count == $highest_total_engagements) {
                if ($content->created_at < $latest_content_created_at) {
                    $content_with_highest_engagement = $content;
                    $latest_content_created_at = $content->created_at;
                }
            }
            elseif ($total_content_engagements_count > $highest_total_engagements) {
                $content_with_highest_engagement = $content;
                $highest_total_engagements = $total_content_engagements_count;
            }
        }
        return $content_with_highest_engagement;
    }

    public function getDigiversePreviousWeekContentWithHighestEngagements($digiverse) {
        $contents = $digiverse->contents;
        $highest_total_engagements = 0;
        $content_with_highest_engagement = null;
        $latest_content_created_at = '2020-09-05 19:00:12';
        foreach ($contents as $content) {
            $content_views = $this->getContentPreviousWeekViews($content) * 0.001;
            $content_likes = $this->getContentPreviousWeekLikes($content) * 0.1;
            $content_purchases = $this->getContentPreviousWeekPurchases($content) * 1;
            $content_revenues = $this->getContentPreviousWeekRevenues($content) * 1.1;

            $total_content_engagements_count = $content_likes + $content_views + $content_purchases + $content_revenues;

            if ($total_content_engagements_count == $highest_total_engagements) {
                if ($content->created_at < $latest_content_created_at) {
                    $content_with_highest_engagement = $content;
                    $latest_content_created_at = $content->created_at;
                }
            }
            elseif ($total_content_engagements_count > $highest_total_engagements) {
                $content_with_highest_engagement = $content;
                $highest_total_engagements = $total_content_engagements_count;
            }
        }
        return $content_with_highest_engagement;
    }

    public function sendWeeklyValidationMail($digiverse_id) {
        $analytics = Cache::get("analytics:digiverse:{$digiverse_id}");
        Log::info($analytics);
        Mail::to($this->user)->send(new WeeklyValidationMail([
        'user' => $this->user,
        'message' => $analytics,
        ]));
    }
}