<template>
  <div class="bg-mesh-gradient text-on-surface min-h-screen flex flex-col font-sans">
    
    <!-- TopNavBar Component -->
    <nav class="sticky top-0 z-50 bg-white/80 backdrop-blur-md border-b border-outline-variant/30 h-20 w-full">
      <div class="max-w-[1440px] mx-auto h-full flex justify-between items-center px-container-padding">
        
        <div class="flex items-center space-x-12">
          <div class="flex items-center space-x-3">
            <img src="/images/sbsi-logo-blue.webp" alt="Scientific Biotech Specialties, Inc." class="h-6 object-contain" />
            <div class="h-6 w-px bg-slate-200 hidden sm:block"></div>
            <span class="text-xl font-bold text-primary tracking-tight">SBSI Unified Operations Center</span>
          </div>
        </div>

        <div class="flex items-center space-x-6">
          <div class="flex items-center space-x-4">
            
            <div class="flex items-center space-x-3 group cursor-pointer">
              <div class="text-right">
                <p class="text-sm font-bold text-white-500 leading-none">{{ userName }}</p>
                <p class="text-[11px] text-on-surface-variant uppercase tracking-wider font-medium mt-1">{{ userRole }}</p>
              </div>
              <div class="w-10 h-10 rounded-full bg-primary flex items-center justify-center border-2 border-primary-dark group-hover:border-secondary transition-all text-white font-bold">
                {{ userInitials }}
              </div>
            </div>

            <button 
              @click="handleLogout"
              class="flex items-center space-x-2 text-on-surface-variant hover:text-red-500 transition-colors px-3 py-2 rounded-lg hover:bg-red-50 group"
            >
              <LogOut :size="20" />
              <span class="text-sm font-medium">Logout</span>
            </button>
            
          </div>
        </div>
        
      </div>
    </nav>

    <!-- Main Content Area -->
    <main class="flex-1 flex flex-col bg-gradient-to-b from-white to-[#f4f7fc]">
    
      <!-- System Hub Grid -->
      <section class="px-container-padding py-8 max-w-[1440px] mx-auto w-full">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          
          <div 
            v-for="sys in subsystems" 
            :key="sys.title"
            class="glass-card group p-6 rounded-2xl flex flex-col h-full relative overflow-hidden bg-gradient-to-br from-white to-[#fafcff] shadow-md hover:shadow-xl hover:shadow-primary/10 transition-all duration-500"
          >
            <div :class="['absolute top-0 right-0 w-24 h-24 rounded-bl-[80px] -z-10 transition-colors', sys.bgBase, sys.bgHover]"></div>
            
            <div class="flex items-start justify-between mb-5">
              <div :class="['w-12 h-12 rounded-xl flex items-center justify-center shadow-md ring-4 ring-white/50', sys.iconBg, sys.iconColor]">
                <component :is="sys.icon" :size="24" stroke-width="1.5" />
              </div>
              <span :class="['text-[10px] font-bold uppercase tracking-widest px-3 py-1 rounded-full backdrop-blur-md border border-current/10', sys.badgeClass]">
                {{ sys.badge }}
              </span>
            </div>

            <h3 class="text-xl font-bold text-primary mb-3">{{ sys.title }}</h3>
            <p class="text-sm text-on-surface-variant mb-6 flex-1 leading-relaxed">
              {{ sys.desc }}
            </p>

            <div class="pt-4 border-t border-outline-variant/30 flex justify-end">
              <button 
                @click="openModule(sys.title)"
                class="w-full sm:w-auto flex items-center justify-center space-x-2 bg-primary text-white px-6 py-3 rounded-lg font-bold hover:bg-primary-dark transition-all active:scale-95 shadow-sm hover:shadow-md hover:shadow-primary/20 group/btn tracking-wide uppercase text-xs"
              >
                <span>Launch System</span>
                <ArrowRight class="transition-transform group-hover/btn:translate-x-1" :size="16" />
              </button>
            </div>
            
          </div>
          
        </div>
      </section>
      
    </main>

    <!-- Footer Component -->
    <footer class="bg-[#07071a] border-t border-white/5 py-8 px-4 md:px-10 mt-auto w-full">
      <div class="max-w-[1300px] mx-auto flex flex-col md:flex-row items-center justify-between gap-4">
        <img src="/images/sbsi-logo-variation1-white.webp" alt="SBSI Logo" class="h-6 object-contain opacity-65" />
        <span class="text-white/35 text-[13px] font-normal text-center md:text-left">
          © 2026 Scientific Biotech Specialties, Inc. All rights reserved.
        </span>
      </div>
    </footer>
    
  </div>
</template>

<script setup lang="ts">
import { computed, ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuth } from '@/composables/useAuth'
import { isItAdmin } from '@/utils/role'
import { toast } from '@/composables/useToast'
import { FileText, Banknote, Ticket, BarChart3, ShieldCheck, LogOut, ArrowRight } from 'lucide-vue-next'

const router = useRouter()
const { logout, fetchCurrentUser } = useAuth()

// Load user from localStorage
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
  return user.value.name || user.value.email || 'User'
})

const userRole = computed(() => {
  return user.value.profile?.role?.name || user.value.role || 'User'
})

const userInitials = computed(() => {
  if (user.value.profile) {
    const first = user.value.profile.first_name || ''
    const last = user.value.profile.last_name || ''
    if (first || last) {
      const firstInitial = first.substring(0, 1).toUpperCase()
      const lastInitial = last.substring(0, 1).toUpperCase()
      return `${firstInitial}${lastInitial}`
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
  return 'US'
})

// Refresh the user from the authoritative backend so role-gated cards reflect
// the current server-side role instead of the one-time localStorage snapshot.
onMounted(async () => {
  try {
    const result = await fetchCurrentUser()
    if (result.success) {
      user.value = result.user
    }
  } finally {
    // fetchCurrentUser handles errors internally; this guards the await.
  }
})

const subsystems = computed(() => {
  const base = [
    {
      title: 'Contract Management',
      desc: 'End-to-end lifecycle management for all legal agreements. Securely draft, review, and archive documents with intelligent digital signature tracking and automated renewal alerts.',
      icon: FileText,
      iconBg: 'bg-blue-400 shadow-blue-400/20',
      iconColor: 'text-white',
      badge: 'Contracts',
      badgeClass: 'text-primary/40 bg-primary/5',
      bgHover: 'group-hover:bg-primary/10',
      bgBase: 'bg-primary/5'
    },
    {
      title: 'Smart Expense Reimbursement',
      desc: 'AI-powered financial workflows. Scan receipts instantly, automate approval routing, and ensure rapid disbursement with detailed real-time spend analytics and tax compliance reporting.',
      icon: Banknote,
      iconBg: 'bg-secondary shadow-secondary/20',
      iconColor: 'text-white',
      badge: 'Expenses',
      badgeClass: 'text-secondary/40 bg-secondary/5',
      bgHover: 'group-hover:bg-secondary/10',
      bgBase: 'bg-secondary/5'
    },
    {
      title: 'Ticketing System',
      desc: 'A centralized engine for internal requests. Resolve issues efficiently with priority-based queuing, automated SLA escalation, and comprehensive knowledge base integration for staff.',
      icon: Ticket,
      iconBg: 'bg-blue-400 shadow-blue-400/20',
      iconColor: 'text-white',
      badge: 'Ticketing',
      badgeClass: 'text-primary/40 bg-primary/5',
      bgHover: 'group-hover:bg-primary/10',
      bgBase: 'bg-primary/5'
    },
    {
      title: 'Productivity Report System',
      desc: 'Interactive dashboards for departmental oversight. Generate high-impact visual reports on KPIs, operational efficiency, and team output for strategic decision-making.',
      icon: BarChart3,
      iconBg: 'bg-secondary shadow-secondary/20',
      iconColor: 'text-white',
      badge: 'Productivity Report',
      badgeClass: 'text-secondary/40 bg-secondary/5',
      bgHover: 'group-hover:bg-secondary/10',
      bgBase: 'bg-secondary/5'
    }
  ]

  if (isItAdmin(user.value)) {
    base.unshift({
      title: 'User & Access Management',
      desc: 'Centralized identity authority, role-based access control (RBAC), fine-grained permission matrices, and departmental structure governance.',
      icon: ShieldCheck,
      iconBg: 'bg-[#252578] shadow-[#252578]/25',
      iconColor: 'text-white',
      badge: 'Administration',
      badgeClass: 'text-[#252578] bg-[#252578]/10 border-[#252578]/20',
      bgHover: 'group-hover:bg-[#252578]/10',
      bgBase: 'bg-[#252578]/5'
    })
  }

  return base
})

const handleLogout = async () => {
  await logout()
  router.push({ name: 'login', query: { message: 'Successfully logged out.' } })
}

const openModule = (subsystemTitle: string) => {
    const cmsUrl = import.meta.env.VITE_CMS_URL || ''
    const sermsUrl = import.meta.env.VITE_SERMS_URL || ''
    const prsUrl = import.meta.env.VITE_PRS_URL || ''

    if (subsystemTitle === 'Contract Management') {
      if (userRole.value === 'IT Admin') {
        router.push('/admin')
      } else if (userRole.value === 'Admin' || userRole.value === 'Super Admin' || userRole.value === 'CEO') {
        window.location.href = `${cmsUrl}/cms/auth/callback?state=/cms/admin/dashboard`
      } else if (userRole.value === 'Manager' || userRole.value === 'Finance Manager') {
        window.location.href = `${cmsUrl}/cms/auth/callback?state=/cms/manager/dashboard`
      } else if (userRole.value === 'Sales' || userRole.value === 'Employee' || userRole.value === 'Finance Employee' || userRole.value === 'Finance') {
        window.location.href = `${cmsUrl}/cms/auth/callback?state=/cms/sales/dashboard`
      } else {
        window.location.href = `${cmsUrl}/cms/auth/callback?state=/cms/dashboard`
      }
    } else if (subsystemTitle === 'Smart Expense Reimbursement') {
      if (userRole.value === 'Admin') {
        window.location.href = `${sermsUrl}/serms/auth/callback?state=/serms/admin/dashboard&message=Successfully%20logged%20in`
      } else if (userRole.value === 'Manager' || userRole.value === 'Finance Manager') {
        window.location.href = `${sermsUrl}/serms/auth/callback?state=/serms/manager/dashboard&message=Successfully%20logged%20in`
      } else if (userRole.value === 'Sales' || userRole.value === 'Employee' || userRole.value === 'Finance Employee' || userRole.value === 'Finance') {
        window.location.href = `${sermsUrl}/serms/auth/callback?state=/serms/sales/dashboard&message=Successfully%20logged%20in`
      } else {
        window.location.href = `${sermsUrl}/serms/auth/callback?state=/serms/dashboard&message=Successfully%20logged%20in`
      }
    } else if (subsystemTitle === 'Productivity Report System') {
      window.location.href = `${prsUrl}/prs/auth/callback?state=/prs/app/dashboard&message=Successfully%20logged%20in`
    } else if (subsystemTitle === 'User & Access Management') {
      router.push('/admin')
    } else if (subsystemTitle === 'Ticketing System') {
      window.location.href = '/ticketing/'
    } else {
      toast.info(`${subsystemTitle} is not active in this development environment.`)
    }
}
</script>

<style scoped>
/* Scoped styles are kept minimal as we use Tailwind classes for almost everything now. */
</style>
