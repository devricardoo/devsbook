<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Post;
use App\Models\PostComment;
use App\Models\PostLike;

class PostController extends Controller
{
    private $loggedUser;

    public function __construct()
    {
        $this->middleware('auth:api');
        $this->loggedUser = auth()->user();
    }

    public function like($id)
    {
        // 1. verificar se o post existe
        $postExists = Post::find($id);
        if ($postExists) {
            // 2. verificar se eu já dei like nesse post
            $isLiked = PostLike::where('id_post', $id)
                ->where('id_user', $this->loggedUser['id'])
                ->count();

            if ($isLiked > 0) {
                // 2.1 se sim, remover o like
                $pl = PostLike::where('id_post', $id)
                    ->where('id_user', $this->loggedUser['id'])
                    ->first();

                $pl->delete();

                $array['isLiked'] = false;
            } else {
                // 2.2 se não, dar like
                $newPostLike = new PostLike();
                $newPostLike->id_post = $id;
                $newPostLike->id_user = $this->loggedUser['id'];
                $newPostLike->created_at = date('Y-m-d H:i:s');
                $newPostLike->save();

                $array['isLiked'] = true;
            }

            $likeCount = PostLike::where('id_post', $id)->count();
            $array['likeCount'] = $likeCount;

            return response()->json($array);
        } else {
            return response()->json(['error' => 'Post não existe!']);
        }


        // 3. atualizar o contador de likes
    }

    public function comment(Request $request, $id)
    {
        $body = $request->input('body');

        $postExists = Post::find($id);
        if ($postExists) {
            if ($body) {
                $newPost = new PostComment();
                $newPost->id_post = $id;
                $newPost->id_user = $this->loggedUser['id'];
                $newPost->body = $body;
                $newPost->created_at = date('Y-m-d H:i:s');
                $newPost->save();

                return response()->json(['success' => 'Comentário enviado'], 200);
            } else {
                return response()->json(['error' => 'Comentário não enviado'], 422);
            }
        } else {
            return response()->json(['error' => 'Post não existe!']);
        }
    }
}
