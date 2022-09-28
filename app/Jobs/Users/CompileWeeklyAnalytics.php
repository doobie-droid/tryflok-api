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
            {  
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
                            "week_sales" => $this->getDigiverseCurrentWeekSales($digiverse),
                            "week_tips" => $this->getDigiverseCurrentWeekTips($digiverse),
                            "week_likes" => $this->getDigiverseCurrentWeekLikes($digiverse),
                            "week_comments" => $this->getDigiverseCurrentWeekComments($digiverse),
                            "content_with_highest_engagements" => [                
                                "content_id" => $current_week_content_with_highest_engagements->id,
                                "content_type" => $current_week_content_with_highest_engagements->type,
                                "title" => $current_week_content_with_highest_engagements->title,
                                "cover" => $this->getContentCover($current_week_content_with_highest_engagements)['url'],
                                "views" => $this->getContentCurrentWeekViews($current_week_content_with_highest_engagements),
                                "purchases" => $this->getContentCurrentWeekPurchases($current_week_content_with_highest_engagements),
                                "revenue" => $this->getContentCurrentWeekRevenues($current_week_content_with_highest_engagements),
                                "likes" => $this->getContentCurrentWeekLikes($current_week_content_with_highest_engagements),
                                "comments" => $this->getContentCurrentWeekComments($current_week_content_with_highest_engagements),
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
                            "week_sales" => $this->getDigiversePreviousWeekSales($digiverse),
                            "week_tips" => $this->getDigiversePreviousWeekTips($digiverse),
                            "week_likes" => $this->getDigiversePreviousWeekLikes($digiverse),
                            "week_comments" => $this->getDigiversePreviousWeekComments($digiverse),
                            "content_with_highest_engagements" => [                
                                "content_id" => $previous_week_content_with_highest_engagements->id,
                                "content_type" => $previous_week_content_with_highest_engagements->type,
                                "title" => $previous_week_content_with_highest_engagements->title,
                                "cover" => $this->getContentCover($previous_week_content_with_highest_engagements)['url'],
                                "views" => $this->getContentPreviousWeekViews($previous_week_content_with_highest_engagements),
                                "purchases" => $this->getContentPreviousWeekPurchases($previous_week_content_with_highest_engagements),
                                "revenue" => $this->getContentPreviousWeekRevenues($previous_week_content_with_highest_engagements),
                                "likes" => $this->getContentPreviousWeekLikes($previous_week_content_with_highest_engagements),
                                "comments" => $this->getContentPreviousWeekComments($previous_week_content_with_highest_engagements),
                                ]   
                        ]
                        ];
                array_push($user_analytics, $previous_week);
                }    
            Cache::put("analytics:{$digiverse->id}", $user_analytics);
            
            $analytics_percentages = array();

            $current_week_revenue = $current_week['current_week']['week_revenue'];
            $previous_week_revenue =  $previous_week['previous_week']['week_revenue'];
            $revenue_percentage = $this->calculatePercentage($current_week_revenue, $previous_week_revenue);
            array_push($analytics_percentages, ['revenue_percentage' => $revenue_percentage]);

            $current_week_subscribers = $current_week['current_week']['week_subscribers'];
            $previous_week_subscribers =  $previous_week['previous_week']['week_subscribers'];
            $subscribers_percentage = $this->calculatePercentage($current_week_subscribers, $previous_week_subscribers);
            array_push($analytics_percentages, ['subscribers_percentage' => $subscribers_percentage]);

            $current_week_sales = $current_week['current_week']['week_sales'];
            $previous_week_sales =  $previous_week['previous_week']['week_sales'];
            $sales_percentage = $this->calculatePercentage($current_week_sales, $previous_week_sales);
            array_push($analytics_percentages, ['sales_percentage' => $sales_percentage]);

            $current_week_tips = $current_week['current_week']['week_tips'];
            $previous_week_tips =  $previous_week['previous_week']['week_tips'];
            $tips_percentage = $this->calculatePercentage($current_week_tips, $previous_week_tips);
            array_push($analytics_percentages, ['tips_percentage' => $tips_percentage]);

            $current_week_likes = $current_week['current_week']['week_likes'];
            $previous_week_likes =  $previous_week['previous_week']['week_likes'];
            $likes_percentage = $this->calculatePercentage($current_week_likes, $previous_week_likes);
            array_push($analytics_percentages, ['likes_percentage' => $likes_percentage]);

            $current_week_comments = $current_week['current_week']['week_comments'];
            $previous_week_comments =  $previous_week['previous_week']['week_comments'];
            $comments_percentage = $this->calculatePercentage($current_week_comments, $previous_week_comments);
            array_push($analytics_percentages, ['comments_percentage' => $comments_percentage]);

            $this->sendWeeklyValidationMail($digiverse->id, $analytics_percentages); 
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

    public function getContentCurrentWeekTips($content) {
        return $content->generatedTips()
        ->where('revenue_from', 'tip')
        ->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])
        ->sum('amount');
    }

    public function getContentPreviousWeekTips($content) {
        return $content->generatedTips()
        ->where('revenue_from', 'tip')
        ->whereBetween('created_at', [Carbon::now()->startOfWeek()->subDays(7), Carbon::now()->endOfWeek()->subDays(7)])
        ->sum('amount');
    }

    public function getContentCurrentWeekSales($content) {
        return $content->revenues()
        ->where('revenue_from', 'sale')
        ->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])
        ->sum('amount');
    }

    public function getContentPreviousWeekSales($content) {
        return $content->revenues()
        ->where('revenue_from', 'sale')
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

    public function getContentCurrentWeekComments($content) {
        return $content->comments()
        ->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])
        ->count();
    }

    public function getContentPreviousWeekComments($content) {
        return $content->comments()
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

    public function getDigiverseCurrentWeekSales($digiverse) {
        $contents = $digiverse->contents;
        $digiverse_sales = array();
        foreach ($contents as $content) {
            $sales = $content->revenues()
            ->where('revenue_from', 'sale')
            ->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])
            ->sum('amount');
            array_push($digiverse_sales, $sales);
        }
        return array_sum($digiverse_sales);
    }

    public function getDigiverseCurrentWeekTips($digiverse) {
        $contents = $digiverse->contents;
        $digiverse_tips = array();
        foreach ($contents as $content) {
            $tips = $content->generatedTips()
            ->where('revenue_from', 'tip')
            ->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])
            ->sum('amount');
            array_push($digiverse_tips, $tips);
        }
        return array_sum($digiverse_tips);
    }

    public function getDigiverseCurrentWeekLikes($digiverse) {
        $contents = $digiverse->contents;
        $digiverse_likes = array();
        foreach ($contents as $content) {
            $likes = $content->likes()
            ->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])
            ->count();
            array_push($digiverse_likes, $likes);
        }
        return array_sum($digiverse_likes);
    }

    public function getDigiverseCurrentWeekComments($digiverse) {
        $contents = $digiverse->contents;
        $digiverse_comments = array();
        foreach ($contents as $content) {
            $comments = $content->comments()
            ->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])
            ->count();
            array_push($digiverse_comments, $comments);
        }
        return array_sum($digiverse_comments);
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

    public function getDigiversePreviousWeekLikes($digiverse) {
        $contents = $digiverse->contents;
        $digiverse_likes = array();
        foreach ($contents as $content) {
            $likes = $content->likes()
            ->whereBetween('created_at', [Carbon::now()->startOfWeek()->subDays(7), Carbon::now()->endOfWeek()->subDays(7)])
            ->count();
            array_push($digiverse_likes, $likes);
        }
        return array_sum($digiverse_likes);
    }

    public function getDigiversePreviousWeekComments($digiverse) {
        $contents = $digiverse->contents;
        $digiverse_comments = array();
        foreach ($contents as $content) {
            $comments = $content->comments()
            ->whereBetween('created_at', [Carbon::now()->startOfWeek()->subDays(7), Carbon::now()->endOfWeek()->subDays(7)])
            ->count();
            array_push($digiverse_comments, $comments);
        }
        return array_sum($digiverse_comments);
    }

    public function getDigiversePreviousWeekTips($digiverse) {
        $contents = $digiverse->contents;
        $digiverse_tips = array();
        foreach ($contents as $content) {
            $tips = $content->generatedTips()
            ->where('revenue_from', 'tip')
            ->whereBetween('created_at', [Carbon::now()->startOfWeek()->subDays(7), Carbon::now()->endOfWeek()->subDays(7)])
            ->sum('amount');
            array_push($digiverse_tips, $tips);
        }
        return array_sum($digiverse_tips);
    }

    public function getDigiversePreviousWeekSales($digiverse) {
        $contents = $digiverse->contents;
        $digiverse_sales = array();
        foreach ($contents as $content) {
            $sales = $content->revenues()
            ->where('revenue_from', 'sale')
            ->whereBetween('created_at', [Carbon::now()->startOfWeek()->subDays(7), Carbon::now()->endOfWeek()->subDays(7)])
            ->sum('amount');
            array_push($digiverse_sales, $sales);
        }
        return array_sum($digiverse_sales);
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

    public function calculatePercentage($current_week, $previous_week) {
        if ($previous_week == 0) {
            $percentage = +100;
            return $percentage;
        }
        elseif ($current_week == 0) {
            $percentage = -100;
            return $percentage;
        }
        if ($current_week > $previous_week) {
            $increase = $current_week - $previous_week;
            $percentage = +($increase / $previous_week) * 100;
        }
        elseif ($current_week < $previous_week) {
            $decrease = $previous_week - $current_week;
            $percentage = -($decrease / $current_week) * 100;
        }
        else {
            $percentage = 0;
        }
        return $percentage;
    }

    public function sendWeeklyValidationMail($digiverse_id, $analytics_percentages) {
        $analytics = Cache::get("analytics:{$digiverse_id}");
        Mail::to($this->user)->send(new WeeklyValidationMail([
        'user' => $this->user,
        'message' => $analytics,
        'analytics_percentages' => $analytics_percentages,
        'start_of_week' => Carbon::now()->startOfWeek()->format('d-m-y'),
        'end_of_week' => Carbon::now()->endOfWeek()->format('d-m-y'),
        ]));
    }
}