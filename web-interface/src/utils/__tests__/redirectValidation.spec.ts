import { describe, it, expect } from 'vitest'
import { isAllowedRedirectUrl } from '../redirectValidation'

describe('redirectValidation utility', () => {
  it('rejects empty or falsy urls', () => {
    expect(isAllowedRedirectUrl('')).toBe(false)
    expect(isAllowedRedirectUrl(null as unknown as string)).toBe(false)
  })

  it('rejects urls containing backslashes to prevent bypass', () => {
    expect(isAllowedRedirectUrl('https:\\\\evil.com')).toBe(false)
    expect(isAllowedRedirectUrl('/dashboard\\evil')).toBe(false)
  })

  it('rejects protocol-relative urls', () => {
    expect(isAllowedRedirectUrl('//evil.com/path')).toBe(false)
  })

  it('rejects dangerous protocols (javascript, data, vbscript, file)', () => {
    expect(isAllowedRedirectUrl('javascript:alert(1)')).toBe(false)
    expect(isAllowedRedirectUrl('data:text/html,<html>')).toBe(false)
    expect(isAllowedRedirectUrl('vbscript:msgbox(1)')).toBe(false)
    expect(isAllowedRedirectUrl('file:///etc/passwd')).toBe(false)
  })

  it('accepts same-origin relative paths', () => {
    expect(isAllowedRedirectUrl('/dashboard')).toBe(true)
    expect(isAllowedRedirectUrl('/admin/users?page=1')).toBe(true)
  })

  it('rejects urls containing user credentials', () => {
    expect(isAllowedRedirectUrl('https://user:pass@localhost:5000/')).toBe(false)
  })
})
