<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\App;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use App\Http\Requests\RegisterRequest;

class CreateNewUser implements CreatesNewUsers
{

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
    // RegisterRequest のバリデーションルールとメッセージを使う
    $request = App::make(RegisterRequest::class);
    $request->merge($input);
    $request->validateResolved();  // フォームリクエストのバリデーションを強制実行

    return User::create([
        'name' => $input['name'],
        'email' => $input['email'],
        'password' => Hash::make($input['password']),
    ]);

    $user->sendEmailVerificationNotification(); //会員登録後に自動で認証メールが送られる

    return $user;
    }
}
