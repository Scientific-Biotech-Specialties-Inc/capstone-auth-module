<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { 
  Shield, 
  Users, 
  Edit2, 
  Trash2, 
  Key,
  AlertCircle,
  ChevronLeft, 
  ChevronRight,
  MoreVertical
} from 'lucide-vue-next'
import type { Role, PaginationMeta } from '@/types'

const props = defineProps<{
  loading: boolean
  error: string | null
  roles: Role[]
  pagination: PaginationMeta | null
  perPage: number
}>()

const emit = defineEmits<{
  'update:perPage': [value: number]
  'fetch': [page: number]
  'open-users-modal': [role: Role]
  'open-perm-modal': [role: Role]
  'open-edit-modal': [role: Role]
  'delete': [id: number]
}>()

const internalPerPage = computed({
  get: () => props.perPage,
  set: (value) => emit('update:perPage', value)
})

const activeDropdownRoleId = ref<number | null>(null)
const openUpward = ref(false)

const toggleDropdown = (id: number, index: number, event?: MouseEvent) => {
  if (activeDropdownRoleId.value === id) {
    activeDropdownRoleId.value = null
    return
  }

  const total = props.roles.length
  let shouldOpenUp = total > 1 && (total <= 3 ? index === total - 1 : index >= total - 2)

  if (event && event.currentTarget) {
    const rect = (event.currentTarget as HTMLElement).getBoundingClientRect()
    const spaceBelow = window.innerHeight - rect.bottom
    if (spaceBelow < 190) {
      shouldOpenUp = true
    }
  }

  openUpward.value = shouldOpenUp
  activeDropdownRoleId.value = id
}

const handleClickOutside = (e: MouseEvent) => {
  const target = e.target as HTMLElement
  if (!target.closest('.dropdown-trigger') && !target.closest('.dropdown-menu')) {
    activeDropdownRoleId.value = null
  }
}

onMounted(() => {
  window.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  window.removeEventListener('click', handleClickOutside)
})
</script>

<template>
  <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
    <!-- Error State -->
    <div v-if="error && roles.length === 0 && !loading" class="p-16 text-center">
      <AlertCircle class="mx-auto mb-3 text-red-400" :size="32" />
      <p class="text-red-600 font-medium">{{ error }}</p>
    </div>

    <!-- Empty State -->
    <div v-else-if="!loading && roles.length === 0" class="p-16 text-center text-slate-500">
      <Shield class="mx-auto mb-3 text-slate-400" :size="32" />
      <p class="font-medium">No roles found.</p>
    </div>

    <!-- Table (always visible; skeleton replaces tbody rows when loading) -->
    <div v-else class="overflow-x-auto min-h-[220px]">
      <table class="w-full text-left border-collapse">
        <thead>
          <tr class="border-b border-slate-100 bg-slate-50/50">
            <th class="px-6 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Role</th>
            <th class="px-6 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Description</th>
            <th class="px-6 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider text-center">Users</th>
            <th class="px-6 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-50">
          <!-- ── Skeleton Loader Rows (DESIGN.md §VIII: skeleton for data tables) ── -->
          <template v-if="loading">
            <tr v-for="i in perPage" :key="`skel-${i}`" class="animate-pulse">
              <!-- Role cell -->
              <td class="px-6 py-4">
                <div class="flex items-center gap-3">
                  <div class="w-8 h-8 rounded-lg bg-slate-200 flex-shrink-0"></div>
                  <div class="h-3 w-32 bg-slate-200 rounded"></div>
                </div>
              </td>
              <!-- Description cell -->
              <td class="px-6 py-4">
                <div class="h-3 w-48 bg-slate-200 rounded"></div>
              </td>
              <!-- Users count cell -->
              <td class="px-6 py-4 text-center">
                <div class="h-6 w-16 bg-slate-200 rounded-full mx-auto"></div>
              </td>
              <!-- Actions cell -->
              <td class="px-6 py-4">
                <div class="h-6 w-6 bg-slate-200 rounded-lg ml-auto"></div>
              </td>
            </tr>
          </template>
          <!-- Real Data Rows -->
          <template v-else>
            <tr v-for="(role, index) in roles" :key="role.id" class="hover:bg-slate-50/50 transition-colors">
            <td class="px-6 py-4">
              <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-indigo-50 flex items-center justify-center">
                  <Shield :size="16" class="text-indigo-600" />
                </div>
                <span class="font-bold text-slate-900">{{ role.name }}</span>
              </div>
            </td>
            <td class="px-6 py-4">
              <p class="text-sm text-slate-500 line-clamp-1 max-w-xs">{{ role.description || 'No description' }}</p>
            </td>
            <td class="px-6 py-4 text-center">
              <button 
                @click="emit('open-users-modal', role)"
                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold transition-all hover:scale-105 active:scale-95"
                :class="(role.users_count ?? 0) > 0 ? 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100' : 'bg-slate-100 text-slate-500 hover:bg-slate-200'"
              >
                <Users :size="12" />
                {{ role.users_count ?? 0 }} Users
              </button>
            </td>
            <td class="px-6 py-4 text-right" :class="{ 'relative z-40': activeDropdownRoleId === role.id }">
              <div class="flex justify-end items-center relative" :class="{ 'z-40': activeDropdownRoleId === role.id }">
                <!-- 3-Dots Action Button -->
                <button 
                  @click.stop="toggleDropdown(role.id, index, $event)"
                  class="dropdown-trigger p-2 hover:bg-slate-100 rounded-lg text-slate-400 hover:text-slate-900 transition-all cursor-pointer"
                  title="Actions"
                >
                  <MoreVertical :size="18" />
                </button>

                <!-- Dropdown Menu (Smart Auto-Flip Up/Down) -->
                <div 
                  v-if="activeDropdownRoleId === role.id"
                  class="dropdown-menu absolute right-0 z-40 bg-white border border-slate-200 rounded-xl shadow-lg p-1.5 flex flex-col gap-1 min-w-[170px] text-left animate-in fade-in duration-150"
                  :class="openUpward ? 'bottom-full mb-1 slide-in-from-bottom-1' : 'top-full mt-1 slide-in-from-top-1'"
                >
                  <button 
                    @click.stop="activeDropdownRoleId = null; emit('open-edit-modal', role)"
                    class="w-full px-3 py-2 hover:bg-slate-50 text-slate-700 hover:text-slate-900 rounded-lg transition-all flex items-center gap-2.5 text-xs font-semibold cursor-pointer"
                  >
                    <Edit2 :size="14" class="text-slate-400" />
                    <span>Edit Role</span>
                  </button>

                  <button 
                    @click.stop="activeDropdownRoleId = null; emit('open-perm-modal', role)"
                    class="w-full px-3 py-2 hover:bg-amber-50 text-slate-700 hover:text-amber-800 rounded-lg transition-all flex items-center gap-2.5 text-xs font-semibold cursor-pointer"
                  >
                    <Key :size="14" class="text-amber-500" />
                    <span>Edit Permissions</span>
                  </button>

                  <div class="h-px bg-slate-100 my-0.5"></div>

                  <button 
                    @click.stop="activeDropdownRoleId = null; emit('delete', role.id)"
                    class="w-full px-3 py-2 hover:bg-red-50 text-red-600 rounded-lg transition-all flex items-center gap-2.5 text-xs font-semibold cursor-pointer"
                  >
                    <Trash2 :size="14" class="text-red-500" />
                    <span>Delete Role</span>
                  </button>
                </div>
              </div>
            </td>
          </tr>
          </template>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div v-if="pagination && pagination.total > 0" class="px-6 py-4 bg-slate-50/50 border-t border-slate-100 flex items-center justify-between">
      <p class="text-xs text-slate-500 font-medium uppercase tracking-wider">
        Showing <span class="text-slate-900 font-bold">{{ roles.length }}</span> of <span class="text-slate-900 font-bold">{{ pagination.total }}</span> roles
      </p>
      <div class="flex items-center gap-6">
        <div class="flex items-center gap-2">
          <span class="text-xs text-slate-500 font-medium uppercase tracking-wider">Rows per page:</span>
          <select 
            v-model.number="internalPerPage"
            class="h-8 px-2 pr-6 rounded-lg bg-white border border-slate-200 text-sm font-semibold text-slate-700 focus:outline-none focus:ring-2 focus:ring-[#3b82f6]/20 focus:border-[#3b82f6] cursor-pointer shadow-sm transition-all"
          >
            <option :value="10">10</option>
            <option :value="15">15</option>
            <option :value="25">25</option>
            <option :value="50">50</option>
            <option :value="100">100</option>
          </select>
        </div>
        <div class="flex items-center gap-2" v-if="pagination.last_page > 1">
          <button 
            @click="emit('fetch', pagination.current_page - 1)"
            :disabled="pagination.current_page === 1"
            class="p-2 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 disabled:opacity-50 transition-all shadow-sm"
          >
            <ChevronLeft :size="16" />
          </button>
          <div class="flex items-center gap-1">
            <button 
              v-for="p in pagination.last_page" 
              :key="p"
              @click="emit('fetch', p)"
              class="w-8 h-8 rounded-lg text-sm font-bold transition-all"
              :class="pagination.current_page === p ? 'bg-slate-900 text-white shadow-md' : 'hover:bg-slate-200 text-slate-600'"
            >
              {{ p }}
            </button>
          </div>
          <button 
            @click="emit('fetch', pagination.current_page + 1)"
            :disabled="pagination.current_page === pagination.last_page"
            class="p-2 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 disabled:opacity-50 transition-all shadow-sm"
          >
            <ChevronRight :size="16" />
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
