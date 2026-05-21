<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class VerificationService
{
    public static function generateCode(): string
    {
        return str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }
    
    public static function sendVerificationEmail(User $user, string $code): bool
    {
        // Log the code for development (since MAIL_MAILER=log)
        Log::info('VERIFICATION CODE for ' . $user->email . ': ' . $code);
        
        try {
            Mail::send('emails.verification-code', [
                'name' => $user->name,
                'code' => $code,
                'valid_minutes' => 10
            ], function ($message) use ($user) {
                $message->to($user->email)
                        ->subject('Fyn Bridals - Login Verification Code');
            });
            
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send verification email: ' . $e->getMessage());
            return false;
        }
    }
    
    public static function verifyCode(User $user, string $inputCode): bool
    {
        if ($user->verification_code !== $inputCode) {
            return false;
        }
        
        if ($user->verification_code_sent_at && 
            $user->verification_code_sent_at->addMinutes(10)->isPast()) {
            return false;
        }
        
        return true;
    }
    
    public static function clearVerification(User $user): void
    {
        $user->update([
            'verification_code' => null,
            'verification_code_sent_at' => null,
            'failed_login_attempts' => 0,
            'locked_until' => null,
        ]);
    }
}