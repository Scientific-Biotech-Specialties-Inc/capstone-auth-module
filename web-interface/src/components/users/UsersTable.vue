<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { 
  ChevronLeft, 
  ChevronRight,
  Shield,
  Building2,
  Mail,
  User as UserIcon,
  X,
  MoreVertical,
  Check,
  MapPin
} from 'lucide-vue-next'
import type { User, PaginationMeta } from '@/types'

const props = defineProps<{
  users: User[]
  isLoading: boolean
  pagination: PaginationMeta
  perPage: number
}>()

const emit = defineEmits<{
  'update:perPage': [value: number]
  'fetch': [page: number]
  'open-role-modal': [user: User]
  'open-branch-modal': [user: User]
  'open-confirm-modal': [user: User]
}>()

const internalPerPage = computed({
  get: () => props.perPage,
  set: (value) => emit('update:perPage', value)
})

const activeDropdownUserId = ref<number | null>(null)
const openUpward = ref(false)

const toggleDropdown = (userId: number, index: number, event?: MouseEvent) => {
  if (activeDropdownUserId.value === userId) {
    activeDropdownUserId.value = null
    return
  }

  const total = props.users.length
  let shouldOpenUp = total > 1 && (total <= 3 ? index === total - 1 : index >= total - 2)

  if (event && event.currentTarget) {
    const rect = (event.currentTarget as HTMLElement).getBoundingClientRect()
    const spaceBelow = window.innerHeight - rect.bottom
    if (spaceBelow < 190) {
      shouldOpenUp = true
    }
  }

  openUpward.value = shouldOpenUp
  activeDropdownUserId.value = userId
}

const handleWindowClick = (event: MouseEvent) => {
  const target = event.target as HTMLElement
  if (!target.closest('.dropdown-trigger') && !target.closest('.dropdown-menu')) {
    activeDropdownUserId.value = null
  }
}

onMounted(() => {
  window.addEventListener('click', handleWindowClick)
})

onUnmounted(() => {
  window.removeEventListener('click', handleWindowClick)
})

const handleOpenRoleModal = (user: User) => {
  activeDropdownUserId.value = null
  emit('open-role-modal', user)
}

const handleOpenBranchModal = (user: User) => {
  activeDropdownUserId.value = null
  emit('open-branch-modal', user)
}

const handleOpenConfirmModal = (user: User) => {
  activeDropdownUserId.value = null
  emit('open-confirm-modal', user)
}
</script>

<template>
  <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="overflow-x-auto min-h-[220px]">
      <table class="w-full text-left border-collapse">
        <thead>
          <tr class="bg-slate-50/50 border-b border-slate-100">
            <th class="px-6 py-4 text-[11px] font-bold text-slate-500 uppercase tracking-wider">User</th>
            <th class="px-6 py-4 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Role</th>
            <th class="px-6 py-4 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Department</th>
            <th class="px-6 py-4 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Branch</th>
            <th class="px-6 py-4 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Status</th>
            <th class="px-8 py-4 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <!-- ── Skeleton Loader Rows (DESIGN.md §VIII: skeleton for data tables) ── -->
          <template v-if="isLoading">
            <tr v-for="i in perPage" :key="`skel-${i}`" class="animate-pulse">
              <!-- User cell -->
              <td class="px-6 py-4">
                <div class="flex items-center gap-3">
                  <div class="w-10 h-10 rounded-full bg-slate-200 flex-shrink-0"></div>
                  <div class="space-y-1.5">
                    <div class="h-3 w-28 bg-slate-200 rounded"></div>
                    <div class="h-2.5 w-36 bg-slate-100 rounded"></div>
                  </div>
                </div>
              </td>
              <!-- Role cell -->
              <td class="px-6 py-4">
                <div class="h-6 w-24 bg-slate-200 rounded-full"></div>
              </td>
              <!-- Department cell -->
              <td class="px-6 py-4">
                <div class="h-3 w-32 bg-slate-200 rounded"></div>
              </td>
              <!-- Branch cell -->
              <td class="px-6 py-4">
                <div class="h-3 w-28 bg-slate-200 rounded"></div>
              </td>
              <!-- Status cell -->
              <td class="px-6 py-4">
                <div class="h-5 w-16 bg-slate-200 rounded-full"></div>
              </td>
              <!-- Actions cell -->
              <td class="px-6 py-4">
                <div class="h-6 w-6 bg-slate-200 rounded-lg ml-auto"></div>
              </td>
            </tr>
          </template>
          <tr v-else-if="users.length === 0" class="animate-in fade-in duration-200">
            <td colspan="6" class="px-6 py-12 text-center">
              <UserIcon class="h-12 w-12 text-slate-200 mx-auto mb-3" />
              <p class="text-slate-500 font-medium">No users found match your criteria.</p>
            </td>
          </tr>
          <template v-else>
            <tr v-for="(user, index) in users" :key="user.id" class="hover:bg-slate-50/50 transition-colors group animate-in fade-in duration-200">
            <td class="px-6 py-4">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-[#252578] flex items-center justify-center font-bold text-white text-xs shadow-sm flex-shrink-0">
                  {{ (user.profile?.first_name || 'U').substring(0, 1).toUpperCase() }}{{ (user.profile?.last_name || '').substring(0, 1).toUpperCase() }}
                </div>
                <div>
                  <p class="text-sm font-bold text-slate-900 leading-tight">
                    {{ user.profile?.first_name }} {{ user.profile?.last_name }}
                  </p>
                  <p class="text-xs text-slate-500 mt-0.5 flex items-center gap-1">
                    <Mail :size="12" />
                    {{ user.email }}
                  </p>
                </div>
              </div>
            </td>
            <td class="px-6 py-4">
              <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-slate-100 text-slate-700 text-xs font-bold">
                <Shield :size="14" />
                {{ user.profile?.role?.name || 'No Role' }}
              </div>
            </td>
            <td class="px-6 py-4 text-sm text-slate-600">
              <div class="flex items-center gap-2">
                <Building2 :size="16" class="text-slate-400" />
                {{ user.profile?.department?.name || 'N/A' }}
              </div>
            </td>
            <td class="px-6 py-4 text-sm text-slate-600">
              <div class="flex items-center gap-2">
                <MapPin :size="16" class="text-slate-400" />
                <span class="truncate max-w-[160px]" :title="user.profile?.branch?.name">
                  {{ user.profile?.branch?.name || '—' }}
                </span>
              </div>
            </td>
            <td class="px-6 py-4">
              <span 
                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold"
                :class="user.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700'"
              >
                {{ user.is_active ? 'Active' : 'Inactive' }}
              </span>
            </td>
            <td class="px-6 py-4" :class="{ 'relative z-40': activeDropdownUserId === user.id }">
              <div class="flex justify-end items-center relative" :class="{ 'z-40': activeDropdownUserId === user.id }">
                <!-- Ellipses Trigger Button -->
                <button 
                  @click.stop="toggleDropdown(user.id, index, $event)"
                  class="dropdown-trigger p-2 hover:bg-slate-100 rounded-lg text-slate-400 hover:text-slate-900 transition-all cursor-pointer"
                  title="Actions"
                >
                  <MoreVertical :size="18" />
                </button>

                <!-- Dropdown expanded container (Smart Auto-Flip Up/Down) -->
                <div 
                  v-if="activeDropdownUserId === user.id"
                  class="dropdown-menu absolute right-0 z-40 bg-white border border-slate-200 rounded-xl shadow-lg p-2 flex flex-col items-center gap-1.5 whitespace-nowrap animate-in fade-in duration-150"
                  :class="openUpward ? 'bottom-full mb-1 slide-in-from-bottom-1' : 'top-full mt-1 slide-in-from-top-1'"
                >
                  <!-- Assign Role Button -->
                  <button 
                    @click="handleOpenRoleModal(user)"
                    class="w-full px-2.5 py-1.5 hover:bg-slate-100 rounded-lg text-slate-700 hover:text-slate-900 transition-all flex items-center justify-start gap-2 text-xs font-semibold cursor-pointer"
                  >
                    <Shield :size="14" class="text-slate-400" />
                    <span>Assign Role</span>
                  </button>

                  <!-- Assign Branch Button -->
                  <button 
                    @click="handleOpenBranchModal(user)"
                    class="w-full px-2.5 py-1.5 hover:bg-slate-100 rounded-lg text-slate-700 hover:text-slate-900 transition-all flex items-center justify-start gap-2 text-xs font-semibold cursor-pointer"
                  >
                    <MapPin :size="14" class="text-slate-400" />
                    <span>Assign Branch</span>
                  </button>

                  <!-- Activate/Deactivate Button -->
                  <button 
                    @click="handleOpenConfirmModal(user)"
                    class="w-full px-2.5 py-1.5 rounded-lg transition-all flex items-center justify-start gap-2 text-xs font-semibold border cursor-pointer"
                    :class="user.is_active 
                      ? 'bg-rose-50 border-rose-100 hover:bg-rose-100/70 text-rose-700' 
                      : 'bg-emerald-50 border-emerald-100 hover:bg-emerald-100/70 text-emerald-700'"
                  >
                    <component :is="user.is_active ? X : Check" :size="14" />
                    <span>{{ user.is_active ? 'Deactivate' : 'Activate' }}</span>
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
    <div v-if="!isLoading && pagination && pagination.total > 0" class="px-6 py-4 bg-slate-50/50 border-t border-slate-100 flex items-center justify-between animate-in fade-in duration-200">
      <p class="text-xs text-slate-500 font-medium uppercase tracking-wider">
        Showing <span class="text-slate-900 font-bold">{{ users.length }}</span> of <span class="text-slate-900 font-bold">{{ pagination.total }}</span> users
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
