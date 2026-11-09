function collectAllowedOrigins(): string[] {
  const envValues = [
    import.meta.env.VITE_SERMS_URL,
    import.meta.env.VITE_CMS_URL,
    import.meta.env.VITE_PRS_URL,
  ]

  const origins: string[] = []
  for (const value of envValues) {
    if (typeof value !== 'string' || value.trim() === '') continue
    try {
      const parsed = new URL(value)
      if (parsed.protocol !== 'http:' && parsed.protocol !== 'https:') continue
      origins.push(parsed.origin)
    } catch {
      // ignore malformed env values
    }
  }
  return origins
}

const allowedOrigins = collectAllowedOrigins()

export function isAllowedRedirectUrl(url: string): boolean {
  if (!url) return false

  // Backslashes are normalized to slashes by browsers and can disguise
  // protocol-relative or cross-origin redirects.
  if (url.includes('\\')) return false

  // Protocol-relative URLs (//evil.com) are never allowed.
  if (url.startsWith('//')) return false

  const lower = url.toLowerCase()
  if (
    lower.startsWith('javascript:') ||
    lower.startsWith('data:') ||
    lower.startsWith('vbscript:') ||
    lower.startsWith('file:')
  ) {
    return false
  }

  // Same-origin relative paths.
  if (url.startsWith('/')) return true

  let parsed: URL
  try {
    parsed = new URL(url)
  } catch {
    return false
  }

  // Reject URLs carrying credentials/userinfo.
  if (parsed.username || parsed.password) return false

  return (
    parsed.origin === window.location.origin ||
    allowedOrigins.includes(parsed.origin)
  )
}
