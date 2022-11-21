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
                        <td>
                            <p>Hi {{ $name }}! </p>
                            @if($pdf_status == 0)
                                <p style="font-size: 16px; line-height: 1.625; color: #51545E; margin: .4em 0 1.1875em;" align="left">{{$contents}} </br>
                            @endif
                            @if($pdf_status == 1)
                                <p style="font-size: 16px; line-height: 1.625; color: #51545E; margin: .4em 0 1.1875em;" align="left">{{$pdf_message}} </br>
                            @endif
                        </td>
                    </tr>
                </table>
                <table>
                    <tr>
                        <td style="text-align: center">
                            <p style="font-size: 16px; color: #ffffff; padding: 20px; width: 90%;  margin-bottom: 20px; background-color: #6730D0;">
                                <span>Your access code(s): </span>
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
                            @if($pdf_status == 0)
                                <ol>
                                    <li>You’ve received this email because of your successful purchase of a content. Your access code to the content is above.</li>
                                    <li>When its time for a live stream to begin, click the link of the event and click the “access code” button.</li>
                                    <li>Kindly ensure that your access code is not shared to anyone, as each access code received can only be used by one(1) person while the live stream is ongoing.</li>
                                    <li>To enjoy a better streaming experience, watch the live stream on the Flok app on Android and iOS.</li>
                                </ol>
                            @endif
                            @if($pdf_status == 1)
                                <ol>
                                    <li>You’ve received this email because of your successful purchase of a content. Your access code to the content is above.</li>
                                    <li>Kindly ensure that your access code is not shared to anyone.</li>
                                </ol>
                            @endif
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
