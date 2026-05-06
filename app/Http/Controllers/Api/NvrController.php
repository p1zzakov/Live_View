<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Nvr;
use Illuminate\Http\Request;

class NvrController extends Controller
{
    public function index()
    {
        $nvrs = Nvr::with('cameras')->get();

        return response()->json([
            'success' => true,
            'nvrs' => $nvrs
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'vendor' => 'required|in:polyvision,dahua,hikvision,other',
            'ip_address' => 'required|ip',
            'http_port' => 'sometimes|integer',
            'rtsp_port' => 'sometimes|integer',
            'credentials' => 'required|array',
            'credentials.username' => 'required|string',
            'credentials.password' => 'required|string',
        ]);

        $nvr = Nvr::create($validated);

        return response()->json([
            'success' => true,
            'nvr' => $nvr
        ], 201);
    }

    public function show(Nvr $nvr)
    {
        $nvr->load('cameras');
        
        return response()->json([
            'success' => true,
            'nvr' => $nvr
        ]);
    }

    public function update(Request $request, Nvr $nvr)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'is_active' => 'sometimes|boolean',
        ]);

        $nvr->update($validated);

        return response()->json([
            'success' => true,
            'nvr' => $nvr
        ]);
    }

    public function destroy(Nvr $nvr)
    {
        $nvr->delete();

        return response()->json([
            'success' => true,
            'message' => 'NVR deleted successfully'
        ]);
    }
}
