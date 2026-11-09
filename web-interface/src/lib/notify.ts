import { toast } from '@/composables/useToast'

export type EntityType = 'user' | 'role' | 'permission' | 'department' | 'branch'

/**
 * Checks if an error represents a duplicate record error.
 * Laravel returns 422 with validation messages like:
 * - "The name has already been taken."
 * - "An account with this email already exists."
 * - "A branch with this name already exists."
 * Or 409 Conflict.
 */
export function isDuplicateError(err: any): boolean {
  if (!err) return false

  const status = err.response?.status
  if (status === 409) return true

  if (status === 422) {
    const message = err.response?.data?.message || ''
    if (/already (been taken|exist)/i.test(message)) {
      return true
    }

    const errors = err.response?.data?.errors
    if (errors && typeof errors === 'object') {
      for (const key of Object.keys(errors)) {
        const fieldErrors = errors[key]
        if (Array.isArray(fieldErrors)) {
          for (const msg of fieldErrors) {
            if (typeof msg === 'string' && /already (been taken|exist)/i.test(msg)) {
              return true
            }
          }
        } else if (typeof fieldErrors === 'string' && /already (been taken|exist)/i.test(fieldErrors)) {
          return true
        }
      }
    }
  }

  return false
}

/**
 * Dispatches a success toast with standard formatting.
 */
export function notifySuccess(message: string) {
  toast.success(message)
}

/**
 * Handles API errors according to the hybrid toast policy:
 * 1. Duplicates trigger an error toast ("DUPLICATE DETECTED, THIS ... ALREADY EXISTS.")
 * 2. Non-duplicate 422 validation errors do NOT trigger a toast (handled inline by form fields).
 * 3. Network, server (500), 403, and other operational errors trigger an error toast with backend message or fallback.
 */
export function notifyApiError(
  err: any,
  entity: EntityType,
  fallback: string
): { isDuplicate: boolean; message: string } {
  if (isDuplicateError(err)) {
    const entityLabel = entity === 'user' ? 'email' : entity
    const duplicateMsg = `Duplicate detected, this ${entityLabel} already exists.`
    toast.error(duplicateMsg)
    return { isDuplicate: true, message: duplicateMsg }
  }

  // Non-duplicate 422 validation errors remain inline in the form
  if (err?.response?.status === 422) {
    return { isDuplicate: false, message: '' }
  }

  const message = err?.response?.data?.message || fallback
  toast.error(message)
  return { isDuplicate: false, message }
}
