<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class PasswordValidation implements Rule
{
    protected $level;

    public function __construct($level = 'strong')
    {
        $this->level = $level;
    }

    public function passes($attribute, $value)
    {
        $length = strlen($value);

        //≥ 6 characters
        if ($this->level === 'weak') {
            return $length >= 6;
        }

        //≥ 8 chars + letters + numbers
        if ($this->level === 'medium') {
            return $length >= 8 &&
                preg_match('/[A-Za-z]/', $value) &&
                preg_match('/\d/', $value);
        }

        //≥ 10 chars + upper + lower + number + symbol
        if ($this->level === 'strong') {
            return $length >= 10 &&
                preg_match('/[A-Z]/', $value) &&
                preg_match('/[a-z]/', $value) &&
                preg_match('/\d/', $value) &&
                preg_match('/[\W_]/', $value);
        }

        return false;
    }

    public function message()
    {
        return "The :attribute does not meet {$this->level} password requirements.";
    }
    
}
