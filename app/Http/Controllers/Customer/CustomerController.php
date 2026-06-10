<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->get('search');

        $customers = Customer::when($search, fn ($q, $s) => $q->where('name', 'like', "%$s%")
            ->orWhere('email', 'like', "%$s%")
            ->orWhere('phone', 'like', "%$s%")
        )
            ->withCount('tickets')
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Customers/Index', [
            'customers' => $customers,
            'filters' => ['search' => $search],
        ]);
    }

    public function search(Request $request)
    {
        $customers = Customer::where('name', 'like', '%'.request('q').'%')
            ->orWhere('email', 'like', '%'.request('q').'%')
            ->limit(5)
            ->get(['id', 'name', 'email', 'phone']);

        return response()->json($customers);
    }
}
