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
						<h1 style="margin-top: 0; color: #333333; font-size: 22px; font-weight: bold; text-align: left;" align="left">Hello, {{ $user['name'] }}!</h1>
						<p style="font-size: 16px; line-height: 1.625; color: #51545E; margin: .4em 0 1.1875em;">You recently reported that you had forgotten your password. Please click the link below to reset it:</p>
						<!-- Action -->
						<table class="body-action" align="center" width="100%" cellpadding="0" cellspacing="0" role="presentation" style="width: 100%; -premailer-width: 100%; -premailer-cellpadding: 0; -premailer-cellspacing: 0; text-align: center; margin: 30px auto; padding: 0;">
							<tr>
								<td align="center" style="word-break: break-word; font-family: &quot;Nunito Sans&quot;, Helvetica, Arial, sans-serif; font-size: 16px;">
									<!-- Border based button
													https://litmus.com/blog/a-guide-to-bulletproof-buttons-in-email-design -->
									<table width="100%" border="0" cellspacing="0" cellpadding="0" role="presentation">
										<tr>
											<td align="center" style="word-break: break-word; font-family: &quot;Nunito Sans&quot;, Helvetica, Arial, sans-serif; font-size: 16px;">
												<a href="{{ join_path(config('flok.frontend_url'), 'password/reset/' . $user['password_token']) }}" class="f-fallback button" target="_blank" style="color: #FFF; border-color: #6E4CF5
; border-style: solid; border-width: 10px 18px; background-color: #6E4CF5
; display: inline-block; text-decoration: none; border-radius: 3px; box-shadow: 0 2px 3px rgba(0, 0, 0, 0.16); -webkit-text-size-adjust: none; box-sizing: border-box;">Reset Password</a>
											</td>
										</tr>
									</table>
								</td>
							</tr>
						</table>
						<p style="font-size: 16px; line-height: 1.625; color: #51545E; margin: .4em 0 1.1875em;">If you have any questions, feel free to <a href="mailto:contact@tryflok.com" style="color: #6E4CF5
;">send us an email</a>.</p>
						<p style="font-size: 16px; line-height: 1.625; color: #51545E; margin: .4em 0 1.1875em;">Thanks,
							<br />Flok</p>
						<!-- Sub copy -->
						<table class="body-sub" role="presentation" style="margin-top: 25px; padding-top: 25px; border-top-width: 1px; border-top-color: #EAEAEC; border-top-style: solid;">
							<tr>
								<td style="word-break: break-word; font-family: &quot;Nunito Sans&quot;, Helvetica, Arial, sans-serif; font-size: 16px;">
									<p class="f-fallback sub" style="font-size: 13px; line-height: 1.625; color: #51545E; margin: .4em 0 1.1875em;">If you’re having trouble with the button above, copy and paste the URL below into your web browser.</p>
									<p class="f-fallback sub" style="font-size: 13px; line-height: 1.625; color: #51545E; margin: .4em 0 1.1875em;">{{ join_path(config('flok.frontend_url'), 'password/reset/' . $user['password_token']) }}</p>
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
