// ─── useAuth Composable ──────────────────────────────────────────────────────
// Manages authentication state, token storage, and session lifecycle.
// Wraps authService with reactive Vue state for component consumption.

import { ref } from 'vue'
import { authService } from '@/services/authService'
import type { LoginCredentials, ResetPasswordPayload } from '@/types'
import { clearClientAuthState } from '@/utils/authState'

export interface FetchCurrentUserResult {
  success: boolean
  user: unknown
  passwordChangeRequired?: boolean
  sessionExpired?: boolean
}

export function useAuth() {
  // ── Login ──────────────────────────────────────────────────────────────────
  const loginLoading = ref(false)
  const loginErrors = ref<Record<string, string[]>>({})
  const loginGeneralError = ref('')

  const login = async (credentials: LoginCredentials) => {
    loginLoading.value = true
    loginErrors.value = {}
    loginGeneralError.value = ''

    try {
      const response = await authService.login(credentials)
      const data = response.data
      const candidate = data?.user ?? data
      const user =
        candidate && typeof candidate === 'object' && ('id' in candidate || 'email' in candidate)
          ? candidate
          : null

      if (user) {
        localStorage.setItem('user', JSON.stringify(user))
      } else {
        localStorage.removeItem('user')
      }

      return { success: true, user }
    } catch (error: any) {
      if (error.response) {
        if (error.response.status === 422) {
          loginErrors.value = error.response.data.errors || {}
          // Set a friendly general error message from the validation errors
          const validationMessage = error.response.data.errors?.email?.[0] || 
                                    error.response.data.errors?.password?.[0] || 
                                    error.response.data.message;
          loginGeneralError.value = validationMessage || 'Invalid email or password.';
        } else if (error.response.status === 429) {
          loginGeneralError.value = error.response.data.message || 'Too many attempts. Please try again later.'
        } else {
          loginGeneralError.value = error.response.data.message || 'An error occurred during login.'
        }
      } else {
        loginGeneralError.value = 'Cannot connect to the server.'
      }
      return { success: false, user: null }
    } finally {
      loginLoading.value = false
    }
  }

  // ── Logout ─────────────────────────────────────────────────────────────────
  const logout = async () => {
    try {
      await authService.logout()
    } catch {
      // Proceed with local cleanup even if the server call fails
    } finally {
      clearClientAuthState()
    }
  }

  /**
   * Clear local auth state without calling the server.
   * Used by idle-timeout handlers and route guards that need synchronous cleanup.
   */
  const clearLocalAuth = () => {
    clearClientAuthState()
  }

  // ── Fetch Current User ─────────────────────────────────────────────────────
  // Hydrates the user from the authoritative backend (/api/user).
  // Shape varies by route definition: AuthController::me wraps as
  // { user: {...} } while the closure route returns the user object
  // directly. Accept both so a shape change can't wipe local state.
  const fetchCurrentUser = async (): Promise<FetchCurrentUserResult> => {
    try {
      const response = await authService.fetchMe()
      const data = response.data
      const candidate = data?.user ?? data
      const user =
        candidate && typeof candidate === 'object' && ('id' in candidate || 'email' in candidate)
          ? candidate
          : null

      if (user) {
        localStorage.setItem('user', JSON.stringify(user))
      } else {
        localStorage.removeItem('user')
      }

      return { success: true, user }
    } catch (error: any) {
      if (error.response?.status === 403) {
        const code = error.response?.data?.code
        if (code === 'PASSWORD_CHANGE_REQUIRED') {
          // Authenticated but must change password first — do NOT clear state.
          return { success: false, user: null, passwordChangeRequired: true }
        }
      }

      if (error.response?.status === 401) {
        // The api.ts response interceptor already attempted a refresh and, on
        // failure, called clearAuthAndRedirect(). That helper invokes
        // clearClientAuthState(), which removes localStorage['user'] AND the
        // is_authenticated cookie, then redirects to '/login'. The explicit
        // clearClientAuthState() below is a defensive no-op kept for safety.
        clearClientAuthState()
        return { success: false, user: null, sessionExpired: true }
      }

      // Network or other failures — keep the current client state intact.
      return { success: false, user: null }
    }
  }

  // ── Forgot Password ───────────────────────────────────────────────────────
  const forgotLoading = ref(false)
  const forgotMessage = ref('')
  const forgotError = ref('')

  const forgotPassword = async (email: string) => {
    forgotLoading.value = true
    forgotMessage.value = ''
    forgotError.value = ''

    try {
      const response = await authService.forgotPassword(email)
      forgotMessage.value = response.data.message || 'If an account exists with that email, we have sent a reset link.'
    } catch (err: any) {
      forgotError.value = err.response?.data?.message || 'Something went wrong. Please try again.'
    } finally {
      forgotLoading.value = false
    }
  }

  // ── Reset Password ─────────────────────────────────────────────────────────
  const resetLoading = ref(false)
  const resetSuccess = ref(false)
  const resetErrors = ref<Record<string, string[]>>({})
  const resetGeneralError = ref('')

  const resetPassword = async (payload: ResetPasswordPayload) => {
    resetLoading.value = true
    resetErrors.value = {}
    resetGeneralError.value = ''

    try {
      await authService.resetPassword(payload)
      resetSuccess.value = true
    } catch (error: any) {
      if (error.response?.status === 422) {
        resetErrors.value = error.response.data.errors
      } else {
        resetGeneralError.value = error.response?.data?.message || 'Failed to reset password. The link may have expired.'
      }
    } finally {
      resetLoading.value = false
    }
  }

  // ── Email Verification ─────────────────────────────────────────────────────
  const verifyStatus = ref<'loading' | 'success' | 'expired' | 'already-verified' | 'error'>('loading')
  const verifyErrorMessage = ref('')

  const verifyEmail = async (token: string | null) => {
    if (!token) {
      verifyStatus.value = 'error'
      verifyErrorMessage.value = 'No verification token provided.'
      return
    }

    try {
      const response = await authService.verifyEmail(token)
      if (response.data.message === 'Email already verified.') {
        verifyStatus.value = 'already-verified'
      } else {
        verifyStatus.value = 'success'
      }
    } catch (error: any) {
      if (error.response?.status === 400) {
        verifyStatus.value = 'expired'
      } else {
        verifyStatus.value = 'error'
        verifyErrorMessage.value = error.response?.data?.message || 'Verification failed.'
      }
    }
  }

  const resending = ref(false)
  const resendMessage = ref('')
  const resendError = ref(false)

  const resendVerification = async () => {
    resending.value = true
    resendMessage.value = ''
    resendError.value = false

    try {
      await authService.resendVerification()
      resendMessage.value = 'Verification email sent successfully.'
    } catch (error: any) {
      resendError.value = true
      resendMessage.value = error.response?.data?.message || 'Failed to resend verification email.'
    } finally {
      resending.value = false
    }
  }

  // ── Change Password ────────────────────────────────────────────────────────
  const changePasswordLoading = ref(false)
  const changePasswordSuccess = ref(false)
  const changePasswordErrors = ref<Record<string, string[]>>({})
  const changePasswordGeneralError = ref('')

  const changePassword = async (payload: any) => {
    changePasswordLoading.value = true
    changePasswordSuccess.value = false
    changePasswordErrors.value = {}
    changePasswordGeneralError.value = ''

    try {
      const response = await authService.changePassword(payload)
      changePasswordSuccess.value = true
      
      // Update local storage user flag
      const userStr = localStorage.getItem('user')
      if (userStr && userStr !== 'undefined') {
        try {
          const user = JSON.parse(userStr)
          user.is_password_changed = true
          user.is_active = true
          localStorage.setItem('user', JSON.stringify(user))
        } catch {
          // Corrupt snapshot — ignore, server is source of truth.
        }
      }

      return {
        success: true,
        message: response.data?.message || 'Password has been successfully updated.'
      }
    } catch (error: any) {
      if (error.response?.status === 422) {
        changePasswordErrors.value = error.response.data.errors
      } else {
        changePasswordGeneralError.value = error.response?.data?.message || 'Failed to change password.'
      }

      return {
        success: false,
        message:
          error.response?.data?.message ||
          error.response?.data?.errors?.new_password?.[0] ||
          error.response?.data?.errors?.current_password?.[0] ||
          'Failed to change password.'
      }
    } finally {
      changePasswordLoading.value = false
    }
  }

  return {
    // Login
    login,
    loginLoading,
    loginErrors,
    loginGeneralError,
    // Logout
    logout,
    clearLocalAuth,
    // Current User
    fetchCurrentUser,
    // Forgot Password
    forgotPassword,
    forgotLoading,
    forgotMessage,
    forgotError,
    // Reset Password
    resetPassword,
    resetLoading,
    resetSuccess,
    resetErrors,
    resetGeneralError,
    // Email Verification
    verifyEmail,
    verifyStatus,
    verifyErrorMessage,
    resendVerification,
    resending,
    resendMessage,
    resendError,
    // Change Password
    changePassword,
    changePasswordLoading,
    changePasswordSuccess,
    changePasswordErrors,
    changePasswordGeneralError,
  }
}
