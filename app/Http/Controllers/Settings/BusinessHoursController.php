<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateBusinessHoursRequest;
use App\Models\BusinessHours;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class BusinessHoursController extends Controller
{
    public function edit(): Response
    {
        $this->authorize('viewAny', BusinessHours::class);

        return Inertia::render('settings/BusinessHours', [
            'businessHours' => $this->businessHours()->only(['working_days', 'starts_at', 'ends_at']),
        ]);
    }

    public function update(UpdateBusinessHoursRequest $request): RedirectResponse
    {
        $this->businessHours()->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Werkuren bijgewerkt.']);

        return back();
    }

    private function businessHours(): BusinessHours
    {
        return BusinessHours::query()->firstOrCreate(
            ['id' => 1],
            ['working_days' => [1, 2, 3, 4, 5], 'starts_at' => '09:00', 'ends_at' => '17:00'],
        );
    }
}
