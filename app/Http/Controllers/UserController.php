<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Intervention\Image\Facades\Image;
use App\Models\User;

class UserController extends Controller
{
    private $loggedUser;

    public function __construct()
    {
        $this->middleware('auth:api');
        $this->loggedUser = auth()->user();
    }

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
}
