<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class PasswordValidation implements Rule
{
    protected $level;
    protected $errorMessage = '';

    public function __construct($level = 'strong')
    {
        $this->level = $level;
    }

    public function passes($attribute, $value)
    {
        $length = strlen($value);

        if ($this->level === 'weak') {
            if ($length < 6) {
                $this->errorMessage = 'Password must be at least 6 characters long.';
                return false;
            }
            return true;
        }

        if ($this->level === 'medium') {
            if ($length < 8) {
                $this->errorMessage = 'Password must be at least 8 characters long.';
                return false;
            }

            if (!preg_match('/[A-Za-z]/', $value)) {
                $this->errorMessage = 'Password must contain at least one letter.';
                return false;
            }

            if (!preg_match('/\d/', $value)) {
                $this->errorMessage = 'Password must contain at least one number.';
                return false;
            }

            return true;
        }

        // STRONG
        if ($this->level === 'strong') {
            if ($length < 10) {
                $this->errorMessage = 'Password must be at least 10 characters long.';
                return false;
            }

            if (!preg_match('/[A-Z]/', $value)) {
                $this->errorMessage = 'Password must contain at least one uppercase letter.';
                return false;
            }

            if (!preg_match('/[a-z]/', $value)) {
                $this->errorMessage = 'Password must contain at least one lowercase letter.';
                return false;
            }

            if (!preg_match('/\d/', $value)) {
                $this->errorMessage = 'Password must contain at least one number.';
                return false;
            }

            if (!preg_match('/[\W_]/', $value)) {
                $this->errorMessage = 'Password must contain at least one special character.';
                return false;
            }

            return true;
        }

        return false;
    }

    public function message()
    {
        return $this->errorMessage ?: 'Invalid password.';
    }
}
