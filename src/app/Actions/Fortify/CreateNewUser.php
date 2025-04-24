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

    // name を姓と名に分割
    $nameParts = preg_split('/\s+/u', trim($input['name']));
    $lastName = $nameParts[0] ?? '';
    $firstName = $nameParts[1] ?? '';

    // ログ出力（デバッグ用）
    \Log::info('【名前分割結果】', [
        '入力値' => $input['name'],
        'last_name' => $lastName,
        'first_name' => $firstName,
    ]);

    // 登録処理
    $user = User::create([
        'last_name' => $lastName,
        'first_name' => $firstName,
        'email' => $input['email'],
        'password' => Hash::make($input['password']),
    ]);

    $user->sendEmailVerificationNotification(); // 認証メール送信

    return $user;
}
}
