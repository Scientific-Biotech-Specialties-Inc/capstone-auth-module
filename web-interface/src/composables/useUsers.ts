// ─── useUsers Composable ─────────────────────────────────────────────────────
// Manages user listing, creation, role assignment, and status toggling
// with reactive Vue state for the admin user management pages.

import { ref } from 'vue'
import { userService } from '@/services/userService'
import { notifySuccess, notifyApiError } from '@/lib/notify'
import type { User, Role, Department, Branch, PaginationMeta, CreateUserPayload } from '@/types'

export function useUsers() {
  // ── List State ─────────────────────────────────────────────────────────────
  const users = ref<User[]>([])
  const roles = ref<Role[]>([])
  const departments = ref<Department[]>([])
  const branches = ref<Branch[]>([])
  const isLoading = ref(true)
  const pagination = ref<PaginationMeta>({
    current_page: 1,
    last_page: 1,
    total: 0,
  })
  const filters = ref({
    role_id: '',
    department_id: '',
    branch_id: '',
    is_active: '',
    search: '',
    per_page: 15,
  })

  const fetchUsers = async (page = 1) => {
    isLoading.value = true
    try {
      const response = await userService.fetchUsers({ page, ...filters.value })
      users.value = response.data.data
      pagination.value = {
        current_page: response.data.current_page,
        last_page: response.data.last_page,
        total: response.data.total,
      }
    } catch (err) {
      console.error('Failed to fetch users', err)
    } finally {
      isLoading.value = false
    }
  }

  const fetchMetadata = async () => {
    try {
      const [rolesRes, deptsRes, branchesRes] = await Promise.allSettled([
        userService.fetchRoleOptions(),
        userService.fetchDepartmentOptions(),
        userService.fetchBranchOptions(),
      ])
      if (rolesRes.status === 'fulfilled') roles.value = rolesRes.value.data
      if (deptsRes.status === 'fulfilled') departments.value = deptsRes.value.data
      if (branchesRes.status === 'fulfilled') branches.value = branchesRes.value.data
    } catch (err) {
      console.error('Failed to fetch metadata', err)
    }
  }

  // ── Create User ────────────────────────────────────────────────────────────
  const isLoadingData = ref(true)
  const isSubmitting = ref(false)
  const isSuccess = ref(false)
  const errors = ref<Record<string, string[]>>({})
  const generalError = ref('')

  const fetchCreateFormData = async () => {
    try {
      const [rolesRes, deptsRes, branchesRes] = await Promise.allSettled([
        userService.fetchRoleOptions(),
        userService.fetchDepartmentOptions(),
        userService.fetchBranchOptions(),
      ])
      if (rolesRes.status === 'fulfilled') roles.value = rolesRes.value.data
      if (deptsRes.status === 'fulfilled') departments.value = deptsRes.value.data
      if (branchesRes.status === 'fulfilled') branches.value = branchesRes.value.data
    } catch {
      generalError.value = 'Failed to load roles, departments, or branches.'
    } finally {
      isLoadingData.value = false
    }
  }

  const createUser = async (payload: CreateUserPayload) => {
    isSubmitting.value = true
    errors.value = {}
    generalError.value = ''

    try {
      await userService.createUser(payload)
      notifySuccess('User created successfully')
      return true
    } catch (error: any) {
      if (error.response?.status === 422) {
        errors.value = error.response.data.errors || {}
      } else if (error.response?.status === 409) {
        errors.value = { email: ['Email already exists'] }
      }

      // Check duplicates or general API error
      const result = notifyApiError(error, 'user', 'Failed to create user.')
      if (result.isDuplicate && !errors.value.email) {
        errors.value.email = ['Email already exists']
      }

      return false
    } finally {
      isSubmitting.value = false
    }
  }

  // ── Role Assignment ────────────────────────────────────────────────────────
  const isAssigning = ref(false)

  const assignRole = async (userId: number, roleId: number | string) => {
    isAssigning.value = true
    try {
      await userService.assignRole(userId, roleId)
      notifySuccess('Role assigned successfully')
      return { success: true }
    } catch (err: any) {
      const { message } = notifyApiError(err, 'role', 'Failed to assign role')
      return {
        success: false,
        message: message || err.response?.data?.message || 'Failed to assign role',
      }
    } finally {
      isAssigning.value = false
    }
  }

  // ── Branch Assignment ──────────────────────────────────────────────────────
  const isAssigningBranch = ref(false)

  const assignBranch = async (userId: number, branchId: number | string | null) => {
    isAssigningBranch.value = true
    try {
      await userService.assignBranch(userId, branchId)
      notifySuccess('Branch assigned successfully')
      return { success: true }
    } catch (err: any) {
      const { message } = notifyApiError(err, 'branch', 'Failed to assign branch')
      return {
        success: false,
        message: message || err.response?.data?.message || 'Failed to assign branch',
      }
    } finally {
      isAssigningBranch.value = false
    }
  }

  // ── Status Toggle ──────────────────────────────────────────────────────────
  const isTogglingStatus = ref(false)
  const confirmPasswordError = ref('')

  const toggleUserStatus = async (userId: number, password: string, isCurrentlyActive = false) => {
    isTogglingStatus.value = true
    confirmPasswordError.value = ''

    try {
      await userService.toggleUserStatus(userId, password)
      const successMsg = isCurrentlyActive
        ? 'User deactivated successfully'
        : 'User activated successfully'
      notifySuccess(successMsg)
      return true
    } catch (err: any) {
      const status = err.response?.status
      const errorMsg =
        err.response?.data?.message ||
        err.response?.data?.errors?.password?.[0] ||
        'Password verification failed'

      if (status === 422 || status === 401) {
        confirmPasswordError.value = errorMsg
      } else {
        notifyApiError(err, 'user', 'Failed to update user status')
      }
      return false
    } finally {
      isTogglingStatus.value = false
    }
  }

  return {
    // List
    users,
    roles,
    departments,
    branches,
    isLoading,
    pagination,
    filters,
    fetchUsers,
    fetchMetadata,
    // Create
    isLoadingData,
    isSubmitting,
    isSuccess,
    errors,
    generalError,
    fetchCreateFormData,
    createUser,
    // Role Assignment
    isAssigning,
    assignRole,
    // Branch Assignment
    isAssigningBranch,
    assignBranch,
    // Status Toggle
    isTogglingStatus,
    confirmPasswordError,
    toggleUserStatus,
  }
}
