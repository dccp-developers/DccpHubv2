<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Password Reset - DCCP Hub</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #3b82f6;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }
        .content {
            background-color: #f8f9fa;
            padding: 30px;
            border-radius: 0 0 8px 8px;
        }
        .password-box {
            background-color: #e3f2fd;
            border: 2px dashed #2196f3;
            padding: 15px;
            margin: 20px 0;
            text-align: center;
            border-radius: 4px;
        }
        .password {
            font-family: 'Courier New', monospace;
            font-size: 18px;
            font-weight: bold;
            color: #1565c0;
            letter-spacing: 1px;
        }
        .warning {
            background-color: #fff3cd;
            border: 1px solid #ffeeba;
            color: #856404;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 12px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Password Reset</h1>
        <p>DCCP Hub Administration</p>
    </div>
    
    <div class="content">
        <p>Hello {{ $user->name }},</p>
        
        <p>Your password has been reset by an administrator. Please use the temporary password below to log into your account:</p>
        
        <div class="password-box">
            <p><strong>Temporary Password:</strong></p>
            <p class="password">{{ $newPassword }}</p>
        </div>
        
        <div class="warning">
            <strong>Important Security Notice:</strong>
            <ul>
                <li>Please change this password immediately after logging in</li>
                <li>All your existing API tokens have been revoked for security</li>
                <li>Do not share this password with anyone</li>
                <li>This email contains sensitive information - please delete it after use</li>
            </ul>
        </div>
        
        <p>If you did not request this password reset or have any concerns, please contact the system administrator immediately.</p>
        
        <p>Thank you,<br>
        DCCP Hub Administration Team</p>
    </div>
    
    <div class="footer">
        <p>This is an automated message. Please do not reply to this email.</p>
    </div>
</body>
</html>
