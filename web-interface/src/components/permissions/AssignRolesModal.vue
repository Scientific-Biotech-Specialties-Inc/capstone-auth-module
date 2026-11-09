<script setup lang="ts">
import { computed } from 'vue'
import { X, Loader2, AlertCircle, Check, ShieldCheck } from 'lucide-vue-next'
import type { Permission, Role } from '@/types'

const props = defineProps<{
  modelValue: boolean
  permission: Permission | null
  roles: Role[]
  selectedRoleIds: number[]
  loading: boolean
  submitting: boolean
  error: string | null
}>()

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  'update:selectedRoleIds': [value: number[]]
  'submit': []
}>()

const internalSelectedRoleIds = computed({
  get: () => props.selectedRoleIds,
  set: (value) => emit('update:selectedRoleIds', value)
})

const closeModal = () => {
  emit('update:modelValue', false)
}

const handleSubmit = () => {
  emit('submit')
}
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
                Assign to Roles
              </h2>
              <p class="text-xs text-slate-500 mt-0.5 font-sans">
                Permission: <span class="font-semibold text-[#252578]">{{ permission?.name }}</span>
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
            <p class="text-xs font-medium">Loading roles...</p>
          </div>

          <div v-else class="space-y-5">
            <div v-if="error" class="p-3.5 rounded-xl bg-red-50 border border-red-100 flex items-center gap-2.5 text-red-700 text-xs font-semibold">
              <AlertCircle :size="16" class="flex-shrink-0" />
              <span>{{ error }}</span>
            </div>

            <p class="text-xs font-semibold text-slate-600">Select roles that should receive this permission:</p>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 max-h-[40vh] overflow-y-auto pr-1">
              <label 
                v-for="role in roles" 
                :key="role.id"
                class="relative flex items-center gap-3 p-3.5 rounded-xl border transition-all cursor-pointer group select-none"
                :class="internalSelectedRoleIds.includes(role.id) 
                  ? 'border-[#252578] bg-blue-50/40 shadow-xs' 
                  : 'border-slate-200 hover:border-slate-300 bg-white'"
              >
                <div 
                  class="w-5 h-5 rounded-md border-2 flex items-center justify-center transition-all flex-shrink-0"
                  :class="internalSelectedRoleIds.includes(role.id)
                    ? 'bg-[#252578] border-[#252578] text-white'
                    : 'border-slate-300 bg-white group-hover:border-slate-400'"
                >
                  <Check v-if="internalSelectedRoleIds.includes(role.id)" :size="12" stroke-width="3" />
                </div>
                <input 
                  type="checkbox" 
                  :value="role.id" 
                  v-model="internalSelectedRoleIds"
                  class="sr-only"
                />
                <span class="text-sm font-semibold text-slate-900 truncate">{{ role.name }}</span>
              </label>
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
                <span>{{ submitting ? 'Updating...' : 'Save Assignments' }}</span>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>
