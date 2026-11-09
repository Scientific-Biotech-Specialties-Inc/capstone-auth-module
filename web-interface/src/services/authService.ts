// ─── Auth Service ────────────────────────────────────────────────────────────
// Thin HTTP wrappers for authentication-related endpoints.
// No reactive state — that responsibility belongs to the useAuth composable.

import api from '@/lib/api'
import type { LoginCredentials, ResetPasswordPayload } from '@/types'
import { fetchEncryptionKey, encryptPayload } from '@/utils/rsa'

export const authService = {
  async login(credentials: LoginCredentials) {
    const { public_key, key_id } = await fetchEncryptionKey()
    
    const encryptedCredentials = {
      ...credentials,
      password: encryptPayload(credentials.password, public_key)
    }

    return api.post('/api/login', encryptedCredentials, {
      headers: {
        'X-Key-Id': key_id
      }
    })
  },

  logout() {
    return api.post('/api/logout')
  },

  fetchMe() {
    return api.get('/api/user')
  },

  forgotPassword(email: string) {
    return api.post('/api/forgot-password', { email })
  },

  async resetPassword(payload: ResetPasswordPayload) {
    const { public_key, key_id } = await fetchEncryptionKey()
    
    const encryptedPayload = {
      ...payload,
      password: encryptPayload(payload.password, public_key),
      password_confirmation: payload.password_confirmation 
        ? encryptPayload(payload.password_confirmation, public_key)
        : undefined
    }

    return api.post('/api/reset-password', encryptedPayload, {
      headers: {
        'X-Key-Id': key_id
      }
    })
  },

  /**
   * Verify an email address using a token.
   * Uses the shared `api` instance (not raw axios) to leverage
   * interceptors for auth headers and base URL resolution.
   */
  verifyEmail(token: string) {
    return api.get('/api/verify-email', { params: { token } })
  },

  resendVerification() {
    return api.post('/api/send-verification')
  },

  async changePassword(payload: any) {
    const { public_key, key_id } = await fetchEncryptionKey()
    
    const encryptedPayload = {
      ...payload,
      current_password: encryptPayload(payload.current_password, public_key),
      new_password: encryptPayload(payload.new_password, public_key),
      new_password_confirmation: payload.new_password_confirmation 
        ? encryptPayload(payload.new_password_confirmation, public_key)
        : undefined
    }

    return api.post('/api/me/password', encryptedPayload, {
      headers: {
        'X-Key-Id': key_id
      }
    })
  },
}
