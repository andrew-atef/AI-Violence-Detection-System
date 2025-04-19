<?php

namespace App\Http\Controllers;

use App\Http\Resources\ViolenceNotificationResource;
use App\Models\ViolenceNotifications;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class ViolenceNotificationController extends Controller
{
    /**
     * Display a paginated list of violence notifications
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        // Validate pagination parameters
        $validator = Validator::make($request->all(), [
            'limit' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $limit = $request->get('limit', 10);
        $notifications = ViolenceNotifications::latest()->paginate($limit);

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

    /**
     * Display a specific violence notification
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $notification = ViolenceNotifications::find($id);
        
        if (!$notification) {
            return response()->json(['error' => 'Notification not found'], 404);
        }
        
        return response()->json([
            'violence_notification' => new ViolenceNotificationResource($notification)
        ]);
    }
}
