<script setup lang="ts">
import { computed } from 'vue'
import { X, Loader2, AlertCircle, Check, ShieldCheck } from 'lucide-vue-next'
import type { Role, Permission } from '@/types'

const props = defineProps<{
  modelValue: boolean
  role: Role | null
  allPermissions: Permission[]
  selectedPermissionIds: number[]
  loading: boolean
  submitting: boolean
  error: string | null
}>()

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  'update:selectedPermissionIds': [value: number[]]
  'submit': []
}>()

const internalSelectedIds = computed({
  get: () => props.selectedPermissionIds,
  set: (value) => emit('update:selectedPermissionIds', value)
})

const closeModal = () => {
  emit('update:modelValue', false)
}

const handleSubmit = () => {
  emit('submit')
}

const togglePermission = (permId: number) => {
  const current = [...internalSelectedIds.value]
  const idx = current.indexOf(permId)
  if (idx >= 0) {
    current.splice(idx, 1)
  } else {
    current.push(permId)
  }
  internalSelectedIds.value = current
}

const safePermissions = computed<Permission[]>(() => {
  if (Array.isArray(props.allPermissions)) {
    return props.allPermissions.filter((p): p is Permission => !!p && typeof p === 'object' && p.id != null)
  }
  if (props.allPermissions && Array.isArray((props.allPermissions as any).data)) {
    return (props.allPermissions as any).data.filter((p: any): p is Permission => !!p && typeof p === 'object' && p.id != null)
  }
  return []
})
</script>

<template>
  <Teleport to="body">
    <div 
      v-if="modelValue" 
      class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm animate-in fade-in duration-200"
      @click.self="closeModal"
    >
      <div class="bg-white rounded-3xl border border-slate-200 shadow-2xl w-full max-w-lg overflow-hidden animate-in zoom-in-95 duration-200">
        <!-- Modal Header -->
        <div class="p-6 border-b border-slate-100 flex items-center justify-between">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-indigo-50 text-[#252578] flex items-center justify-center flex-shrink-0">
              <ShieldCheck :size="20" />
            </div>
            <div>
              <h2 class="text-lg font-bold text-slate-900 font-sans">
                Manage Permissions
              </h2>
              <p class="text-xs text-slate-500 mt-0.5 font-sans">
                Role: <span class="font-semibold text-[#252578]">{{ role?.name }}</span>
              </p>
            </div>
          </div>
          <button 
            @click="closeModal" 
            class="p-2 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100 transition-all cursor-pointer"
          >
            <X :size="18" />
          </button>
        </div>

        <div class="p-6">
          <div v-if="loading" class="py-12 flex flex-col items-center justify-center gap-4 text-slate-500">
            <Loader2 class="animate-spin text-[#252578]" :size="32" />
            <p class="text-xs font-medium">Loading permissions...</p>
          </div>

          <div v-else class="space-y-4">
            <div v-if="safePermissions.length === 0" class="py-8 text-center text-slate-500 text-sm">
              <p>No permissions available.</p>
            </div>

            <div v-else class="max-h-[360px] overflow-y-auto space-y-2 pr-1">
              <label 
                v-for="perm in safePermissions" 
                :key="perm.id"
                class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-slate-50 cursor-pointer transition-colors border border-transparent"
                :class="internalSelectedIds.includes(perm.id) ? 'bg-blue-50/50 border-blue-100' : ''"
              >
                <div 
                  class="w-5 h-5 rounded-md border-2 flex items-center justify-center flex-shrink-0 transition-all cursor-pointer"
                  :class="internalSelectedIds.includes(perm.id) ? 'bg-[#252578] border-[#252578]' : 'border-slate-300'"
                  @click.prevent="togglePermission(perm.id)"
                >
                  <Check v-if="internalSelectedIds.includes(perm.id)" :size="12" class="text-white" />
                </div>
                <div @click.prevent="togglePermission(perm.id)" class="flex-1 min-w-0">
                  <div class="text-sm font-semibold text-slate-900 truncate">{{ perm?.name }}</div>
                  <div class="text-xs text-slate-500 font-mono truncate">{{ perm?.slug }}</div>
                </div>
              </label>
            </div>

            <div v-if="error" class="p-3.5 rounded-xl bg-red-50 border border-red-100 flex items-center gap-2.5 text-red-700 text-xs font-semibold">
              <AlertCircle :size="16" class="flex-shrink-0" />
              <span>{{ error }}</span>
            </div>

            <!-- Action Buttons -->
            <div class="pt-2 flex items-center justify-end gap-3">
              <button 
                type="button"
                @click="closeModal"
                class="px-4 py-2.5 rounded-xl border border-slate-200 text-slate-700 text-sm font-semibold hover:bg-slate-50 transition-all cursor-pointer"
              >
                Cancel
              </button>
              <button 
                @click="handleSubmit"
                :disabled="submitting"
                class="px-5 py-2.5 rounded-xl bg-[#252578] hover:bg-[#1e1e66] text-white text-sm font-semibold shadow-sm hover:shadow-md transition-all flex items-center gap-2 disabled:opacity-50 cursor-pointer"
              >
                <Loader2 v-if="submitting" class="animate-spin h-4 w-4" />
                <span>{{ submitting ? 'Saving...' : 'Save Permissions' }}</span>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>
