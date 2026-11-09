<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, watch, toRef } from 'vue'
import { X, Loader2, Shield, ChevronDown, Check } from 'lucide-vue-next'
import { useBodyScrollLock } from '@/composables/useBodyScrollLock'
import type { User, Role } from '@/types'

const props = defineProps<{
  modelValue: boolean
  user: User | null
  roles: Role[]
  selectedRoleId: number | string
  isAssigning: boolean
}>()

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  'update:selectedRoleId': [value: number | string]
  'submit': []
}>()

useBodyScrollLock(toRef(props, 'modelValue'))

const isDropdownOpen = ref(false)
const dropdownRef = ref<HTMLElement | null>(null)

const selectedRole = computed(() => {
  return props.roles.find(r => r.id === Number(props.selectedRoleId)) || null
})

const closeModal = () => {
  isDropdownOpen.value = false
  emit('update:modelValue', false)
}

const toggleDropdown = () => {
  isDropdownOpen.value = !isDropdownOpen.value
}

const selectRole = (roleId: number) => {
  emit('update:selectedRoleId', roleId)
  isDropdownOpen.value = false
}

const handleDocumentClick = (e: MouseEvent) => {
  if (dropdownRef.value && !dropdownRef.value.contains(e.target as Node)) {
    isDropdownOpen.value = false
  }
}

const handleKeydown = (e: KeyboardEvent) => {
  if (e.key === 'Escape') {
    if (isDropdownOpen.value) {
      isDropdownOpen.value = false
    } else {
      closeModal()
    }
  }
}

watch(() => props.modelValue, (isOpen) => {
  if (!isOpen) {
    isDropdownOpen.value = false
  }
})

onMounted(() => {
  window.addEventListener('click', handleDocumentClick)
  window.addEventListener('keydown', handleKeydown)
})

onUnmounted(() => {
  window.removeEventListener('click', handleDocumentClick)
  window.removeEventListener('keydown', handleKeydown)
})
</script>

<template>
  <Teleport to="body">
    <div v-if="modelValue" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
      <!-- Full screen backdrop covering header and sidebar -->
      <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm transition-opacity" @click="closeModal"></div>
      
      <div class="relative bg-white w-full max-w-md rounded-3xl shadow-2xl border border-slate-100 animate-in fade-in zoom-in duration-200 flex flex-col overflow-visible">
        <!-- Header -->
        <div class="p-6 border-b border-slate-100 flex items-center justify-between flex-shrink-0">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-indigo-50 text-[#252578] flex items-center justify-center flex-shrink-0">
              <Shield :size="20" />
            </div>
            <div>
              <h2 class="text-lg font-bold text-slate-900 font-sans">Assign Role</h2>
              <p class="text-xs text-slate-500 mt-0.5 font-sans">Updating role for <span class="font-semibold text-slate-700">{{ user?.email }}</span></p>
            </div>
          </div>
          <button @click="closeModal" class="p-2 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100 transition-all cursor-pointer">
            <X :size="18" />
          </button>
        </div>

        <!-- Body -->
        <div class="p-6 space-y-6 overflow-visible">
          <!-- User Summary Card -->
          <div class="flex items-center gap-4 p-4 bg-slate-50 rounded-2xl border border-slate-100">
            <div class="w-12 h-12 rounded-full bg-[#252578] flex items-center justify-center font-bold text-white shadow-sm flex-shrink-0">
              {{ (user?.profile?.first_name || 'U').substring(0, 1).toUpperCase() }}{{ (user?.profile?.last_name || '').substring(0, 1).toUpperCase() }}
            </div>
            <div class="min-w-0">
              <p class="font-bold text-slate-900 truncate">{{ user?.profile?.first_name }} {{ user?.profile?.last_name }}</p>
              <p class="text-xs text-slate-500 font-medium truncate">Current: {{ user?.profile?.role?.name || 'No Role' }}</p>
            </div>
          </div>

          <!-- Custom Designed Role Dropdown (Opens Downwards) -->
          <div ref="dropdownRef" class="space-y-2 relative">
            <label class="text-xs font-semibold text-slate-700">Select New Role</label>
            
            <!-- Trigger Button -->
            <button
              type="button"
              @click.stop="toggleDropdown"
              class="w-full h-11 px-3.5 rounded-xl bg-slate-50 border text-sm font-medium flex items-center justify-between transition-all cursor-pointer select-none"
              :class="isDropdownOpen 
                ? 'border-[#252578] ring-2 ring-[#252578]/15 bg-white text-slate-900 shadow-sm' 
                : 'border-slate-200 text-slate-800 hover:border-slate-300 hover:bg-white'"
            >
              <div class="flex items-center gap-2.5 min-w-0">
                <Shield :size="17" :class="selectedRole ? 'text-[#252578]' : 'text-slate-400'" />
                <span class="truncate" :class="{ 'text-slate-400 font-normal': !selectedRole }">
                  {{ selectedRole ? selectedRole.name : 'Choose a role...' }}
                </span>
              </div>
              <ChevronDown 
                :size="18" 
                class="text-slate-400 transition-transform duration-200 flex-shrink-0"
                :class="{ 'rotate-180 text-[#252578]': isDropdownOpen }"
              />
            </button>

            <!-- Floating Downward Dropdown Menu -->
            <div
              v-if="isDropdownOpen"
              class="absolute top-full left-0 right-0 mt-1.5 z-40 bg-white border border-slate-200/90 rounded-2xl shadow-2xl p-1.5 max-h-56 overflow-y-auto space-y-0.5 animate-in fade-in slide-in-from-top-1 duration-150"
            >
              <div v-if="roles.length === 0" class="py-4 text-center text-xs text-slate-400">
                No roles available
              </div>
              <button
                v-for="role in roles"
                :key="role.id"
                type="button"
                @click.stop="selectRole(role.id)"
                class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl text-xs font-semibold text-left transition-all cursor-pointer group"
                :class="selectedRoleId === role.id
                  ? 'bg-[#EFF6FF] text-[#252578] font-bold'
                  : 'text-slate-700 hover:bg-slate-50 hover:text-slate-900'"
              >
                <div class="flex items-center gap-2.5 min-w-0">
                  <Shield 
                    :size="15" 
                    :class="selectedRoleId === role.id ? 'text-[#252578]' : 'text-slate-400 group-hover:text-slate-600'" 
                  />
                  <span class="truncate">{{ role.name }}</span>
                </div>
                <Check v-if="selectedRoleId === role.id" :size="15" class="text-[#252578] flex-shrink-0" />
              </button>
            </div>
          </div>

          <!-- Actions -->
          <div class="pt-2 flex gap-3">
            <button 
              type="button"
              @click="closeModal"
              class="flex-1 px-5 py-2.5 text-sm font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-xl transition-colors cursor-pointer"
            >
              Cancel
            </button>
            <button 
              type="button"
              @click="emit('submit')"
              :disabled="isAssigning || !selectedRoleId"
              class="flex-1 px-5 py-2.5 bg-[#252578] hover:bg-[#1e1e66] text-white text-sm font-semibold rounded-xl hover:shadow-md transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2 cursor-pointer"
            >
              <Loader2 v-if="isAssigning" class="animate-spin" :size="16" />
              <span>{{ isAssigning ? 'Updating...' : 'Confirm Assignment' }}</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>
