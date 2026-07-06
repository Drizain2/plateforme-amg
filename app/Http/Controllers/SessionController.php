<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class SessionController extends Controller
{
    public function index(Request $request): Response
    {
        $currentSessionId = $request->session()->getId();

        $sessions = DB::table('sessions')
            ->where('user_id', auth()->id())
            ->orderByDesc('last_activity')
            ->get()
            ->map(fn (object $s) => [
                'id' => $s->id,
                'ip_address' => $s->ip_address,
                'user_agent' => $s->user_agent,
                'last_activity' => date('Y-m-d\TH:i:sP', $s->last_activity),
                'is_current' => $s->id === $currentSessionId,
            ]);

        return Inertia::render('Settings/Sessions', [
            'sessions' => $sessions,
        ]);
    }

    public function destroy(Request $request, string $session): RedirectResponse
    {
        abort_if($session === $request->session()->getId(), 422, 'Impossible de supprimer la session active.');

        DB::table('sessions')
            ->where('id', $session)
            ->where('user_id', auth()->id())
            ->delete();

        return back()->with('success', 'Session révoquée.');
    }

    public function destroyAll(Request $request): RedirectResponse
    {
        DB::table('sessions')
            ->where('user_id', auth()->id())
            ->where('id', '!=', $request->session()->getId())
            ->delete();

        return back()->with('success', 'Toutes les autres sessions ont été révoquées.');
    }
}
