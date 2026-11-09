<script setup lang="ts">
import { ref, reactive, onMounted, watch } from 'vue'
import { Plus, Search, Building } from 'lucide-vue-next'
import { branchService, type BranchPayload } from '@/services/branchService'
import { notifySuccess, notifyApiError } from '@/lib/notify'
import { toast } from '@/composables/useToast'
import type { Branch, PaginationMeta } from '@/types'
import BranchesTable from './BranchesTable.vue'
import BranchFormModal from './BranchFormModal.vue'
import ConfirmModal from '@/components/common/ConfirmModal.vue'

const branches = ref<Branch[]>([])
const loading = ref(true)
const error = ref<string | null>(null)
const submitting = ref(false)
const modalError = ref<string | null>(null)

const pagination = ref<PaginationMeta>({
  current_page: 1,
  last_page: 1,
  total: 0,
})

const filters = reactive({
  search: '',
  per_page: 15,
})

const showModal = ref(false)
const modalMode = ref<'create' | 'edit'>('create')
const selectedBranchForEdit = ref<{
  id: number | null
  name: string
  address: string
}>({
  id: null,
  name: '',
  address: '',
})

const fetchBranches = async (page = 1) => {
  loading.value = true
  error.value = null
  try {
    const res = await branchService.fetchBranches({
      page,
      per_page: filters.per_page,
      search: filters.search.trim() || undefined,
    })
    branches.value = res.data.data
    pagination.value = {
      current_page: res.data.current_page,
      last_page: res.data.last_page,
      total: res.data.total,
    }
  } catch (err: any) {
    error.value = err.response?.data?.message || 'Failed to fetch branches'
  } finally {
    loading.value = false
  }
}

const openCreateModal = () => {
  modalMode.value = 'create'
  modalError.value = null
  selectedBranchForEdit.value = { id: null, name: '', address: '' }
  showModal.value = true
}

const openEditModal = (branch: Branch) => {
  modalMode.value = 'edit'
  modalError.value = null
  selectedBranchForEdit.value = {
    id: branch.id,
    name: branch.name,
    address: branch.address || '',
  }
  showModal.value = true
}

const handleSubmit = async (payload: { id: number | null; name: string; address: string }) => {
  submitting.value = true
  modalError.value = null

  try {
    const data: BranchPayload = {
      name: payload.name.trim(),
      address: payload.address.trim(),
    }

    if (modalMode.value === 'create') {
      await branchService.createBranch(data)
      notifySuccess('Branch created successfully')
    } else if (payload.id !== null) {
      await branchService.updateBranch(payload.id, data)
      notifySuccess('Branch updated successfully')
    }

    showModal.value = false
    await fetchBranches(pagination.value.current_page)
  } catch (err: any) {
    modalError.value = err.response?.data?.message || 'Failed to save branch'
    notifyApiError(err, 'branch', 'Failed to save branch')
  } finally {
    submitting.value = false
  }
}

// ── Delete Confirmation ──────────────────────────────────────────────────────
const showDeleteModal = ref(false)
const branchToDelete = ref<Branch | null>(null)
const isDeletingBranch = ref(false)

const handleDelete = (id: number) => {
  const branch = branches.value.find(b => b.id === id)
  if (!branch) return

  if ((branch.users_count ?? 0) > 0) {
    toast.error(`Cannot delete '${branch.name}' because it has ${branch.users_count} assigned users. Reassign them first.`)
    return
  }

  branchToDelete.value = branch
  showDeleteModal.value = true
}

const handleConfirmDelete = async () => {
  if (!branchToDelete.value) return
  isDeletingBranch.value = true
  try {
    await branchService.deleteBranch(branchToDelete.value.id)
    notifySuccess('Branch deleted successfully')
    await fetchBranches(pagination.value.current_page)
  } catch (err: any) {
    notifyApiError(err, 'branch', 'Failed to delete branch')
  } finally {
    isDeletingBranch.value = false
    showDeleteModal.value = false
    branchToDelete.value = null
  }
}

onMounted(() => fetchBranches())

watch(
  () => filters.search,
  () => {
    fetchBranches(1)
  }
)
</script>

<template>
  <div class="space-y-6 animate-in fade-in duration-500">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Branch Management</h1>
        <p class="text-slate-500 mt-1 text-sm">Organize users by geographical branches and office sites.</p>
      </div>
      
      <button 
        @click="openCreateModal"
        class="inline-flex items-center gap-2 bg-[#252578] hover:bg-[#1e1e66] text-white px-5 py-2.5 rounded-xl text-sm font-semibold shadow-sm hover:shadow-md transition-all active:scale-98 cursor-pointer"
      >
        <Plus :size="18" />
        <span>Create New Branch</span>
      </button>
    </div>

    <!-- Stats/Filters Section -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm flex items-center">
        <div class="flex items-center gap-4">
          <div class="w-12 h-12 bg-indigo-50 text-[#252578] rounded-xl flex items-center justify-center">
            <Building :size="24" />
          </div>
          <div>
            <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">Total Branches</p>
            <p class="text-2xl font-bold text-slate-900">{{ pagination.total }}</p>
          </div>
        </div>
      </div>

      <div class="md:col-span-2 bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex items-center">
        <div class="relative flex-1">
          <Search :size="18" class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
          <input 
            v-model="filters.search"
            type="text" 
            placeholder="Search branches by name or physical address..." 
            class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-100 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-900 transition-all"
          />
        </div>
      </div>
    </div>

    <!-- Table -->
    <BranchesTable
      :loading="loading"
      :error="error"
      :branches="branches"
      :pagination="pagination"
      v-model:per-page="filters.per_page"
      @fetch="fetchBranches"
      @open-edit-modal="openEditModal"
      @delete="handleDelete"
    />

    <!-- Create/Edit Modal (styled like UserCreate form) -->
    <BranchFormModal
      v-model="showModal"
      :mode="modalMode"
      :initial-data="selectedBranchForEdit"
      :submitting="submitting"
      :error="modalError"
      @submit="handleSubmit"
    />

    <!-- Delete Confirmation Modal -->
    <ConfirmModal
      :show="showDeleteModal"
      title="Delete Branch"
      confirm-label="Delete"
      variant="danger"
      :is-submitting="isDeletingBranch"
      @close="showDeleteModal = false"
      @confirm="handleConfirmDelete"
    >
      <p>
        Are you sure you want to permanently delete branch <strong class="font-bold text-slate-800">'{{ branchToDelete?.name }}'</strong>? This action cannot be undone and the record data will be removed from the system.
      </p>
    </ConfirmModal>
  </div>
</template>
