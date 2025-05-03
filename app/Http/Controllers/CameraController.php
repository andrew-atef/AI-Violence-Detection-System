<?php

namespace App\Http\Controllers;

use App\Models\Camera;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CameraController extends Controller
{

    public function index()
    {
        $cameras = Camera::with('users')->get();
        return response()->json(['cameras' => $cameras]);
    }


    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'user_id' => 'nullable|exists:users,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $camera = Camera::create($request->all());
        return response()->json(['message' => 'Camera created successfully', 'camera' => $camera], 201);
    }


    public function show($id)
    {
        $camera = Camera::with('users')->find($id);

        if (!$camera) {
            return response()->json(['error' => 'Camera not found'], 404);
        }

        return response()->json(['camera' => $camera]);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'user_id' => 'nullable|exists:users,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $camera = Camera::find($id);

        if (!$camera) {
            return response()->json(['error' => 'Camera not found'], 404);
        }

        $camera->update($request->all());
        return response()->json(['message' => 'Camera updated successfully', 'camera' => $camera]);
    }


    public function destroy($id)
    {
        $camera = Camera::find($id);

        if (!$camera) {
            return response()->json(['error' => 'Camera not found'], 404);
        }

        $camera->delete();
        return response()->json(['message' => 'Camera deleted successfully']);
    }


    public function assignToUser(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $camera = Camera::find($id);

        if (!$camera) {
            return response()->json(['error' => 'Camera not found'], 404);
        }

        $user = User::find($request->user_id);

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        $camera->user_id = $request->user_id;
        $camera->save();

        return response()->json([
            'message' => 'Camera assigned to user successfully',
            'camera' => $camera
        ]);
    }
}
