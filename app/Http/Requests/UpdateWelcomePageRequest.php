<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWelcomePageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->user()->company);
    }

    public function rules(): array
    {
        return [
            'hero' => ['nullable', 'array'],
            'hero.enabled' => ['boolean'],
            'hero.title' => ['required_if:hero.enabled,true', 'string', 'max:255'],
            'hero.subtitle' => ['nullable', 'string', 'max:500'],
            'hero.backgroundImage' => ['nullable', 'url'],
            'hero.videoUrl' => ['nullable', 'url'],

            'quickStartSteps' => ['nullable', 'array'],
            'quickStartSteps.enabled' => ['boolean'],
            'quickStartSteps.steps' => ['required_if:quickStartSteps.enabled,true', 'array', 'max:5'],
            'quickStartSteps.steps.*.title' => ['required', 'string', 'max:255'],
            'quickStartSteps.steps.*.description' => ['required', 'string', 'max:500'],

            'announcements' => ['nullable', 'array'],
            'announcements.enabled' => ['boolean'],
            'announcements.items' => ['array'],
            'announcements.items.*.title' => ['required', 'string', 'max:255'],
            'announcements.items.*.content' => ['required', 'string'],
            'announcements.items.*.publishedAt' => ['nullable', 'date'],

            'supportContacts' => ['nullable', 'array'],
            'supportContacts.enabled' => ['boolean'],
            'supportContacts.contacts' => ['array'],
            'supportContacts.contacts.*.name' => ['required', 'string', 'max:255'],
            'supportContacts.contacts.*.email' => ['nullable', 'email'],
            'supportContacts.contacts.*.phone' => ['nullable', 'string', 'max:50'],

            'ctas' => ['nullable', 'array', 'max:3'],
            'ctas.*.text' => ['required', 'string', 'max:100'],
            'ctas.*.url' => ['required', 'string', 'max:500'],
            'ctas.*.variant' => ['nullable', 'in:primary,secondary'],
        ];
    }

    public function messages(): array
    {
        return [
            'hero.title.required_if' => 'Hero 標題為必填',
            'quickStartSteps.steps.max' => '快速開始步驟最多 5 項',
            'ctas.max' => 'CTA 按鈕最多 3 個',
        ];
    }
}
