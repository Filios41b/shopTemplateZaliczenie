<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index(Request $request)
    {
        $cart = Cart::with('items')->firstOrCreate([
            'user_id' => $request->user()->id
        ]);

        return response()->json($cart);
    }

    public function sync(Request $request)
    {
        $validated = $request->validate([
            'items' => ['present', 'array'],
            'items.*.id' => ['required', 'integer', 'min:1'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ]);

        $user = $request->user();

        $cart = DB::transaction(function () use ($user, $validated) {
            $cart = Cart::firstOrCreate(['user_id' => $user->id]);
            $cart->items()->delete();

            foreach ($validated['items'] as $item) {
                $cart->items()->create([
                    'product_id' => $item['id'],
                    'quantity' => $item['quantity'],
                ]);
            }

            return $cart;
        });

        return response()->json($cart->load('items'));
    }
}
