<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.12.0-2/css/fontawesome.min.css" />  
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.12.0-2/css/all.min.css" /> 
	<title></title>
	<style type="text/css" rel="stylesheet" media="all">
		/* Base ------------------------------ */

		@import url("https://fonts.googleapis.com/css?family=Nunito+Sans:400,700&amp;display=swap");
		body {
			width: 100% !important;
			height: 100%;
			margin: 0;
			-webkit-text-size-adjust: none;
		}

		.preheader {
			display: none !important;
			visibility: hidden;
			mso-hide: all;
			font-size: 1px;
			line-height: 1px;
			max-height: 0;
			max-width: 0;
			opacity: 0;
			overflow: hidden;
		}
		/* Type ------------------------------ */

		body,
		td,
		th {
			font-family: "Nunito Sans", Helvetica, Arial, sans-serif;
		}

		h1 {
			margin-top: 0;
			color: #333333;
			font-size: 22px;
			font-weight: bold;
			text-align: left;
		}

		p,
		ul,
		ol,
		blockquote {
			margin: .4em 0 1.1875em;
			font-size: 16px;
			line-height: 1.625;
		}

		p.sub {
			font-size: 13px;
		}
		/* Utilities ------------------------------ */

		.align-right {
			text-align: right;
		}

		.align-left {
			text-align: left;
		}

		.align-center {
			text-align: center;
		}
		
		/* Attribute list ------------------------------ */

		.attributes {
			margin: 0 0 21px;
		}

		.attributes_content {
			background-color: #F4F4F7;
			padding: 16px;
		}

		.attributes_item {
			padding: 0;
		}
		

		body {
			background-color: #F2F4F6;
			color: #51545E;
		}

		.email-wrapper {
			width: 100%;
			margin: 0;
			padding: 0;
			-premailer-width: 100%;
			-premailer-cellpadding: 0;
			-premailer-cellspacing: 0;
			background-color: #F2F4F6;
		}

		.email-content {
			width: 100%;
			margin: 0;
			padding: 0;
			-premailer-width: 100%;
			-premailer-cellpadding: 0;
			-premailer-cellspacing: 0;
		}
	
		

		.content-cell {
			padding: 45px;
		}
		/*Media Queries ------------------------------ */

		@media only screen and (max-width: 600px) {
			.email-body_inner,
			.email-footer {
				width: 100% !important;
			}
		}

       
	</style>
	<!--[if mso]>
	<style type="text/css">
		.f-fallback  {
			font-family: Arial, sans-serif;
		}
	</style>
	<![endif]-->
	<style type="text/css" rel="stylesheet" media="all">
		body {
			width: 100% !important;
			height: 100%;
			margin: 0;
			-webkit-text-size-adjust: none;
		}

		body {
			font-family: "Nunito Sans", Helvetica, Arial, sans-serif;
		}

		body {
			background-color: #F2F4F6;
			color: #51545E;
		}
	</style>
</head>
<body style="width: 100% !important; height: 100%; -webkit-text-size-adjust: none; font-family: &quot;Nunito Sans&quot;, Helvetica, Arial, sans-serif; background-color: #F2F4F6; color: #51545E; margin: 0;" bgcolor="#F2F4F6">
<table class="email-wrapper" width="100%" cellpadding="0" cellspacing="0" role="presentation" style="width: 100%; -premailer-width: 100%; -premailer-cellpadding: 0; -premailer-cellspacing: 0; background-color: #F2F4F6; margin: 0; padding: 0;" bgcolor="#F2F4F6">
	<tr>
		<td align="center" style="word-break: break-word; font-family: &quot;Nunito Sans&quot;, Helvetica, Arial, sans-serif; font-size: 16px;">
			<table class="email-content" width="100%" cellpadding="0" cellspacing="0" role="presentation" style="width: 100%; -premailer-width: 100%; -premailer-cellpadding: 0; -premailer-cellspacing: 0; margin: 0; padding: 0;">
				<tr>
					<td class="email-masthead" style="word-break: break-word; font-family: &quot;Nunito Sans&quot;, Helvetica, Arial, sans-serif; font-size: 16px; text-align: center; padding: 25px 0;" align="center">
						<a href="{{ config('flok.frontend_url') }}" class="f-fallback email-masthead_name" style="color: #A8AAAF; font-size: 16px; font-weight: bold; text-decoration: none; text-shadow: 0 1px 0 white;">
							<img src="https://res.cloudinary.com/akiddie/image/upload/v1639156702/flok-logo.png" style="width: 100px; border-radius: 50%;">
						</a>
					</td>
				</tr>
                <!-- Email Body -->
                @yield('body')
				<tr>
					<td style="word-break: break-word; font-family: &quot;Nunito Sans&quot;, Helvetica, Arial, sans-serif; font-size: 16px;">
						<table class="email-footer" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation" style="width: 570px; -premailer-width: 570px; -premailer-cellpadding: 0; -premailer-cellspacing: 0; text-align: center; margin: 0 auto; padding: 0;">
							<tr>
								<td class="content-cell" align="center" style="word-break: break-word; font-family: &quot;Nunito Sans&quot;, Helvetica, Arial, sans-serif; font-size: 16px; padding: 45px;">
									<p class="f-fallback sub align-center" style="font-size: 13px; line-height: 1.625; text-align: center; color: #A8AAAF; margin: .4em 0 1.1875em;" align="center">© {{ date('Y') }} Flok. All rights reserved.</p>
									<p class="f-fallback sub align-center" style="font-size: 13px; line-height: 1.625; text-align: center; color: #A8AAAF; margin: .4em 0 1.1875em;" align="center">
										Flok
										<br />www.tryflok.com
									</p>
								</td>
							</tr>
						</table>
					</td>
				</tr>
			</table>
		</td>
	</tr>
</table>
</body>
</html>
