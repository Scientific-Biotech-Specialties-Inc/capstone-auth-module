import { describe, it, expect } from 'vitest'
import { getCurrentRoleName, isCurrentRole, isItAdmin } from '../role'

describe('role utility', () => {
  it('returns null for non-object user values', () => {
    expect(getCurrentRoleName(null)).toBeNull()
    expect(getCurrentRoleName(undefined)).toBeNull()
    expect(getCurrentRoleName('string')).toBeNull()
  })

  it('resolves direct string role on user object', () => {
    const user = { role: 'Admin' }
    expect(getCurrentRoleName(user)).toBe('admin')
    expect(isCurrentRole(user, 'Admin')).toBe(true)
  })

  it('resolves nested profile role name', () => {
    const user = {
      role: null,
      profile: {
        role: {
          name: 'IT Admin',
        },
      },
    }
    expect(getCurrentRoleName(user)).toBe('it admin')
    expect(isItAdmin(user)).toBe(true)
  })

  it('correctly identifies non-IT Admin users', () => {
    const user = { role: 'Employee' }
    expect(isItAdmin(user)).toBe(false)
  })
})
