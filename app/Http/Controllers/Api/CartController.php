<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CartController extends Controller
{
    // Get user's cart
    public function index()
    {
        $cart = Cart::firstOrCreate([
            'user_id' => Auth::id()
        ]);

        $cart->load('items.product');

        return response()->json([
            'success' => true,
            'cart' => [
                'id' => $cart->id,
                'items' => $cart->items->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'product_id' => $item->product_id,
                        'product_name' => $item->product->name,
                        'product_price' => $item->product->price,
                        'product_image' => $item->product->image,
                        'quantity' => $item->quantity,
                        'subtotal' => $item->subtotal,
                    ];
                }),
                'total' => $cart->total,
                'item_count' => $cart->item_count,
            ]
        ]);
    }

    // Add item to cart
    public function addItem(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'integer|min:1|max:99'
        ]);

        $cart = Cart::firstOrCreate(['user_id' => Auth::id()]);

        $cartItem = CartItem::where('cart_id', $cart->id)
            ->where('product_id', $request->product_id)
            ->first();

        if ($cartItem) {
            $cartItem->quantity += $request->quantity ?? 1;
            $cartItem->save();
        } else {
            $cartItem = CartItem::create([
                'cart_id' => $cart->id,
                'product_id' => $request->product_id,
                'quantity' => $request->quantity ?? 1,
            ]);
        }

        // Get updated cart data
        $cart->load('items.product');

        return response()->json([
            'success' => true,
            'message' => 'Item added to cart',
            'cart_item' => $cartItem,
            'cart' => [
                'total' => $cart->total,
                'item_count' => $cart->item_count,
            ]
        ]);
    }

    // Update cart item quantity
    public function updateItem(Request $request, $itemId)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1|max:99'
        ]);

        $cartItem = CartItem::findOrFail($itemId);
        
        // Verify ownership through cart
        $cart = Cart::where('user_id', Auth::id())->first();
        if (!$cart || $cartItem->cart_id != $cart->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $cartItem->quantity = $request->quantity;
        $cartItem->save();

        // Get updated cart data
        $cart->load('items.product');

        return response()->json([
            'success' => true,
            'message' => 'Cart updated',
            'cart' => [
                'total' => $cart->total,
                'item_count' => $cart->item_count,
            ]
        ]);
    }

    // Remove item from cart
    public function removeItem($itemId)
    {
        $cartItem = CartItem::findOrFail($itemId);
        
        $cart = Cart::where('user_id', Auth::id())->first();
        if (!$cart || $cartItem->cart_id != $cart->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $cartItem->delete();

        // Get updated cart data
        $cart->load('items.product');

        return response()->json([
            'success' => true,
            'message' => 'Item removed from cart',
            'cart' => [
                'total' => $cart->total,
                'item_count' => $cart->item_count,
            ]
        ]);
    }

    // Clear cart
    public function clear()
    {
        $cart = Cart::where('user_id', Auth::id())->first();
        if ($cart) {
            $cart->items()->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Cart cleared'
        ]);
    }

    // Checkout - Convert cart to order
    public function checkout(Request $request)
    {
        $request->validate([
            'shipping_address' => 'required|string',
            'payment_method' => 'required|in:cash_on_delivery,mobile_money,bank_transfer',
            'notes' => 'nullable|string',
        ]);

        $cart = Cart::where('user_id', Auth::id())->with('items.product')->first();

        if (!$cart || $cart->items->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Cart is empty'
            ], 422);
        }

        DB::beginTransaction();

        try {
            // Calculate totals
            $subtotal = $cart->total;
            $tax = $subtotal * 0.18; // 18% VAT
            $deliveryFee = 15000;
            $total = $subtotal + $tax + $deliveryFee;

            // Create order
            $order = Order::create([
                'user_id' => Auth::id(),
                'order_number' => Order::generateOrderNumber(),
                'subtotal' => $subtotal,
                'tax' => $tax,
                'delivery_fee' => $deliveryFee,
                'total' => $total,
                'payment_method' => $request->payment_method,
                'payment_status' => 'pending',
                'order_status' => 'pending',
                'shipping_address' => $request->shipping_address,
                'notes' => $request->notes,
            ]);

            // Create order items and update stock
            foreach ($cart->items as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'price' => $item->product->price,
                ]);

                // Reduce stock
                $product = Product::find($item->product_id);
                if ($product) {
                    $product->stock_quantity -= $item->quantity;
                    $product->in_stock = $product->stock_quantity > 0;
                    $product->save();
                }
            }

            // Clear cart
            $cart->items()->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Order placed successfully',
                'order_id' => $order->id,
                'order_number' => $order->order_number
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Checkout failed: ' . $e->getMessage()
            ], 500);
        }
    }
}