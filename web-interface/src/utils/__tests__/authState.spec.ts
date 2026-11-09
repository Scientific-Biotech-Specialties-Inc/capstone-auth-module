import { describe, it, expect, beforeEach } from 'vitest'
import {
  hasAuthStatusCookie,
  hasLocalUser,
  isAuthenticatedClientSide,
  clearClientAuthState,
} from '../authState'

describe('authState utility', () => {
  beforeEach(() => {
    localStorage.clear()
    document.cookie = 'is_authenticated=; Max-Age=0; path=/'
  })

  describe('hasAuthStatusCookie', () => {
    it('returns false when no cookie is set', () => {
      expect(hasAuthStatusCookie()).toBe(false)
    })

    it('returns true when is_authenticated cookie is present', () => {
      document.cookie = 'is_authenticated=1; path=/'
      expect(hasAuthStatusCookie()).toBe(true)
    })
  })

  describe('hasLocalUser', () => {
    it('returns false when localStorage does not have user', () => {
      expect(hasLocalUser()).toBe(false)
    })

    it('returns false when user is string "undefined" or "null"', () => {
      localStorage.setItem('user', 'undefined')
      expect(hasLocalUser()).toBe(false)

      localStorage.setItem('user', 'null')
      expect(hasLocalUser()).toBe(false)
    })

    it('returns false when user JSON is invalid', () => {
      localStorage.setItem('user', '{invalid_json')
      expect(hasLocalUser()).toBe(false)
    })

    it('returns true when valid user JSON is stored', () => {
      localStorage.setItem('user', JSON.stringify({ id: 1, name: 'Admin' }))
      expect(hasLocalUser()).toBe(true)
    })
  })

  describe('isAuthenticatedClientSide', () => {
    it('relies on local user storage rather than marker cookie', () => {
      document.cookie = 'is_authenticated=1; path=/'
      expect(isAuthenticatedClientSide()).toBe(false)

      localStorage.setItem('user', JSON.stringify({ id: 1 }))
      expect(isAuthenticatedClientSide()).toBe(true)
    })
  })

  describe('clearClientAuthState', () => {
    it('removes user from localStorage and resets cookie', () => {
      localStorage.setItem('user', JSON.stringify({ id: 1 }))
      document.cookie = 'is_authenticated=1; path=/'

      clearClientAuthState()

      expect(localStorage.getItem('user')).toBeNull()
      expect(hasLocalUser()).toBe(false)
    })
  })
})
