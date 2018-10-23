<?php

namespace App\Http\Controllers;

use App\Exceptions\ValidationFaildException;
use App\Services\UploadFileService;
use App\Services\UserValidationService;
use App\User;
use Auth;
use Illuminate\Http\Request;
use Hash;
use Image;
use Storage;

class UsersController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }
    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \App\Exceptions\ValidationFaildException
     */
    public function edit(Request $request)
    {
        /** @var User $user */
        $user = Auth::guard('api')->user();

        if ($request->hasFile('avatar')) {

            if ($user->avatar !== 'storage/avatars/default.png') {
                unlink(public_path().$user->avatar);
            }
            $user->avatar = UploadFileService::uploadUserAvatar($request->file('avatar'), $user->id);
            $user->save();
        }
        try {
            /** @var UserValidationService $userValidationService */
            $userValidationService = new UserValidationService();
            $userValidationService->validateUser($request->all(), $user->id);
        } catch (ValidationFaildException $e) {
            return response()->json($userValidationService->errors(), 401);
        }

        if (!Hash::check($request->password, $user->password)) {
            return response()->json(['errors' => ['Old password is invalid']], 401);
        }

        $user->update([
            'email' => $request->email,
            'name' => $request->name,
            'password' => bcrypt($request->newPassword)
        ]);

        return response()->json($user);
    }
}