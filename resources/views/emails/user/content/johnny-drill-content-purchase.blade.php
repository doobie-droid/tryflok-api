@extends('emails.layouts.master')

@section('body')
<!-- Email Body -->
<tr>
    <td class="email-body" width="570" cellpadding="0" cellspacing="0" style="word-break: break-word; margin: 0; padding: 0; font-family: &quot;Nunito Sans&quot;, Helvetica, Arial, sans-serif; font-size: 16px; width: 100%; -premailer-width: 100%; -premailer-cellpadding: 0; -premailer-cellspacing: 0;">
        <table class="email-body_inner" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation" style="width: 570px; -premailer-width: 570px; -premailer-cellpadding: 0; -premailer-cellspacing: 0; background-color: #FFFFFF; margin: 0 auto; padding: 0;" bgcolor="#FFFFFF">
        <!-- Body content -->
            <tr>
                <td class="content-cell" style="word-break: break-word; font-family: &quot;Nunito Sans&quot;, Helvetica, Arial, sans-serif; font-size: 16px; padding: 45px;">
                    <div class="f-fallback">

                    <table width="85%" style="margin: 0 auto; color: #51545E;">
                    <tr>
                        <td style="padding: 10px;">
                            <p>Hi {{ $name}}! </p>
                            <p style="margin-top: 0; color: #333333; font-size: 22px; font-weight: bold;" align="center">You are Johnny's friend #{{$sales_count}} </br>
                            <span style="font-size: 16px; line-height: 1.625; color: #51545E; margin: .4em 0 1.1875em;" align="center"> Johnny’s Room IV Virtual Concert </span> </p>
                        </td>
                    </tr>
                </table>
                <table>
                        @if($avatar_url != '')
                    <tr>
                        <td style="text-align: center">
                            <img src="{{$avatar_url}}" alt="no img" width="350px" style="max-width:100%; text-align: center; margin-bottom: 15px;">
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <p style="font-size: 16px; line-height: 1.625; color: #51545E; margin: .4em 0 1.1875em;"> This is your personal Johnny Drille avatar. It’s unique to just you and won’t be assigned to anybody else. Feel free to download it share it on social media to let your friends know what you’d be doing on November 13th 😉</p>
                        </td>
                    </tr>
                        @endif
                    <!-- <tr>
                        <td>
                            <img src="https://www.mail-signatures.com/wp-content/uploads/2014/08/Twitter.png" alt="twitter-icon" width="20px">
                        </td>
                        <td>
                            <img src="https://www.mail-signatures.com/wp-content/uploads/2014/08/Instagram.png" alt="instagram-icon" width="20px">
                        </td>
                        <td>
                            <img src="https://cdn-icons-png.flaticon.com/128/6509/6509383.png" alt="copy-link-icon" width="20px">
                        </td>
                    </tr> -->
                    <tr>
                        <td style="text-align: center">
                            <p style="font-size: 16px; color: #ffffff; padding: 20px; width: 90%;  margin-bottom: 20px; background-color: #6730D0;">
                                <span>Your livestream access code(s): </span>
                                @foreach ($access_tokens  as $access_token)
                                    {{ $access_token}}  
                                        @if( !$loop->last)
                                            ,
                                        @endif                              
                                @endforeach
                                </br>
                                @if($content_url != '')
                                <span>Follow <a href="{{ $content_url }}" style="color: #ffffff;"> this link </a> to view the content </span>
                                @endif
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td>
                        <h1 style="margin-top: 0; color: #333333; font-size: 22px; font-weight: bold; text-align: left;" align="left">Instructions:</h1>
                            <p style="font-size: 16px; line-height: 1.625; color: #51545E; margin: .4em 0 1.1875em;">
                                <ol>
                                    <li>You’ve received this email because of your successful purchase of a ticket to Johnny’s Room IV Virtual Experience. Your access code to the live stream is above.</li>
                                    <li>When its time for the live stream to begin, click the link of the event and click the “access code” button.</li>
                                    <li>Kindly ensure that your access code is not shared to anyone, as each access code received can only be used by one(1) person while the live stream is ongoing.</li>
                                    <li>To enjoy a better streaming experience, watch the live stream on the Flok app on Android and iOS.</li>
                                    <li>If you purchased multiple tickets, each of their avatars are attached to this email.</li>
                                </ol>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <p style="font-size: 16px; line-height: 1.625; color: #51545E; margin: .4em 0 1.1875em;">If you have any questions, feel free to <a href="mailto:contact@tryflok.com" style="color: #6E4CF5;">send us an email</a>.</p>
                            <p style="font-size: 16px; line-height: 1.625; color: #51545E; margin: .4em 0 1.1875em;">Thanks,
                                <br />Flok</p>
                        </td>
                    </tr>
                </table>                   
					</div>
				</td>
			</tr>
		</table>
	</td>
</tr>
@endsection
