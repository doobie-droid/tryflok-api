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
                            <div class="banner-background" style = "background: url({{asset('images/weekly-flokdate.png')}});"></div>
                        </div>
                    <div >Hi (First Name)! <br />

                    It’s the end of the week! <br />

                    I imagine that you are planning your execution plans for next week, so we’ve been gathering insights to show you what your audience wants to see in the coming week.
                    </div>
                    <div class="analytics-wrapper">
                    <div class="analytics-title" style="font-size: 14px; line-height: 1.625; color: #51545E; margin: .4em 0 1.1875em;">Engagement and Revenue for 
                    15/06/22 (M) to 21/06/22 (S)
                    </div>
                    <div class="col-9 box-main">
                        <div class="row box-row">
                            <div class="col-6 box-inner">

                            </div>
                            <div class="col-6 box-inner">
                                
                            </div>
                        </div>
                        <div class="row box-row">
                            <div class="col-6 box-inner">

                            </div>
                            <div class="col-6 box-inner">
                                
                            </div>
                        </div>
                        <div class="row box-row">
                            <div class="col-6 box-inner">

                            </div>
                            <div class="col-6 box-inner">
                                
                            </div>
                        </div>

                    </div>
                    <!-- <div class="box-wrapper">
                            <div class="analytics-box1">
                                <span class="box-title">Total Revenue <span class="percentage">10%</span></span>
                                <span class="this-week">$2020<span class="previous-week">$2020</span></span>
                                <span class="this-week-label">This Week <span class="previous-week-label">Last Week</span></span>
                            </div>
                            <div class="analytics-box2">
                                <span class="box-title">New Subscribers <span class="percentage">10%</span></span>
                                <span class="this-week">202<span class="previous-week">20</span></span>
                                <span class="this-week-label">This Week <span class="previous-week-label">Last Week</span></span>
                            </div>
                    </div> -->
                    </div>
                    <div class="box-wrapper">
                       
                    </div>
                    <div class="box-wrapper">
                       
                    </div>
                        
					<div style="font-size: 16px; line-height: 1.625; color: #51545E; margin: .4em 0 1.1875em;">See you next week!
							<br />Flok</div>
					</div>
				</td>
			</tr>
		</table>
	</td>
</tr>
@endsection