<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class AuthController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api', [
            'except' => [
                'login',
                'create',
                'unauthorized'
            ]
        ]);
    }

    public function create(Request $request)
    {
        $name = $request->input('name');
        $email = $request->input('email');
        $password = $request->input('password');
        $birthdate = $request->input('birthdate');

        if ($name && $email && $password && $birthdate) {
            // validando a data de nascimento
            if (strtotime($birthdate) === false) {
                return response()->json(['error' => 'Data de nascimento inválida!'], 422);
            }
            //verificar a exitência do email
            $emailExists = User::where('email', $email)->count();
            if ($emailExists === 0) {
                $hash = password_hash($password, PASSWORD_DEFAULT);

                $newUser = User::create([
                    'name' => $name,
                    'email' => $email,
                    'password' => $hash,
                    'birthdate' => $birthdate
                ]);
                $newUser->save();

                $token = auth()->attempt([
                    'email' => $email,
                    'password' => $password
                ]);
                if (!$token) {
                    return response()->json(['error' => 'Unauthorized'], 401);
                }
                return response()->json(['token' => $token], 200);
            } else {
                return response()->json(['error' => 'Este e-mail já fo cadastrado!'], 422);
            }
        } else {
            return response()->json(['error' => 'Os dados não foram preenchidos!'], 422);
        }
    }
}