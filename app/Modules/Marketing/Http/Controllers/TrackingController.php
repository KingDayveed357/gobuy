<?php

namespace App\Modules\Marketing\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Marketing\Models\BlockEvent;
use App\Modules\Marketing\Models\HomepageSection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

class TrackingController extends Controller
{
    /** Cap per beacon so a hostile client can't flood the table in one request. */
    private const MAX_EVENTS = 50;

    /**
     * Record a batch of storefront block impressions/clicks. Sent by a fetch
     * keepalive beacon, so it stays cheap, always answers JSON (never the
     * global redirect-on-validation path), and never breaks the page.
     */
    public function store(Request $request): Response|JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'events' => ['required', 'array', 'max:'.self::MAX_EVENTS],
            'events.*.id' => ['required', 'integer'],
            'events.*.type' => ['required', 'in:'.implode(',', BlockEvent::TYPES)],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Invalid telemetry payload.'], 422);
        }

        $events = collect($validator->validated()['events']);

        // Only keep events for sections that actually exist (drops FK violations
        // from stale tabs / tampered payloads without erroring the request).
        $validIds = HomepageSection::whereIn('id', $events->pluck('id')->unique())->pluck('id')->flip();
        $now = now();

        $rows = $events
            ->filter(fn (array $e): bool => $validIds->has($e['id']))
            ->map(fn (array $e): array => [
                'homepage_section_id' => $e['id'],
                'type' => $e['type'],
                'created_at' => $now,
            ])
            ->all();

        if ($rows !== []) {
            BlockEvent::insert($rows);
        }

        return response()->noContent();
    }
}
