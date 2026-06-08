<?php

namespace App\Rules;

use App\Services\StreamingPolicy;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\ValidationException;

class AllowedStreamingUrl implements ValidationRule
{
    public function __construct(
        private readonly bool $playlist = false,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        try {
            $policy = app(StreamingPolicy::class);

            $this->playlist
                ? $policy->assertPlaylistUrlAllowed((string) $value)
                : $policy->assertStreamUrlAllowed((string) $value);
        } catch (ValidationException) {
            $fail('The :attribute host is not approved for legal streaming.');
        }
    }
}
