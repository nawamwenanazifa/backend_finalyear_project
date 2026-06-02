<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    // Get user's orders
    public function index()
    {
        $orders = Order::where('user_id', Auth::id())
            ->withCount('items')  // Add items count to each order
            ->with('items.product')
            ->orderBy('created_at', 'desc')
            ->get();
        
        return response()->json([
            'success' => true,
            'orders' => $orders
        ]);
    }

    // Get single order
    public function show($id)
    {
        $order = Order::where('user_id', Auth::id())
            ->withCount('items')  // Add items count
            ->with('items.product')
            ->findOrFail($id);
        
        return response()->json([
            'success' => true,
            'order' => $order
        ]);
    }

    // Create order from booking
    public function store(Request $request)
    {
        try {
            \Log::info('Order creation request:', $request->all());

            $request->validate([
                'booking_id'      => 'required|exists:bookings,id',
                'payment_method'  => 'required|in:cash_on_delivery,mobile_money,bank_transfer',
                'shipping_address'=> 'required|string',
                'total_amount'    => 'nullable|numeric',
                'notes'           => 'nullable|string',
            ]);

            $booking = Booking::where('user_id', Auth::id())
                ->findOrFail($request->booking_id);

            DB::beginTransaction();

            try {
                // Service-only booking — use amount passed from app or default
                $subtotal    = $request->total_amount ?? 150000;
                $tax         = 0;
                $deliveryFee = 0;
                $total       = $subtotal + $tax + $deliveryFee;

                // Create order
                $order = Order::create([
                    'user_id'          => Auth::id(),
                    'booking_id'       => $booking->id,
                    'order_number'     => Order::generateOrderNumber(),
                    'subtotal'         => $subtotal,
                    'tax'              => $tax,
                    'delivery_fee'     => $deliveryFee,
                    'total'            => $total,
                    'payment_method'   => $request->payment_method,
                    'payment_status'   => 'pending',
                    'order_status'     => 'pending',
                    'shipping_address' => $request->shipping_address,
                    'notes'            => $request->notes,
                ]);

                DB::commit();

                \Log::info('Order created successfully:', ['order_id' => $order->id]);

                return response()->json([
                    'success' => true,
                    'message' => 'Order created successfully',
                    'order'   => $order
                ], 201);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            \Log::error('Order creation failed: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create order: ' . $e->getMessage()
            ], 500);
        }
    }

    // Cancel order
    public function cancel($id)
    {
        $order = Order::where('user_id', Auth::id())
            ->whereIn('order_status', ['pending', 'confirmed'])
            ->findOrFail($id);

        DB::beginTransaction();

        try {
            // Restore stock if order had items
            foreach ($order->items as $item) {
                $product = Product::find($item->product_id);
                if ($product) {
                    $product->stock_quantity += $item->quantity;
                    $product->in_stock = true;
                    $product->save();
                }
            }

            $order->order_status = 'cancelled';
            $order->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Order cancelled successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel order'
            ], 500);
        }
    }
}