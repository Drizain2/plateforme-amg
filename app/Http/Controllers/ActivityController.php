<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;
use Spatie\Activitylog\Models\Activity;

class ActivityController extends Controller
{
    public function index(): Response
    {
        $shopId = auth()->user()->shop_id;

        $logs = Activity::with('causer')
            ->where(function ($query) use ($shopId): void {
                $query->where(function ($q) use ($shopId): void {
                    $q->where('subject_type', 'App\Models\Ticket')
                        ->whereIn('subject_id', fn ($sub) => $sub->select('id')->from('tickets')->where('shop_id', $shopId));
                })->orWhere(function ($q) use ($shopId): void {
                    $q->where('subject_type', 'App\Models\Invoice')
                        ->whereIn('subject_id', fn ($sub) => $sub->select('id')->from('invoices')->where('shop_id', $shopId));
                })->orWhere(function ($q) use ($shopId): void {
                    $q->where('subject_type', 'App\Models\User')
                        ->whereIn('subject_id', fn ($sub) => $sub->select('id')->from('users')->where('shop_id', $shopId));
                })->orWhere(function ($q) use ($shopId): void {
                    $q->where('subject_type', 'App\Models\Payment')
                        ->whereIn('subject_id', fn ($sub) => $sub->select('id')->from('payments')->where('shop_id', $shopId));
                });
            })
            ->latest()
            ->paginate(50)
            ->through(fn (Activity $a) => [
                'id' => $a->id,
                'log_name' => $a->log_name,
                'event' => $a->event,
                'subject_type' => class_basename((string) $a->subject_type),
                'subject_id' => $a->subject_id,
                'description' => $a->description,
                'causer' => $a->causer ? ['name' => $a->causer->name] : null,
                'properties' => $a->properties->toArray(),
                'created_at' => $a->created_at?->toIso8601String(),
            ]);

        return Inertia::render('Settings/ActivityLog', [
            'logs' => $logs,
        ]);
    }
}
