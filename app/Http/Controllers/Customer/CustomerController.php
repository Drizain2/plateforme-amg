<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function search(Request $request)
    {
        $customers = Customer::where('name', 'like', '%' . request('q') . '%')
        ->orWhere('email', 'like', '%' . request('q') . '%')
        ->limit(5)
        ->get(['id', 'name', 'email', 'phone']);

    return response()->json($customers);
    }
}
