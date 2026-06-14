<?php

namespace App\Services;

use App\Models\User;
use App\Models\OtpCode;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class OtpService
{
    public function generateOtp(User $user)
    {
        // Delete old unused OTPs
        OtpCode::where('user_id', $user->id)
            ->where('is_used', false)
            ->where('expires_at', '<', now())
            ->delete();
        
        // Generate 6-digit OTP
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        
        // Store OTP
        $otpCode = OtpCode::create([
            'user_id' => $user->id,
            'otp' => $otp,
            'expires_at' => now()->addMinutes(10),
            'is_used' => false,
        ]);
        
        return $otpCode;
    }
    
    public function sendOtpViaEmail(User $user, $otpCode)
    {
        try {
            Mail::send('emails.otp', ['otp' => $otpCode->otp, 'user' => $user], function ($message) use ($user) {
                $message->to($user->email)
                        ->subject('Your Fyn Bridals Login Verification Code');
            });
            return true;
        } catch (\Exception $e) {
            Log::error('OTP Email failed: ' . $e->getMessage());
            return false;
        }
    }
    
    public function verifyOtp(User $user, $otp)
    {
        $otpRecord = OtpCode::where('user_id', $user->id)
            ->where('otp', $otp)
            ->where('is_used', false)
            ->where('expires_at', '>', now())
            ->first();
        
        if (!$otpRecord) {
            return false;
        }
        
        // Mark as used
        $otpRecord->update(['is_used' => true]);
        
        return true;
    }
    
    public function resendOtp(User $user)
    {
        // Delete old unused OTPs
        OtpCode::where('user_id', $user->id)
            ->where('is_used', false)
            ->delete();
        
        // Generate new OTP
        $otpCode = $this->generateOtp($user);
        $this->sendOtpViaEmail($user, $otpCode);
        
        return $otpCode;
    }
}