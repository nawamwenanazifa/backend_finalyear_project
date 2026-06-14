<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Login Verification Code</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 20px; }
        .container { max-width: 500px; margin: 0 auto; background: white; border-radius: 10px; padding: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .logo { text-align: center; margin-bottom: 20px; }
        .logo h1 { color: #570013; font-family: Georgia, serif; }
        .otp-code { font-size: 32px; font-weight: bold; text-align: center; letter-spacing: 5px; color: #570013; background: #f9f9f9; padding: 15px; border-radius: 8px; margin: 20px 0; }
        .warning { color: #666; font-size: 12px; text-align: center; margin-top: 20px; }
        .footer { text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; color: #999; font-size: 11px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <h1>Fyn Bridals</h1>
            <p style="color: #D4AF37;">Your Atelier Partner</p>
        </div>
        
        <h2 style="text-align: center;">Login Verification Code</h2>
        
        <p>Hello <strong>{{ $user->name }}</strong>,</p>
        
        <p>Please use the following verification code to complete your login to Fyn Bridals:</p>
        
        <div class="otp-code">{{ $otp }}</div>
        
        <p>This code will expire in <strong>10 minutes</strong>.</p>
        
        <div class="warning">
            ⚠️ If you didn't request this code, please ignore this email or contact support.
        </div>
        
        <div class="footer">
            <p>Fyn Bridals - Seeta, Uganda</p>
            <p>&copy; {{ date('Y') }} Fyn Bridals. All rights reserved.</p>
        </div>
    </div>
</body>
</html>