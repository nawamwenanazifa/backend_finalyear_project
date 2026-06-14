<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    private function getOrCreateCart(): Cart
    {
        return Cart::firstOrCreate(['user_id' => Auth::id()]);
    }

    private function formatCart(Cart $cart): array
    {
        $cart->load('items.product');

        $items = $cart->items->map(function ($item) {
            $imageUrl = '';
            if ($item->product && $item->product->image) {
                $appUrl = rtrim(config('app.url'), '/');
                $imageUrl = $appUrl . '/storage/' . ltrim($item->product->image, '/');
            }

            return [
                'id'            => $item->id,
                'product_id'    => $item->product_id,
                'product_name'  => $item->product->name ?? 'Unknown',
                'product_price' => (float) ($item->product->price ?? 0),
                'product_image' => $imageUrl ?: null,
                'quantity'      => $item->quantity,
                'subtotal'      => (float) ($item->quantity * ($item->product->price ?? 0)),
            ];
        });

        return [
            'id'         => $cart->id,
            'items'      => $items,
            'total'      => (float) $items->sum('subtotal'),
            'item_count' => $items->count(),
        ];
    }

    // GET /api/cart
    public function index()
    {
        try {
            $cart = $this->getOrCreateCart();
            return response()->json([
                'success' => true,
                'cart'    => $this->formatCart($cart),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // POST /api/cart/add
    public function addItem(Request $request)
    {
        try {
            $request->validate([
                'product_id' => 'required|exists:products,id',
                'quantity'   => 'sometimes|integer|min:1|max:99',
            ]);

            $cart     = $this->getOrCreateCart();
            $quantity = $request->get('quantity', 1);

            $item = CartItem::where('cart_id', $cart->id)
                ->where('product_id', $request->product_id)
                ->first();

            if ($item) {
                $item->update(['quantity' => $item->quantity + $quantity]);
            } else {
                CartItem::create([
                    'cart_id'    => $cart->id,
                    'product_id' => $request->product_id,
                    'quantity'   => $quantity,
                ]);
            }

            return response()->json(['success' => true, 'message' => 'Item added to cart'], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // PUT /api/cart/item/{itemId}
    public function updateItem(Request $request, $itemId)
    {
        try {
            $request->validate(['quantity' => 'required|integer|min:1|max:99']);
            $cart = $this->getOrCreateCart();
            CartItem::where('id', $itemId)->where('cart_id', $cart->id)
                ->update(['quantity' => $request->quantity]);

            return response()->json(['success' => true, 'message' => 'Cart updated']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // DELETE /api/cart/item/{itemId}
    public function removeItem($itemId)
    {
        try {
            $cart = $this->getOrCreateCart();
            CartItem::where('id', $itemId)->where('cart_id', $cart->id)->delete();
            return response()->json(['success' => true, 'message' => 'Item removed']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // DELETE /api/cart/clear
    public function clear()
    {
        try {
            $cart = $this->getOrCreateCart();
            CartItem::where('cart_id', $cart->id)->delete();
            return response()->json(['success' => true, 'message' => 'Cart cleared']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // POST /api/cart/checkout
    public function checkout(Request $request)
    {
        try {
            $request->validate([
                'shipping_address' => 'required|string',
                'payment_method'   => 'required|string',
                'notes'            => 'nullable|string',
            ]);

            $cart = $this->getOrCreateCart();
            $cart->load('items.product');

            if ($cart->items->isEmpty()) {
                return response()->json(['success' => false, 'message' => 'Cart is empty'], 400);
            }

            // Clear cart after checkout
            CartItem::where('cart_id', $cart->id)->delete();

            return response()->json([
                'success' => true,
                'message' => 'Order placed successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}