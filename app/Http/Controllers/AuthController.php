<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Models\User;
use Tymon\JWTAuth\Facades\JWTAuth;

/**
 * @OA\Tag(
 *     name="Auth",
 *     description="Authentication endpoints"
 */

/**
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 */
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

    public function unauthorized()
    {
        return response()->json([
            'error' => 'Não autorizado',
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/auth/login",
     *     tags={"Auth"},
     *     summary="Efetuar login",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email", "password"},
     *             @OA\Property(property="email", type="string", example="ricardo18@gmail.com"),
     *             @OA\Property(property="password", type="string", example="1234"),
     *         )
     *     ),
     *     @OA\Response(response=200, description="Login efetuado com sucesso"),
     *     @OA\Response(response=400, description="E-mail ou senha incorretos"),
     * )
     */

    public function login(Request $request)
    {
        $email = $request->input('email');
        $password = $request->input('password');

        if ($email && $password) {
            $token = Auth::attempt([
                'email' => $email,
                'password' => $password
            ]);

            if (!$token) {
                return \response()->json([
                    'error' => 'E-mail ou senha incorretos!'
                ]);
            }

            return \response()->json([
                'token' => $token,
                'user' => auth()->user()
            ]);
        }
        return \response()->json([
            'error' => 'Os dados não foram preenchidos!'
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/auth/logout",
     *     tags={"Auth"},
     *     summary="Efetuar logout",
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(response=200, description="Usuário deslogado com sucesso"),
     *     @OA\Response(response=400, description="Token inválido"),
     * )
     */

    public function logout()
    {
        auth()->logout();
        return response()->json(['message' => 'Logout efetuado com sucesso!']);
    }

    /**
     * @OA\Post(
     *     path="/api/auth/refresh",
     *     tags={"Auth"},
     *     summary="Atualizar token",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Token atualizado com sucesso",
     * ),
     * )
     */

    public function refresh()
    {
        $token = JWTAuth::parseToken()->refresh();

        $info = auth()->user();

        return response()->json([
            'token' => $token,
            'data' => $info,
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/devbook/user",
     *     tags={"Auth"},
     *     summary="Registrar um novo usuário",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "email", "password", "birthdate"},
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="email", type="string"),
     *             @OA\Property(property="password", type="string"),
     *             @OA\Property(property="birthdate", type="string")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Usuário registrado com sucesso"),
     *     @OA\Response(response=400, description="Dados inválidos"),
     *     @OA\Response(response=422, description="E-mail ja cadastrado"),
     * )
     */

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
                return response()->json([
                    'token' => $token,
                    'user' => auth()->user()
                ], 200);
            } else {
                return response()->json(['error' => 'Este e-mail já fo cadastrado!'], 422);
            }
        } else {
            return response()->json(['error' => 'Os dados não foram preenchidos!'], 422);
        }
    }
}
