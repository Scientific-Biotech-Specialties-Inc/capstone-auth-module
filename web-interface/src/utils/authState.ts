export function hasAuthStatusCookie(): boolean {
  return document.cookie
    .split(';')
    .map((cookie) => cookie.trim())
    .some((cookie) => {
      if (!cookie.startsWith('is_authenticated=')) return false
      const val = cookie.substring('is_authenticated='.length).trim()
      return val.length > 0 && val !== 'false' && val !== '0'
    })
}

export function hasLocalUser(): boolean {
  const raw = localStorage.getItem('user')
  if (!raw || raw === 'undefined' || raw === 'null') return false
  try {
    JSON.parse(raw)
    return true
  } catch {
    return false
  }
}

/**
 * Determines whether the client believes a live session exists.
 *
 * The `is_authenticated` cookie is a NON-HttpOnly *status* marker that is NOT
 * tied to the real HttpOnly server session tokens (`access_token`,
 * `session_id`, `refresh_token`). JavaScript cannot read those HttpOnly tokens,
 * so the authoritative client-side signal of a live session is
 * `localStorage['user']`, which is written only on a successful login/refresh.
 *
 * Trusting the marker cookie caused a redirect loop: when the real session was
 * deleted server-side but the marker cookie survived, the client still believed
 * it was authenticated, was redirected to a protected route, failed the real
 * session check, and was bounced back — looping indefinitely.
 */
export function isAuthenticatedClientSide(): boolean {
  return hasLocalUser()
}

export function clearClientAuthState(): void {
  localStorage.removeItem('user')
  document.cookie = 'is_authenticated=; Max-Age=0; path=/; SameSite=Strict'
}
