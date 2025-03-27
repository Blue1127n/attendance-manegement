<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => [
            'required',
            'string',
            'max:255',
            function ($attribute, $value, $fail) {
                // 半角・全角スペースのどちらかが含まれているかチェック
                if (count(preg_split('/\s+/u', trim($value))) < 2) { //preg_split('/\s+/u', trim($value))スペース（全角/半角）で分割
                    $fail('姓と名の間にスペースを入れて入力してください');
                }
            },
        ],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'お名前を入力してください',
            'email.required' => 'メールアドレスを入力してください',
            'email.email' => 'メールアドレスの形式が正しくありません',
            'email.unique' => 'このメールアドレスは既に使用されています',
            'password.required' => 'パスワードを入力してください',
            'password.min' => 'パスワードは8文字以上で入力してください',
            'password.confirmed' => 'パスワードと一致しません',
        ];
    }
}
