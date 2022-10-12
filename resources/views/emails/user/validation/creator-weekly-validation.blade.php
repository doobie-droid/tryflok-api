@extends('emails.layouts.validation-head')

@section('body')
<!-- Email Body -->
<tr>
    <td class="email-body" width="570" cellpadding="0" cellspacing="0" style="word-break: break-word; margin: 0; padding: 0; font-family: &quot;Nunito Sans&quot;, Helvetica, Arial, sans-serif; font-size: 16px; width: 100%; -premailer-width: 100%; -premailer-cellpadding: 0; -premailer-cellspacing: 0;">
        <table class="email-body_inner" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation" style="width: 570px; -premailer-width: 570px; -premailer-cellpadding: 0; -premailer-cellspacing: 0; background-color: #FFFFFF; margin: 0 auto; padding: 0;" bgcolor="#FFFFFF">
        <!-- Body content -->
            <tr>
                <td class="content-cell" style="word-break: break-word; font-family: &quot;Nunito Sans&quot;, Helvetica, Arial, sans-serif; font-size: 16px; padding: 45px;">
                    <div class="f-fallback">
                    <center class="wrapper" style="width:100%; table-layout: fixed; padding-bottom:  60px;">
    <table class="main" width="100%" style="margin: auto; width:100%;max-width:600px; border-spacing: 0;font-family: sans-serif;color: #171a1b; padding-top: 25px;">
<!-- BANNER IMAGE -->
    <tr>
        <td>
           <img src="https://i.ibb.co/Lrmg911/weekly-flokdate.png" alt="flokdate" width="600px" style="max-width:100%; margin-bottom: 15px;">
        </td>
    </tr>
    <!-- TEXT SECTION -->
    <tr>
        <td>
            <table width="85%" style="margin: 0 auto; color: #51545E;">
                <tr>
                    <td style="padding: 10px;">
                        <p>Hi {{ $user['name'] }}! </p>

                        <p> It’s the end of the week!</p>

                        <p>I imagine that you are planning your execution plans for next week, so we’ve been gathering insights to
                            show
                            you what your audience wants to see in the coming week.</p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    <tr>
        <td>
            <table width="85%" style="background-color: rgba(103, 48, 208, 0.05); border-radius: 10px; margin: 0 auto; padding-bottom: 10px;">
                <tr>
                    <td>
                        <p style="text-align: center; padding-top: 10px;">Engagement and Revenue for {{$start_of_week}} (M) to {{$end_of_week}} (S)</p>
                    </td>
                </tr>
               <tr>
                    <td class="two-columns" style="text-align: center;">
                        <table class="column" style="width: 100%; max-width: 150px; display: inline-block; vertical-align: top; text-align: center; background-color: #ffffff; border-radius: 5px; padding: 10px; margin: 10px;">
                            <tr>
                                <td class="inner-two-columns" style="text-align: left;">
                                    <table class="inner" style="width: 80%; max-width: 100px; display: inline; vertical-align: top; font-size: 10px; padding: 5px; margin: 0; font-weight: bold; text-align: left;">
                                        <tr>
                                            <td>
                                                Total Revenue
                                            </td>
                                        </tr>
                                    </table>
                                    <table class="inner" style="width: 80%; max-width: 100px; display: inline; vertical-align: top; font-size: 10px;  margin: 0; font-weight: bold; text-align: left;">
                                        <tr>
                                            <td>
                                                @if ($analytics_percentages[0]['revenue_percentage'] < 0)
                                                <small style="color: #ff0000;">{{$analytics_percentages[0]['revenue_percentage']}}%</small>
                                                @elseif ($analytics_percentages[0]['revenue_percentage'] > 0)
                                                <small style="color: #00ff00;">{{$analytics_percentages[0]['revenue_percentage']}}%</small>
                                                @else
                                                <small>{{$analytics_percentages[0]['revenue_percentage']}}%</small>
                                                @endif
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                            <tr>
                                <td class="inner-two-columns" style="text-align: left;">
                                    <table class="inner" style="width: 80%; max-width: 100px; display: inline; vertical-align: top; font-size: 10px; padding: 5px; margin: 0; font-weight: bold; text-align: left;padding: 10px;">
                                        <tr>
                                            <td style="color: #6730D0;">
                                                @isset($contents[0]['current_week'])
                                                    ${{$contents[0]['current_week']['week_revenue']}}
                                                @elseif (!isset($contents[0]['current_week']))
                                                    $ 0
                                                @endisset
                                            </td>
                                        </tr>
                                    </table>
                                    <table class="inner" style="width: 80%; max-width: 100px; display: inline; vertical-align: top; font-size: 10px; padding: 5px; margin: 0; font-weight: bold; text-align: left;">
                                        <tr>
                                            <td>
                                                @isset($contents[1]['previous_week'])
                                                    ${{$contents[1]['previous_week']['week_revenue']}}
                                                @elseif(isset($contents[0]['previous_week']))
                                                    ${{$contents[0]['previous_week']['week_revenue']}}
                                                @else
                                                    $ 0
                                                @endisset
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                            <tr>
                                <td class="inner-two-columns" style="text-align: left;">
                                    <table class="inner" style="width: 80%; max-width: 100px; display: inline; vertical-align: top; font-size: 10px; margin: 0; font-weight: bold; text-align: left;">
                                        <tr>
                                            <td>
                                                This Week
                                            </td>
                                        </tr>
                                    </table>
                                    <table class="inner" style="width: 80%; max-width: 100px; display: inline; vertical-align: top; font-size: 10px; margin: 0; font-weight: bold; text-align: left;">
                                        <tr>
                                            <td>
                                                Last Week
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                        <table class="column" style="width: 100%; max-width: 150px; display: inline-block; vertical-align: top; text-align: center; background-color: #ffffff; border-radius: 5px; padding: 10px; margin: 10px;">
                            <tr>
                                <td class="inner-two-columns" style="text-align: left;">
                                    <table class="inner" style="width: 80%; max-width: 100px; display: inline; vertical-align: top; font-size: 10px; padding: 5px; margin: 0; font-weight: bold; text-align: left;">
                                        <tr>
                                            <td>
                                                Subscribers
                                            </td>
                                        </tr>
                                    </table>
                                    <table class="inner" style="width: 80%; max-width: 100px; display: inline; vertical-align: top; font-size: 10px; padding: 5px; margin: 0; font-weight: bold; text-align: left;">
                                        <tr>
                                            <td>
                                                @if ($analytics_percentages[1]['subscribers_percentage'] < 0)
                                                <small style="color: #ff0000;">{{$analytics_percentages[1]['subscribers_percentage']}}%</small>
                                                @elseif ($analytics_percentages[1]['subscribers_percentage'] > 0)
                                                <small style="color: #00ff00;">{{$analytics_percentages[1]['subscribers_percentage']}}%</small>
                                                @else
                                                <small>{{$analytics_percentages[1]['subscribers_percentage']}}%</small>
                                                @endif
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                            <tr>
                                <td class="inner-two-columns" style="text-align: left;">
                                    <table class="inner" style="width: 80%; max-width: 100px; display: inline; vertical-align: top; font-size: 10px; padding: 5px; margin: 0; font-weight: bold; text-align: left;padding: 20px;">
                                        <tr>
                                            <td style="color: #6730D0;">
                                                @isset($contents[0]['current_week'])
                                                    {{$contents[0]['current_week']['week_subscribers']}}
                                                @elseif (!isset($contents[0]['current_week']))
                                                    $ 0
                                                @endisset
                                            </td>
                                        </tr>
                                    </table>
                                    <table class="inner" style="width: 80%; max-width: 100px; display: inline; vertical-align: top; font-size: 10px; padding: 5px; margin: 0; font-weight: bold; text-align: left;">
                                        <tr>
                                            <td>
                                                @isset($contents[1]['previous_week'])
                                                    {{$contents[1]['previous_week']['week_subscribers']}}
                                                @elseif(isset($contents[0]['previous_week']))
                                                    ${{$contents[0]['previous_week']['week_subscribers']}}
                                                    @else
                                                    $ 0
                                                @endisset
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                            <tr>
                                <td class="inner-two-columns" style="text-align: left;">
                                    <table class="inner" style="width: 80%; max-width: 100px; display: inline; vertical-align: top; font-size: 10px;  margin: 0; font-weight: bold; text-align: left;">
                                        <tr>
                                            <td>
                                                This Week
                                            </td>
                                        </tr>
                                    </table>
                                    <table class="inner" style="width: 80%; max-width: 100px; display: inline; vertical-align: top; font-size: 10px;  margin: 0; font-weight: bold; text-align: left;">
                                        <tr>
                                            <td>
                                                Last Week
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                    </td>
               </tr>
               <tr>
                    <td class="two-columns" style="text-align: center;">
                    <table class="column" style="width: 100%; max-width: 150px; display: inline-block; vertical-align: top; text-align: center; background-color: #ffffff; border-radius: 5px; padding: 10px; margin: 10px;">
                            <tr>
                                <td class="inner-two-columns" style="text-align: left;">
                                    <table class="inner" style="width: 80%; max-width: 100px; display: inline; vertical-align: top; font-size: 10px; padding: 5px; margin: 0; font-weight: bold; text-align: left;padding: 10px;">
                                        <tr>
                                            <td>
                                                Sales
                                            </td>
                                        </tr>
                                    </table>
                                    <table class="inner" style="width: 80%; max-width: 100px; display: inline; vertical-align: top; font-size: 10px; padding: 5px; margin: 0; font-weight: bold; text-align: left;">
                                        <tr>
                                            <td>
                                              @if ($analytics_percentages[2]['sales_percentage'] < 0)
                                                <small style="color: #ff0000;">{{$analytics_percentages[2]['sales_percentage']}}%</small>
                                                @elseif ($analytics_percentages[2]['sales_percentage'] > 0)
                                                <small style="color: #00ff00;">{{$analytics_percentages[2]['sales_percentage']}}%</small>
                                                @else
                                                <small>{{$analytics_percentages[2]['sales_percentage']}}%</small>
                                                @endif
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                            <tr>
                                <td class="inner-two-columns" style="text-align: left;">
                                    <table class="inner" style="width: 80%; max-width: 100px; display: inline; vertical-align: top; font-size: 10px; padding: 5px; margin: 0; font-weight: bold; text-align: left;padding: 20px;">
                                        <tr>
                                            <td style="color: #6730D0;">
                                                @isset($contents[0]['current_week'])
                                                    ${{$contents[0]['current_week']['week_sales']}}
                                                @elseif (!isset($contents[0]['current_week']))
                                                    $ 0
                                                @endisset
                                            </td>
                                        </tr>
                                    </table>
                                    <table class="inner" style="width: 80%; max-width: 100px; display: inline; vertical-align: top; font-size: 10px; padding: 5px; margin: 0; font-weight: bold; text-align: left;">
                                        <tr>
                                            <td>
                                                @isset($contents[1]['previous_week'])
                                                    ${{$contents[1]['previous_week']['week_sales']}}
                                                @elseif(isset($contents[0]['previous_week']))
                                                    ${{$contents[0]['previous_week']['week_sales']}}
                                                    @else
                                                    $ 0
                                                @endisset
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                            <tr>
                                <td class="inner-two-columns" style="text-align: left;">
                                    <table class="inner" style="width: 80%; max-width: 100px; display: inline; vertical-align: top; font-size: 10px; margin: 0; font-weight: bold; text-align: left;">
                                        <tr>
                                            <td>
                                                This Week
                                            </td>
                                        </tr>
                                    </table>
                                    <table class="inner" style="width: 80%; max-width: 100px; display: inline; vertical-align: top; font-size: 10px;  margin: 0; font-weight: bold; text-align: left;">
                                        <tr>
                                            <td>
                                                Last Week
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                        <table class="column" style="width: 100%; max-width: 150px; display: inline-block; vertical-align: top; text-align: center; background-color: #ffffff; border-radius: 5px; padding: 10px; margin: 10px;">
                            <tr>
                                <td class="inner-two-columns" style="text-align: left;">
                                    <table class="inner" style="width: 80%; max-width: 100px; display: inline; vertical-align: top; font-size: 10px; padding: 5px; margin: 0; font-weight: bold; text-align: left;padding: 10px;">
                                        <tr>
                                            <td>
                                                Gifts
                                            </td>
                                        </tr>
                                    </table>
                                    <table class="inner" style="width: 80%; max-width: 100px; display: inline; vertical-align: top; font-size: 10px; padding: 5px; margin: 0; font-weight: bold; text-align: left;">
                                        <tr>
                                            <td>
                                                @if ($analytics_percentages[3]['tips_percentage'] < 0)
                                                <small style="color: #ff0000;">{{$analytics_percentages[3]['tips_percentage']}}%</small>
                                                @elseif ($analytics_percentages[3]['tips_percentage'] > 0)
                                                <small style="color: #00ff00;">{{$analytics_percentages[3]['tips_percentage']}}%</small>
                                                @else
                                                <small>{{$analytics_percentages[3]['tips_percentage']}}%</small>
                                                @endif
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                            <tr>
                                <td class="inner-two-columns" style="text-align: left;">
                                    <table class="inner" style="width: 80%; max-width: 100px; display: inline; vertical-align: top; font-size: 10px; padding: 5px; margin: 0; font-weight: bold; text-align: left;padding: 20px;">
                                        <tr>
                                            <td style="color: #6730D0;">
                                                @isset($contents[0]['current_week'])
                                                    ${{$contents[0]['current_week']['week_tips']}}
                                                @elseif (!isset($contents[0]['current_week']))
                                                    $ 0
                                                @endisset
                                            </td>
                                        </tr>
                                    </table>
                                    <table class="inner" style="width: 80%; max-width: 100px; display: inline; vertical-align: top; font-size: 10px; padding: 5px; margin: 0; font-weight: bold; text-align: left;">
                                        <tr>
                                            <td>
                                                @isset($contents[1]['previous_week'])
                                                    ${{$contents[1]['previous_week']['week_tips']}}
                                                @elseif(isset($contents[0]['previous_week']))
                                                    ${{$contents[0]['previous_week']['week_tips']}}
                                                    @else
                                                    $ 0
                                                @endisset
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                            <tr>
                                <td class="inner-two-columns" style="text-align: left;">
                                    <table class="inner" style="width: 80%; max-width: 100px; display: inline; vertical-align: top; font-size: 10px; margin: 0; font-weight: bold; text-align: left;">
                                        <tr>
                                            <td>
                                                This Week
                                            </td>
                                        </tr>
                                    </table>
                                    <table class="inner" style="width: 80%; max-width: 100px; display: inline; vertical-align: top; font-size: 10px; margin: 0; font-weight: bold; text-align: left;">
                                        <tr>
                                            <td>
                                                Last Week
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                    </td>
               </tr>
               <tr>
                    <td class="two-columns" style="text-align: center;">
                    <table class="column" style="width: 100%; max-width: 150px; display: inline-block; vertical-align: top; text-align: center; background-color: #ffffff; border-radius: 5px; padding: 10px; margin: 10px;">
                            <tr>
                                <td class="inner-two-columns" style="text-align: left;">
                                    <table class="inner" style="width: 80%; max-width: 100px; display: inline; vertical-align: top; font-size: 10px; margin: 0; font-weight: bold; text-align: left; padding: 10px;">
                                        <tr>
                                            <td>
                                                Likes
                                            </td>
                                        </tr>
                                    </table>
                                    <table class="inner" style="width: 80%; max-width: 100px; display: inline; vertical-align: top; font-size: 10px; margin: 0; font-weight: bold; text-align: left;">
                                        <tr>
                                            <td>
                                                @if ($analytics_percentages[4]['likes_percentage'] < 0)
                                                <small style="color: #ff0000;">{{$analytics_percentages[4]['likes_percentage']}}%</small>
                                                @elseif ($analytics_percentages[4]['likes_percentage'] > 0)
                                                <small style="color: #00ff00;">{{$analytics_percentages[4]['likes_percentage']}}%</small>
                                                @else
                                                <small>{{$analytics_percentages[4]['likes_percentage']}}%</small>
                                                @endif
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                            <tr>
                                <td class="inner-two-columns" style="text-align: left;">
                                    <table class="inner" style="width: 80%; max-width: 100px; display: inline; vertical-align: top; font-size: 10px; padding: 5px; margin: 0; font-weight: bold; text-align: left; padding: 20px;">
                                        <tr>
                                            <td style="color: #6730D0;">
                                                @isset($contents[0]['current_week'])
                                                    {{$contents[0]['current_week']['week_likes']}}
                                                @elseif (!isset($contents[0]['current_week']))
                                                    $ 0
                                                @endisset
                                            </td>
                                        </tr>
                                    </table>
                                    <table class="inner" style="width: 80%; max-width: 100px; display: inline; vertical-align: top; font-size: 10px; padding: 5px; margin: 0; font-weight: bold; text-align: left;">
                                        <tr>
                                            <td>
                                                @isset($contents[1]['previous_week'])
                                                    {{$contents[1]['previous_week']['week_likes']}}
                                                @elseif(isset($contents[0]['previous_week']))
                                                    {{$contents[0]['previous_week']['week_likes']}}
                                                    @else
                                                    $ 0
                                                @endisset
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                            <tr>
                                <td class="inner-two-columns" style="text-align: left;">
                                    <table class="inner" style="width: 80%; max-width: 100px; display: inline; vertical-align: top; font-size: 10px;  margin: 0; font-weight: bold; text-align: left;">
                                        <tr>
                                            <td>
                                                This Week
                                            </td>
                                        </tr>
                                    </table>
                                    <table class="inner" style="width: 80%; max-width: 100px; display: inline; vertical-align: top; font-size: 10px; margin: 0; font-weight: bold; text-align: left;">
                                        <tr>
                                            <td>
                                                Last Week
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                        <table class="column" style="width: 100%; max-width: 150px; display: inline-block; vertical-align: top; text-align: center; background-color: #ffffff; border-radius: 5px; padding: 10px; margin: 10px;">
                            <tr>
                                <td class="inner-two-columns" style="text-align: left;">
                                    <table class="inner" style="width: 80%; max-width: 100px; display: inline; vertical-align: top; font-size: 10px; margin: 0; font-weight: bold; text-align: left; padding: 10px;">
                                        <tr>
                                            <td>
                                                Comments
                                            </td>
                                        </tr>
                                    </table>
                                    <table class="inner" style="width: 80%; max-width: 100px; display: inline; vertical-align: top; font-size: 10px; padding: 5px; margin: 0; font-weight: bold; text-align: left;">
                                        <tr>
                                            <td>
                                                @if ($analytics_percentages[5]['comments_percentage'] < 0)
                                                <small style="color: #ff0000;">{{$analytics_percentages[5]['comments_percentage']}}%</small>
                                                @elseif ($analytics_percentages[5]['comments_percentage'] > 0)
                                                <small style="color: #00ff00;">{{$analytics_percentages[5]['comments_percentage']}}%</small>
                                                @else
                                                <small>{{$analytics_percentages[5]['comments_percentage']}}%</small>
                                                @endif
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                            <tr>
                                <td class="inner-two-columns" style="text-align: left;">
                                    <table class="inner" style="width: 80%; max-width: 100px; display: inline; vertical-align: top; font-size: 10px; padding: 5px; margin: 0; font-weight: bold; text-align: left; padding: 10px;">
                                        <tr>
                                            <td style="color: #6730D0;">
                                                @isset($contents[0]['current_week'])
                                                    {{$contents[0]['current_week']['week_comments']}}
                                                @elseif (!isset($contents[0]['current_week']))
                                                    $ 0
                                                @endisset
                                            </td>
                                        </tr>
                                    </table>
                                    <table class="inner" style="width: 80%; max-width: 100px; display: inline; vertical-align: top; font-size: 10px; padding: 15px; margin: 0; font-weight: bold; text-align: left;">
                                        <tr>
                                            <td>
                                                @isset($contents[1]['previous_week'])
                                                    {{$contents[1]['previous_week']['week_comments']}}
                                                @elseif(isset($contents[0]['previous_week']))
                                                    {{$contents[0]['previous_week']['week_comments']}}
                                                    @else
                                                    $ 0
                                                @endisset
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                            <tr>
                                <td class="inner-two-columns" style="text-align: left;">
                                    <table class="inner" style="width: 80%; max-width: 100px; display: inline; vertical-align: top; font-size: 10px; margin: 0; font-weight: bold; text-align: left;">
                                        <tr>
                                            <td>
                                                This Week
                                            </td>
                                        </tr>
                                    </table>
                                    <table class="inner" style="width: 80%; max-width: 100px; display: inline; vertical-align: top; font-size: 10px;  margin: 0; font-weight: bold; text-align: left;">
                                        <tr>
                                            <td>
                                                Last Week
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                    </td>
               </tr>
               @isset($contents[0]['current_week'])
               <tr>
                    <td>
                        <p style="text-align: center;">Your content with the most engagement this week is:</p>
                    </td>
               </tr>
               <tr>
                    <td style="text-align: center">
                         <img src="{{{$contents[0]['current_week']['content_with_highest_engagements']['cover']}}}" alt="no img" width="350px" style="max-width:100%; text-align: center; border-radius: 10px;">
                         <p class="content-title" width="350px" style="height: 48.62px; background: rgba(255, 255, 255, 0.02); backdrop-filter: blur(10px); max-width: 100%; position: relative; top: 0px;right: 0px;color: #000000; border-radius: 10px; padding-left: 0px;padding-right: 0px;">{{{$contents[0]['current_week']['content_with_highest_engagements']['title']}}}</p>
                    </td>
               </tr>
               <tr>
                <td>
                    <table>
                        <tr>
                            <td class="three-columns" style="text-align: center; width: 400px;">
                                <table class="column-three" style="width: 100%; max-width: 110px; display: inline-block; vertical-align: top; background-color: #ffffff; border-radius: 5px; padding: 10px; margin: 5px; font-weight: bold;">
                                    <tr>
                                        <td>
                                            <p>{{{$contents[0]['current_week']['content_with_highest_engagements']['views']}}}</p>
                                            <p>Views</p>
                                        </td>
                                    </tr>
                                </table>
                                <table class="column-three" style="width: 100%; max-width: 110px; display: inline-block; vertical-align: top; background-color: #ffffff; border-radius: 5px; padding: 10px; margin: 5px; font-weight: bold;">
                                    <tr>
                                        <td>
                                            <p>{{{$contents[0]['current_week']['content_with_highest_engagements']['likes']}}}</p>
                                            <p>Likes</p>
                                        </td>
                                    </tr>
                                </table>
                                <table class="column-three" style="width: 100%; max-width: 110px; display: inline-block; vertical-align: top; background-color: #ffffff; border-radius: 5px; padding: 10px; margin: 5px; font-weight: bold;">
                                    <tr>
                                        <td>
                                            <p>{{{$contents[0]['current_week']['content_with_highest_engagements']['comments']}}}</p>
                                            <p>Comments</p>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                </td>
               </tr>
               @endisset
            </table>
        </td>
    </tr>
    <tr>
        <td>
            <table width="85%" style="margin: 0 auto; color: #51545E;">
                <tr>
                <td>
                    <p>Pro Tips for next week:</p>
                        <ol>
                            @if(isset($contents[0]['current_week']))
                            <li>Focus on @isset($contents[0]['current_week']) {{{$contents[0]['current_week']['content_with_highest_engagements']['content_type']}}}@endisset, as it has had the highest engagement in the past week.</li>
                            @elseif(isset($contents[0]['previous_week']))
                            <li>Focus on @isset($contents[0]['previous_week']) {{{$contents[0]['previous_week']['content_with_highest_engagements']['content_type']}}}@endisset, as it has had the highest engagement in the past week.</li>
                            @endisset
                            <li>Remember to share your link to contacts outside Flok!</li>
                        </ol>
                        <!-- Click this <a href="">link </a> to see detailed breakdown of your numbers this week. <br /> -->
                    <p style="font-size: 16px; line-height: 1.625; color: #51545E; margin: .4em 0 1.1875em;">See you next week!
                            <br />Flok</div>
                    </p>
                </td>
                </tr>
            </table>
        </td>
    </tr>
    </table>
</center>
                    </div>                        
                </td>
            </tr>
		</table>
	</td>
</tr>
@endsection