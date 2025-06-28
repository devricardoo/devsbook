<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

/**
 * @OA\Tag(
 *     name="Search",
 *     description="Authentication endpoints"
 */

/**
 */

class SearchController extends Controller
{
    private $loggedUser;

    public function __construct()
    {
        $this->middleware('auth:api');
        $this->loggedUser = auth()->user();
    }

    /**
     * @OA\Get(
     *     path="/api/search",
     *     tags={"Search"},
     *     summary="Filtrar usuários",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="txt",
     *         in="query",
     *         required=true,
     *         description="Texto de busca para filtrar os usuários",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Busca realizada com sucesso",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="users",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="name", type="string"),
     *                     @OA\Property(property="avatar", type="string", format="url")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Texto de busca não informado",
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Erro na busca"
     *     )
     * )
     */


    public function search(Request $request)
    {
        $txt = $request->input('txt');

        if ($txt) {
            $userList = User::where('name', 'like', '%' . $txt . '%')
                //->orWhere('email', 'like', '%' . $txt . '%')
                ->get();

            foreach ($userList as $userItem) {
                $array['users'][] = [
                    'id' => $userItem['id'],
                    'name' => $userItem['name'],
                    //'email' => $userItem['email'],
                    'avatar' => \url('/media/avatars/' . $userItem['avatar'])
                ];
            }
        } else {
            return response()->json(['error' => 'Digite alguma coisa para buscar!'], 422);
        }

        return response()->json(['users' => $array['users']], 200);
    }
}