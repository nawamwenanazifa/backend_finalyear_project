<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class BookingController extends Controller
{
    /**
     * Get all bookings for the authenticated user
     */
    public function index()
    {
        $bookings = Booking::where('user_id', auth()->id())
            ->latest()
            ->get()
            ->map(function ($b) {
                return [
                    'id'                => $b->id,
                    'booking_reference' => $b->booking_reference ?? '#BK-' . $b->id,
                    'status'            => $b->status,
                    'service_type'      => $b->service_type,
                    'booking_date'      => $b->booking_date?->format('Y-m-d H:i:s'),
                    'phone'             => $b->phone,
                    'email'             => $b->email,
                    'notes'             => $b->notes,
                ];
            });

        return response()->json([
            'success' => true,
            'data'    => $bookings,
        ]);
    }

    /**
     * Store a new booking
     */
    public function store(Request $request)
    {
        try {
            Log::info('📅 Booking request received:', $request->all());

            $validated = $request->validate([
                'phone'         => 'required|string|max:20',
                'email'         => 'required|email',
                'service_type'  => 'required|string',
                'booking_date'  => 'required|string',
                'notes'         => 'nullable|string',
            ]);

            Log::info('✅ Validation passed');

            $booking = Booking::create([
                'user_id'       => Auth::id(),
                'phone'         => $validated['phone'],
                'email'         => $validated['email'],
                'service_type'  => $validated['service_type'],
                'booking_date'  => $validated['booking_date'],
                'notes'         => $validated['notes'] ?? null,
                'status'        => 'upcoming',
            ]);

            Log::info('✅ Booking created successfully', ['booking_id' => $booking->id]);

            return response()->json([
                'success' => true,
                'message' => 'Booking created successfully',
                'data'    => [
                    'id'                => $booking->id,
                    'booking_reference' => $booking->booking_reference ?? '#BK-' . $booking->id,
                    'service_type'      => $booking->service_type,
                    'booking_date'      => $booking->booking_date,
                    'phone'             => $booking->phone,
                    'email'             => $booking->email,
                    'notes'             => $booking->notes,
                    'status'            => $booking->status,
                ],
            ], 201);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('❌ Validation failed:', $e->errors());
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('❌ Booking creation failed: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Booking failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get details of a specific booking
     */
    public function show($id)
    {
        $booking = Booking::where('id', $id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data'    => [
                'id'                => $booking->id,
                'booking_reference' => $booking->booking_reference ?? '#BK-' . $booking->id,
                'status'            => $booking->status,
                'service_type'      => $booking->service_type,
                'booking_date'      => $booking->booking_date?->format('Y-m-d H:i:s'),
                'phone'             => $booking->phone,
                'email'             => $booking->email,
                'notes'             => $booking->notes,
                'created_at'        => $booking->created_at?->format('Y-m-d H:i:s'),
            ],
        ]);
    }

    /**
     * Cancel an upcoming booking
     */
    public function cancel($id)
    {
        $booking = Booking::where('id', $id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        if ($booking->status !== 'upcoming') {
            return response()->json([
                'success' => false,
                'message' => 'Only upcoming bookings can be cancelled',
            ], 422);
        }

        $booking->update(['status' => 'cancelled']);

        return response()->json([
            'success' => true,
            'message' => 'Booking cancelled successfully',
        ]);
    }
}