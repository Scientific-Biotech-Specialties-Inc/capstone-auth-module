// ─── useDepartments Composable ────────────────────────────────────────────────
// Manages department list, CRUD operations, and department-user association
// with reactive Vue state for the department management page.

import { ref } from 'vue'
import { departmentService } from '@/services/departmentService'
import { notifySuccess, notifyApiError } from '@/lib/notify'
import type { Department, User, PaginationMeta, DepartmentForm } from '@/types'

export function useDepartments() {
  // ── List State ─────────────────────────────────────────────────────────────
  const departments = ref<Department[]>([])
  const loading = ref(true)
  const error = ref('')
  const pagination = ref<PaginationMeta>({
    current_page: 1,
    last_page: 1,
    total: 0,
  })
  const filters = ref({
    search: '',
    per_page: 15,
  })

  const fetchDepartments = async (page = 1) => {
    loading.value = true
    error.value = ''
    try {
      const response = await departmentService.fetchDepartments({
        page,
        ...filters.value,
      })
      departments.value = response.data.data
      pagination.value = {
        current_page: response.data.current_page,
        last_page: response.data.last_page,
        total: response.data.total,
      }
    } catch (err: any) {
      console.error('Failed to fetch departments', err)
      error.value = 'Failed to load departments'
    } finally {
      loading.value = false
    }
  }

  // ── CRUD ───────────────────────────────────────────────────────────────────
  const submitting = ref(false)

  const createDepartment = async (form: Omit<DepartmentForm, 'id'>) => {
    submitting.value = true
    error.value = ''
    try {
      await departmentService.createDepartment(form)
      notifySuccess('Department created successfully')
      await fetchDepartments()
      return true
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Something went wrong'
      notifyApiError(err, 'department', 'Failed to create department.')
      return false
    } finally {
      submitting.value = false
    }
  }

  const updateDepartment = async (id: number, form: Omit<DepartmentForm, 'id'>) => {
    submitting.value = true
    error.value = ''
    try {
      await departmentService.updateDepartment(id, form)
      notifySuccess('Department updated successfully')
      await fetchDepartments()
      return true
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Something went wrong'
      notifyApiError(err, 'department', 'Failed to update department.')
      return false
    } finally {
      submitting.value = false
    }
  }

  const deleteDepartment = async (id: number) => {
    try {
      await departmentService.deleteDepartment(id)
      notifySuccess('Department deleted successfully')
      await fetchDepartments()
      return { success: true }
    } catch (err: any) {
      const { message } = notifyApiError(err, 'department', 'Failed to delete department')
      return {
        success: false,
        message: message || err.response?.data?.message || 'Failed to delete department',
      }
    }
  }

  // ── Department Users ───────────────────────────────────────────────────────
  const departmentUsers = ref<User[]>([])
  const loadingUsers = ref(false)
  const userPagination = ref<PaginationMeta>({
    current_page: 1,
    last_page: 1,
    total: 0,
  })

  const fetchDepartmentUsers = async (deptId: number, page = 1) => {
    loadingUsers.value = true
    try {
      const response = await departmentService.fetchDepartmentUsers(deptId, page)
      departmentUsers.value = response.data.data
      userPagination.value = {
        current_page: response.data.current_page,
        last_page: response.data.last_page,
        total: response.data.total,
      }
    } catch (err) {
      console.error('Failed to fetch department users', err)
      departmentUsers.value = []
    } finally {
      loadingUsers.value = false
    }
  }

  return {
    departments,
    loading,
    error,
    pagination,
    filters,
    fetchDepartments,
    submitting,
    createDepartment,
    updateDepartment,
    deleteDepartment,
    departmentUsers,
    loadingUsers,
    userPagination,
    fetchDepartmentUsers,
  }
}
