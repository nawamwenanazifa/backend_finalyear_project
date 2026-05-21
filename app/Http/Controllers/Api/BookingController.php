<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/bookings",
     *     summary="Get all bookings for the authenticated bride",
     *     tags={"Bookings"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="List of bookings")
     * )
     */
    public function index()
    {
        $bookings = Booking::where('user_id', auth()->id())
            ->latest()
            ->get()
            ->map(function ($b) {
                return [
                    'id'            => $b->booking_reference,
                    'status'        => $b->status,
                    'service'       => $b->service,
                    'product_name'  => $b->product_name,
                    'customer_name' => $b->customer_name,
                    'phone'         => $b->phone,
                    'email'         => $b->email,
                    'booking_date'  => $b->booking_date?->format('Y-m-d H:i:s'),
                    'address'       => $b->address,
                    'notes'         => $b->notes,
                ];
            });

        return response()->json([
            'success' => true,
            'data'    => $bookings,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/bookings",
     *     summary="Schedule a new fitting or consultation",
     *     tags={"Bookings"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"service","booking_date","customer_name","phone"},
     *             @OA\Property(property="service", type="string", example="Bespoke Consultation"),
     *             @OA\Property(property="booking_date", type="string", format="date-time", example="2024-10-12 14:00:00"),
     *             @OA\Property(property="customer_name", type="string", example="Genevieve Rose"),
     *             @OA\Property(property="phone", type="string", example="+256700000000")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Booking created")
     * )
     */
    public function store(Request $request)
    {
        $request->validate([
            'customer_name' => 'required|string|max:255',
            'phone'         => 'required|string|max:20',
            'email'         => 'nullable|email',
            'service'       => 'required|string',
            'booking_date'  => 'required|string',
            'address'       => 'nullable|string',
            'notes'         => 'nullable|string',
            'product_name'  => 'nullable|string',
        ]);

        $booking = Booking::create([
            'user_id'       => auth()->id(),
            'customer_name' => $request->customer_name,
            'phone'         => $request->phone,
            'email'         => $request->email,
            'service'       => $request->service,
            'booking_date'  => $request->booking_date,
            'address'       => $request->address,
            'notes'         => $request->notes,
            'product_name'  => $request->product_name,
            'status'        => 'upcoming',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Booking created successfully',
            'data'    => [
                'id' => $booking->booking_reference,
            ],
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/bookings/{id}",
     *     summary="Get details of a specific booking",
     *     tags={"Bookings"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Booking details")
     * )
     */
    public function show($id)
    {
        $booking = Booking::where('booking_reference', $id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data'    => $booking,
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/bookings/{id}/cancel",
     *     summary="Cancel an upcoming booking",
     *     tags={"Bookings"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Booking cancelled")
     * )
     */
    public function cancel($id)
    {
        $booking = Booking::where('booking_reference', $id)
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
