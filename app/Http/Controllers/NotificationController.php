<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\UserPreference;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $notifications = Notification::forUser($request->user()->id)
            ->orderByDesc('created_at')
            ->limit(30)
            ->get();

        $unread = Notification::forUser($request->user()->id)
            ->unread()
            ->count();

        return response()->json([
            'notifications' => $notifications,
            'unread'        => $unread,
        ]);
    }

    public function markRead(Request $request, Notification $notification): JsonResponse
    {
        if ($notification->user_id !== $request->user()->id) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $notification->update(['read_at' => now()]);
        return response()->json(['message' => 'Marked as read']);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        Notification::forUser($request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['message' => 'All marked as read']);
    }

    public function preferences(Request $request): JsonResponse
    {
        $prefs = UserPreference::firstOrCreate(
            ['user_id' => $request->user()->id],
            ['notify_market_status' => true, 'notify_price_alerts' => true]
        );

        return response()->json($prefs->only(['notify_market_status', 'notify_price_alerts']));
    }

    public function updatePreferences(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'notify_market_status' => 'boolean',
            'notify_price_alerts'  => 'boolean',
        ]);

        UserPreference::updateOrCreate(
            ['user_id' => $request->user()->id],
            $validated
        );

        return response()->json(['message' => 'Preferences updated']);
    }
}
