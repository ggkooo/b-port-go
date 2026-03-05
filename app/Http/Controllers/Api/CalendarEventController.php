<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ListCalendarEventsByMonthRequest;
use App\Http\Requests\Api\StoreCalendarEventRequest;
use App\Http\Requests\Api\UpdateCalendarEventRequest;
use App\Models\CalendarEvent;
use Illuminate\Http\JsonResponse;

class CalendarEventController extends Controller
{
    public function byMonth(ListCalendarEventsByMonthRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $events = CalendarEvent::query()
            ->whereYear('event_date', $validated['year'])
            ->whereMonth('event_date', $validated['month'])
            ->orderBy('event_date')
            ->orderBy('event_time')
            ->get([
                'id',
                'event_date',
                'event_time',
                'name',
                'description',
            ]);

        return response()->json([
            'count' => $events->count(),
            'events' => $events,
        ]);
    }

    public function store(StoreCalendarEventRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $validated['event_time'] = $validated['event_time'].':00';

        $event = CalendarEvent::query()->create($validated);

        return response()->json([
            'message' => 'Evento criado com sucesso.',
            'event' => $event,
        ], 201);
    }

    public function update(UpdateCalendarEventRequest $request, CalendarEvent $calendarEvent): JsonResponse
    {
        $validated = $request->validated();

        if (isset($validated['event_time'])) {
            $validated['event_time'] = $validated['event_time'].':00';
        }

        $calendarEvent->update($validated);

        return response()->json([
            'message' => 'Evento atualizado com sucesso.',
            'event' => $calendarEvent,
        ]);
    }

    public function destroy(CalendarEvent $calendarEvent): JsonResponse
    {
        $calendarEvent->delete();

        return response()->json([
            'message' => 'Evento removido com sucesso.',
        ]);
    }
}
