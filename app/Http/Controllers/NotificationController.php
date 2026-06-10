<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    
    public function index():JsonResponse
    {
        return response()->json([
            'notifications' => auth()->user()
                ->notifications()
                ->latest()
                ->limit(20)
                ->get()
                ->map(fn($n) => [
                    'id'         => $n->id,
                    'data'       => $n->data,
                    'read_at'    => $n->read_at?->diffForHumans(),
                    'created_at' => $n->created_at->diffForHumans(),
                ]),
            'unread_count' => auth()->user()->unreadNotifications()->count(),
        ]);
    }

    public function markRead(string $id): JsonResponse
    {
        auth()->user()
            ->notifications()
            ->find($id)
            ?->markAsRead();

        return response()->json(['ok' => true]);
    }

    public function markAllRead(): JsonResponse
    {
        auth()->user()->unreadNotifications->markAsRead();
        return response()->json(['ok' => true]);
    }
}
