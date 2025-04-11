<?php

namespace App\Http\Controllers;

use App\Http\Collections\ViolenceNotificationCollection;
use App\Http\Resources\ViolenceNotificationResource;
use App\Models\ViolenceNotifications;
use Illuminate\Http\Request;

class ViolenceNotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = ViolenceNotifications::latest()->paginate($request->get('limit', 10));

        return response()->json([
            'violence_notifications' => ViolenceNotificationResource::collection($notifications->items()),
            'pagination' => [
                'total' => $notifications->total(),
                'count' => $notifications->count(),
                'per_page' => $notifications->perPage(),
                'current_page' => $notifications->currentPage(),
                'total_pages' => $notifications->lastPage(),
            ],
        ]);
    }
}
