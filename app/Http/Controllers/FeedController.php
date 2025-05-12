<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Intervention\Image\Facades\Image;
use App\Models\Post;

class FeedController extends Controller
{
    private $loggedUser;

    public function __construct()
    {
        $this->middleware('auth:api');
        $this->loggedUser = auth()->user();
    }

    public function read()
    {
        return response()->json(['teste' => 'ok']);
    }

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
}
