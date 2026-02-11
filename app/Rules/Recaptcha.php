<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;

class Recaptcha implements ValidationRule
{
    protected ?string $action;
    protected float $minScore;

    public function __construct(?string $action = null, float $minScore = 0.5)
    {
        $this->action = $action;
        $this->minScore = $minScore;
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
            'secret' => config('services.recaptcha.secret'),
            'response' => $value,
        ]);

        $data = $response->json();

        if (!$data['success']) {
            $fail('The google recaptcha verification failed. Please try again.');
            return;
        }

        // Check Score
        if (isset($data['score']) && $data['score'] < $this->minScore) {
            $fail('Recaptcha validation failed (low score).');
            return;
        }

        // Check Action if provided
        if ($this->action && isset($data['action']) && $data['action'] !== $this->action) {
            $fail('Recaptcha validation failed (invalid action).');
        }
    }
}
