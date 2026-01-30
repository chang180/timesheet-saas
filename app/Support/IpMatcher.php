<?php

namespace App\Support;

class IpMatcher
{
    /**
     * Check if an IP address matches any of the given rules.
     *
     * @param  string  $ip  The IP address to check
     * @param  array<int, string>  $rules  List of IPs or CIDR notations
     */
    public static function matches(string $ip, array $rules): bool
    {
        if (empty($rules)) {
            return true;
        }

        foreach ($rules as $rule) {
            if (self::matchesRule($ip, $rule)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if an IP matches a single rule (IP or CIDR).
     */
    public static function matchesRule(string $ip, string $rule): bool
    {
        $rule = trim($rule);

        if ($rule === '') {
            return false;
        }

        if (str_contains($rule, '/')) {
            return self::matchesCidr($ip, $rule);
        }

        return $ip === $rule;
    }

    /**
     * Check if an IP address is within a CIDR range.
     */
    public static function matchesCidr(string $ip, string $cidr): bool
    {
        $parts = explode('/', $cidr);

        if (count($parts) !== 2) {
            return false;
        }

        [$subnet, $mask] = $parts;

        if (! is_numeric($mask)) {
            return false;
        }

        $mask = (int) $mask;

        if (filter_var($subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return self::matchesIpv4Cidr($ip, $subnet, $mask);
        }

        if (filter_var($subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return self::matchesIpv6Cidr($ip, $subnet, $mask);
        }

        return false;
    }

    /**
     * Check if an IPv4 address is within a CIDR range.
     */
    private static function matchesIpv4Cidr(string $ip, string $subnet, int $mask): bool
    {
        if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return false;
        }

        if ($mask < 0 || $mask > 32) {
            return false;
        }

        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);

        if ($ipLong === false || $subnetLong === false) {
            return false;
        }

        $netmask = $mask === 0 ? 0 : (~0 << (32 - $mask));

        return ($ipLong & $netmask) === ($subnetLong & $netmask);
    }

    /**
     * Check if an IPv6 address is within a CIDR range.
     */
    private static function matchesIpv6Cidr(string $ip, string $subnet, int $mask): bool
    {
        if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return false;
        }

        if ($mask < 0 || $mask > 128) {
            return false;
        }

        $ipBin = inet_pton($ip);
        $subnetBin = inet_pton($subnet);

        if ($ipBin === false || $subnetBin === false) {
            return false;
        }

        $ipHex = bin2hex($ipBin);
        $subnetHex = bin2hex($subnetBin);

        $ipBits = self::hexToBits($ipHex);
        $subnetBits = self::hexToBits($subnetHex);

        return substr($ipBits, 0, $mask) === substr($subnetBits, 0, $mask);
    }

    /**
     * Convert hex string to binary string representation.
     */
    private static function hexToBits(string $hex): string
    {
        $bits = '';
        for ($i = 0; $i < strlen($hex); $i++) {
            $bits .= str_pad(base_convert($hex[$i], 16, 2), 4, '0', STR_PAD_LEFT);
        }

        return $bits;
    }
}
