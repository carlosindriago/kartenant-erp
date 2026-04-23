<?php

namespace App\Services;

use Illuminate\Support\Facades\Session;

class SimpleCaptchaService
{
    private const SESSION_KEY = 'simple_captcha_answer';

    private const SESSION_QUESTION = 'simple_captcha_question';

    /**
     * Generate a simple math captcha
     */
    public function generate(): array
    {
        $num1 = rand(1, 10);
        $num2 = rand(1, 10);
        $operator = rand(0, 1) ? '+' : '-';

        if ($operator === '+') {
            $answer = $num1 + $num2;
            $question = "{$num1} + {$num2}";
        } else {
            // Ensure positive result
            if ($num1 < $num2) {
                [$num1, $num2] = [$num2, $num1];
            }
            $answer = $num1 - $num2;
            $question = "{$num1} - {$num2}";
        }

        Session::put(self::SESSION_KEY, $answer);
        Session::put(self::SESSION_QUESTION, $question);

        return [
            'question' => $question,
            'answer' => $answer, // Don't send to client!
        ];
    }

    /**
     * Validate captcha answer
     */
    public function validate(?string $userAnswer): bool
    {
        $correctAnswer = Session::get(self::SESSION_KEY);

        if ($correctAnswer === null) {
            return false;
        }

        $result = ((int) $userAnswer) === ((int) $correctAnswer);

        // Clear session after validation
        Session::forget([self::SESSION_KEY, self::SESSION_QUESTION]);

        return $result;
    }

    /**
     * Get current question (for display)
     */
    public function getCurrentQuestion(): ?string
    {
        return Session::get(self::SESSION_QUESTION);
    }
}
