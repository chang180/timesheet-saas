<?php

namespace App\Http\Requests;

use App\Support\ReservedHandles;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserHandleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'handle' => [
                'required', 'string', 'min:3', 'max:30',
                'regex:/^[a-z0-9_-]+$/',
                Rule::unique('users', 'handle')->ignore($this->user()->id),
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (ReservedHandles::isReserved((string) $value)) {
                        $fail('此代號已被系統保留，請選擇其他代號。');
                    }
                },
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'handle.regex' => '代號只能包含小寫英數、底線（_）與連字號（-），3–30 字。',
            'handle.unique' => '此代號已被其他用戶使用。',
            'handle.min' => '代號至少 3 字。',
            'handle.max' => '代號最多 30 字。',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('handle')) {
            $this->merge([
                'handle' => strtolower(trim((string) $this->input('handle'))),
            ]);
        }
    }
}
