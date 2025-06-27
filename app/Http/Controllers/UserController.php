<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Intervention\Image\Facades\Image;
use App\Models\User;
use App\Models\UserRelation;
use DateTime;

/**
 * @OA\Tag(
 *     name="User",
 *     description="Authentication endpoints"
 */

/**
 */

class UserController extends Controller
{
    private $loggedUser;

    public function __construct()
    {
        $this->middleware('auth:api');
        $this->loggedUser = auth()->user();
    }

    /**
     * @OA\Put(
     *     path="/api/user",
     *     tags={"User"},
     *     summary="Atualizar informações do usuário logado",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example=""),
     *             @OA\Property(property="email", type="string", format="email", example=""),
     *             @OA\Property(property="password", type="string", example=""),
     *             @OA\Property(property="password_confirmation", type="string", example=""),
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Usuário atualizado com sucesso"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erro de validação"
     *     )
     * )
     */

    public function update(Request $request)
    {
        $name = $request->input('name');
        $email = $request->input('email');
        $birthdate = $request->input('birthdate');
        $city = $request->input('city');
        $work = $request->input('work');
        $password = $request->input('password');
        $password_confirm = $request->input('password_confirm');

        $user = User::find($this->loggedUser['id']);

        //NAME
        if ($name) {
            $user->name = $name;
        }
        //EMAIL
        if ($email) {
            if ($email != $user->email) {
                $emailExists = User::where('email', $email)->count();
                if ($emailExists === 0) {
                    $user->email = $email;
                }
            } else {
                return response()->json(['error' => 'E-mail já existe!'], 422);
            }
        }
        //BIRTHDATE
        if ($birthdate) {
            if (strtotime($birthdate) === false) {
                return response()->json(['error' => 'Data de nascimento inválida!'], 422);
            }
            $user->birthdate = $birthdate;
        }
        //CITY
        if ($city) {
            $user->city = $city;
        }
        //WORK
        if ($work) {
            $user->work = $work;
        }
        //PASSWORD
        if ($password && $password_confirm) {
            if ($password === $password_confirm) {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $user->password = $hash;
            } else {
                return response()->json(['error' => 'as senhas não conferem!'], 422);
            }
        }
        $user->save();
    }

    /**
     * @OA\Post(
     *     path="/api/user/avatar",
     *     tags={"User"},
     *     summary="Adicionar avatar ao usuário logado",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="avatar",
     *                     type="file",
     *                     format="binary",
     *                 ),
     *             ),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Avatar adicionado com sucesso"
     *     ),
     *     @OA\Response(response=400, description="Dados inválidos"),
     * )
     */

    public function updateAvatar(Request $request)
    {
        $allowedTypes = ['image/jpg', 'image/jpeg', 'image/png'];

        $image = $request->file('avatar');

        if ($image) {
            if (in_array($image->getClientMimeType(), $allowedTypes)) {
                $fileName = md5(time() . rand(0, 9999)) . '.jpg';

                $destPath = public_path('/media/avatars');

                $img = Image::make($image->path())
                    ->fit(200, 200)
                    ->save($destPath . '/' . $fileName);

                $user = User::find($this->loggedUser['id']);
                $user->avatar = $fileName;
                $user->save();

                return response()->json(['url' => '/media/avatars/' . $fileName]);
            } else {
                return response()->json(['error' => 'Arquivo não suportado!'], 422);
            }
        } else {
            return response()->json(['error' => 'Arquivo não enviado!'], 422);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/user/cover",
     *     tags={"User"},
     *     summary="Adicionar cover ao usuário logado",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="cover",
     *                     type="file",
     *                     format="binary",
     *                 ),
     *             ),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Cover adicionado com sucesso"
     *     ),
     *     @OA\Response(response=400, description="Dados inválidos"),
     * )
     */

    public function updateCover(Request $request)
    {
        $allowedTypes = ['image/jpg', 'image/jpeg', 'image/png'];

        $image = $request->file('cover');

        if ($image) {
            if (in_array($image->getClientMimeType(), $allowedTypes)) {
                $fileName = md5(time() . rand(0, 9999)) . '.jpg';

                $destPath = public_path('/media/covers');

                $img = Image::make($image->path())
                    ->fit(850, 310)
                    ->save($destPath . '/' . $fileName);

                $user = User::find($this->loggedUser['id']);
                $user->cover = $fileName;
                $user->save();

                return response()->json(['url' => '/media/covers/' . $fileName]);
            } else {
                return response()->json(['error' => 'Arquivo não suportado!'], 422);
            }
        } else {
            return response()->json(['error' => 'Arquivo não enviado!'], 422);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/user",
     *     tags={"User"},
     *     summary="Listar o usuário logado",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Usuário retornado com sucesso"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Usuário não encontrado"
     *     )
     * )
     */

    public function read($id = false) //o id é opcional
    {
        if ($id) {
            $info = User::find($id);
            if (!$info) {
                return \response()->json(['error' => 'Usuário não existente!'], 422);
            }
        } else {
            $info = $this->loggedUser;
        }

        $info['avatar'] = '/media/avatars/' . $info['avatar'];
        $info['cover'] = '/media/covers/' . $info['cover'];

        $info['me'] = ($info['id'] == $this->loggedUser['id']) ? true : false;

        $dateFrom = new DateTime($info['birthdate']);
        $dateTo = new DateTime('today');
        $info['age'] = $dateFrom->diff($dateTo)->y;

        $info['followers'] = UserRelation::where('user_to', $info['id'])->count();

        $info['following'] = UserRelation::where('user_from', $info['id'])->count();

        $info['photoCount'] = Post::where('user_id', $info['id'])->where('type', 'photo')->count();

        $hasRelation = UserRelation::where('user_from', $this->loggedUser['id'])
            ->where('user_to', $info['id'])
            ->count();
        $info['isFollowing'] = ($hasRelation > 0) ? true : false;

        $array['data'] = $info;

        return $array;
    }

    public function follow($id)
    {
        if ($id == $this->loggedUser['id']) {
            return response()->json(['error' => 'Você não pode seguir a si mesmo!'], 422);
        }

        $userExists = User::find($id);
        if ($userExists) {
            $relation = UserRelation::where('user_from', $this->loggedUser['id'])
                ->where('user_to', $id)
                ->first();

            if ($relation) {
                //paro de seguir
                $relation->delete();
            } else {
                //começo a seguir
                $newRelation = new UserRelation();
                $newRelation->user_from = $this->loggedUser['id'];
                $newRelation->user_to = $id;
                $newRelation->save();
            }
        } else {
            return response()->json(['error' => 'Usuário inexistente!'], 422);
        }

        return response()->json(['success' => 'Operação realizada com sucesso!']);
    }

    public function followers($id)
    {
        $userExists = User::find($id);
        if ($userExists) {
            $followers = UserRelation::where('user_to', $id)->get();
            $following = UserRelation::where('user_from', $id)->get();

            $array['followers'] = [];
            $array['following'] = [];

            foreach ($followers as $item) {
                $user = User::find($item['user_from']);
                $array['followers'][] = [
                    'id' => $user['id'],
                    'name' => $user['name'],
                    'avatar' => '/media/avatars/' . $user['avatar']
                ];
            }
            foreach ($following as $item) {
                $user = User::find($item['user_to']);
                $array['following'][] = [
                    'id' => $user['id'],
                    'name' => $user['name'],
                    'avatar' => '/media/avatars/' . $user['avatar']
                ];
            }
        } else {
            return response()->json(['error' => 'Usuário inexistente!'], 422);
        }

        return $array;
    }
}
