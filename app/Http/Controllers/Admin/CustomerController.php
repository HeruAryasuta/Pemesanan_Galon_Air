<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $customers = User::query()
            ->where('role', 'customer')
            ->withCount('orders')
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = '%' . $request->string('search') . '%';

                $query->where(function ($customerQuery) use ($search): void {
                    $customerQuery
                        ->where('name', 'like', $search)
                        ->orWhere('email', 'like', $search)
                        ->orWhere('phone', 'like', $search);
                });
            })
            ->latest()
            ->paginate(15);

        return view('admin.customers.index', compact('customers'));
    }

    public function show(User $customer): View
    {
        abort_if($customer->role !== 'customer', 404);

        $customer->loadCount('orders')->load([
            'addresses' => fn ($query) => $query->orderByDesc('is_primary')->latest(),
            'orders' => fn ($query) => $query->with('address')->latest()->take(10),
        ]);

        return view('admin.customers.show', compact('customer'));
    }
}