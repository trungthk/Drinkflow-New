<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SanitizeInputStrings
{
    /**
     * Field names that should be excluded from HTML stripping / sanitization (passwords, tokens, encrypted payloads).
     *
     * @var array<int, string>
     */
    protected array $except = [
        'password',
        'password_confirmation',
        'current_password',
        'token',
        'remember_token',
        'config_encrypted',
        'secret',
        'private_key',
    ];

    /**
     * Handle an incoming request and sanitize input string values.
     *
     * @param Request $request Current HTTP request instance.
     * @param Closure(Request): (Response) $next Next middleware closure.
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $input = $request->all();

        if (! empty($input)) {
            $cleaned = $this->cleanArray($input);
            $request->merge($cleaned);
        }

        return $next($request);
    }

    /**
     * Recursively clean array elements.
     *
     * @param array<string, mixed> $data Array of inputs.
     * @param string $keyPrefix Current key path prefix.
     * @return array<string, mixed> Cleaned array.
     */
    protected function cleanArray(array $data, string $keyPrefix = ''): array
    {
        foreach ($data as $key => $value) {
            $currentKey = $keyPrefix !== '' ? "{$keyPrefix}.{$key}" : (string) $key;

            if ($this->isExcluded($key, $currentKey)) {
                continue;
            }

            if (is_array($value)) {
                $data[$key] = $this->cleanArray($value, $currentKey);
            } elseif (is_string($value)) {
                $data[$key] = $this->cleanString($value);
            }
        }

        return $data;
    }

    /**
     * Strip the most common script-injection constructs from free text.
     *
     * This is defence in depth only: it is a deny-list and cannot make arbitrary text safe for HTML.
     * Every place that renders user text must still encode its output (Blade `{{ }}` / escapeHtml in JS).
     * The rules are applied repeatedly so nested payloads such as `<scr<script></script>ipt>` cannot re-form.
     *
     * @param string $value Input string.
     * @return string Sanitized string.
     */
    public function cleanString(string $value): string
    {
        // 1. Remove null bytes and non-printable control characters (except newline, tab, carriage return)
        $cleaned = str_replace(chr(0), '', $value);
        $cleaned = (string) preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $cleaned);

        for ($pass = 0; $pass < 5; $pass++) {
            $before = $cleaned;

            // 2. Remove <script> tags and their contents
            $cleaned = (string) preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $cleaned);

            // 3. Remove standalone <script> and dangerous tags
            $cleaned = (string) preg_replace('/<\/?(script|iframe|object|embed|applet|meta|link|style)\b[^>]*>/is', '', $cleaned);

            // 4. Remove javascript: / vbscript: URIs and data: URIs that carry active content
            $cleaned = (string) preg_replace('/(javascript|vbscript):[^\s"\']+/is', '', $cleaned);
            $cleaned = (string) preg_replace('/data:\s*(text\/html|application\/|image\/svg)[^\s"\']*/is', '', $cleaned);

            // 5. Remove inline event handler attributes (onerror=, onload=, ...) that sit inside an HTML tag
            $cleaned = (string) preg_replace('/(<[a-z][^>]*?[\s\/"\'])on[a-z]+\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>]*)/i', '$1', $cleaned);

            if ($cleaned === $before) {
                break;
            }
        }

        return $cleaned;
    }

    /**
     * Check if the field is excluded from sanitization.
     *
     * @param string|int $key Field key.
     * @param string $fullKey Full dotted key path.
     * @return bool True if excluded.
     */
    protected function isExcluded(string|int $key, string $fullKey): bool
    {
        $keyStr = (string) $key;

        foreach ($this->except as $exceptKey) {
            if ($keyStr === $exceptKey || str_ends_with($fullKey, ".{$exceptKey}")) {
                return true;
            }
        }

        return false;
    }
}
