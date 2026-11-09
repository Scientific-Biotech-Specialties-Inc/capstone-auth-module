export function getCurrentRoleName(user: unknown): string | null {
  if (!user || typeof user !== 'object') {
    return null
  }

  const record = user as Record<string, unknown>
  let roleName: unknown = record.role

  const profile = record.profile
  if (profile && typeof profile === 'object') {
    const profileRole = (profile as Record<string, unknown>).role
    if (profileRole && typeof profileRole === 'object') {
      const profileRoleName = (profileRole as Record<string, unknown>).name
      if (typeof profileRoleName === 'string') {
        roleName = profileRoleName
      }
    }
  }

  if (typeof roleName !== 'string') {
    return null
  }

  const normalized = roleName.trim().toLowerCase()
  return normalized.length > 0 ? normalized : null
}

export function isCurrentRole(user: unknown, roleName: string): boolean {
  return getCurrentRoleName(user) === roleName.toLowerCase()
}

export function isItAdmin(user: unknown): boolean {
  return isCurrentRole(user, 'it admin')
}
