<?php
declare(strict_types=1);

/** Small rule-based validator. Rules: required, email, max:N, min:N, phone, url, in:a,b,c, accepted */
final class Validator
{
    private array $errors = [];
    private array $clean = [];
    private array $allowed = [];

    public function __construct(private array $input)
    {
    }

    public function check(string $field, string $label, string $rules): self
    {
        $raw = $this->input[$field] ?? '';
        $value = is_string($raw) ? trim(str_replace("\0", '', $raw)) : '';
        $value = preg_replace('/[\x{0001}-\x{0008}\x{000B}\x{000C}\x{000E}-\x{001F}\x{007F}]/u', '', $value) ?? '';

        foreach (explode('|', $rules) as $rule) {
            [$name, $arg] = array_pad(explode(':', $rule, 2), 2, null);
            $error = match ($name) {
                'required' => $value === '' ? "$label is required." : null,
                'accepted' => !in_array($value, ['1', 'on', 'yes'], true) ? "Please accept $label." : null,
                'email' => $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL) ? "Please enter a valid email address." : null,
                'max' => mb_strlen($value) > (int) $arg ? "$label must be $arg characters or fewer." : null,
                'min' => $value !== '' && mb_strlen($value) < (int) $arg ? "$label must be at least $arg characters." : null,
                'phone' => $value !== '' && !preg_match('/^[0-9 +().\-]{6,25}$/', $value) ? "Please enter a valid phone number." : null,
                'url' => $value !== '' && (!filter_var($value, FILTER_VALIDATE_URL) || !preg_match('#^https?://#i', $value)) ? "Please enter a full link starting with https://." : null,
                'in' => $value !== '' && !in_array($value, explode(',', (string) $arg), true) ? "Please choose a valid $label." : null,
                'oneof' => $value !== '' && !in_array($value, $this->allowed[$field] ?? [], true) ? "Please choose a valid option for $label." : null,
                'nolinks' => preg_match_all('#https?://#i', $value) > 2 ? "Please remove some links from $label." : null,
                default => null,
            };
            if ($error !== null) {
                $this->errors[$field] = $error;
                break;
            }
        }
        $this->clean[$field] = $value;
        return $this;
    }

    /** Allowed options for "oneof" rules, supplied by code. */
    public function options(string $field, array $options): self
    {
        $this->allowed[$field] = $options;
        return $this;
    }

    public function fails(): bool
    {
        return $this->errors !== [];
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function clean(): array
    {
        return $this->clean;
    }
}
