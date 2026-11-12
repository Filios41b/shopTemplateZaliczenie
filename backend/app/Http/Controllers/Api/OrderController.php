<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Order::with('items')->latest()->get());
    }

    public function updateStatus(Request $request, Order $order): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['pending', 'completed'])],
        ]);

        $order->update(['status' => $validated['status']]);

        return response()->json($order->load('items'));
    }

    public function store(Request $request): JsonResponse
    {
        $products = config('shop.products', []);
        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:150'],
            'customer_email' => ['required', 'email', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:30'],
            'address_line' => ['required', 'string', 'max:255'],
            'postal_code' => ['required', 'string', 'regex:/^\\d{2}-\\d{3}$/'],
            'city' => ['required', 'string', 'max:100'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'integer', Rule::in(array_keys($products))],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:99'],
        ]);

        $order = DB::transaction(function () use ($request, $validated, $products) {
            $order = Order::create([
                'user_id' => $request->user()?->id,
                'order_number' => 'ORD-'.Str::upper(Str::random(10)),
                'status' => 'pending',
                'customer_name' => $validated['customer_name'],
                'customer_email' => $validated['customer_email'],
                'customer_phone' => $validated['customer_phone'],
                'address_line' => $validated['address_line'],
                'postal_code' => $validated['postal_code'],
                'city' => $validated['city'],
                'total' => 0,
            ]);

            $total = 0;
            foreach ($validated['items'] as $item) {
                $product = $products[$item['id']];
                $total += $product['price'] * $item['quantity'];
                $order->items()->create([
                    'product_id' => $item['id'],
                    'product_name' => $product['name'],
                    'unit_price' => $product['price'],
                    'quantity' => $item['quantity'],
                ]);
            }

            $order->update(['total' => $total]);

            return $order->load('items');
        });

        return response()->json([
            'message' => 'Zamówienie zostało zapisane.',
            'order_number' => $order->order_number,
            'order' => $order,
        ], 201);
    }
}
