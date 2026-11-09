<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { 
  Plus, 
  Search, 
  Shield
} from 'lucide-vue-next'
import { useRoles } from '@/composables/useRoles'
import type { Role } from '@/types'
import RolesTable from './RolesTable.vue'
import RoleFormModal from './RoleFormModal.vue'
import RoleUsersModal from './RoleUsersModal.vue'
import ConfirmModal from '@/components/common/ConfirmModal.vue'

const {
  roles,
  loading,
  error,
  pagination,
  filters,
  fetchRoles,
  submitting,
  createRole,
  updateRole,
  deleteRole,
  // Role Users
  roleUsers,
  loadingUsers,
  fetchRoleUsers,
} = useRoles()

// ── Create / Edit Modal ──────────────────────────────────────────────────────
const showModal = ref(false)
const modalMode = ref<'create' | 'edit'>('create')
const form = ref({
  id: null as number | null,
  name: '',
  description: ''
})

const openCreateModal = () => {
  modalMode.value = 'create'
  form.value = { id: null, name: '', description: '' }
  error.value = ''
  showModal.value = true
}

const openEditModal = (role: Role) => {
  modalMode.value = 'edit'
  form.value = { id: role.id, name: role.name, description: role.description || '' }
  error.value = ''
  showModal.value = true
}

const handleSubmit = async (payload: { id: number | null, name: string, description: string }) => {
  const { id, ...data } = payload
  let success = false

  if (modalMode.value === 'create') {
    success = await createRole(data)
  } else if (id !== null) {
    success = await updateRole(id, data)
  }

  if (success) {
    showModal.value = false
  }
}

// ── Delete Confirmation ──────────────────────────────────────────────────────
const showDeleteModal = ref(false)
const roleToDelete = ref<Role | null>(null)
const isDeletingRole = ref(false)

const handleDelete = (id: number) => {
  const role = roles.value.find(r => r.id === id)
  if (!role) return
  roleToDelete.value = role
  showDeleteModal.value = true
}

const handleConfirmDelete = async () => {
  if (!roleToDelete.value) return
  isDeletingRole.value = true
  try {
    await deleteRole(roleToDelete.value.id)
  } finally {
    isDeletingRole.value = false
    showDeleteModal.value = false
    roleToDelete.value = null
  }
}

const router = useRouter()

// ── Permission Management Navigation ─────────────────────────────────────────
const openPermModal = (role: Role) => {
  router.push({ name: 'admin-role-permissions', params: { id: role.id } })
}

// ── Users Modal ──────────────────────────────────────────────────────────────
const showUsersModal = ref(false)
const viewingRole = ref<Role | null>(null)

const openUsersModal = async (role: Role) => {
  viewingRole.value = role
  showUsersModal.value = true
  await fetchRoleUsers(role.id)
}

onMounted(() => fetchRoles())

watch(filters, () => {
  fetchRoles(1)
}, { deep: true })
</script>

<template>
  <div class="space-y-6">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-slate-900">Role Management</h1>
        <p class="text-slate-500 mt-1">Create and manage user roles and their permissions</p>
      </div>
      
      <button 
        @click="openCreateModal"
        class="inline-flex items-center gap-2 bg-[#252578] hover:bg-[#1e1e66] text-white px-5 py-2.5 rounded-lg text-sm font-semibold shadow-sm hover:shadow-md transition-all active:scale-98 cursor-pointer"
      >
        <Plus :size="18" />
        <span>Create Role</span>
      </button>
    </div>

    <!-- Stats/Filters Section -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
        <div class="flex items-center gap-4">
          <div class="w-12 h-12 bg-indigo-50 text-indigo-600 rounded-xl flex items-center justify-center">
            <Shield :size="24" />
          </div>
          <div>
            <p class="text-sm font-medium text-slate-500 uppercase tracking-wider">Total Roles</p>
            <p class="text-2xl font-bold text-slate-900">{{ roles.length }}</p>
          </div>
        </div>
      </div>

      <div class="md:col-span-2 bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex items-center">
        <div class="relative flex-1">
          <Search :size="18" class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
          <input 
            v-model="filters.search"
            type="text" 
            placeholder="Search roles by name or description..." 
            class="w-full pl-10 pr-4 py-2 bg-slate-50 border-none rounded-xl text-sm focus:ring-2 focus:ring-slate-900/5 transition-all"
          />
        </div>
      </div>
    </div>

    <!-- Table -->
    <RolesTable
      :loading="loading"
      :error="error"
      :roles="roles"
      :pagination="pagination"
      v-model:per-page="filters.per_page"
      @fetch="fetchRoles"
      @open-users-modal="openUsersModal"
      @open-perm-modal="openPermModal"
      @open-edit-modal="openEditModal"
      @delete="handleDelete"
    />

    <!-- Create/Edit Role Modal -->
    <RoleFormModal
      v-model="showModal"
      :mode="modalMode"
      :initial-data="form"
      :submitting="submitting"
      :error="error"
      @submit="handleSubmit"
    />

    <!-- Users Modal -->
    <RoleUsersModal
      v-model="showUsersModal"
      :role="viewingRole"
      :users="roleUsers"
      :loading="loadingUsers"
    />

    <!-- Delete Confirmation Modal -->
    <ConfirmModal
      :show="showDeleteModal"
      title="Delete Role"
      confirm-label="Delete"
      variant="danger"
      :is-submitting="isDeletingRole"
      @close="showDeleteModal = false"
      @confirm="handleConfirmDelete"
    >
      <p>
        Are you sure you want to permanently delete the role <strong class="font-bold text-slate-800">'{{ roleToDelete?.name }}'</strong>? This action cannot be undone and the record data will be removed from the system.
      </p>
    </ConfirmModal>
  </div>
</template>
