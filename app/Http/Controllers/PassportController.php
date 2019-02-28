<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\User;

class PassportController extends Controller
{
    public function register(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|min:3',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
        ]);
 
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password)
        ]);
 
        $token = $user->createToken('image-manipulation')->accessToken;
 
        return response()->json(['token' => $token])
            ->setStatusCode(Response::HTTP_OK);
    }

    public function login(Request $request)
    {
        $credentials = [
            'email' => $request->email,
            'password' => $request->password
        ];
 
        if (auth()->attempt($credentials)) {
            $token = auth()->user()->createToken('image-manipulation')->accessToken;

            return response()->json(['token' => $token])
                ->setStatusCode(Response::HTTP_OK);
        }

        return response()->json(['error' => 'Unauthorized.'])
            ->setStatusCode(Response::HTTP_UNAUTHORIZED);
    }

    public function details()
    {
        return response()->json(['user' => auth()->user()])
            ->setStatusCode(Response::HTTP_OK);
    }
}
