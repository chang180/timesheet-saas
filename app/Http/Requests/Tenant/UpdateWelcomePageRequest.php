<?php

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWelcomePageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->isCompanyAdmin() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'headline' => ['required', 'string', 'max:120'],
            'subheadline' => ['nullable', 'string', 'max:200'],
            'cta.primary.label' => ['required', 'string', 'max:60'],
            'cta.primary.href' => ['required', 'url'],
            'cta.secondary.label' => ['nullable', 'string', 'max:60'],
            'cta.secondary.href' => ['nullable', 'url'],
            'highlights' => ['nullable', 'array', 'max:5'],
            'highlights.*' => ['string', 'max:120'],
            'steps' => ['nullable', 'array', 'max:5'],
            'steps.*.title' => ['required', 'string', 'max:60'],
            'steps.*.description' => ['nullable', 'string', 'max:180'],
            'media.type' => ['nullable', 'in:default,video,custom'],
            'media.videoUrl' => ['nullable', 'url', 'required_if:media.type,video'],
            'media.embedCode' => ['nullable', 'string', 'required_if:media.type,custom'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function welcomePagePayload(): array
    {
        $data = $this->validated();

        return [
            'headline' => $data['headline'],
            'subheadline' => $data['subheadline'] ?? null,
            'cta' => [
                'primary' => $data['cta']['primary'],
                'secondary' => $data['cta']['secondary'] ?? null,
            ],
            'highlights' => $data['highlights'] ?? [],
            'steps' => $data['steps'] ?? [],
            'media' => [
                'type' => $data['media']['type'] ?? 'default',
                'videoUrl' => $data['media']['videoUrl'] ?? null,
                'embedCode' => $data['media']['embedCode'] ?? null,
            ],
        ];
    }
}
