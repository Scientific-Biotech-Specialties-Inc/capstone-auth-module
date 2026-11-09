<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import {
  ArrowLeft,
  Shield,
  Check,
  Loader2,
  Save,
  AlertCircle,
  Search,
  FileText,
  BarChart3,
  Banknote,
  Ticket,
  Layers
} from 'lucide-vue-next'
import { roleService } from '@/services/roleService'
import { permissionService } from '@/services/permissionService'
import { notifySuccess, notifyApiError } from '@/lib/notify'
import type { Role, Permission } from '@/types'

const route = useRoute()
const router = useRouter()

const roleId = computed(() => Number(route.params.id))
const role = ref<Role | null>(null)
const allPermissions = ref<Permission[]>([])
const selectedPermissionIds = ref<number[]>([])

const loading = ref(true)
const saving = ref(false)
const errorMessage = ref('')
const searchQuery = ref('')

// ─── Subsystem Definitions ────────────────────────────────────────────────────
interface SystemDef {
  key: string
  name: string
  fullName: string
  description: string
  icon: any
  available: boolean
  badgeColor: string
}

const SYSTEMS: SystemDef[] = [
  {
    key: 'cms',
    name: 'CMS',
    fullName: 'Contract Management System',
    description: 'Contract lifecycle, drafting, approval workflows, vendor reviews, and AI risk analysis.',
    icon: FileText,
    available: true,
    badgeColor: 'bg-[#252578]/10 text-[#252578] border-[#252578]/20',
  },
  {
    key: 'prs',
    name: 'PRS',
    fullName: 'Productivity Report System',
    description: 'Daily itineraries, field submissions, supervisor validation, leaderboards, and KPI reports.',
    icon: BarChart3,
    available: true,
    badgeColor: 'bg-emerald-50 text-emerald-700 border-emerald-200',
  },
  {
    key: 'serms',
    name: 'SERMS',
    fullName: 'Smart Expense Reimbursement',
    description: 'AI receipt scans, reimbursement claims, expense liquidation, and cash advance tracking.',
    icon: Banknote,
    available: true,
    badgeColor: 'bg-amber-50 text-amber-700 border-amber-200',
  },
  {
    key: 'ticketing',
    name: 'Ticketing',
    fullName: 'Ticketing System',
    description: 'Centralized IT helpdesk, issue escalation queues, priority SLAs, and knowledge base.',
    icon: Ticket,
    available: true,
    badgeColor: 'bg-sky-50 text-sky-700 border-sky-200',
  },
]

// ─── Role Subsystem Access State ──────────────────────────────────────────────
const activeSystems = ref<Set<string>>(new Set())

const loadSystemAccess = () => {
  if (!roleId.value) return
  const key = `role_system_access_${roleId.value}`
  const saved = localStorage.getItem(key)
  if (saved) {
    try {
      const parsed = JSON.parse(saved)
      if (Array.isArray(parsed)) {
        activeSystems.value = new Set(parsed.filter((k: string) => k !== 'hrm'))
        return
      }
    } catch (e) {
      console.warn('Failed to parse saved system access', e)
    }
  }
  // All subsystems remain disabled by default
  activeSystems.value = new Set()
}

const toggleSystemAccess = (systemKey: string) => {
  const current = new Set(activeSystems.value)
  if (current.has(systemKey)) {
    current.delete(systemKey)
  } else {
    current.add(systemKey)
  }
  activeSystems.value = current
}

// ─── Load Role and Permissions ────────────────────────────────────────────────
const loadData = async () => {
  loading.value = true
  errorMessage.value = ''
  try {
    // 1. Fetch Role Details
    try {
      const roleRes = await roleService.getRole(roleId.value)
      role.value = roleRes.data?.data ?? roleRes.data
    } catch {
      // Fallback: fetch from roles list
      const rolesRes = await roleService.fetchRoles({ per_page: 100 })
      const list = rolesRes.data?.data ?? rolesRes.data ?? []
      role.value = list.find((r: Role) => r.id === roleId.value) || null
    }

    if (!role.value) {
      errorMessage.value = `Role with ID #${roleId.value} not found.`
      loading.value = false
      return
    }

    // 2. Fetch all system permissions (increase limit to ensure everything is fetched)
    const permsRes = await permissionService.fetchPermissions({ per_page: 250 })
    const permsData = permsRes.data?.data ?? permsRes.data
    allPermissions.value = Array.isArray(permsData) ? permsData : []

    // 3. Fetch currently assigned permissions for this role
    const assignedRes = await roleService.fetchRolePermissions(roleId.value)
    const assignedData = assignedRes.data?.data ?? assignedRes.data
    selectedPermissionIds.value = Array.isArray(assignedData)
      ? assignedData.map((item: any) => (typeof item === 'object' ? item.id : item))
      : []

    // 4. Load persisted subsystem access
    loadSystemAccess()
  } catch (err: any) {
    console.error('Failed to load role permissions', err)
    errorMessage.value = err.response?.data?.message || 'Failed to load permissions. Please check connection.'
  } finally {
    loading.value = false
  }
}

// ─── Permission Categorization & Segregation ──────────────────────────────────
// Group CMS permissions into clean functional modules
const CMS_MODULES = [
  {
    key: 'contracts',
    title: 'Contracts Lifecycle',
    description: 'Creation, editing, lifecycle tracking, and approval sign-off',
    slugs: ['cms.contracts.view', 'cms.contracts.create', 'cms.contracts.edit', 'cms.contracts.delete', 'cms.contracts.approve'],
  },
  {
    key: 'users',
    title: 'User & Access Control',
    description: 'CMS user management and role delegation within contracts',
    slugs: ['cms.users.view', 'cms.users.create', 'cms.users.edit', 'cms.users.delete', 'cms.roles.manage'],
  },
  {
    key: 'partners',
    title: 'Business Partners & Suppliers',
    description: 'Vendor directory, supplier profiles, and stakeholder records',
    slugs: ['cms.partners.view', 'cms.partners.create', 'cms.partners.edit', 'cms.partners.delete'],
  },
  {
    key: 'ai',
    title: 'AI & Automated Risk Analysis',
    description: 'OCR document processing, automatic drafting, and clause risk assessment',
    slugs: [
      'cms.ocr.upload',
      'cms.ocr.process',
      'cms.ocr.review',
      'cms.contracts.generate',
      'cms.risk.assess',
      'cms.risk.view',
      'cms.risk.approve',
    ],
  },
  {
    key: 'templates',
    title: 'Contract Templates',
    description: 'Standardized clause templates and boilerplate configurations',
    slugs: ['cms.templates.manage', 'cms.templates.use'],
  },
]

// Group PRS permissions
const PRS_MODULES = [
  {
    key: 'submissions',
    title: 'Submissions & Field Validation',
    description: 'Itinerary submissions, supervisor validation, and field logs',
    slugs: ['prs.submissions.create', 'prs.submission.validate', 'prs.itineraries.manage'],
  },
  {
    key: 'reports',
    title: 'Reports & Operational Dashboard',
    description: 'Performance overview, activity audit trails, and departmental KPIs',
    slugs: ['prs.dashboard.view', 'prs.activity-log.view', 'prs.leaderboard.view', 'prs.reports.view', 'prs.files.view'],
  },
  {
    key: 'settings',
    title: 'System Settings',
    description: 'PRS user assignment, department configuration, and institutional records',
    slugs: [
      'prs.settings.users.manage',
      'prs.settings.departments.manage',
      'prs.settings.products.manage',
      'prs.settings.institutions.manage',
    ],
  },
]

// Helpers to classify all permissions
const getSystemOfPerm = (perm: Permission): string => {
  if (perm.system) return perm.system.toLowerCase()
  if (perm.slug.startsWith('cms.')) return 'cms'
  if (perm.slug.startsWith('prs.')) return 'prs'
  if (perm.slug.startsWith('serms.')) return 'serms'
  if (perm.slug.startsWith('ticketing.') || perm.slug.startsWith('ts.')) return 'ticketing'
  return 'auth'
}

// Filtered permissions by search query
const filteredPermissions = computed(() => {
  const query = searchQuery.value.trim().toLowerCase()
  if (!query) return allPermissions.value
  return allPermissions.value.filter(
    p => p.name.toLowerCase().includes(query) || p.slug.toLowerCase().includes(query) || (p.description && p.description.toLowerCase().includes(query))
  )
})

// Permissions segregated by system
const cmsPermissions = computed(() => filteredPermissions.value.filter(p => getSystemOfPerm(p) === 'cms'))
const prsPermissions = computed(() => filteredPermissions.value.filter(p => getSystemOfPerm(p) === 'prs'))
const sermsPermissions = computed(() => filteredPermissions.value.filter(p => getSystemOfPerm(p) === 'serms'))
const ticketingPermissions = computed(() => filteredPermissions.value.filter(p => getSystemOfPerm(p) === 'ticketing'))
const authPermissions = computed(() => filteredPermissions.value.filter(p => getSystemOfPerm(p) === 'auth'))

// Categorized CMS items
const cmsGrouped = computed(() => {
  const handledIds = new Set<number>()
  const groups: Array<{ key: string; title: string; description: string; perms: Permission[] }> = []

  for (const mod of CMS_MODULES) {
    const matched = cmsPermissions.value.filter(p => mod.slugs.includes(p.slug))
    matched.forEach(p => handledIds.add(p.id))
    if (matched.length > 0) {
      groups.push({
        key: mod.key,
        title: mod.title,
        description: mod.description,
        perms: matched,
      })
    }
  }

  // Any remaining CMS permissions not covered in explicit modules
  const remaining = cmsPermissions.value.filter(p => !handledIds.has(p.id))
  if (remaining.length > 0) {
    groups.push({
      key: 'other_cms',
      title: 'Additional CMS Features',
      description: 'Other contract management permissions',
      perms: remaining,
    })
  }

  return groups
})

// Categorized PRS items
const prsGrouped = computed(() => {
  const handledIds = new Set<number>()
  const groups: Array<{ key: string; title: string; description: string; perms: Permission[] }> = []

  for (const mod of PRS_MODULES) {
    const matched = prsPermissions.value.filter(p => mod.slugs.includes(p.slug))
    matched.forEach(p => handledIds.add(p.id))
    if (matched.length > 0) {
      groups.push({
        key: mod.key,
        title: mod.title,
        description: mod.description,
        perms: matched,
      })
    }
  }

  const remaining = prsPermissions.value.filter(p => !handledIds.has(p.id))
  if (remaining.length > 0) {
    groups.push({
      key: 'other_prs',
      title: 'Additional PRS Features',
      description: 'Other productivity report permissions',
      perms: remaining,
    })
  }

  return groups
})

// ─── Toggle Actions ───────────────────────────────────────────────────────────
const isPermSelected = (permId: number) => selectedPermissionIds.value.includes(permId)

const togglePermission = (permId: number) => {
  const current = [...selectedPermissionIds.value]
  const idx = current.indexOf(permId)
  if (idx >= 0) {
    current.splice(idx, 1)
  } else {
    current.push(permId)
  }
  selectedPermissionIds.value = current
}

const areAllSelected = (perms: Permission[]) => {
  if (perms.length === 0) return false
  return perms.every(p => selectedPermissionIds.value.includes(p.id))
}

const toggleGroup = (perms: Permission[]) => {
  const allOn = areAllSelected(perms)
  const current = new Set(selectedPermissionIds.value)
  if (allOn) {
    perms.forEach(p => current.delete(p.id))
  } else {
    perms.forEach(p => current.add(p.id))
  }
  selectedPermissionIds.value = Array.from(current)
}

const toggleEntireSystem = (perms: Permission[]) => {
  toggleGroup(perms)
}

// ─── Save Changes ─────────────────────────────────────────────────────────────
const handleSave = async () => {
  if (!role.value) return
  saving.value = true
  errorMessage.value = ''

  try {
    // 1. Sync backend permissions via API
    await roleService.syncRolePermissions(role.value.id, selectedPermissionIds.value)

    // 2. Persist system access settings to localStorage
    const systemArray = Array.from(activeSystems.value)
    localStorage.setItem(`role_system_access_${role.value.id}`, JSON.stringify(systemArray))

    // 3. Also write role name system access for cross-module sync with CMS
    if (role.value.name) {
      localStorage.setItem(`cms_system_access_${role.value.name}`, JSON.stringify(systemArray))
    }

    notifySuccess('Role permissions saved successfully')
  } catch (err: any) {
    console.error('Failed to save permissions', err)
    notifyApiError(err, 'role', 'Failed to update permissions. Please try again.')
  } finally {
    saving.value = false
  }
}

onMounted(() => {
  loadData()
})
</script>

<template>
  <div class="space-y-8 pb-16">
    <!-- Top Navigation Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div class="flex items-center gap-4">
        <button
          @click="router.push('/admin/roles')"
          class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-white border border-slate-200 text-slate-600 hover:text-slate-900 hover:bg-slate-50 transition-all font-medium text-sm shadow-sm"
        >
          <ArrowLeft :size="16" />
          <span>Back to Roles</span>
        </button>

        <div>
          <div class="flex items-center gap-2.5">
            <h1 class="text-2xl font-bold text-slate-900">
              {{ role?.name ? `${role.name} Permissions` : 'Role Permissions' }}
            </h1>
            <span
              v-if="role"
              class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider bg-[#252578]/10 text-[#252578] border border-[#252578]/20"
            >
              Role #{{ role.id }}
            </span>
          </div>
          <p class="text-sm text-slate-500 mt-0.5">
            {{ role?.description || 'Configure accessible subsystems and granular permission capabilities.' }}
          </p>
        </div>
      </div>

      <!-- Action Button -->
      <div class="flex items-center gap-3">
        <button
          @click="handleSave"
          :disabled="saving || loading || !role"
          class="inline-flex items-center gap-2 bg-gradient-to-r from-[#252578] to-[#3b82f6] text-white px-6 py-2.5 rounded-xl text-sm font-semibold hover:shadow-lg transition-all active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed shadow-md"
        >
          <Loader2 v-if="saving" :size="18" class="animate-spin" />
          <Save v-else :size="18" />
          <span>{{ saving ? 'Saving Changes...' : 'Save Permissions' }}</span>
        </button>
      </div>
    </div>

    <!-- Alert Messages -->
    <div
      v-if="errorMessage"
      class="flex items-center gap-3 p-4 rounded-2xl bg-red-50 border border-red-200 text-red-800 text-sm animate-in fade-in"
    >
      <AlertCircle :size="18" class="text-red-600 shrink-0" />
      <span class="font-medium">{{ errorMessage }}</span>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="py-20 flex flex-col items-center justify-center gap-4 bg-white rounded-3xl border border-slate-200 shadow-sm">
      <Loader2 :size="40" class="animate-spin text-[#252578]" />
      <p class="text-slate-600 font-semibold text-sm">Loading role details and system permissions...</p>
    </div>

    <template v-else-if="role">
      <!-- ═════════════════════════════════════════════════════════════════════ -->
      <!-- SECTION 1: SUBSYSTEM ACCESS (CAN THE ROLE VISIT CMS, PRS, ETC.?)    -->
      <!-- ═════════════════════════════════════════════════════════════════════ -->
      <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-6 border-b border-slate-100 bg-slate-50/50 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
          <div>
            <div class="flex items-center gap-2.5">
              <div class="w-8 h-8 rounded-lg bg-[#252578]/10 text-[#252578] flex items-center justify-center font-bold">
                <Layers :size="18" />
              </div>
              <h2 class="text-lg font-bold text-slate-900">Subsystem Access</h2>
            </div>
            <p class="text-xs text-slate-500 mt-1">
              Select which subsystems users with the <strong>{{ role.name }}</strong> role are permitted to visit and log into from the Operations Hub.
            </p>
          </div>

          <div class="flex items-center gap-2">
            <span class="text-xs font-semibold px-3 py-1 rounded-full bg-slate-100 text-slate-600 border border-slate-200">
              {{ activeSystems.size }} Active System{{ activeSystems.size === 1 ? '' : 's' }}
            </span>
          </div>
        </div>

        <div class="p-6 grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
          <div
            v-for="sys in SYSTEMS"
            :key="sys.key"
            @click="sys.available && toggleSystemAccess(sys.key)"
            class="relative rounded-2xl border transition-all duration-200 p-5 flex flex-col justify-between overflow-hidden select-none"
            :class="[
              !sys.available
                ? 'bg-slate-50/60 border-slate-200/80 opacity-60 cursor-not-allowed'
                : activeSystems.has(sys.key)
                ? 'bg-[#252578]/[0.03] border-[#252578]/40 shadow-sm ring-1 ring-[#252578]/15 cursor-pointer hover:shadow-md'
                : 'bg-white border-slate-200 hover:border-slate-300 hover:shadow-md cursor-pointer'
            ]"
          >
            <div>
              <!-- Top Row: Icon & Toggle Switch -->
              <div class="flex items-center justify-between gap-3 mb-3.5">
                <div
                  class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 transition-colors"
                  :class="activeSystems.has(sys.key) && sys.available ? 'bg-[#252578] text-white shadow-sm' : 'bg-slate-100 text-slate-500'"
                >
                  <component :is="sys.icon" :size="20" stroke-width="2" />
                </div>

                <!-- Toggle Switch -->
                <button
                  v-if="sys.available"
                  type="button"
                  @click.stop="toggleSystemAccess(sys.key)"
                  class="w-11 h-6 rounded-full flex items-center p-0.5 transition-colors focus:outline-none focus:ring-2 focus:ring-[#252578]/20 shrink-0 cursor-pointer"
                  :class="activeSystems.has(sys.key) ? 'bg-[#252578] justify-end' : 'bg-slate-200 justify-start'"
                  :title="activeSystems.has(sys.key) ? 'Click to disable access' : 'Click to allow access'"
                >
                  <span class="w-5 h-5 rounded-full bg-white shadow-sm transform transition-transform" />
                </button>
              </div>

              <!-- Name & Status Badge -->
              <div class="mb-2">
                <div class="flex items-center justify-between gap-2">
                  <span class="font-bold text-slate-900 text-base leading-tight">{{ sys.name }}</span>
                  <span
                    class="text-[10px] font-bold px-2 py-0.5 rounded-full uppercase tracking-wider shrink-0"
                    :class="sys.available
                      ? (activeSystems.has(sys.key) ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-500')
                      : 'bg-slate-200 text-slate-600'"
                  >
                    {{ sys.available ? (activeSystems.has(sys.key) ? 'Allowed' : 'Disabled') : 'Coming Soon' }}
                  </span>
                </div>
                <p class="text-xs text-slate-500 font-medium mt-1 leading-snug line-clamp-1" :title="sys.fullName">
                  {{ sys.fullName }}
                </p>
              </div>

              <!-- Description -->
              <p class="text-xs text-slate-500 leading-relaxed mb-4">
                {{ sys.description }}
              </p>
            </div>

            <!-- Card Footer Status -->
            <div class="pt-3 border-t border-slate-100/80 flex items-center justify-between text-xs mt-auto">
              <span class="text-slate-400 font-medium">Environment Access</span>
              <span
                class="font-semibold"
                :class="activeSystems.has(sys.key) && sys.available ? 'text-[#252578]' : 'text-slate-400'"
              >
                {{ sys.available ? (activeSystems.has(sys.key) ? 'Authorized' : 'Restricted') : 'Planned' }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- ═════════════════════════════════════════════════════════════════════ -->
      <!-- SECTION 2: GRANULAR PERMISSIONS SEGREGATED PER SYSTEM                -->
      <!-- ═════════════════════════════════════════════════════════════════════ -->
      <div class="space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
          <div>
            <h2 class="text-xl font-bold text-slate-900">Segregated System Permissions</h2>
            <p class="text-xs text-slate-500 mt-1">
              Configure fine-grained capability flags organized distinctly by subsystem.
            </p>
          </div>

          <!-- Quick Permission Search -->
          <div class="relative w-full sm:w-80">
            <Search :size="16" class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Filter permissions by name or slug..."
              class="w-full pl-9 pr-4 py-2 bg-white border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#252578]/15 focus:border-[#252578] transition-all shadow-sm"
            />
          </div>
        </div>

        <!-- ─────────────────────────────────────────────────────────────────── -->
        <!-- 1. CMS (CONTRACT MANAGEMENT SYSTEM) PERMISSIONS                     -->
        <!-- ─────────────────────────────────────────────────────────────────── -->
        <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
          <!-- System Header Strip -->
          <div class="px-6 py-4 bg-gradient-to-r from-[#252578]/5 via-[#3b82f6]/5 to-transparent border-b border-slate-100 flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-3">
              <div class="w-9 h-9 rounded-xl bg-[#252578] text-white flex items-center justify-center shadow-sm">
                <FileText :size="18" />
              </div>
              <div>
                <div class="flex items-center gap-2">
                  <h3 class="text-base font-bold text-slate-900">Contract Management System (CMS)</h3>
                  <span class="text-xs font-bold px-2 py-0.5 rounded-full bg-[#252578]/10 text-[#252578] border border-[#252578]/20">
                    CMS
                  </span>
                </div>
                <p class="text-xs text-slate-500 mt-0.5">
                  Granular permissions governing legal contracts, partners, AI risk features, and templates.
                </p>
              </div>
            </div>

            <div class="flex items-center gap-3">
              <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-slate-100 text-slate-600">
                {{ cmsPermissions.filter(p => isPermSelected(p.id)).length }} / {{ cmsPermissions.length }} Enabled
              </span>
              <button
                type="button"
                @click="toggleEntireSystem(cmsPermissions)"
                class="text-xs font-semibold px-3 py-1.5 rounded-lg border border-slate-200 hover:bg-slate-50 text-slate-700 transition-colors"
              >
                {{ areAllSelected(cmsPermissions) ? 'Deselect All CMS' : 'Select All CMS' }}
              </button>
            </div>
          </div>

          <!-- CMS Group Modules -->
          <div class="p-6 space-y-6 divide-y divide-slate-100">
            <div
              v-for="grp in cmsGrouped"
              :key="grp.key"
              class="pt-6 first:pt-0"
            >
              <div class="flex items-center justify-between mb-3">
                <div>
                  <h4 class="text-sm font-bold text-slate-900">{{ grp.title }}</h4>
                  <p class="text-xs text-slate-400">{{ grp.description }}</p>
                </div>
                <button
                  type="button"
                  @click="toggleGroup(grp.perms)"
                  class="text-xs font-medium text-[#3b82f6] hover:text-[#252578] transition-colors"
                >
                  {{ areAllSelected(grp.perms) ? 'Deselect group' : 'Select group' }}
                </button>
              </div>

              <!-- Permission Pills Grid -->
              <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                <div
                  v-for="perm in grp.perms"
                  :key="perm.id"
                  @click="togglePermission(perm.id)"
                  class="group flex items-start gap-3 p-3.5 rounded-2xl border cursor-pointer select-none transition-all duration-200"
                  :class="[
                    isPermSelected(perm.id)
                      ? 'bg-[#252578]/[0.04] border-[#252578]/30 shadow-xs ring-1 ring-[#252578]/10'
                      : 'bg-white border-slate-200 hover:border-slate-300 hover:bg-slate-50/50'
                  ]"
                >
                  <!-- Checkbox Box -->
                  <div
                    class="w-5 h-5 rounded-md border flex items-center justify-center shrink-0 mt-0.5 transition-colors"
                    :class="[
                      isPermSelected(perm.id)
                        ? 'bg-[#252578] border-[#252578] text-white shadow-xs'
                        : 'bg-white border-slate-300 group-hover:border-slate-400'
                    ]"
                  >
                    <Check v-if="isPermSelected(perm.id)" :size="12" stroke-width="3" />
                  </div>

                  <!-- Details -->
                  <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-slate-900 group-hover:text-[#252578] transition-colors line-clamp-1">
                      {{ perm.name }}
                    </p>
                    <p class="text-[11px] font-mono text-slate-400 truncate mt-0.5">
                      {{ perm.slug }}
                    </p>
                    <p v-if="perm.description" class="text-xs text-slate-500 mt-1 line-clamp-2">
                      {{ perm.description }}
                    </p>
                  </div>
                </div>
              </div>
            </div>

            <div v-if="cmsPermissions.length === 0" class="py-8 text-center text-slate-400 text-sm">
              No CMS permissions matching the filter.
            </div>
          </div>
        </div>

        <!-- ─────────────────────────────────────────────────────────────────── -->
        <!-- 2. PRS (PRODUCTIVITY REPORT SYSTEM) PERMISSIONS                     -->
        <!-- ─────────────────────────────────────────────────────────────────── -->
        <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
          <div class="px-6 py-4 bg-gradient-to-r from-emerald-500/5 via-teal-500/5 to-transparent border-b border-slate-100 flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-3">
              <div class="w-9 h-9 rounded-xl bg-emerald-600 text-white flex items-center justify-center shadow-sm">
                <BarChart3 :size="18" />
              </div>
              <div>
                <div class="flex items-center gap-2">
                  <h3 class="text-base font-bold text-slate-900">Productivity Report System (PRS)</h3>
                  <span class="text-xs font-bold px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200">
                    PRS
                  </span>
                </div>
                <p class="text-xs text-slate-500 mt-0.5">
                  Itinerary management, supervisor validation, KPI analytics, and department settings.
                </p>
              </div>
            </div>

            <div class="flex items-center gap-3">
              <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-slate-100 text-slate-600">
                {{ prsPermissions.filter(p => isPermSelected(p.id)).length }} / {{ prsPermissions.length }} Enabled
              </span>
              <button
                type="button"
                @click="toggleEntireSystem(prsPermissions)"
                class="text-xs font-semibold px-3 py-1.5 rounded-lg border border-slate-200 hover:bg-slate-50 text-slate-700 transition-colors"
              >
                {{ areAllSelected(prsPermissions) ? 'Deselect All PRS' : 'Select All PRS' }}
              </button>
            </div>
          </div>

          <div class="p-6 space-y-6 divide-y divide-slate-100">
            <div
              v-for="grp in prsGrouped"
              :key="grp.key"
              class="pt-6 first:pt-0"
            >
              <div class="flex items-center justify-between mb-3">
                <div>
                  <h4 class="text-sm font-bold text-slate-900">{{ grp.title }}</h4>
                  <p class="text-xs text-slate-400">{{ grp.description }}</p>
                </div>
                <button
                  type="button"
                  @click="toggleGroup(grp.perms)"
                  class="text-xs font-medium text-emerald-600 hover:text-emerald-800 transition-colors"
                >
                  {{ areAllSelected(grp.perms) ? 'Deselect group' : 'Select group' }}
                </button>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                <div
                  v-for="perm in grp.perms"
                  :key="perm.id"
                  @click="togglePermission(perm.id)"
                  class="group flex items-start gap-3 p-3.5 rounded-2xl border cursor-pointer select-none transition-all duration-200"
                  :class="[
                    isPermSelected(perm.id)
                      ? 'bg-emerald-50/50 border-emerald-300 shadow-xs ring-1 ring-emerald-200/50'
                      : 'bg-white border-slate-200 hover:border-slate-300 hover:bg-slate-50/50'
                  ]"
                >
                  <div
                    class="w-5 h-5 rounded-md border flex items-center justify-center shrink-0 mt-0.5 transition-colors"
                    :class="[
                      isPermSelected(perm.id)
                        ? 'bg-emerald-600 border-emerald-600 text-white shadow-xs'
                        : 'bg-white border-slate-300 group-hover:border-slate-400'
                    ]"
                  >
                    <Check v-if="isPermSelected(perm.id)" :size="12" stroke-width="3" />
                  </div>

                  <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-slate-900 group-hover:text-emerald-700 transition-colors line-clamp-1">
                      {{ perm.name }}
                    </p>
                    <p class="text-[11px] font-mono text-slate-400 truncate mt-0.5">
                      {{ perm.slug }}
                    </p>
                    <p v-if="perm.description" class="text-xs text-slate-500 mt-1 line-clamp-2">
                      {{ perm.description }}
                    </p>
                  </div>
                </div>
              </div>
            </div>

            <div v-if="prsPermissions.length === 0" class="py-8 text-center text-slate-400 text-sm">
              No PRS permissions matching the filter.
            </div>
          </div>
        </div>

        <!-- ─────────────────────────────────────────────────────────────────── -->
        <!-- 3. SERMS (SMART EXPENSE REIMBURSEMENT) PERMISSIONS                  -->
        <!-- ─────────────────────────────────────────────────────────────────── -->
        <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
          <div class="px-6 py-4 bg-gradient-to-r from-amber-500/5 via-yellow-500/5 to-transparent border-b border-slate-100 flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-3">
              <div class="w-9 h-9 rounded-xl bg-amber-600 text-white flex items-center justify-center shadow-sm">
                <Banknote :size="18" />
              </div>
              <div>
                <div class="flex items-center gap-2">
                  <h3 class="text-base font-bold text-slate-900">Smart Expense Reimbursement (SERMS)</h3>
                  <span class="text-xs font-bold px-2 py-0.5 rounded-full bg-amber-50 text-amber-700 border border-amber-200">
                    SERMS
                  </span>
                </div>
                <p class="text-xs text-slate-500 mt-0.5">
                  Receipt processing, financial claims, disbursement approval, and cash advance requests.
                </p>
              </div>
            </div>

            <div class="flex items-center gap-3">
              <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-slate-100 text-slate-600">
                {{ sermsPermissions.filter(p => isPermSelected(p.id)).length }} / {{ sermsPermissions.length }} Enabled
              </span>
              <button
                type="button"
                @click="toggleEntireSystem(sermsPermissions)"
                class="text-xs font-semibold px-3 py-1.5 rounded-lg border border-slate-200 hover:bg-slate-50 text-slate-700 transition-colors"
              >
                {{ areAllSelected(sermsPermissions) ? 'Deselect All SERMS' : 'Select All SERMS' }}
              </button>
            </div>
          </div>

          <div class="p-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
              <div
                v-for="perm in sermsPermissions"
                :key="perm.id"
                @click="togglePermission(perm.id)"
                class="group flex items-start gap-3 p-3.5 rounded-2xl border cursor-pointer select-none transition-all duration-200"
                :class="[
                  isPermSelected(perm.id)
                    ? 'bg-amber-50/50 border-amber-300 shadow-xs ring-1 ring-amber-200/50'
                    : 'bg-white border-slate-200 hover:border-slate-300 hover:bg-slate-50/50'
                ]"
              >
                <div
                  class="w-5 h-5 rounded-md border flex items-center justify-center shrink-0 mt-0.5 transition-colors"
                  :class="[
                    isPermSelected(perm.id)
                      ? 'bg-amber-600 border-amber-600 text-white shadow-xs'
                      : 'bg-white border-slate-300 group-hover:border-slate-400'
                  ]"
                >
                  <Check v-if="isPermSelected(perm.id)" :size="12" stroke-width="3" />
                </div>

                <div class="flex-1 min-w-0">
                  <p class="text-sm font-semibold text-slate-900 group-hover:text-amber-700 transition-colors line-clamp-1">
                    {{ perm.name }}
                  </p>
                  <p class="text-[11px] font-mono text-slate-400 truncate mt-0.5">
                    {{ perm.slug }}
                  </p>
                  <p v-if="perm.description" class="text-xs text-slate-500 mt-1 line-clamp-2">
                    {{ perm.description }}
                  </p>
                </div>
              </div>
            </div>

            <div v-if="sermsPermissions.length === 0" class="py-6 text-center text-slate-400 text-sm">
              No SERMS permissions configured.
            </div>
          </div>
        </div>

        <!-- ─────────────────────────────────────────────────────────────────── -->
        <!-- 4. TICKETING (TICKETING SYSTEM) PERMISSIONS                         -->
        <!-- ─────────────────────────────────────────────────────────────────── -->
        <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
          <div class="px-6 py-4 bg-gradient-to-r from-sky-500/5 via-blue-500/5 to-transparent border-b border-slate-100 flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-3">
              <div class="w-9 h-9 rounded-xl bg-sky-600 text-white flex items-center justify-center shadow-sm">
                <Ticket :size="18" />
              </div>
              <div>
                <div class="flex items-center gap-2">
                  <h3 class="text-base font-bold text-slate-900">Ticketing System (TS)</h3>
                  <span class="text-xs font-bold px-2 py-0.5 rounded-full bg-sky-50 text-sky-700 border border-sky-200">
                    TS
                  </span>
                </div>
                <p class="text-xs text-slate-500 mt-0.5">
                  Internal support helpdesk, issue escalation queues, priority SLAs, and ticket management.
                </p>
              </div>
            </div>

            <div class="flex items-center gap-3">
              <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-slate-100 text-slate-600">
                {{ ticketingPermissions.filter(p => isPermSelected(p.id)).length }} / {{ ticketingPermissions.length }} Enabled
              </span>
              <button
                v-if="ticketingPermissions.length > 0"
                type="button"
                @click="toggleEntireSystem(ticketingPermissions)"
                class="text-xs font-semibold px-3 py-1.5 rounded-lg border border-slate-200 hover:bg-slate-50 text-slate-700 transition-colors"
              >
                {{ areAllSelected(ticketingPermissions) ? 'Deselect All TS' : 'Select All TS' }}
              </button>
            </div>
          </div>

          <div class="p-6">
            <div v-if="ticketingPermissions.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
              <div
                v-for="perm in ticketingPermissions"
                :key="perm.id"
                @click="togglePermission(perm.id)"
                class="group flex items-start gap-3 p-3.5 rounded-2xl border cursor-pointer select-none transition-all duration-200"
                :class="[
                  isPermSelected(perm.id)
                    ? 'bg-sky-50/50 border-sky-300 shadow-xs ring-1 ring-sky-200/50'
                    : 'bg-white border-slate-200 hover:border-slate-300 hover:bg-slate-50/50'
                ]"
              >
                <div
                  class="w-5 h-5 rounded-md border flex items-center justify-center shrink-0 mt-0.5 transition-colors"
                  :class="[
                    isPermSelected(perm.id)
                      ? 'bg-sky-600 border-sky-600 text-white shadow-xs'
                      : 'bg-white border-slate-300 group-hover:border-slate-400'
                  ]"
                >
                  <Check v-if="isPermSelected(perm.id)" :size="12" stroke-width="3" />
                </div>

                <div class="flex-1 min-w-0">
                  <p class="text-sm font-semibold text-slate-900 group-hover:text-sky-700 transition-colors line-clamp-1">
                    {{ perm.name }}
                  </p>
                  <p class="text-[11px] font-mono text-slate-400 truncate mt-0.5">
                    {{ perm.slug }}
                  </p>
                  <p v-if="perm.description" class="text-xs text-slate-500 mt-1 line-clamp-2">
                    {{ perm.description }}
                  </p>
                </div>
              </div>
            </div>

            <div v-else class="py-6 text-center text-slate-400 text-sm">
              No granular Ticketing permissions configured. Subsystem access can be toggled above.
            </div>
          </div>
        </div>

        <!-- ─────────────────────────────────────────────────────────────────── -->
        <!-- 5. AUTH & CORE PLATFORM PERMISSIONS                                 -->
        <!-- ─────────────────────────────────────────────────────────────────── -->
        <div v-if="authPermissions.length > 0" class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
          <div class="px-6 py-4 bg-gradient-to-r from-purple-500/5 to-transparent border-b border-slate-100 flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-3">
              <div class="w-9 h-9 rounded-xl bg-purple-600 text-white flex items-center justify-center shadow-sm">
                <Shield :size="18" />
              </div>
              <div>
                <div class="flex items-center gap-2">
                  <h3 class="text-base font-bold text-slate-900">Auth &amp; Platform Administration</h3>
                  <span class="text-xs font-bold px-2 py-0.5 rounded-full bg-purple-50 text-purple-700 border border-purple-200">
                    Platform
                  </span>
                </div>
                <p class="text-xs text-slate-500 mt-0.5">
                  Core user accounts, role definitions, system audit trails, and department structures.
                </p>
              </div>
            </div>

            <div class="flex items-center gap-3">
              <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-slate-100 text-slate-600">
                {{ authPermissions.filter(p => isPermSelected(p.id)).length }} / {{ authPermissions.length }} Enabled
              </span>
              <button
                type="button"
                @click="toggleEntireSystem(authPermissions)"
                class="text-xs font-semibold px-3 py-1.5 rounded-lg border border-slate-200 hover:bg-slate-50 text-slate-700 transition-colors"
              >
                {{ areAllSelected(authPermissions) ? 'Deselect All' : 'Select All' }}
              </button>
            </div>
          </div>

          <div class="p-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
              <div
                v-for="perm in authPermissions"
                :key="perm.id"
                @click="togglePermission(perm.id)"
                class="group flex items-start gap-3 p-3.5 rounded-2xl border cursor-pointer select-none transition-all duration-200"
                :class="[
                  isPermSelected(perm.id)
                    ? 'bg-purple-50/50 border-purple-300 shadow-xs ring-1 ring-purple-200/50'
                    : 'bg-white border-slate-200 hover:border-slate-300 hover:bg-slate-50/50'
                ]"
              >
                <div
                  class="w-5 h-5 rounded-md border flex items-center justify-center shrink-0 mt-0.5 transition-colors"
                  :class="[
                    isPermSelected(perm.id)
                      ? 'bg-purple-600 border-purple-600 text-white shadow-xs'
                      : 'bg-white border-slate-300 group-hover:border-slate-400'
                  ]"
                >
                  <Check v-if="isPermSelected(perm.id)" :size="12" stroke-width="3" />
                </div>

                <div class="flex-1 min-w-0">
                  <p class="text-sm font-semibold text-slate-900 group-hover:text-purple-700 transition-colors line-clamp-1">
                    {{ perm.name }}
                  </p>
                  <p class="text-[11px] font-mono text-slate-400 truncate mt-0.5">
                    {{ perm.slug }}
                  </p>
                  <p v-if="perm.description" class="text-xs text-slate-500 mt-1 line-clamp-2">
                    {{ perm.description }}
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>


      </div>
    </template>
  </div>
</template>
