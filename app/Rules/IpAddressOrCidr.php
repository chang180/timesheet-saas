<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class IpAddressOrCidr implements ValidationRule
{
    /**
     * Validate the attribute.
     *
     * @param  Closure(string): void  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $value === '') {
            $fail(__('The :attribute must be a valid IP address or CIDR range.'));

            return;
        }

        if ($this->isIpAddress($value) || $this->isCidr($value)) {
            return;
        }

        $fail(__('The :attribute must be a valid IP address or CIDR range.'));
    }

    private function isIpAddress(string $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6) !== false;
    }

    private function isCidr(string $value): bool
    {
        if (! str_contains($value, '/')) {
            return false;
        }

        [$ip, $prefix] = explode('/', $value, 2);

        if ($ip === '' || $prefix === '') {
            return false;
        }

        $ipVersion = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6);

        if ($ipVersion === false) {
            return false;
        }

        $prefixLength = filter_var($prefix, FILTER_VALIDATE_INT);

        if ($prefixLength === false) {
            return false;
        }

        $maxPrefix = str_contains($ip, ':') ? 128 : 32;

        return $prefixLength >= 0 && $prefixLength <= $maxPrefix;
    }
}
