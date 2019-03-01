<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\User;

class PassportController extends Controller
{
    /**
     * Creates an api token while registering a new user
     *
     * @param Request $request
     * @return Response
     */
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
}
