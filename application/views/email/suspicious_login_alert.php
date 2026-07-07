<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title><?php echo $subject; ?></title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #f6f6f6; margin: 0; padding: 0; -webkit-font-smoothing: antialiased; }
        .container { max-width: 600px; margin: 0 auto; background-color: #ffffff; padding: 0; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .header { background-color: #2754c5; color: #ffffff; text-align: center; padding: 30px 20px; }
        .header h1 { margin: 0; font-size: 24px; font-weight: 500; }
        .content { padding: 40px 30px; color: #333333; line-height: 1.6; }
        .content h2 { margin-top: 0; color: #333333; font-size: 20px; font-weight: 500; }
        .info-box { background-color: #f9f9f9; border-left: 4px solid #2754c5; padding: 15px 20px; margin: 25px 0; border-radius: 0 4px 4px 0; }
        .info-row { margin-bottom: 10px; }
        .info-row:last-child { margin-bottom: 0; }
        .info-label { font-weight: bold; color: #555555; width: 100px; display: inline-block; }
        .info-value { color: #333333; }
        .action-area { text-align: center; margin-top: 35px; margin-bottom: 20px; }
        .btn { display: inline-block; padding: 12px 24px; text-decoration: none; border-radius: 4px; font-weight: bold; font-size: 16px; margin: 10px 5px; }
        .btn-yes { background-color: #28a745; color: #ffffff; }
        .btn-no { background-color: #dc3545; color: #ffffff; }
        .footer { background-color: #f9f9f9; padding: 20px 30px; text-align: center; color: #777777; font-size: 13px; border-top: 1px solid #eeeeee; }
        .footer a { color: #2754c5; text-decoration: none; }
        @media only screen and (max-width: 620px) {
            .container { width: 100%; border-radius: 0; }
            .content { padding: 30px 20px; }
            .btn { display: block; margin: 15px 0; }
        }
    </style>
</head>
<body>
    <div style="padding: 20px 0;">
        <div class="container">
            <div class="header">
                <h1>Security Alert: New Login</h1>
            </div>
            
            <div class="content">
                <h2>Hi <?php echo htmlspecialchars($name); ?>,</h2>
                <p>We noticed a new login to your <?php echo htmlspecialchars($system_name); ?> account from an unrecognized device or location.</p>
                
                <div class="info-box">
                    <div class="info-row">
                        <span class="info-label">When:</span>
                        <span class="info-value"><?php echo htmlspecialchars($login_info['login_time']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Device:</span>
                        <span class="info-value"><?php echo htmlspecialchars($device_label); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Location:</span>
                        <span class="info-value"><?php echo htmlspecialchars($location); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">IP Address:</span>
                        <span class="info-value"><?php echo htmlspecialchars($login_info['ip_address']); ?></span>
                    </div>
                </div>
                
                <p><strong>Was this you?</strong></p>
                
                <div class="action-area">
                    <a href="<?php echo $yes_url; ?>" class="btn btn-yes">Yes, this was me</a>
                    <a href="<?php echo $no_url; ?>" class="btn btn-no">No, secure my account</a>
                </div>
                
                <p style="font-size: 14px; color: #666; margin-top: 30px;">
                    If you don't recognize this activity, someone else might be trying to access your account. Please click "No, secure my account" above immediately.
                </p>
            </div>
            
            <div class="footer">
                <p>This is an automated security message from <?php echo htmlspecialchars($system_name); ?>.</p>
                <p>If you need help, please contact us at <a href="mailto:<?php echo htmlspecialchars($support_email); ?>"><?php echo htmlspecialchars($support_email); ?></a></p>
            </div>
        </div>
    </div>
</body>
</html>
