@extends('emails.layouts.master')

@section('body')
<tr>
	<td class="email-body" width="570" cellpadding="0" cellspacing="0" style="word-break: break-word; margin: 0; padding: 0; font-family: &quot;Nunito Sans&quot;, Helvetica, Arial, sans-serif; font-size: 16px; width: 100%; -premailer-width: 100%; -premailer-cellpadding: 0; -premailer-cellspacing: 0;">
		<table class="email-body_inner" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation" style="width: 570px; -premailer-width: 570px; -premailer-cellpadding: 0; -premailer-cellspacing: 0; background-color: #FFFFFF; margin: 0 auto; padding: 0;" bgcolor="#FFFFFF">
			<!-- Body content -->
			<tr>
				<td class="content-cell" style="word-break: break-word; font-family: &quot;Nunito Sans&quot;, Helvetica, Arial, sans-serif; font-size: 16px; padding: 45px;">
					<div class="f-fallback">
						<h1 style="margin-top: 0; color: #333333; font-size: 22px; font-weight: bold; text-align: left;" align="left">Dear {{$user['name']}},</h1>
						<p style="font-size: 16px; line-height: 1.625; color: #51545E; margin: .4em 0 1.1875em;">We were unable to renew your subscription. Please log into your account and pay for a new subscription. Thank you for your continued patronage.</p>
						<p style="font-size: 16px; line-height: 1.625; color: #51545E; margin: .4em 0 1.1875em;">If you have any questions, feel free to <a href="mailto:contact@akiddie.com.ng" style="color: #276db8;">send us an email</a>.</p>
						<p style="font-size: 16px; line-height: 1.625; color: #51545E; margin: .4em 0 1.1875em;">Thanks,
							<br />Akiddie</p>
						<!-- Sub copy -->
						<table class="body-sub" role="presentation" style="margin-top: 25px; padding-top: 25px; border-top-width: 1px; border-top-color: #EAEAEC; border-top-style: solid;">
							<tr>
								<td style="word-break: break-word; font-family: &quot;Nunito Sans&quot;, Helvetica, Arial, sans-serif; font-size: 16px;">
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