<?php

namespace App\Concerns;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait ProfileValidationRules
{
    /**
     * Get the validation rules used to validate user profiles.
     *
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function profileRules(?int $userId = null): array
    {
        return [
            'name' => $this->nameRules(),
            'email' => $this->emailRules($userId),
        ];
    }

    /**
     * Human-friendly validation messages for the profile fields.
     *
     * @return array<string, string>
     */
    protected function profileValidationMessages(): array
    {
        return [
            'name.regex' => 'Vul je volledige naam in (voor- en achternaam).',
            'email.ends_with' => 'Registratie is alleen mogelijk met een @valicare.nl e-mailadres.',
        ];
    }

    /**
     * Get the validation rules used to validate user names.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function nameRules(): array
    {
        return [
            'required',
            'string',
            'max:255',
            // Require a full name: at least two parts (first + last name).
            'regex:/^\p{L}[\p{L}\'\-\.]*(?:\s+\p{L}[\p{L}\'\-\.]*)+$/u',
        ];
    }

    /**
     * Get the validation rules used to validate user emails.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function emailRules(?int $userId = null): array
    {
        return [
            'required',
            'string',
            'email',
            'max:255',
            'ends_with:@valicare.nl',
            $userId === null
                ? Rule::unique(User::class)
                : Rule::unique(User::class)->ignore($userId),
        ];
    }
}
