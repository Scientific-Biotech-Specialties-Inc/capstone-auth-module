<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { 
  Users, 
  LayoutDashboard, 
  Shield, 
  Key, 
  Building2, 
  LogOut, 
  PanelLeft, 
  Bell, 
  Home, 
  ChevronDown, 
  History,
  MapPin
} from 'lucide-vue-next'
import sidebarLogo from '@/assets/logo.png'
import { useAuth } from '@/composables/useAuth'
import ConfirmModal from '@/components/common/ConfirmModal.vue'

const router = useRouter()
const route = useRoute()
const { logout, clearLocalAuth, fetchCurrentUser } = useAuth()

// ── User Identity ─────────────────────────────────────────────────────────────
function getStoredUser(): Record<string, any> {
  const raw = localStorage.getItem('user')
  if (!raw || raw === 'undefined' || raw === 'null') return {}
  try {
    return JSON.parse(raw) ?? {}
  } catch {
    return {}
  }
}
const user = ref<any>(getStoredUser())

const userName = computed(() => {
  if (user.value.profile) {
    const first = user.value.profile.first_name || ''
    const last = user.value.profile.last_name || ''
    if (first || last) {
      return `${first} ${last}`.trim()
    }
  }
  return user.value.name || user.value.email || 'Admin User'
})

const userRole = computed(() => {
  return user.value.profile?.role?.name || user.value.role || 'System Administrator'
})

const userInitials = computed(() => {
  if (user.value.profile) {
    const first = user.value.profile.first_name || ''
    const last = user.value.profile.last_name || ''
    if (first || last) {
      return `${first.charAt(0)}${last.charAt(0)}`.toUpperCase()
    }
  }
  const name = userName.value
  if (name) {
    const parts = name.split(' ')
    if (parts.length > 1) {
      return `${parts[0][0]}${parts[1][0]}`.toUpperCase()
    }
    return name.substring(0, 2).toUpperCase()
  }
  return 'AD'
})

// ── Sidebar State ─────────────────────────────────────────────────────────────
const isSidebarCollapsed = ref(false)
const toggleSidebar = () => {
  isSidebarCollapsed.value = !isSidebarCollapsed.value
}

// ── User Dropdown Menu ────────────────────────────────────────────────────────
const isUserMenuOpen = ref(false)
const toggleUserMenu = () => {
  isUserMenuOpen.value = !isUserMenuOpen.value
}
const closeUserMenu = () => {
  isUserMenuOpen.value = false
}

// ── Logout Handlers & Confirmation Modal ──────────────────────────────────────
const showLogoutConfirmModal = ref(false)
const isLoggingOut = ref(false)

const handleLogout = () => {
  closeUserMenu()
  showLogoutConfirmModal.value = true
}

const confirmLogout = async () => {
  try {
    isLoggingOut.value = true
    await logout()
    showLogoutConfirmModal.value = false
    router.push({ name: 'login', query: { message: 'Successfully logged out.' } })
  } finally {
    isLoggingOut.value = false
  }
}

// ── Idle Logout Logic (2 hours) ───────────────────────────────────────────────
const IDLE_TIMEOUT = 2 * 60 * 60 * 1000
let idleTimer: any = null

const resetIdleTimer = () => {
  if (idleTimer) clearTimeout(idleTimer)
  idleTimer = setTimeout(() => {
    console.log('User idle for 2 hours, logging out...')
    clearLocalAuth()
    router.push('/')
  }, IDLE_TIMEOUT)
}

const handleClickOutside = (e: MouseEvent) => {
  const target = e.target as HTMLElement
  if (!target.closest('.user-menu-container')) {
    closeUserMenu()
  }
}

onMounted(async () => {
  const events = ['mousemove', 'keydown', 'click', 'scroll', 'mousedown', 'touchstart']
  events.forEach(event => window.addEventListener(event, resetIdleTimer))
  window.addEventListener('click', handleClickOutside)
  resetIdleTimer()

  try {
    const res = await fetchCurrentUser()
    if (res.success && res.user) {
      user.value = res.user
    }
  } catch (err) {
    // handled internally
  }
})

onUnmounted(() => {
  const events = ['mousemove', 'keydown', 'click', 'scroll', 'mousedown', 'touchstart']
  events.forEach(event => window.removeEventListener(event, resetIdleTimer))
  window.removeEventListener('click', handleClickOutside)
  if (idleTimer) clearTimeout(idleTimer)
})
</script>

<template>
  <div class="min-h-screen bg-[#F8F9FA] text-[#1E293B] font-sans antialiased flex">
    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <!-- 1. SIDEBAR COMPONENT (Solid Brand Primary #252578, ONE UI Spec)        -->
    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <aside 
      class="fixed top-0 left-0 h-screen bg-[#252578] text-white transition-all duration-300 ease-in-out z-30 flex flex-col justify-between select-none shadow-[4px_0_24px_rgba(37,37,120,0.18)]"
      :class="isSidebarCollapsed ? 'w-[88px]' : 'w-64'"
    >
      <!-- Top Brand Lockup & Navigation -->
      <div class="flex flex-col flex-1 overflow-y-auto overflow-x-hidden scrollbar-none">
        <!-- Subsystem Brand Lockup (72px aligned with top header) -->
        <div class="h-18 px-6 border-b border-white/10 flex items-center gap-3.5 flex-shrink-0">
          <router-link to="/home" class="flex items-center gap-2.5 group w-full" :title="isSidebarCollapsed ? 'SBSI Home' : ''">
            <div class="h-8 w-8 rounded-lg bg-white flex items-center justify-center flex-shrink-0 group-hover:bg-slate-100 transition-all p-1 shadow-sm">
              <img :src="sidebarLogo" alt="SBSI" class="h-5 w-auto object-contain" />
            </div>
            
            <div v-show="!isSidebarCollapsed" class="flex flex-col overflow-hidden transition-all duration-200">
              <span class="font-bold text-[13px] tracking-wide text-white leading-tight uppercase font-sans">
                USER & ACCESS
              </span>
              <span class="text-[11px] font-normal text-white/70 leading-tight tracking-normal font-sans">
                MANAGEMENT SYSTEM
              </span>
            </div>
          </router-link>
        </div>

        <!-- Navigation Links Stack -->
        <nav class="flex flex-col gap-6 px-3.5 py-6">
          <!-- CATEGORY 1: MAIN -->
          <div class="flex flex-col gap-1.5">
            <span 
              v-show="!isSidebarCollapsed" 
              class="px-3 text-[10.5px] font-bold text-white/50 uppercase tracking-[0.12em] mb-1 font-sans"
            >
              MAIN
            </span>
            <router-link 
              to="/admin" 
              class="sidebar-nav-item"
              :class="{ 'sidebar-nav-active': route.path === '/admin' }"
              :title="isSidebarCollapsed ? 'Dashboard' : ''"
            >
              <LayoutDashboard :size="20" class="flex-shrink-0" />
              <span v-show="!isSidebarCollapsed" class="sidebar-nav-label">Dashboard</span>
            </router-link>
          </div>

          <!-- CATEGORY 2: ACCESS CONTROL -->
          <div class="flex flex-col gap-1.5">
            <span 
              v-show="!isSidebarCollapsed" 
              class="px-3 text-[10.5px] font-bold text-white/50 uppercase tracking-[0.12em] mb-1 font-sans"
            >
              ACCESS CONTROL
            </span>
            <router-link 
              to="/admin/users" 
              class="sidebar-nav-item"
              :class="{ 'sidebar-nav-active': route.path === '/admin/users' }"
              :title="isSidebarCollapsed ? 'User Management' : ''"
            >
              <Users :size="20" class="flex-shrink-0" />
              <span v-show="!isSidebarCollapsed" class="sidebar-nav-label">User Management</span>
            </router-link>

            <router-link 
              to="/admin/roles" 
              class="sidebar-nav-item"
              :class="{ 'sidebar-nav-active': route.path.startsWith('/admin/roles') }"
              :title="isSidebarCollapsed ? 'Role Management' : ''"
            >
              <Shield :size="20" class="flex-shrink-0" />
              <span v-show="!isSidebarCollapsed" class="sidebar-nav-label">Role Management</span>
            </router-link>

            <router-link 
              to="/admin/permissions" 
              class="sidebar-nav-item"
              :class="{ 'sidebar-nav-active': route.path === '/admin/permissions' }"
              :title="isSidebarCollapsed ? 'Permissions' : ''"
            >
              <Key :size="20" class="flex-shrink-0" />
              <span v-show="!isSidebarCollapsed" class="sidebar-nav-label">Permissions</span>
            </router-link>
          </div>

          <!-- CATEGORY 3: ORGANIZATION -->
          <div class="flex flex-col gap-1.5">
            <span 
              v-show="!isSidebarCollapsed" 
              class="px-3 text-[10.5px] font-bold text-white/50 uppercase tracking-[0.12em] mb-1 font-sans"
            >
              ORGANIZATION
            </span>
            <router-link 
              to="/admin/departments" 
              class="sidebar-nav-item"
              :class="{ 'sidebar-nav-active': route.path === '/admin/departments' }"
              :title="isSidebarCollapsed ? 'Departments' : ''"
            >
              <Building2 :size="20" class="flex-shrink-0" />
              <span v-show="!isSidebarCollapsed" class="sidebar-nav-label">Departments</span>
            </router-link>

            <router-link 
              to="/admin/branches" 
              class="sidebar-nav-item"
              :class="{ 'sidebar-nav-active': route.path.startsWith('/admin/branches') }"
              :title="isSidebarCollapsed ? 'Branches' : ''"
            >
              <MapPin :size="20" class="flex-shrink-0" />
              <span v-show="!isSidebarCollapsed" class="sidebar-nav-label">Branch Management</span>
            </router-link>
          </div>

          <!-- CATEGORY 4: SECURITY & AUDIT -->
          <div class="flex flex-col gap-1.5">
            <span 
              v-show="!isSidebarCollapsed" 
              class="px-3 text-[10.5px] font-bold text-white/50 uppercase tracking-[0.12em] mb-1 font-sans"
            >
              SECURITY & AUDIT
            </span>
            <router-link 
              to="/admin/audit-trail" 
              class="sidebar-nav-item"
              :class="{ 'sidebar-nav-active': route.path.startsWith('/admin/audit-trail') }"
              :title="isSidebarCollapsed ? 'Audit Trail' : ''"
            >
              <History :size="20" class="flex-shrink-0" />
              <span v-show="!isSidebarCollapsed" class="sidebar-nav-label">Audit Trail</span>
            </router-link>
          </div>
        </nav>
      </div>

      <!-- Bottom Pinned Action Section -->
      <div class="p-3.5 border-t border-white/10 flex flex-col gap-1.5 bg-[#202068]/60">
        <router-link 
          to="/home" 
          class="sidebar-nav-item !text-white/80 hover:!text-white hover:!bg-white/10"
          :title="isSidebarCollapsed ? 'Back to Hub' : ''"
        >
          <Home :size="20" class="flex-shrink-0" />
          <span v-show="!isSidebarCollapsed" class="sidebar-nav-label">Back to Operations Hub</span>
        </router-link>

        <button 
          @click="handleLogout"
          class="sidebar-nav-item !text-red-300 hover:!text-red-100 hover:!bg-red-500/20 text-left w-full cursor-pointer"
          :title="isSidebarCollapsed ? 'Logout' : ''"
        >
          <LogOut :size="20" class="flex-shrink-0" />
          <span v-show="!isSidebarCollapsed" class="sidebar-nav-label">Logout</span>
        </button>
      </div>
    </aside>

    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <!-- 2. MAIN LAYOUT WRAPPER (Top Header + Dynamic Viewport)                -->
    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <div 
      class="flex-1 flex flex-col min-h-screen transition-all duration-300 ease-in-out"
      :class="isSidebarCollapsed ? 'pl-[88px]' : 'pl-64'"
    >
      <!-- Top Header Component (Pure White, 72px / h-18, Shadow-sm, ONE UI Spec) -->
      <header class="sticky top-0 z-20 h-18 min-h-[72px] bg-white border-b border-slate-200/80 shadow-sm px-6 flex items-center justify-between">
        <!-- Left Section: Toggle -->
        <div class="flex items-center gap-4">
          <button 
            @click="toggleSidebar"
            class="p-2 rounded-lg text-slate-500 hover:text-slate-800 hover:bg-slate-100 transition-colors cursor-pointer"
            title="Toggle Sidebar"
          >
            <PanelLeft :size="20" />
          </button>
        </div>

        <!-- Right Section: Notifications & User Profile Card -->
        <div class="flex items-center gap-4">
          <!-- Notification Bell -->
          <button 
            class="relative p-2 rounded-xl text-slate-500 hover:text-slate-800 hover:bg-slate-100 transition-colors cursor-pointer"
            title="Notifications"
          >
            <Bell :size="20" />
            <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-[#2E85D8] rounded-full ring-2 ring-white"></span>
          </button>

          <div class="h-6 w-px bg-slate-200"></div>

          <!-- User Profile Card Trigger -->
          <div class="relative user-menu-container">
            <div 
              @click="toggleUserMenu"
              class="flex items-center gap-3 p-1.5 pr-2.5 rounded-xl hover:bg-slate-100/80 transition-all cursor-pointer select-none"
            >
              <!-- Avatar Circle: Dark Blue (#252578) with #A7A7C8 border ring and bold white initials -->
              <div class="w-9 h-9 rounded-full bg-[#252578] border-2 border-[#A7A7C8] flex items-center justify-center text-white text-xs font-bold shadow-sm">
                {{ userInitials }}
              </div>

              <div class="hidden sm:flex flex-col text-left">
                <span class="text-xs font-semibold text-[#1E293B] leading-tight">
                  {{ userName }}
                </span>
                <span class="text-[11px] font-normal text-[#8D8B8F] leading-tight mt-0.5">
                  {{ userRole }}
                </span>
              </div>

              <ChevronDown :size="16" class="text-slate-400 hidden sm:block" />
            </div>

            <!-- User Profile Dropdown Menu (ONE UI Spec) -->
            <div 
              v-if="isUserMenuOpen"
              class="absolute right-0 top-full mt-2 w-56 bg-white border border-slate-200 rounded-xl shadow-lg py-2 z-50 animate-in fade-in slide-in-from-top-1 duration-150"
            >
              <div class="px-4 py-2 border-b border-slate-100 sm:hidden">
                <p class="text-xs font-bold text-slate-800">{{ userName }}</p>
                <p class="text-[11px] text-slate-500">{{ userRole }}</p>
              </div>

              <router-link 
                to="/home" 
                @click="closeUserMenu"
                class="flex items-center gap-2.5 px-4 py-2.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 hover:text-[#252578] transition-colors"
              >
                <Home :size="16" class="text-slate-400" />
                <span>Back to Operations Hub</span>
              </router-link>

              <div class="h-px bg-slate-100 my-1"></div>

              <button 
                @click="handleLogout"
                class="w-full flex items-center gap-2.5 px-4 py-2.5 text-xs font-semibold text-red-600 hover:bg-red-50 transition-colors text-left cursor-pointer"
              >
                <LogOut :size="16" class="text-red-500" />
                <span>Logout</span>
              </button>
            </div>
          </div>
        </div>
      </header>

      <!-- Dynamic Page Canvas -->
      <main class="flex-1 p-8 max-w-7xl w-full mx-auto">
        <router-view v-slot="{ Component }">
          <transition 
            name="fade" 
            mode="out-in"
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0 translate-y-1.5"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 -translate-y-1.5"
          >
            <component :is="Component" />
          </transition>
        </router-view>
      </main>
    </div>

    <!-- Global Logout Confirmation Modal -->
    <ConfirmModal
      :show="showLogoutConfirmModal"
      title="Confirm Logout"
      description="Are you sure you want to end your active session? You will be redirected to the sign-in screen."
      confirmLabel="Log Out"
      cancelLabel="Cancel"
      variant="logout"
      :isSubmitting="isLoggingOut"
      @close="showLogoutConfirmModal = false"
      @confirm="confirmLogout"
    />
  </div>
</template>

<style scoped>
/* ── Sidebar Navigation Items ──────────────────────────────────────────────── */
.sidebar-nav-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 10px 14px;
  border-radius: 8px;
  color: rgba(255, 255, 255, 0.72);
  font-size: 13px;
  font-weight: 500;
  text-decoration: none;
  border: 1px solid transparent;
  transition: all 0.2s ease;
  white-space: nowrap;
}

.sidebar-nav-item:hover {
  background-color: rgba(255, 255, 255, 0.10);
  color: #ffffff;
}

/* ONE UI Specification: Brand Deep #3A3A85 with Brand Deep Border #4E4E91 */
.sidebar-nav-active {
  background-color: #3A3A85 !important;
  border-color: #4E4E91 !important;
  color: #ffffff !important;
  font-weight: 600;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.14);
}

.sidebar-nav-label {
  font-size: 13px;
  letter-spacing: -0.01em;
}

/* Hide scrollbar for Chrome, Safari, Edge, Opera */
.scrollbar-none::-webkit-scrollbar {
  display: none;
}
/* Hide scrollbar for IE, Edge and Firefox */
.scrollbar-none {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
</style>
