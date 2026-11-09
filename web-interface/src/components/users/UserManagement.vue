<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import { useUsers } from '@/composables/useUsers'
import { 
  Search, 
  Loader2,
} from 'lucide-vue-next'
import ConfirmPasswordModal from '../common/ConfirmPasswordModal.vue'
import UsersTable from './UsersTable.vue'
import AssignRoleModal from './AssignRoleModal.vue'
import AssignBranchModal from './AssignBranchModal.vue'
import type { User } from '@/types'

const {
  users,
  roles,
  departments,
  branches,
  isLoading,
  pagination,
  filters,
  fetchUsers,
  fetchMetadata,
  isAssigning,
  assignRole,
  isAssigningBranch,
  assignBranch,
  isTogglingStatus,
  confirmPasswordError,
  toggleUserStatus,
} = useUsers()

onMounted(() => {
  fetchUsers()
  fetchMetadata()
})

// ── Search Debounce (300ms) & Direct Filter Watchers ───────────────────────
let searchDebounceTimer: ReturnType<typeof setTimeout> | null = null

watch(() => filters.value.search, () => {
  if (searchDebounceTimer) clearTimeout(searchDebounceTimer)
  searchDebounceTimer = setTimeout(() => {
    fetchUsers(1)
  }, 300)
})

watch([
  () => filters.value.role_id,
  () => filters.value.department_id,
  () => filters.value.branch_id,
  () => filters.value.is_active,
], () => {
  fetchUsers(1)
})

// ── Role Assignment Modal ───────────────────────────────────────────────────
const showRoleModal = ref(false)
const selectedUser = ref<User | null>(null)
const selectedRoleId = ref<number | string>('')

const openRoleModal = (user: User) => {
  selectedUser.value = user
  selectedRoleId.value = user.profile?.role?.id || ''
  showRoleModal.value = true
}

const handleAssignRole = async () => {
  if (!selectedUser.value || !selectedRoleId.value) return
  
  const result = await assignRole(selectedUser.value.id, selectedRoleId.value)
  if (result.success) {
    showRoleModal.value = false
    fetchUsers(pagination.value.current_page)
  }
}

// ── Branch Assignment Modal ─────────────────────────────────────────────────
const showBranchModal = ref(false)
const selectedBranchId = ref<number | string | null>('')

const openBranchModal = (user: User) => {
  selectedUser.value = user
  selectedBranchId.value = user.profile?.branch?.id ?? null
  showBranchModal.value = true
}

const handleAssignBranch = async () => {
  if (!selectedUser.value) return

  const result = await assignBranch(selectedUser.value.id, selectedBranchId.value)
  if (result.success) {
    showBranchModal.value = false
    fetchUsers(pagination.value.current_page)
  }
}

// ── Status Toggle Modal ─────────────────────────────────────────────────────
const showConfirmModal = ref(false)
const userToToggleStatus = ref<User | null>(null)

const openConfirmModal = (user: User) => {
  userToToggleStatus.value = user
  confirmPasswordError.value = ''
  showConfirmModal.value = true
}

const handleConfirmStatusToggle = async (password: string) => {
  if (!userToToggleStatus.value) return

  const success = await toggleUserStatus(
    userToToggleStatus.value.id, 
    password, 
    Boolean(userToToggleStatus.value.is_active)
  )
  if (success) {
    showConfirmModal.value = false
    fetchUsers(pagination.value.current_page)
  }
}
</script>

<template>
  <div class="space-y-6 animate-in fade-in duration-500">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <div class="flex items-center gap-3">
          <h1 class="text-3xl font-bold text-slate-900 tracking-tight font-sans">User Management</h1>
          <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-[#EFF6FF] text-[#252578] border border-blue-100">
            {{ pagination?.total || users.length }} Users
          </span>
        </div>
        <p class="text-slate-500 mt-1">Manage and monitor all system users and their permissions.</p>
      </div>
      <router-link 
        to="/admin/users/create"
        class="inline-flex items-center justify-center h-10 px-5 rounded-lg bg-[#252578] hover:bg-[#1e1e66] text-white text-sm font-semibold shadow-sm hover:shadow-md transition-all active:scale-98"
      >
        <span>+ Add New User</span>
      </router-link>
    </div>

    <!-- Unified Search & Filter Toolbar (ONE UI Spec) -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm flex flex-col lg:flex-row gap-4 items-stretch lg:items-center justify-between">
      <!-- Search input -->
      <div class="relative w-full lg:w-80 flex-shrink-0">
        <Loader2 v-if="isLoading && filters.search" :size="18" class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 animate-spin" />
        <Search v-else :size="18" class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" />
        <input 
          v-model="filters.search"
          type="text" 
          placeholder="Search by name or email..." 
          class="w-full pl-10 pr-4 h-10 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#252578]/15 focus:border-[#252578] transition-all"
        />
      </div>

      <!-- Filter dropdowns (evenly spaced) -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 w-full lg:w-auto flex-1 max-w-3xl">
        <select 
          v-model="filters.role_id"
          class="h-10 px-3 rounded-xl bg-slate-50 border border-slate-200 text-sm font-medium text-slate-700 focus:outline-none focus:ring-2 focus:ring-[#252578]/15 focus:border-[#252578] transition-all cursor-pointer"
        >
          <option value="">All Roles</option>
          <option v-for="role in roles" :key="role.id" :value="role.id">{{ role.name }}</option>
        </select>

        <select 
          v-model="filters.department_id"
          class="h-10 px-3 rounded-xl bg-slate-50 border border-slate-200 text-sm font-medium text-slate-700 focus:outline-none focus:ring-2 focus:ring-[#252578]/15 focus:border-[#252578] transition-all cursor-pointer"
        >
          <option value="">All Departments</option>
          <option v-for="dept in departments" :key="dept.id" :value="dept.id">{{ dept.name }}</option>
        </select>

        <select 
          v-model="filters.branch_id"
          class="h-10 px-3 rounded-xl bg-slate-50 border border-slate-200 text-sm font-medium text-slate-700 focus:outline-none focus:ring-2 focus:ring-[#252578]/15 focus:border-[#252578] transition-all cursor-pointer"
        >
          <option value="">All Branches</option>
          <option v-for="branch in branches" :key="branch.id" :value="branch.id">{{ branch.name }}</option>
        </select>

        <select 
          v-model="filters.is_active"
          class="h-10 px-3 rounded-xl bg-slate-50 border border-slate-200 text-sm font-medium text-slate-700 focus:outline-none focus:ring-2 focus:ring-[#252578]/15 focus:border-[#252578] transition-all cursor-pointer"
        >
          <option value="">All Status</option>
          <option value="1">Active</option>
          <option value="0">Inactive</option>
        </select>
      </div>
    </div>

    <!-- Table -->
    <UsersTable
      :users="users"
      :is-loading="isLoading"
      :pagination="pagination"
      v-model:per-page="filters.per_page"
      @fetch="fetchUsers"
      @open-role-modal="openRoleModal"
      @open-branch-modal="openBranchModal"
      @open-confirm-modal="openConfirmModal"
    />

    <!-- Role Assignment Modal -->
    <AssignRoleModal
      v-model="showRoleModal"
      :user="selectedUser"
      :roles="roles"
      v-model:selected-role-id="selectedRoleId"
      :is-assigning="isAssigning"
      @submit="handleAssignRole"
    />

    <!-- Branch Assignment Modal -->
    <AssignBranchModal
      v-model="showBranchModal"
      :user="selectedUser"
      :branches="branches"
      v-model:selected-branch-id="selectedBranchId"
      :is-assigning="isAssigningBranch"
      @submit="handleAssignBranch"
    />
    <!-- Password Confirmation Modal for Status Toggle -->
    <ConfirmPasswordModal
      :show="showConfirmModal"
      :title="userToToggleStatus?.is_active ? 'Deactivate User Account' : 'Activate User Account'"
      :description="userToToggleStatus?.is_active ? 'Are you sure you want to deactivate this user? They will be signed out of all active sessions.' : 'Are you sure you want to activate this user?'"
      :confirmLabel="userToToggleStatus?.is_active ? 'Deactivate' : 'Activate'"
      :variant="userToToggleStatus?.is_active ? 'danger' : 'primary'"
      :isSubmitting="isTogglingStatus"
      :error="confirmPasswordError"
      @close="showConfirmModal = false"
      @confirm="handleConfirmStatusToggle"
    />
  </div>
</template>
