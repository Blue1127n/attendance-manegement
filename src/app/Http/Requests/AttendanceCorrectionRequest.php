<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Illuminate\Support\Carbon;

class AttendanceCorrectionRequest extends FormRequest
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
            'breaks.*.start' => ['nullable'],
            'breaks.*.end' => ['nullable'],
            'remarks' => ['required', 'string'],
        ];
    }

    public function messages()
    {
        return [
            'remarks.required' => '備考を記入してください',
        ];
    }

    public function withValidator(Validator $validator)
{
    $validator->after(function ($validator) {
        $clockIn = $this->input('clock_in');
        $clockOut = $this->input('clock_out');

        // どちらかが未入力、または順序がおかしい場合も含めてまとめてバリデーション
        if (!$clockIn || !$clockOut || $clockIn >= $clockOut) {
            $validator->errors()->add('clock_in', '出勤時間もしくは退勤時間が不適切な値です');
        }

        foreach ($this->input('breaks', []) as $index => $break) {
            $start = $break['start'] ?? null;
            $end = $break['end'] ?? null;

            if ($start && $end) {
                if (($clockIn && $start < $clockIn) || ($clockOut && $end > $clockOut)) {
                    $validator->errors()->add("breaks.$index", '休憩時間が勤務時間外です');
                }
            }
        }
    });
}
}
