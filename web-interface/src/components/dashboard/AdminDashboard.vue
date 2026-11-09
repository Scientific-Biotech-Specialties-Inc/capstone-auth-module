<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { 
  Users, 
  Shield, 
  Building2, 
  Key, 
  ArrowRight, 
  Clock, 
  RefreshCw,
  MapPin
} from 'lucide-vue-next'
import { dashboardService, type DashboardStats } from '@/services/dashboardService'

const loading = ref(true)
const stats = ref<DashboardStats>({
  kpis: {
    total_users: 0,
    active_users: 0,
    total_roles: 0,
    total_departments: 0,
    total_branches: 0,
    total_permissions: 0,
  },
  recent_audit_logs: [],
})

const fetchStats = async () => {
  loading.value = true
  try {
    const res = await dashboardService.fetchDashboardStats()
    if (res.data && res.data.data) {
      stats.value = res.data.data
    }
  } catch (err) {
    console.error('Failed to load dashboard statistics:', err)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchStats()
})

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
  <div class="space-y-8 animate-in fade-in duration-300">
    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <!-- STANDARD PAGE HEADER (Pattern B from DESIGN.md)                         -->
    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-[#1E293B] tracking-tight font-sans">
          User & Access Governance Dashboard
        </h1>
        <p class="text-xs text-[#8086A3] mt-1 font-sans">
          Centralized authority oversight for user identities, roles, departments, permissions, and security audit metrics.
        </p>
      </div>

      <div class="flex items-center gap-3">
        <button 
          @click="fetchStats"
          :disabled="loading"
          class="inline-flex items-center gap-2 px-3.5 py-2 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold shadow-sm transition-all cursor-pointer disabled:opacity-50"
          title="Refresh statistics"
        >
          <RefreshCw :size="14" :class="{ 'animate-spin': loading }" />
          <span>Refresh</span>
        </button>

        <router-link 
          to="/admin/users/create"
          class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-[#252578] hover:bg-[#1e1e66] text-white text-xs font-semibold shadow-sm hover:shadow-md transition-all active:scale-98"
        >
          <span>+ Add New User</span>
        </router-link>
      </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <!-- SUMMARY METRIC CARDS (Section VI.4 from DESIGN.md)                      -->
    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-6">
      <!-- CARD 1: TOTAL USERS -->
      <div class="bg-white p-6 rounded-[14px] border border-slate-200/80 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 flex flex-col justify-between">
        <div class="flex items-start justify-between">
          <div>
            <span class="text-[11px] font-bold text-[#8086A3] uppercase tracking-wider font-sans">
              TOTAL USERS
            </span>
            <div class="text-3xl font-bold text-[#1E293B] mt-2 font-sans">
              {{ loading ? '...' : stats.kpis.total_users }}
            </div>
          </div>
          <div class="w-11 h-11 rounded-full bg-[#252578]/10 flex items-center justify-center text-[#252578]">
            <Users :size="22" />
          </div>
        </div>

        <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
          <span class="px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-700 font-semibold text-[11px] border border-emerald-200">
            {{ stats.kpis.active_users }} Active
          </span>
          <router-link to="/admin/users" class="text-xs font-semibold text-[#2E85D8] hover:underline flex items-center gap-1">
            <span>View all</span>
            <ArrowRight :size="12" />
          </router-link>
        </div>
      </div>

      <!-- CARD 2: TOTAL ROLES -->
      <div class="bg-white p-6 rounded-[14px] border border-slate-200/80 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 flex flex-col justify-between">
        <div class="flex items-start justify-between">
          <div>
            <span class="text-[11px] font-bold text-[#8086A3] uppercase tracking-wider font-sans">
              ACCESS ROLES
            </span>
            <div class="text-3xl font-bold text-[#1E293B] mt-2 font-sans">
              {{ loading ? '...' : stats.kpis.total_roles }}
            </div>
          </div>
          <div class="w-11 h-11 rounded-full bg-[#2E85D8]/10 flex items-center justify-center text-[#2E85D8]">
            <Shield :size="22" />
          </div>
        </div>

        <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
          <span class="px-2.5 py-0.5 rounded-full bg-blue-50 text-[#252578] font-semibold text-[11px] border border-blue-200">
            RBAC Governed
          </span>
          <router-link to="/admin/roles" class="text-xs font-semibold text-[#2E85D8] hover:underline flex items-center gap-1">
            <span>Configure</span>
            <ArrowRight :size="12" />
          </router-link>
        </div>
      </div>

      <!-- CARD 3: TOTAL DEPARTMENTS -->
      <div class="bg-white p-6 rounded-[14px] border border-slate-200/80 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 flex flex-col justify-between">
        <div class="flex items-start justify-between">
          <div>
            <span class="text-[11px] font-bold text-[#8086A3] uppercase tracking-wider font-sans">
              DEPARTMENTS
            </span>
            <div class="text-3xl font-bold text-[#1E293B] mt-2 font-sans">
              {{ loading ? '...' : stats.kpis.total_departments }}
            </div>
          </div>
          <div class="w-11 h-11 rounded-full bg-emerald-500/10 flex items-center justify-center text-emerald-600">
            <Building2 :size="22" />
          </div>
        </div>

        <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
          <span class="px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-700 font-semibold text-[11px]">
            Org Structure
          </span>
          <router-link to="/admin/departments" class="text-xs font-semibold text-[#2E85D8] hover:underline flex items-center gap-1">
            <span>Manage</span>
            <ArrowRight :size="12" />
          </router-link>
        </div>
      </div>

      <!-- CARD 4: TOTAL BRANCHES -->
      <div class="bg-white p-6 rounded-[14px] border border-slate-200/80 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 flex flex-col justify-between">
        <div class="flex items-start justify-between">
          <div>
            <span class="text-[11px] font-bold text-[#8086A3] uppercase tracking-wider font-sans">
              TOTAL BRANCHES
            </span>
            <div class="text-3xl font-bold text-[#1E293B] mt-2 font-sans">
              {{ loading ? '...' : stats.kpis.total_branches }}
            </div>
          </div>
          <div class="w-11 h-11 rounded-full bg-violet-500/10 flex items-center justify-center text-violet-600">
            <MapPin :size="22" />
          </div>
        </div>

        <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
          <span class="px-2.5 py-0.5 rounded-full bg-violet-50 text-violet-700 font-semibold text-[11px] border border-violet-200">
            Office Sites
          </span>
          <router-link to="/admin/branches" class="text-xs font-semibold text-[#2E85D8] hover:underline flex items-center gap-1">
            <span>Manage</span>
            <ArrowRight :size="12" />
          </router-link>
        </div>
      </div>

      <!-- CARD 5: TOTAL PERMISSIONS -->
      <div class="bg-white p-6 rounded-[14px] border border-slate-200/80 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 flex flex-col justify-between">
        <div class="flex items-start justify-between">
          <div>
            <span class="text-[11px] font-bold text-[#8086A3] uppercase tracking-wider font-sans">
              PERMISSIONS
            </span>
            <div class="text-3xl font-bold text-[#1E293B] mt-2 font-sans">
              {{ loading ? '...' : stats.kpis.total_permissions }}
            </div>
          </div>
          <div class="w-11 h-11 rounded-full bg-amber-500/10 flex items-center justify-center text-amber-600">
            <Key :size="22" />
          </div>
        </div>

        <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
          <span class="px-2.5 py-0.5 rounded-full bg-amber-50 text-amber-700 font-semibold text-[11px] border border-amber-200">
            Fine-Grained
          </span>
          <router-link to="/admin/permissions" class="text-xs font-semibold text-[#2E85D8] hover:underline flex items-center gap-1">
            <span>Matrix</span>
            <ArrowRight :size="12" />
          </router-link>
        </div>
      </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <!-- RECENT AUDIT TRAIL TABLE (Section VI.10 from DESIGN.md)                 -->
    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
      <!-- Table Header & Toolbar -->
      <div class="p-6 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <div class="flex items-center gap-2.5">
            <h2 class="text-base font-bold text-[#1E293B] font-sans">
              Recent Audit Trail Activity
            </h2>
            <span class="px-2 py-0.5 rounded-full bg-slate-100 text-slate-700 text-[11px] font-bold">
              Last {{ stats.recent_audit_logs.length }} Events
            </span>
          </div>
          <p class="text-xs text-[#8086A3] mt-0.5 font-sans">
            Immutable log of identity changes, role assignments, and security operations.
          </p>
        </div>

        <router-link 
          to="/admin/audit-trail"
          class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-[#252578] hover:bg-[#1e1e66] text-white text-xs font-semibold shadow-sm hover:shadow-md transition-all active:scale-98"
        >
          <span>See All Activity</span>
          <ArrowRight :size="14" />
        </router-link>
      </div>

      <!-- Table Content -->
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
            <tr v-else-if="stats.recent_audit_logs.length === 0">
              <td colspan="5" class="px-6 py-12 text-center text-slate-500">
                <Clock :size="32" class="mx-auto mb-2 text-slate-300" />
                <p class="font-medium text-sm">No recent audit log activities recorded.</p>
              </td>
            </tr>

            <!-- Audit Log Rows -->
            <tr 
              v-else 
              v-for="log in stats.recent_audit_logs" 
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

              <!-- Action Badge (Title Case, Medium-Emphasis Pill) -->
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

              <!-- Timestamp (Stacked Two-Line) -->
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
    </div>
  </div>
</template>
