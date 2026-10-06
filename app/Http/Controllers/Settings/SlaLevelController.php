<?php

namespace App\Http\Controllers\Settings;

use App\Actions\SaveSlaLevel;
use App\Enums\IssuePriority;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\StoreSlaLevelRequest;
use App\Http\Requests\Settings\UpdateSlaLevelRequest;
use App\Models\SlaLevel;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SlaLevelController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', SlaLevel::class);

        return Inertia::render('settings/SlaLevels', [
            'slaLevels' => SlaLevel::query()->with('targets')->orderBy('name')->get()->map(
                fn (SlaLevel $slaLevel): array => $this->slaLevelData($slaLevel),
            ),
        ]);
    }

    public function store(StoreSlaLevelRequest $request, SaveSlaLevel $saveSlaLevel): RedirectResponse
    {
        $saveSlaLevel->handle(null, $request->validated('name'), $request->validated('targets'));

        return to_route('sla-levels.index');
    }

    public function update(UpdateSlaLevelRequest $request, SlaLevel $slaLevel, SaveSlaLevel $saveSlaLevel): RedirectResponse
    {
        $saveSlaLevel->handle($slaLevel, $request->validated('name'), $request->validated('targets'));

        return to_route('sla-levels.index');
    }

    public function destroy(SlaLevel $slaLevel): RedirectResponse
    {
        $this->authorize('delete', $slaLevel);
        $slaLevel->delete();

        return to_route('sla-levels.index');
    }

    /** @return array{id: int, name: string, targets: array<int, array{priority: string, response_minutes: int, resolution_minutes: int}>} */
    private function slaLevelData(SlaLevel $slaLevel): array
    {
        return [
            'id' => $slaLevel->id,
            'name' => $slaLevel->name,
            'targets' => collect(IssuePriority::cases())->map(fn (IssuePriority $priority): array => [
                'priority' => $priority->value,
                'response_minutes' => $slaLevel->targets->firstWhere('priority', $priority)->response_minutes,
                'resolution_minutes' => $slaLevel->targets->firstWhere('priority', $priority)->resolution_minutes,
            ])->all(),
        ];
    }
}
