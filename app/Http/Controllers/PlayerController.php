<?php

namespace App\Http\Controllers;

use App\Models\Player;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;

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

    private function teamsMap(): array
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }

        $base = rtrim(env('TEAMS_BASE', 'http://127.0.0.1:8081'), '/');
        $path = env('TEAMS_PATH', '/api/teams');
        $url  = $base . $path;

        try {
            $res = Http::timeout(3)->acceptJson()->get($url);
            if (!$res->ok()) {
                return $cache = [];
            }

            $data = $res->json();

            $items = [];
            if (is_array($data)) {
                if (array_key_exists('items', $data) && is_array($data['items'])) {
                    $items = $data['items'];
                } else {
                    $items = $data;
                }
            }

            $map = [];
            foreach ($items as $it) {
                $id   = $it['id']   ?? null;
                $name = $it['name'] ?? ($it['nombre'] ?? null);
                if ($id !== null && $name !== null) {
                    $map[(int)$id] = $name;
                }
            }
            return $cache = $map;
        } catch (\Throwable $e) {
            return $cache = [];
        }
    }

    public function index(Request $request): JsonResponse
    {
        $page     = max(1, (int) $request->query('page', 1));
        $pageSize = max(1, (int) $request->query('pageSize', 10));

        $q            = $request->query('q', $request->query('search'));
        $teamId       = $request->query('team_id', $request->query('equipoId'));
        $position     = $request->query('position', $request->query('posicion'));
        $equipoNombre = $request->query('equipoNombre'); 
        $sortBy       = $request->query('sortBy');
        $sortDir      = strtolower($request->query('sortDir', 'asc')) === 'desc' ? 'desc' : 'asc';

        $query = Player::query();

        if ($q) {
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', '%' . $q . '%')
                    ->orWhere('position', 'like', '%' . $q . '%')
                    ->orWhere('number', (int) $q);
            });
        }

        if ($teamId !== null && $teamId !== '') {
            $query->where('team_id', (int) $teamId);
        }

        if ($position) {
            $query->where('position', 'like', '%' . $position . '%');
        }

        if ($equipoNombre) {
            $map = $this->teamsMap();
            if ($map) {
                $needle = mb_strtolower($equipoNombre);
                $ids = [];
                foreach ($map as $id => $name) {
                    if (strpos(mb_strtolower($name), $needle) !== false) {
                        $ids[] = (int)$id;
                    }
                }
                if ($ids) {
                    $query->whereIn('team_id', $ids);
                } else {
                    $query->whereRaw('1=0');
                }
            }
        }

        if ($sortBy) {
            $mapSort = [
                'nombre'   => 'name',
                'equipo'   => 'team_id',
                'posicion' => 'position',
            ];
            $col = $mapSort[$sortBy] ?? null;
            if ($col) {
                $query->orderBy($col, $sortDir);
            } else {
                $query->orderBy('id', 'desc');
            }
        } else {
            $query->orderBy('id', 'desc');
        }

        $total = $query->count();
        $items = $query->forPage($page, $pageSize)->get();

        $teams = $this->teamsMap();
        $items = $items->map(function ($p) use ($teams) {
            $teamName = $teams[$p->team_id] ?? null;

            $arr = $p->toArray();
            $arr['nombre']        = $p->name;
            $arr['posicion']      = $p->position;
            $arr['equipoId']      = $p->team_id;
            $arr['equipoNombre']  = $teamName;   
            $arr['equipo']        = $teamName;   
            return $arr;
        });

        return response()->json([
            'items'      => $items->values(),
            'totalItems' => $total,
            'page'       => $page,
            'pageSize'   => $pageSize,
        ]);
    }

    /**
     * GET /api/jugadores 
     */
    public function listAll(Request $request): JsonResponse
    {
        $q            = $request->query('q', $request->query('search'));
        $teamId       = $request->query('team_id', $request->query('equipoId'));
        $position     = $request->query('position', $request->query('posicion'));
        $equipoNombre = $request->query('equipoNombre');

        $query = Player::query();

        if ($q) {
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', '%' . $q . '%')
                    ->orWhere('position', 'like', '%' . $q . '%')
                    ->orWhere('number', (int) $q);
            });
        }
        if ($teamId !== null && $teamId !== '') {
            $query->where('team_id', (int) $teamId);
        }
        if ($position) {
            $query->where('position', 'like', '%' . $position . '%');
        }
        if ($equipoNombre) {
            $map = $this->teamsMap();
            if ($map) {
                $needle = mb_strtolower($equipoNombre);
                $ids = [];
                foreach ($map as $id => $name) {
                    if (strpos(mb_strtolower($name), $needle) !== false) {
                        $ids[] = (int)$id;
                    }
                }
                if ($ids) {
                    $query->whereIn('team_id', $ids);
                } else {
                    $query->whereRaw('1=0');
                }
            }
        }

        $teams = $this->teamsMap();
        $items = $query->orderBy('id', 'desc')->get()->map(function ($p) use ($teams) {
            $teamName = $teams[$p->team_id] ?? null;

            $arr = $p->toArray();
            $arr['nombre']        = $p->name;
            $arr['posicion']      = $p->position;
            $arr['equipoId']      = $p->team_id;
            $arr['equipoNombre']  = $teamName;  
            $arr['equipo']        = $teamName;  
            return $arr;
        });

        return response()->json($items->values());
    }

    // GET /api/players/{id}  |  /api/jugadores/{id}
    public function show(int $id): JsonResponse
    {
        $player = Player::findOrFail($id);
        $teams  = $this->teamsMap();
        $teamName = $teams[$player->team_id] ?? null;

        $arr = $player->toArray();
        $arr['nombre']        = $player->name;
        $arr['posicion']      = $player->position;
        $arr['equipoId']      = $player->team_id;
        $arr['equipoNombre']  = $teamName;   
        $arr['equipo']        = $teamName;   

        return response()->json($arr);
    }

    // POST /api/players  |  /api/jugadores
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

        $input = $request->all();
        $input['name']     = $input['name']     ?? $input['nombre']   ?? null;
        $input['position'] = $input['position'] ?? $input['posicion'] ?? null;
        $input['team_id']  = $input['team_id']  ?? $input['equipoId'] ?? null;
        $input['number']   = $input['number']   ?? $input['numero']   ?? 0;

        $data = validator($input, [
            'name'      => 'required|string|max:255',
            'number'    => 'required|integer|min:0',
            'position'  => 'required|string|max:10',
            'team_id'   => 'required|integer|min:1',
            'photo_url' => 'nullable|string',
        ])->validate();

        $player = Player::create($data);
        return response()->json($player, 201);
    }

    // PUT /api/players/{id} | /api/jugadores/{id}
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

        $input = $request->all();
        if (isset($input['nombre']))   $input['name']     = $input['nombre'];
        if (isset($input['posicion'])) $input['position'] = $input['posicion'];
        if (isset($input['equipoId'])) $input['team_id']  = $input['equipoId'];
        if (isset($input['numero']))   $input['number']   = $input['numero'];

        $data = validator($input, [
            'name'      => 'sometimes|required|string|max:255',
            'number'    => 'sometimes|required|integer|min:0',
            'position'  => 'sometimes|required|string|max:10',
            'team_id'   => 'sometimes|required|integer|min:1',
            'photo_url' => 'nullable|string',
        ])->validate();

        $player = Player::findOrFail($id);
        $player->fill($data)->save();

        return response()->json($player);
    }

    // DELETE /api/players/{id} | /api/jugadores/{id}
    public function destroy(int $id): JsonResponse
    {
        $player = Player::findOrFail($id);
        $player->delete();

        return response()->json(['deleted' => true]);
    }
}
