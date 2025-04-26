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
    $request = App::make(RegisterRequest::class);
    $request->merge($input);
    $request->validateResolved();

    $nameParts = preg_split('/\s+/u', trim($input['name']));
    $lastName = $nameParts[0] ?? '';
    $firstName = $nameParts[1] ?? '';

    \Log::info('【名前分割結果】', [
        '入力値' => $input['name'],
        'last_name' => $lastName,
        'first_name' => $firstName,
    ]);

    $user = User::create([
        'last_name' => $lastName,
        'first_name' => $firstName,
        'email' => $input['email'],
        'password' => Hash::make($input['password']),
    ]);

    $user->sendEmailVerificationNotification();

    return $user;
}
}
