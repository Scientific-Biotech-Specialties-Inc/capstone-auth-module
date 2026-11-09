<script setup lang="ts">
import { toRef } from 'vue'
import { X, Loader2, Users } from 'lucide-vue-next'
import { useBodyScrollLock } from '@/composables/useBodyScrollLock'
import type { Role, User } from '@/types'

const props = defineProps<{
  modelValue: boolean
  role: Role | null
  users: User[]
  loading: boolean
}>()

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
}>()

useBodyScrollLock(toRef(props, 'modelValue'))

const closeModal = () => {
  emit('update:modelValue', false)
}
</script>

<template>
  <Teleport to="body">
    <div 
      v-if="modelValue" 
      class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm animate-in fade-in duration-200"
      @click.self="closeModal"
    >
      <div class="bg-white rounded-3xl border border-slate-200 shadow-2xl w-full max-w-2xl overflow-hidden animate-in zoom-in-95 duration-200">
        <!-- Modal Header -->
        <div class="p-6 border-b border-slate-100 flex items-center justify-between">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-indigo-50 text-[#252578] flex items-center justify-center flex-shrink-0">
              <Users :size="20" />
            </div>
            <div>
              <h2 class="text-lg font-bold text-slate-900 font-sans">
                Role Users
              </h2>
              <p class="text-xs text-slate-500 mt-0.5 font-sans">
                Active assignees for role: <span class="font-semibold text-[#252578]">{{ role?.name }}</span>
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
            <p class="text-xs font-medium">Loading users...</p>
          </div>

          <div v-else-if="users.length === 0" class="py-12 text-center text-slate-500 text-sm">
            <p>No users currently assigned to this role.</p>
          </div>

          <div v-else class="space-y-4">
            <div class="overflow-hidden border border-slate-200 rounded-2xl">
              <table class="w-full text-left border-collapse">
                <thead>
                  <tr class="bg-slate-50 border-b border-slate-200/80">
                    <th class="px-4 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider font-sans">User</th>
                    <th class="px-4 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider font-sans">Department</th>
                    <th class="px-4 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider font-sans">Email</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                  <tr v-for="user in users" :key="user.id" class="hover:bg-slate-50/60 transition-colors">
                    <td class="px-4 py-3">
                      <div class="text-sm font-semibold text-slate-900">{{ user.profile?.first_name }} {{ user.profile?.last_name }}</div>
                    </td>
                    <td class="px-4 py-3 text-sm text-slate-600">
                      {{ user.profile?.department?.name || 'N/A' }}
                    </td>
                    <td class="px-4 py-3 text-sm text-slate-500 font-mono text-xs">
                      {{ user.email }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div class="pt-2 flex justify-end">
              <button 
                type="button" 
                @click="closeModal"
                class="px-5 py-2.5 rounded-xl border border-slate-200 text-slate-700 text-sm font-semibold hover:bg-slate-50 transition-all cursor-pointer"
              >
                Close
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>
