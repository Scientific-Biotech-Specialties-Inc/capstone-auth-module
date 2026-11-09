<script setup lang="ts">
import { ref, reactive, onMounted, watch } from 'vue'
import { 
  Search, 
  RefreshCw, 
  ChevronLeft, 
  ChevronRight, 
  Clock 
} from 'lucide-vue-next'
import { dashboardService, type AuditLogEntry, type AuditLogParams } from '@/services/dashboardService'

const loading = ref(true)
const logs = ref<AuditLogEntry[]>([])

const pagination = reactive({
  current_page: 1,
  last_page: 1,
  per_page: 15,
  total: 0,
  from: 0,
  to: 0
})

const filters = reactive({
  search: '',
  action: '',
  date: '',
  per_page: 15
})

let debounceTimer: any = null

const fetchLogs = async (page = 1) => {
  loading.value = true
  try {
    const params: AuditLogParams = {
      page,
      per_page: filters.per_page,
      search: filters.search || undefined,
      action: filters.action || undefined,
      date: filters.date || undefined
    }

    const res = await dashboardService.fetchAuditLogs(params)
    if (res.data && res.data.data) {
      logs.value = res.data.data
      if (res.data.meta) {
        Object.assign(pagination, res.data.meta)
      }
    }
  } catch (err) {
    console.error('Failed to fetch audit logs:', err)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchLogs()
})

watch(() => filters.search, () => {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => {
    fetchLogs(1)
  }, 350)
})

watch([() => filters.action, () => filters.date, () => filters.per_page], () => {
  fetchLogs(1)
})

const resetFilters = () => {
  filters.search = ''
  filters.action = ''
  filters.date = ''
  filters.per_page = 15
  fetchLogs(1)
}

const getInitials = (firstName?: string, lastName?: string, email?: string) => {
  if (firstName || lastName) {
    return `${(firstName || '').charAt(0)}${(lastName || '').charAt(0)}`.toUpperCase() || 'U'
  }
  return (email || 'US').substring(0, 2).toUpperCase()
}

const formatDate = (dateStr?: string) => {
  if (!dateStr) return { date: '—', time: '—' }
  try {
    const d = new Date(dateStr)
    return {
      date: d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }),
      time: d.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit' })
    }
  } catch {
    return { date: dateStr, time: '' }
  }
}

const getActionBadgeClass = (action: string) => {
  const lower = action.toLowerCase()
  if (lower.includes('create') || lower.includes('success') || lower.includes('activate')) {
    return 'bg-emerald-50 text-emerald-700 border-emerald-200'
  }
  if (lower.includes('delete') || lower.includes('fail') || lower.includes('deactivate')) {
    return 'bg-red-50 text-red-700 border-red-200'
  }
  if (lower.includes('update') || lower.includes('assign') || lower.includes('edit')) {
    return 'bg-blue-50 text-[#252578] border-blue-200'
  }
  return 'bg-slate-50 text-slate-700 border-slate-200'
}

const formatActionTitle = (action: string) => {
  return action
    .replace(/_/g, ' ')
    .toLowerCase()
    .replace(/\b\w/g, char => char.toUpperCase())
}
</script>

<template>
  <div class="space-y-6 animate-in fade-in duration-300">
    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <!-- STANDARD PAGE HEADER (Pattern B from DESIGN.md)                         -->
    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-[#1E293B] tracking-tight font-sans">
          Security & Activity Audit Trail
        </h1>
        <p class="text-xs text-[#8086A3] mt-1 font-sans">
          Immutable audit log tracking all authentication, user management, and authorization changes.
        </p>
      </div>

      <div class="flex items-center gap-3">
        <button 
          @click="fetchLogs(pagination.current_page)"
          :disabled="loading"
          class="inline-flex items-center gap-2 px-3.5 py-2 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold shadow-sm transition-all cursor-pointer disabled:opacity-50"
          title="Refresh logs"
        >
          <RefreshCw :size="14" :class="{ 'animate-spin': loading }" />
          <span>Refresh</span>
        </button>

        <button 
          @click="resetFilters"
          class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 text-xs font-semibold shadow-sm transition-all cursor-pointer"
        >
          <span>Reset Filters</span>
        </button>
      </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <!-- FILTER TOOLBAR (ONE UI Spec)                                            -->
    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm flex flex-col md:flex-row gap-4 items-center justify-between">
      <!-- Search Input -->
      <div class="relative w-full md:w-80">
        <Search :size="16" class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
        <input 
          v-model="filters.search"
          type="text" 
          placeholder="Search by user, action, IP, or details..."
          class="w-full pl-9 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-[#2E85D8]/20 focus:border-[#2E85D8] transition-all"
        />
      </div>

      <!-- Action & Date Filters -->
      <div class="flex flex-wrap items-center gap-3 w-full md:w-auto">
        <!-- Action Select -->
        <select 
          v-model="filters.action"
          class="h-9 px-3 rounded-lg bg-slate-50 border border-slate-200 text-xs text-slate-700 focus:outline-none focus:ring-2 focus:ring-[#2E85D8]/20 focus:border-[#2E85D8] transition-all cursor-pointer"
        >
          <option value="">All Action Types</option>
          <option value="LOGIN_SUCCESS">Login Success</option>
          <option value="LOGIN_FAILED">Login Failed</option>
          <option value="LOGOUT">Logout</option>
          <option value="USER_CREATED">User Created</option>
          <option value="USER_UPDATED">User Updated</option>
          <option value="USER_STATUS_TOGGLED">User Status Toggled</option>
          <option value="USER_ROLE_ASSIGNED">User Role Assigned</option>
          <option value="ROLE_CREATED">Role Created</option>
          <option value="ROLE_UPDATED">Role Updated</option>
          <option value="ROLE_DELETED">Role Deleted</option>
          <option value="ROLE_PERMISSIONS_SYNCED">Role Permissions Synced</option>
          <option value="DEPARTMENT_CREATED">Department Created</option>
          <option value="DEPARTMENT_UPDATED">Department Updated</option>
          <option value="DEPARTMENT_DELETED">Department Deleted</option>
        </select>

        <!-- Date Filter -->
        <div class="relative">
          <input 
            v-model="filters.date"
            type="date"
            class="h-9 px-3 rounded-lg bg-slate-50 border border-slate-200 text-xs text-slate-700 focus:outline-none focus:ring-2 focus:ring-[#2E85D8]/20 focus:border-[#2E85D8] transition-all"
          />
        </div>

        <!-- Per Page -->
        <select 
          v-model.number="filters.per_page"
          class="h-9 px-3 rounded-lg bg-slate-50 border border-slate-200 text-xs text-slate-700 focus:outline-none focus:ring-2 focus:ring-[#2E85D8]/20 focus:border-[#2E85D8] transition-all cursor-pointer"
        >
          <option :value="10">10 / page</option>
          <option :value="15">15 / page</option>
          <option :value="25">25 / page</option>
          <option :value="50">50 / page</option>
        </select>
      </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <!-- AUDIT TRAIL TABLE (ONE UI Spec)                                         -->
    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="bg-[#F8F9FA] border-b border-slate-100">
              <th class="px-6 py-4 text-[11px] font-bold text-[#94A3B8] uppercase tracking-wider font-sans">
                ACTOR / USER
              </th>
              <th class="px-6 py-4 text-[11px] font-bold text-[#94A3B8] uppercase tracking-wider font-sans">
                ACTION
              </th>
              <th class="px-6 py-4 text-[11px] font-bold text-[#94A3B8] uppercase tracking-wider font-sans">
                DESCRIPTION
              </th>
              <th class="px-6 py-4 text-[11px] font-bold text-[#94A3B8] uppercase tracking-wider font-sans">
                IP ADDRESS
              </th>
              <th class="px-6 py-4 text-[11px] font-bold text-[#94A3B8] uppercase tracking-wider font-sans">
                TIMESTAMP
              </th>
            </tr>
          </thead>

          <tbody class="divide-y divide-slate-100 text-sm">
            <!-- Loading Skeletons -->
            <tr v-if="loading" v-for="i in 5" :key="i" class="animate-pulse">
              <td class="px-6 py-4">
                <div class="flex items-center gap-3">
                  <div class="w-8 h-8 rounded-full bg-slate-200"></div>
                  <div class="space-y-1.5">
                    <div class="h-3 w-28 bg-slate-200 rounded"></div>
                    <div class="h-2.5 w-36 bg-slate-100 rounded"></div>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4"><div class="h-5 w-24 bg-slate-200 rounded-full"></div></td>
              <td class="px-6 py-4"><div class="h-3 w-48 bg-slate-200 rounded"></div></td>
              <td class="px-6 py-4"><div class="h-3 w-20 bg-slate-200 rounded"></div></td>
              <td class="px-6 py-4"><div class="h-3 w-24 bg-slate-200 rounded"></div></td>
            </tr>

            <!-- Empty State -->
            <tr v-else-if="logs.length === 0">
              <td colspan="5" class="px-6 py-12 text-center text-slate-500">
                <Clock :size="32" class="mx-auto mb-2 text-slate-300" />
                <p class="font-medium text-sm">No audit logs matching your search criteria.</p>
              </td>
            </tr>

            <!-- Row Iteration -->
            <tr 
              v-else 
              v-for="log in logs" 
              :key="log.id"
              class="hover:bg-slate-50/70 transition-colors"
            >
              <!-- Actor Cell with Avatar Circle -->
              <td class="px-6 py-3.5">
                <div class="flex items-center gap-3">
                  <div class="w-8 h-8 rounded-full bg-[#252578] flex items-center justify-center text-white text-[11px] font-bold shadow-sm flex-shrink-0">
                    {{ getInitials(log.actor?.first_name, log.actor?.last_name, log.actor?.email) }}
                  </div>
                  <div class="flex flex-col">
                    <span class="text-xs font-bold text-[#1E293B] leading-tight">
                      {{ log.actor?.first_name ? `${log.actor.first_name} ${log.actor.last_name || ''}` : (log.actor?.email || 'System Operation') }}
                    </span>
                    <span class="text-[11px] text-[#8D8B8F] leading-tight mt-0.5">
                      {{ log.actor?.department || log.actor?.role || log.actor?.email || 'Automated Job' }}
                    </span>
                  </div>
                </div>
              </td>

              <!-- Action Badge -->
              <td class="px-6 py-3.5 whitespace-nowrap">
                <span 
                  class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold border"
                  :class="getActionBadgeClass(log.action)"
                >
                  {{ formatActionTitle(log.action) }}
                </span>
              </td>

              <!-- Description -->
              <td class="px-6 py-3.5 text-xs text-slate-700 max-w-md truncate" :title="log.description">
                {{ log.description || 'Action performed.' }}
              </td>

              <!-- IP Address -->
              <td class="px-6 py-3.5 text-xs font-mono text-slate-500 whitespace-nowrap">
                {{ log.ip_address || '—' }}
              </td>

              <!-- Timestamp -->
              <td class="px-6 py-3.5 whitespace-nowrap text-xs">
                <div class="flex flex-col">
                  <span class="font-semibold text-slate-700 leading-tight">
                    {{ formatDate(log.performed_at).date }}
                  </span>
                  <span class="text-[11px] text-slate-400 leading-tight mt-0.5">
                    {{ formatDate(log.performed_at).time }}
                  </span>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination Footer -->
      <div class="p-4 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4 text-xs text-slate-500">
        <div>
          Showing <span class="font-bold text-slate-700">{{ pagination.from || 0 }}</span> to <span class="font-bold text-slate-700">{{ pagination.to || 0 }}</span> of <span class="font-bold text-slate-700">{{ pagination.total }}</span> total records
        </div>

        <div class="flex items-center gap-2">
          <button 
            @click="fetchLogs(pagination.current_page - 1)"
            :disabled="pagination.current_page <= 1 || loading"
            class="p-2 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer transition-all"
            title="Previous page"
          >
            <ChevronLeft :size="16" />
          </button>

          <span class="px-3 py-1 font-semibold text-slate-700">
            Page {{ pagination.current_page }} of {{ pagination.last_page }}
          </span>

          <button 
            @click="fetchLogs(pagination.current_page + 1)"
            :disabled="pagination.current_page >= pagination.last_page || loading"
            class="p-2 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer transition-all"
            title="Next page"
          >
            <ChevronRight :size="16" />
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
