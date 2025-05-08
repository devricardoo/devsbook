<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
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
}
