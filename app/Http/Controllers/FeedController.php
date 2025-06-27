<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Intervention\Image\Facades\Image;
use App\Models\Post;
use App\Models\PostLike;
use App\Models\PostComment;
use App\Models\UserRelation;
use App\Models\User;

/**
 * @OA\Tag(
 *     name="Feed",
 *     description="Authentication endpoints"
 */

/**
 */

class FeedController extends Controller
{
    private $loggedUser;

    public function __construct()
    {
        $this->middleware('auth:api');
        $this->loggedUser = auth()->user();
    }

    /**
     * @OA\Post(
     *     path="/api/feed",
     *     summary="Cria uma nova postagem do tipo texto ou foto",
     *     tags={"Feed"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"type"},
     *                 @OA\Property(
     *                     property="type",
     *                     type="string",
     *                     description="Tipo da postagem: 'text' ou 'photo'",
     *                     enum={"text", "photo"}
     *                 ),
     *                 @OA\Property(
     *                     property="body",
     *                     type="string",
     *                 ),
     *                 @OA\Property(
     *                     property="photo",
     *                     type="file",
     *                     format="binary",
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Postagem criada com sucesso"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erro de validação ou dados ausentes"
     *     )
     * )
     */


    public function create(Request $request)
    {
        $type = $request->input('type');
        $body = $request->input('body');
        $photo = $request->file('photo');

        if ($type) {
            switch ($type) {
                case 'text':
                    if (!$body) {
                        return response()->json(['error' => 'Texto não enviado'], 422);
                    }
                    break;
                case 'photo':
                    if ($photo) {
                        $allowedTypes = ['image/jpg', 'image/jpeg', 'image/png'];

                        if (in_array($photo->getClientMimeType(), $allowedTypes)) {
                            $fileName = md5(time() . rand(0, 9999)) . '.jpg';

                            $destPath = public_path('/media/uploads');

                            $img = Image::make($photo->path())
                                ->resize(800, null, function ($constraint) {
                                    $constraint->aspectRatio(); //keep the proportion
                                })
                                ->save($destPath . '/' . $fileName);

                            $body = $fileName;
                        } else {
                            return response()->json(['error' => 'Arquivo não suportado!'], 422);
                        }
                    } else {
                        return response()->json(['error' => 'Arquivo não enviada!'], 422);
                    }
                    break;
                default:
                    return response()->json(['error' => 'Tipo de postagem inexistente!'], 422);
                    break;
            }

            if ($body) {
                $newPost = new Post();
                $newPost->user_id = $this->loggedUser['id'];
                $newPost->type = $type;
                $newPost->created_at = date('Y-m-d H:i:s');
                $newPost->body = $body;
                $newPost->save();
            }
        } else {
            return response()->json(['error' => 'Dados não enviados!'], 422);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/feed",
     *     tags={"Feed"},
     *     summary="Listar o feed",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Feed retornado com sucesso"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Feed não encontrado"
     *     )
     * )
     */

    public function read(Request $request)
    {
        $page = intval($request->input('page'));
        $perPage = 2;

        //pegar a lista de usuarios que eu sigo (incluindo o meu)
        $users = [];
        $userList = UserRelation::where('user_from', $this->loggedUser['id'])->get();

        foreach ($userList as $userItem) {
            $users[] = $userItem['user_to'];
        }

        $users[] = $this->loggedUser['id'];
        //pegar os posts dos usuarios que eu sigo ORDENANDO PELA DATA
        $postList = Post::whereIn('user_id', $users)
            ->orderBy('created_at', 'desc')
            ->offset($page * $perPage)
            ->limit($perPage)
            ->get();

        $total = Post::whereIn('user_id', $users)->count();
        $pageCount = ceil($total / $perPage);
        //preencher as informaçoes adicionais
        $posts = $this->_postListToObject($postList, $this->loggedUser['id']);

        $array['posts'] = $postList;
        $array['pageCount'] = $pageCount;
        $array['currentPage'] = $page;

        return $array;
    }

    private function _postListToObject($postList, $loggedId)
    {
        foreach ($postList as $postKey => $postItem) {
            // verificar se o post é meu
            if ($postItem['user_id'] == $loggedId) {
                $postList[$postKey]['mine'] = true;
            } else {
                $postList[$postKey]['mine'] = false;
            }

            //preencher informações adicionais
            $userInfo = User::find($postItem['user_id']);
            $userInfo['avatar'] = '/media/avatars/' . $userInfo['avatar'];
            $userInfo['cover'] = '/media/covers/' . $userInfo['cover'];
            $postList[$postKey]['user'] = $userInfo;

            //preencher informaçoes de LIKE
            $likes = PostLike::where('id_post', $postItem['id'])->count();
            $postList[$postKey]['likeCount'] = $likes;

            $isLiked = PostLike::where('id_post', $postItem['id'])
                ->where('id_user', $loggedId)
                ->count();
            $postList[$postKey]['liked'] = ($isLiked > 0) ? true : false;
            //preencher informaçoes de COMMENTS
            $comments = PostComment::where('id_post', $postItem['id'])->get();
            foreach ($comments as $commentsKey => $comemnt) {
                $user = User::find($comemnt['id_user']);
                $user['avatar'] = '/media/avatars/' . $user['avatar'];
                $user['cover'] = '/media/covers/' . $user['cover'];
                $comments[$commentsKey]['user'] = $user;
            }
            $postList[$postKey]['comments'] = $comments;
        }
    }

    /**
     * @OA\Get(
     *     path="/api/user/feed",
     *     summary="Retorna o feed do usuário por paginação",
     *     tags={"Feed"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         required=false,
     *         description="Número da página da listagem",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Feed do usuário retornado com sucesso"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Usuário não encontrado ou sem posts"
     *     )
     * )
     */


    public function userFeed(Request $request, $id = false)
    {
        if ($id == false) {
            $id = $this->loggedUser['id'];
        }

        $page = intval($request->input('page'));
        $perPage = 2;

        // pegar os post do usuario ordenado pela data
        $postList = Post::where('user_id', $id)
            ->orderBy('created_at', 'desc')
            ->offset($page * $perPage)
            ->limit($perPage)
            ->get();

        $total = Post::where('user_id', $id)->count();
        $pageCount = ceil($total / $perPage);

        //preencher as ifnormaçoes adicionais
        $posts = $this->_postListToObject($postList, $this->loggedUser['id']);

        $array['posts'] = $postList;
        $array['pageCount'] = $pageCount;
        $array['currentPage'] = $page;

        return $array;
    }

    /**
     * @OA\Get(
     *     path="/api/user/photos",
     *     summary="Retorna as fotos do usuário logado com paginação",
     *     tags={"Feed"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         required=false,
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Fotos do usuário retornadas com sucesso"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Usuário não encontrado ou sem fotos"
     *     )
     * )
     */


    public function userPhotos(Request $request, $id = false)
    {
        if ($id == false) {
            $id = $this->loggedUser['id'];
        }

        $page = intval($request->input('page'));
        $perPage = 2;

        // pegar as fotos do usuario ordenado pela data
        $postList = Post::where('user_id', $id)
            ->where('type', 'photo')
            ->orderBy('created_at', 'desc')
            ->offset($page * $perPage)
            ->limit($perPage)
            ->get();

        $total = Post::where('user_id', $id)
            ->where('type', 'photo')
            ->count();
        $pageCount = ceil($total / $perPage);

        //preencher as informaçoes adicionais
        $posts = $this->_postListToObject($postList, $this->loggedUser['id']);

        foreach ($postList as $pKey => $post) {
            $postList[$pKey]['body'] = '/media/uploads/' . $postList[$pKey]['body'];
        }

        $array['posts'] = $postList;
        $array['pageCount'] = $pageCount;
        $array['currentPage'] = $page;

        return $array;
    }
}
