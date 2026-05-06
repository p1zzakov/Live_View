<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Camera;
use Illuminate\Http\Request;

class CameraController extends Controller
{
    public function index()
    {
        $cameras = Camera::with(['nvr', 'groups'])
            ->where('is_active', true)
            ->get();

        return response()->json([
            'success' => true,
            'cameras' => $cameras
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:dahua,hikvision,polyvision,analog',
            'nvr_id' => 'required|exists:nvrs,id',
            'rtsp_live_url' => 'required|string',
            'channel_number' => 'required|integer',
            'location' => 'nullable|string',
        ]);

        $camera = Camera::create($validated);

        return response()->json([
            'success' => true,
            'camera' => $camera
        ], 201);
    }

    public function show(Camera $camera)
    {
        $camera->load(['nvr', 'groups', 'recordings']);
        
        return response()->json([
            'success' => true,
            'camera' => $camera
        ]);
    }

    public function update(Request $request, Camera $camera)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'is_active' => 'sometimes|boolean',
            'location' => 'sometimes|nullable|string',
        ]);

        $camera->update($validated);

        return response()->json([
            'success' => true,
            'camera' => $camera
        ]);
    }

    public function destroy(Camera $camera)
    {
        $camera->delete();

        return response()->json([
            'success' => true,
            'message' => 'Camera deleted successfully'
        ]);
    }
}
