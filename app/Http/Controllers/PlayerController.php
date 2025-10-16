<?php

namespace App\Http\Controllers;

use App\Models\Player;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PlayerController extends Controller
{
    public function health(): JsonResponse
    {
        return response()->json([
            'service' => 'players-service',
            'status'  => 'ok',
            'time'    => now()->toISOString(),
        ]);
    }

    // GET /api/players?page=&pageSize=&q=
    public function index(Request $request): JsonResponse
    {
        $page     = (int) $request->query('page', 1);
        $pageSize = (int) $request->query('pageSize', 10);
        $q        = $request->query('q');

        $query = Player::query();

        if ($q) {
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('position', 'like', "%{$q}%")
                    ->orWhere('number', (int) $q);
            });
        }

        $total    = $query->count();
        $items    = $query->orderBy('id', 'desc')->forPage($page, $pageSize)->get();

        return response()->json([
            'items'      => $items,
            'totalItems' => $total,
            'page'       => $page,
            'pageSize'   => $pageSize,
        ]);
    }

    // GET /api/players/{id}
    public function show(int $id): JsonResponse
    {
        $player = Player::findOrFail($id);
        return response()->json($player);
    }

    // POST /api/players
    public function store(Request $request): JsonResponse
    {
        $payload = $request->all();
        if (empty($payload)) {
            $raw = $request->getContent();
            $decoded = json_decode($raw, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $request->merge($decoded);
            }
        }

        $data = $request->validate([
            'name'      => 'required|string|max:255',
            'number'    => 'required|integer|min:0',
            'position'  => 'required|string|max:10',
            'team_id'   => 'required|integer|min:1',
            'photo_url' => 'nullable|string',
        ]);

        $player = Player::create($data);
        return response()->json($player, 201);
    }

    // PUT /api/players/{id}
    public function update(Request $request, int $id): JsonResponse
    {
        $payload = $request->all();
        if (empty($payload)) {
            $raw = $request->getContent();
            $decoded = json_decode($raw, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $request->merge($decoded);
            }
        }

        $data = $request->validate([
            'name'      => 'sometimes|required|string|max:255',
            'number'    => 'sometimes|required|integer|min:0',
            'position'  => 'sometimes|required|string|max:10',
            'team_id'   => 'sometimes|required|integer|min:1',
            'photo_url' => 'nullable|string',
        ]);

        $player = Player::findOrFail($id);
        $player->fill($data)->save();

        return response()->json($player);
    }

    // DELETE /api/players/{id}
    public function destroy(int $id): JsonResponse
    {
        $player = Player::findOrFail($id);
        $player->delete();

        return response()->json(['deleted' => true]);
    }
}
