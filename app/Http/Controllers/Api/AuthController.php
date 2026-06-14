<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Services\OtpService;

class AuthController extends Controller
{
    private const MAX_FAILED_ATTEMPTS = 3;
    private const LOCKOUT_MINUTES = 15;
    
    protected $otpService;

    public function __construct(OtpService $otpService)
    {
        $this->otpService = $otpService;
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => 'required|string|max:20',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'is_admin' => false,
            'gender' => 'female',
            'two_factor_enabled' => true,
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;
        $navigation = $this->safeNavigation($user);

        return response()->json([
            'success' => true,
            'message' => 'Registration successful',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'gender' => $user->gender,
                    'is_admin' => $user->is_admin ?? false,
                ],
                'token' => $token,
                'navigation' => $navigation,
                'redirect_to' => '/home',
            ]
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);
        
        $user = User::where('email', $request->email)->first();
        
        // Check if account is locked
        if ($user && $user->locked_until && now()->lt($user->locked_until)) {
            $remainingMinutes = now()->diffInMinutes($user->locked_until);
            return response()->json([
                'success' => false,
                'message' => "Account is locked. Please try again in {$remainingMinutes} minutes.",
                'locked' => true,
                'remaining_minutes' => $remainingMinutes
            ], 423);
        }
        
        // Check credentials
        if (!$user || !Hash::check($request->password, $user->password)) {
            $this->handleFailedLogin($user, $request->email);
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials.'
            ], 401);
        }
        
        // Reset failed attempts on successful password verification
        $user->update([
            'failed_login_attempts' => 0,
            'locked_until' => null,
        ]);
        
        $this->recordLoginAttempt($request->email, $request->ip(), true);
        
        // Check if 2FA is enabled for this user
        if ($user->two_factor_enabled) {
            // Generate and send OTP
            $otpCode = $this->otpService->generateOtp($user);
            $this->otpService->sendOtpViaEmail($user, $otpCode);
            
            return response()->json([
                'success' => true,
                'message' => 'OTP sent to your email',
                'requires_otp' => true,
                'user_id' => $user->id,
                'email' => $user->email,
            ]);
        }
        
        // If 2FA is disabled, login directly
        $user->tokens()->delete();
        $token = $user->createToken('auth_token')->plainTextToken;
        
        $navigation = $this->safeNavigation($user);

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'gender' => $user->gender,
                    'is_admin' => $user->is_admin ?? false,
                ],
                'token' => $token,
                'navigation' => $navigation,
                'redirect_to' => '/home',
            ]
        ]);
    }
    
    /**
     * Verify OTP for Two-Factor Authentication
     */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'otp' => 'required|string|size:6',
        ]);
        
        $user = User::find($request->user_id);
        
        if (!$this->otpService->verifyOtp($user, $request->otp)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired OTP code'
            ], 422);
        }
        
        // Generate token after successful OTP verification
        $user->tokens()->delete();
        $token = $user->createToken('auth_token')->plainTextToken;
        
        $navigation = $this->safeNavigation($user);
        
        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'gender' => $user->gender,
                    'is_admin' => $user->is_admin ?? false,
                ],
                'token' => $token,
                'navigation' => $navigation,
                'redirect_to' => '/home',
            ]
        ]);
    }
    
    /**
     * Resend OTP code
     */
    public function resendOtp(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);
        
        $user = User::find($request->user_id);
        $this->otpService->resendOtp($user);
        
        return response()->json([
            'success' => true,
            'message' => 'New OTP sent to your email'
        ]);
    }
    
    /**
     * Toggle Two-Factor Authentication for user
     */
    public function toggleTwoFactor(Request $request)
    {
        $user = $request->user();
        
        $user->update([
            'two_factor_enabled' => !$user->two_factor_enabled
        ]);
        
        return response()->json([
            'success' => true,
            'message' => $user->two_factor_enabled 
                ? 'Two-factor authentication enabled' 
                : 'Two-factor authentication disabled',
            'two_factor_enabled' => $user->two_factor_enabled
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully',
        ]);
    }

    public function user(Request $request)
    {
        $user = $request->user();
        $navigation = $this->safeNavigation($user);

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'gender' => $user->gender,
                    'is_admin' => $user->is_admin ?? false,
                    'two_factor_enabled' => $user->two_factor_enabled ?? true,
                ],
                'navigation' => $navigation,
            ]
        ]);
    }

    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email'
        ]);

        // Generate a reset token
        $token = \Illuminate\Support\Str::random(60);
        
        // Store token in password_resets table
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $request->email],
            [
                'token' => $token,
                'created_at' => now()
            ]
        );
        
        // Here you would send an email with the reset link
        // For now, we'll just return success with the token (remove token in production)
        
        // In production, send email:
        // Mail::to($request->email)->send(new PasswordResetMail($token, $request->email));
        
        return response()->json([
            'success' => true,
            'message' => 'Password reset link sent to your email address'
        ]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'token' => 'required|string',
            'password' => 'required|string|min:8|confirmed'
        ]);
        
        // Verify token
        $resetRecord = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->where('token', $request->token)
            ->first();
        
        if (!$resetRecord) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired reset token'
            ], 400);
        }
        
        // Check if token expired (24 hours)
        if (now()->diffInHours($resetRecord->created_at) > 24) {
            return response()->json([
                'success' => false,
                'message' => 'Reset token has expired. Please request a new one.'
            ], 400);
        }
        
        // Update password
        $user = User::where('email', $request->email)->first();
        $user->password = Hash::make($request->password);
        $user->save();
        
        // Delete reset token
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();
        
        // Delete all user tokens (force logout on all devices)
        $user->tokens()->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Password has been reset successfully. Please login with your new password.'
        ]);
    }

    private function handleFailedLogin($user, $email)
    {
        if ($user) {
            $attempts = ($user->failed_login_attempts ?? 0) + 1;
            $updateData = ['failed_login_attempts' => $attempts];
            
            if ($attempts >= self::MAX_FAILED_ATTEMPTS) {
                $updateData['locked_until'] = now()->addMinutes(self::LOCKOUT_MINUTES);
            }
            
            $user->update($updateData);
        }
        
        DB::table('login_attempts')->insert([
            'email' => $email,
            'ip_address' => request()->ip(),
            'was_successful' => false,
            'attempted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
    
    private function recordLoginAttempt($email, $ip, $successful)
    {
        DB::table('login_attempts')->insert([
            'email' => $email,
            'ip_address' => $ip,
            'was_successful' => $successful,
            'attempted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
    
    private function maskEmail($email)
    {
        $parts = explode('@', $email);
        $name = $parts[0];
        $domain = $parts[1] ?? '';
        
        $maskedName = substr($name, 0, 2) . str_repeat('*', max(3, strlen($name) - 4)) . substr($name, -2);
        
        return $maskedName . '@' . $domain;
    }

    private function safeNavigation($user)
    {
        try {
            return $this->getNavigationData($user);
        } catch (\Exception $e) {
            return [
                'main_menu' => [],
                'categories' => [],
                'user_menu' => [],
            ];
        }
    }

    private function getNavigationData($user)
    {
        $mainMenu = [
            ['title' => 'Home', 'route' => '/home', 'icon' => 'home', 'auth' => false],
            ['title' => 'Gallery', 'route' => '/gallery', 'icon' => 'photo_library', 'auth' => false],
            ['title' => 'Bookings', 'route' => '/my-bookings', 'icon' => 'calendar_today', 'auth' => true],
            ['title' => 'Messages', 'route' => '/conversations', 'icon' => 'chat', 'auth' => true],
            ['title' => 'Profile', 'route' => '/profile', 'icon' => 'person', 'auth' => true],
        ];

        $categories = Category::all()->map(function ($category) {
            return [
                'id' => $category->id,
                'title' => $category->name,
                'route' => "/category/{$category->id}",
                'icon' => $this->getIconForCategory($category->name),
                'params' => ['categoryId' => $category->id],
            ];
        });

        $userMenu = [
            ['title' => 'My Profile', 'route' => '/profile', 'icon' => 'person', 'section' => 'profile'],
            ['title' => 'My Bookings', 'route' => '/my-bookings', 'icon' => 'list', 'section' => 'bookings'],
            ['title' => 'Messages', 'route' => '/conversations', 'icon' => 'chat', 'section' => 'messages'],
            ['title' => 'Settings', 'route' => '/settings', 'icon' => 'settings', 'section' => 'settings'],
            ['title' => 'Two-Factor Auth', 'route' => '/2fa', 'icon' => 'security', 'section' => 'security'],
            ['title' => 'Logout', 'route' => '/logout', 'icon' => 'logout', 'action' => 'logout', 'section' => 'auth'],
        ];

        if ($user && ($user->is_admin ?? false)) {
            $adminMenu = [
                ['title' => 'Admin Dashboard', 'route' => '/admin', 'icon' => 'dashboard', 'section' => 'admin'],
                ['title' => 'Manage Products', 'route' => '/admin/products', 'icon' => 'inventory', 'section' => 'admin'],
                ['title' => 'Manage Categories', 'route' => '/admin/categories', 'icon' => 'category', 'section' => 'admin'],
                ['title' => 'Manage Bookings', 'route' => '/admin/bookings', 'icon' => 'booking', 'section' => 'admin'],
                ['title' => 'Manage Users', 'route' => '/admin/users', 'icon' => 'people', 'section' => 'admin'],
            ];
            $userMenu = array_merge($userMenu, $adminMenu);
        }

        return [
            'main_menu' => $mainMenu,
            'categories' => $categories,
            'user_menu' => $userMenu,
        ];
    }

    private function getIconForCategory($name)
    {
        $icons = [
            'gomesi' => 'woman',
            'kanzu' => 'man',
            'busuuti' => 'dress',
            'accessories' => 'diamond',
            'wedding' => 'celebration',
            'changing' => 'style',
            'traditional' => 'emoji_people',
        ];

        $name = strtolower($name);

        foreach ($icons as $key => $icon) {
            if (str_contains($name, $key)) {
                return $icon;
            }
        }

        return 'category';
    }
}