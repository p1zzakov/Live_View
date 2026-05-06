<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Layout;
use Illuminate\Http\Request;

class LayoutController extends Controller
{
    protected function formatCamera($camera)
    {
        return [
            'id' => $camera->id,
            'name' => $camera->name,
            'channel_number' => $camera->channel_number,
            'nvr_id' => $camera->nvr_id,
            'stream_path' => "nvr{$camera->nvr_id}ch{$camera->channel_number}",
            'position' => $camera->pivot->position,
            'location' => $camera->location,
        ];
    }

    protected function getLayoutsQuery(Request $request)
    {
        $user = $request->user();

        // Если авторизован и есть ограничения по раскладкам
        if ($user && !$user->isAdmin()) {
            $allowedIds = $user->allowedLayouts()->pluck('layouts.id');
            if ($allowedIds->isNotEmpty()) {
                return Layout::whereIn('id', $allowedIds);
            }
        }

        // Админ или нет ограничений — все публичные
        return Layout::where('is_public', true);
    }

    public function index(Request $request)
    {
        $layouts = $this->getLayoutsQuery($request)
            ->with(['cameras' => function($query) {
                $query->where('is_active', true)
                      ->orderBy('layout_cameras.position');
            }])
            ->orderBy('is_default', 'desc')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $layouts->map(function($layout) {
                return [
                    'id' => $layout->id,
                    'name' => $layout->name,
                    'description' => $layout->description,
                    'grid_type' => $layout->grid_type,
                    'is_default' => $layout->is_default,
                    'cameras' => $layout->cameras->map(fn($c) => $this->formatCamera($c)),
                ];
            }),
        ]);
    }

    public function show(Request $request, $id)
    {
        $query = $this->getLayoutsQuery($request);
        $layout = $query->with(['cameras' => function($query) {
                $query->where('is_active', true)
                      ->orderBy('layout_cameras.position');
            }])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $layout->id,
                'name' => $layout->name,
                'description' => $layout->description,
                'grid_type' => $layout->grid_type,
                'is_default' => $layout->is_default,
                'cameras' => $layout->cameras->map(fn($c) => $this->formatCamera($c)),
            ],
        ]);
    }

    public function default(Request $request)
    {
        $user = $request->user();
        $query = $this->getLayoutsQuery($request);

        // Сначала ищем раскладку по умолчанию пользователя
        $layout = null;
        if ($user && $user->layout_id) {
            $layout = $query->with(['cameras' => function($q) {
                $q->where('is_active', true)->orderBy('layout_cameras.position');
            }])->find($user->layout_id);
        }

        // Потом глобальный default
        if (!$layout) {
            $layout = $query->where('is_default', true)
                ->with(['cameras' => function($q) {
                    $q->where('is_active', true)->orderBy('layout_cameras.position');
                }])->first();
        }

        // Потом первая доступная
        if (!$layout) {
            $layout = $query->with(['cameras' => function($q) {
                $q->where('is_active', true)->orderBy('layout_cameras.position');
            }])->first();
        }

        if (!$layout) {
            return response()->json(['success' => false, 'message' => 'No layouts found'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $layout->id,
                'name' => $layout->name,
                'description' => $layout->description,
                'grid_type' => $layout->grid_type,
                'is_default' => $layout->is_default,
                'cameras' => $layout->cameras->map(fn($c) => $this->formatCamera($c)),
            ],
        ]);
    }
}