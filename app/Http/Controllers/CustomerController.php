<?php

namespace App\Http\Controllers;

use App\Enums\IssueStatus;
use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Models\Customer;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', Customer::class);

        return Inertia::render('Customers/Index', ['customers' => Customer::query()->withCount('projects')->orderBy('name')->paginate(20)->withQueryString()->through(fn (Customer $customer): array => ['id' => $customer->id, 'name' => $customer->name, 'projects_count' => $customer->projects_count])]);
    }

    public function store(StoreCustomerRequest $request): RedirectResponse
    {
        $customer = Customer::query()->create($request->validated());

        return to_route('customers.show', $customer);
    }

    public function show(Customer $customer): Response
    {
        $this->authorize('view', $customer);

        return Inertia::render('Customers/Show', [
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'created_at' => $customer->created_at->toDateTimeString(),
            ],
            'projects' => $customer->projects()
                ->withCount([
                    'issues as active_issues_count' => fn ($query) => $query
                        ->where('status', '!=', IssueStatus::Completed->value),
                ])
                ->orderBy('name')
                ->paginate(20)
                ->withQueryString()
                ->through(fn ($project): array => [
                    'id' => $project->id,
                    'name' => $project->name,
                    'is_active' => $project->is_active,
                    'active_issues_count' => $project->active_issues_count,
                ]),
        ]);
    }

    public function edit(Customer $customer): Response
    {
        $this->authorize('update', $customer);

        return Inertia::render('Customers/Edit', [
            'customer' => $customer->only(['id', 'name']),
        ]);
    }

    public function update(UpdateCustomerRequest $request, Customer $customer): RedirectResponse
    {
        $customer->update($request->validated());

        return to_route('customers.show', $customer);
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        $this->authorize('delete', $customer);
        $customer->delete();

        return to_route('customers.index');
    }
}
