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
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%' . $request->string('search') . '%'))
            ->latest()
            ->paginate(15);

        return view('admin.customers.index', compact('customers'));
    }

    public function show(User $customer): View
    {
        abort_if($customer->role !== 'customer', 404);

        $customer->load(['addresses', 'orders' => fn ($q) => $q->latest()->take(10)]);

        return view('admin.customers.show', compact('customer'));
    }
}