<?php

namespace App\Http\Controllers\Customer;

use App\Enums\TicketStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\StoreCustomerRequest;
use App\Http\Requests\Customer\UpdateCustomerRequest;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use App\Models\Invoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    public function index(): Response
    {
        $filters = request()->only(['search']);

        $customers = Customer::withCount(['tickets', 'devices'])
            ->withSum(
                ['invoices as total_spent' => fn ($q) => $q->where('status', 'paid')],
                'total_ttc'
            )
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->where(fn ($q) => $q->where('name', 'like', "%$s%")
                ->orWhere('email', 'like', "%$s%")
                ->orWhere('phone', 'like', "%$s%")
            )
            )
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Customers/Index', [
            'customers' => CustomerResource::collection($customers),
            'filters' => $filters,
        ]);
    }

    public function show(Customer $customer): Response
    {
        $customer->loadCount(['tickets', 'devices'])
            ->load([
                'tickets' => fn ($q) => $q->with('device')->latest()->limit(10),
                'devices',
            ]);

        $totalSpent = Invoice::where('customer_id', $customer->id)
            ->where('status', 'paid')
            ->sum('total_ttc');

        return Inertia::render('Customers/Show', [
            'customer' => (new CustomerResource($customer))->resolve(),
            'total_spent' => $totalSpent,
        ]);
    }

    public function store(StoreCustomerRequest $request): RedirectResponse
    {
        $customer = Customer::create($request->validated());

        return redirect()
            ->route('customers.show', $customer->id)
            ->with('success', 'Client créé.');
    }

    public function update(UpdateCustomerRequest $request, Customer $customer): RedirectResponse
    {
        $customer->update($request->validated());

        return back()->with('success', 'Client mis à jour.');
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        if ($customer->tickets()->whereNotIn('status', [
            TicketStatus::Returned->value,
            TicketStatus::Cancelled->value,
        ])->exists()) {
            return back()->with('error', 'Ce client a des tickets ouverts.');
        }

        $customer->delete();

        return redirect()
            ->route('customers.index')
            ->with('success', 'Client supprimé.');
    }

    public function search(): JsonResponse
    {
        $customers = Customer::where(fn ($q) => $q->where('name', 'like', '%'.request('q').'%')
            ->orWhere('email', 'like', '%'.request('q').'%')
            ->orWhere('phone', 'like', '%'.request('q').'%')
        )
            ->limit(5)
            ->get(['id', 'name', 'email', 'phone']);

        return response()->json($customers);
    }
}
