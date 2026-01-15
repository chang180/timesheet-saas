/**
 * API client helper for making authenticated requests with CSRF token support
 */

function getCookie(name: string): string | null {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) {
        return parts.pop()?.split(';').shift() || null;
    }
    return null;
}

function getCsrfToken(): string | null {
    return getCookie('XSRF-TOKEN');
}

export interface ApiRequestOptions extends RequestInit {
    skipCsrf?: boolean;
}

/**
 * Make an authenticated API request with CSRF token
 */
export async function apiRequest(
    url: string,
    options: ApiRequestOptions = {},
): Promise<Response> {
    const { skipCsrf = false, headers = {}, ...restOptions } = options;

    const requestHeaders: Record<string, string> = {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        ...(headers as Record<string, string>),
    };

    if (!skipCsrf) {
        const csrfToken = getCsrfToken();
        if (csrfToken) {
            requestHeaders['X-XSRF-TOKEN'] = decodeURIComponent(csrfToken);
        }
    }

    return fetch(url, {
        ...restOptions,
        headers: requestHeaders,
        credentials: 'include',
    });
}

/**
 * Ensure CSRF cookie is set before making API requests
 */
export async function ensureCsrfCookie(): Promise<void> {
    const csrfToken = getCsrfToken();
    if (!csrfToken) {
        await fetch('/sanctum/csrf-cookie', {
            method: 'GET',
            credentials: 'include',
        });
    }
}
