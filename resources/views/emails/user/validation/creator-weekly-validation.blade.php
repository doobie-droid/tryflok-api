@extends('emails.layouts.email-master')

@section('body')
<!-- Email Body -->
<tr>
    <td class="email-body" width="570" cellpadding="0" cellspacing="0" style="word-break: break-word; margin: 0; padding: 0; font-family: &quot;Nunito Sans&quot;, Helvetica, Arial, sans-serif; font-size: 16px; width: 100%; -premailer-width: 100%; -premailer-cellpadding: 0; -premailer-cellspacing: 0;">
        <table class="email-body_inner" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation" style="width: 570px; -premailer-width: 570px; -premailer-cellpadding: 0; -premailer-cellspacing: 0; background-color: #FFFFFF; margin: 0 auto; padding: 0;" bgcolor="#FFFFFF">
        <!-- Body content -->
            <tr>
                <td class="content-cell" style="word-break: break-word; font-family: &quot;Nunito Sans&quot;, Helvetica, Arial, sans-serif; font-size: 16px; padding: 45px;">
                    <div class="f-fallback">
                        <div class="banner">
                            <div class="banner-background" style = "background: url({{asset('images/weekly-flokdate.png')}});">
        
                            </div>
                        </div>
                        <div class="email_template">
                        <div class="description">
                            <p>Hi {{ $user['name'] }}! </p>

                            <p> It’s the end of the week!</p>

                            <p>I imagine that you are planning your execution plans for next week, so we’ve been gathering insights to
                                show
                                you what your audience wants to see in the coming week.</p>
                        </div>
        <section class="container">
            <div class="overview_engagements">
                <p class="analytics-title" style="font-size: 14px; line-height: 1.625; color: #51545E; margin: .4em 0 1.1875em;">Engagement and Revenue for 
                    {{$start_of_week}} (M) to {{$end_of_week}} (S)
                </p>
                <div class="card">
                    <div>
                        <p>Total Revenue</p>
                        <div>
                            @if ($analytics_percentages[0]['revenue_percentage'] < 0)
                            <small style="color: #ff0000;">{{$analytics_percentages[0]['revenue_percentage']}}%</small>
                            @elseif ($analytics_percentages[0]['revenue_percentage'] > 0)
                            <small style="color: #00ff00;">{{$analytics_percentages[0]['revenue_percentage']}}%</small>
                            @else
                            <small>{{$analytics_percentages[0]['revenue_percentage']}}%</small>
                            @endif
                        </div>
                    </div>
                    <div>
                    @isset($contents[0]['current_week'])
                        <h2>${{$contents[0]['current_week']['week_revenue']}}</h2>
                    @endisset
                    @isset($contents[1]['previous_week'])
                        <h2>${{$contents[1]['previous_week']['week_revenue']}}</h2>
                    @elseif(isset($contents[0]['previous_week']))
                        <h2>${{$contents[0]['previous_week']['week_revenue']}}</h2>
                    @endisset
                    </div>
                    <div>
                        <p>This week</p>
                        <p>Last week</p>
                    </div>
                </div>
                <div class="card">
                    <div>
                        <p>New Subscribers</p>
                        <div>
                            @if ($analytics_percentages[1]['subscribers_percentage'] < 0)
                            <small style="color: #ff0000;">{{$analytics_percentages[1]['subscribers_percentage']}}%</small>
                            @elseif ($analytics_percentages[1]['subscribers_percentage'] > 0)
                            <small style="color: #00ff00;">{{$analytics_percentages[1]['subscribers_percentage']}}%</small>
                            @else
                            <small>{{$analytics_percentages[1]['subscribers_percentage']}}%</small>
                            @endif
                        </div>
                    </div>
                    <div>
                    @isset($contents[0]['current_week'])
                        <h2>{{$contents[0]['current_week']['week_subscribers']}}</h2>
                    @endisset
                    @isset($contents[1]['previous_week'])
                        <h2>{{$contents[1]['previous_week']['week_subscribers']}}</h2>
                    @elseif(isset($contents[0]['previous_week']))
                        <h2>${{$contents[0]['previous_week']['week_subscribers']}}</h2>
                    @endisset
                    </div>
                    <div>
                        <p>This week</p>
                        <p>Last week</p>
                    </div>
                </div>
                <div class="card">
                    <div>
                        <p>Sales Generated</p>
                        <div>
                            <div>
                                @if ($analytics_percentages[2]['sales_percentage'] < 0)
                                <small style="color: #ff0000;">{{$analytics_percentages[2]['sales_percentage']}}%</small>
                                @elseif ($analytics_percentages[2]['sales_percentage'] > 0)
                                <small style="color: #00ff00;">{{$analytics_percentages[2]['sales_percentage']}}%</small>
                                @else
                                <small>{{$analytics_percentages[2]['sales_percentage']}}%</small>
                                @endif
                        </div>
                        </div>
                    </div>
                    <div>
                    @isset($contents[0]['current_week'])
                        <h2>${{$contents[0]['current_week']['week_sales']}}</h2>
                    @endisset
                    @isset($contents[1]['previous_week'])
                        <h2>${{$contents[1]['previous_week']['week_sales']}}</h2>
                    @elseif(isset($contents[0]['previous_week']))
                        <h2>${{$contents[0]['previous_week']['week_sales']}}</h2>
                    @endisset
                    </div>
                    <div>
                        <p>This week</p>
                        <p>Last week</p>
                    </div>
                </div>
                <div class="card">
                    <div>
                        <p>Gifts Generated</p>
                        <div>
                            @if ($analytics_percentages[3]['tips_percentage'] < 0)
                            <small style="color: #ff0000;">{{$analytics_percentages[3]['tips_percentage']}}%</small>
                            @elseif ($analytics_percentages[3]['tips_percentage'] > 0)
                            <small style="color: #00ff00;">{{$analytics_percentages[3]['tips_percentage']}}%</small>
                            @else
                            <small>{{$analytics_percentages[3]['tips_percentage']}}%</small>
                            @endif
                        </div>
                    </div>
                    <div>
                    @isset($contents[0]['current_week'])
                        <h2>${{$contents[0]['current_week']['week_tips']}}</h2>
                    @endisset
                    @isset($contents[1]['previous_week'])
                        <h2>${{$contents[1]['previous_week']['week_tips']}}</h2>
                    @elseif(isset($contents[0]['previous_week']))
                        <h2>${{$contents[0]['previous_week']['week_tips']}}</h2>
                    @endisset
                    </div>
                    <div>
                        <p>This week</p>
                        <p>Last week</p>
                    </div>
                </div>
                <div class="card">
                    <div>
                        <p>Likes</p>
                        <div>
                            @if ($analytics_percentages[4]['likes_percentage'] < 0)
                            <small style="color: #ff0000;">{{$analytics_percentages[4]['likes_percentage']}}%</small>
                            @elseif ($analytics_percentages[4]['likes_percentage'] > 0)
                            <small style="color: #00ff00;">{{$analytics_percentages[4]['likes_percentage']}}%</small>
                            @else
                            <small>{{$analytics_percentages[4]['likes_percentage']}}%</small>
                            @endif
                        </div>
                    </div>
                    <div>
                    @isset($contents[0]['current_week'])
                        <h2>{{$contents[0]['current_week']['week_likes']}}</h2>
                    @endisset
                    @isset($contents[1]['previous_week'])
                        <h2>{{$contents[1]['previous_week']['week_likes']}}</h2>
                    @elseif(isset($contents[0]['previous_week']))
                        <h2>${{$contents[0]['previous_week']['week_likes']}}</h2>
                    @endisset
                    </div>
                    <div>
                        <p>This week</p>
                        <p>Last week</p>
                    </div>
                </div>
                <div class="card">
                    <div>
                        <p>Comments</p>
                        <div>
                            @if ($analytics_percentages[5]['comments_percentage'] < 0)
                            <small style="color: #ff0000;">{{$analytics_percentages[5]['comments_percentage']}}%</small>
                            @elseif ($analytics_percentages[5]['comments_percentage'] > 0)
                            <small style="color: #00ff00;">{{$analytics_percentages[5]['comments_percentage']}}%</small>
                            @else
                            <small>{{$analytics_percentages[5]['comments_percentage']}}%</small>
                            @endif
                        </div>
                    </div>
                    <div>
                    @isset($contents[0]['current_week'])
                        <h2>{{$contents[0]['current_week']['week_comments']}}</h2>
                    @endisset
                    @isset($contents[1]['previous_week'])
                        <h2>{{$contents[1]['previous_week']['week_comments']}}</h2>
                    @elseif(isset($contents[0]['previous_week']))
                        <h2>${{$contents[0]['previous_week']['week_comments']}}</h2>
                    @endisset
                    </div>
                    <div>
                        <p>This week</p>
                        <p>Last week</p>
                    </div>
                </div>
            </div>
            @isset($contents[0]['current_week'])
            <div class="content-details">
                <div class="content-text"> Your content with the most
                engagement this week is:</div>
                <div class="image-container">
                    <img src="{{$contents[0]['current_week']['content_with_highest_engagements']['cover']}}" alt="no image">
                        <div class="content-title">
                            <h3>{{$contents[0]['current_week']['content_with_highest_engagements']['title']}}</h3>
                            <span>Every body loves to dance</span>
                        </div>
                </div>
                @endisset
            </div>

            <div class="content_engaged">
                <div class="card content-card">
                @isset($contents[0]['current_week'])
                    <h2>{{$contents[0]['current_week']['content_with_highest_engagements']['views']}}</h2>
                @endisset
                    <p>Views</p>
                </div>
                <div class="card content-card">
                @isset($contents[0]['current_week'])
                    <h2>{{$contents[0]['current_week']['content_with_highest_engagements']['likes']}}</h2>
                @endisset
                    <p>Likes</p>
                </div>
                <div class="card content-card">
                @isset($contents[0]['current_week'])
                    <h2>{{$contents[0]['current_week']['content_with_highest_engagements']['comments']}}</h2>
                @endisset
                    <p>Comments</p>
                </div>
            </div>
        </section>

        <section>
            <div>
                <h2>Pro Tips for next week:</h2>
                <ol>
                <li>Focus on @isset($contents[0]['current_week']) {{$contents[0]['current_week']['content_with_highest_engagements']['content_type']}}@endisset, as it has had the highest engagement in the past week.</li>
                <li>Remember to share your link to contacts outside Flok!</li>
            </ol>
            <!-- Click this <a href="">link </a> to see detailed breakdown of your numbers this week. <br /> -->
            </div>
        </section>
        <div style="font-size: 16px; line-height: 1.625; color: #51545E; margin: .4em 0 1.1875em;">See you next week!
                <br />Flok</div>
        </div>
                    </td>
                </tr>
		</table>
	</td>
</tr>
@endsection