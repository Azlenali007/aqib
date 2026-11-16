<?php
declare(strict_types=1);

class Validator
{
    private array $errors = [];

    public function validate(array $data, array $rules): bool
    {
        $this->errors = [];
        foreach ($rules as $field => $ruleStr) {
            $ruleList = is_array($ruleStr) ? $ruleStr : explode('|', $ruleStr);
            $val = $data[$field] ?? null;
            $label = ucfirst(str_replace('_', ' ', $field));

            foreach ($ruleList as $rule) {
                $parts = explode(':', $rule, 2);
                $name = $parts[0];
                $param = $parts[1] ?? null;

                if ($name === 'required' && ($val === null || trim((string) $val) === '')) {
                    $this->errors[$field] = "$label is required.";
                    break;
                }
                if ($val === null || trim((string) $val) === '') {
                    continue;
                }
                $str = trim((string) $val);
                if ($name === 'email' && !filter_var($str, FILTER_VALIDATE_EMAIL)) {
                    $this->errors[$field] = "$label must be a valid email address.";
                } elseif ($name === 'url' && !filter_var($str, FILTER_VALIDATE_URL)) {
                    $this->errors[$field] = "$label must be a valid URL.";
                } elseif ($name === 'min' && mb_strlen($str) < (int) $param) {
                    $this->errors[$field] = "$label must be at least $param characters.";
                } elseif ($name === 'max' && mb_strlen($str) > (int) $param) {
                    $this->errors[$field] = "$label must not exceed $param characters.";
                } elseif ($name === 'numeric' && !is_numeric($str)) {
                    $this->errors[$field] = "$label must be numeric.";
                } elseif ($name === 'int' && filter_var($str, FILTER_VALIDATE_INT) === false) {
                    $this->errors[$field] = "$label must be a whole number.";
                } elseif ($name === 'positive' && (float) $str <= 0) {
                    $this->errors[$field] = "$label must be greater than zero.";
                } elseif ($name === 'alnum_dash' && !preg_match('/^[a-zA-Z0-9_-]+$/', $str)) {
                    $this->errors[$field] = "$label may only contain letters, numbers, dashes, and underscores.";
                } elseif ($name === 'in') {
                    $allowed = explode(',', (string) $param);
                    if (!in_array($str, $allowed, true)) {
                        $this->errors[$field] = "$label contains an invalid selection.";
                    }
                }
            }
        }
        return empty($this->errors);
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function firstError(): ?string
    {
        return $this->errors ? reset($this->errors) : null;
    }
}
