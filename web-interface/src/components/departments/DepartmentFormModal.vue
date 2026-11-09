<script setup lang="ts">
import { ref, watch } from 'vue'
import { X, Loader2, AlertCircle, Building2 } from 'lucide-vue-next'

const props = defineProps<{
  modelValue: boolean
  mode: 'create' | 'edit'
  initialData?: { id?: number | null, name: string, description: string }
  submitting: boolean
  error?: string | null
}>()

const emit = defineEmits<{
  (e: 'update:modelValue', value: boolean): void
  (e: 'submit', payload: { id: number | null, name: string, description: string }): void
}>()

const form = ref({
  id: null as number | null,
  name: '',
  description: ''
})

watch(() => props.modelValue, (newVal) => {
  if (newVal) {
    if (props.mode === 'edit' && props.initialData) {
      form.value = {
        id: props.initialData.id ?? null,
        name: props.initialData.name,
        description: props.initialData.description
      }
    } else {
      form.value = { id: null, name: '', description: '' }
    }
  }
})

const closeModal = () => {
  emit('update:modelValue', false)
}

const handleSubmit = () => {
  emit('submit', form.value)
}
</script>

<template>
  <Teleport to="body">
    <div
      v-if="modelValue"
      class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm animate-in fade-in duration-200"
      @click.self="closeModal"
    >
      <!-- Modal Card — DESIGN.md §VI.6 Form Modal spec -->
      <div
        class="relative bg-white w-full max-w-md rounded-2xl shadow-2xl border border-slate-100 overflow-hidden animate-in zoom-in-95 duration-200"
        role="dialog"
        aria-modal="true"
      >
        <!-- Header: left-aligned icon badge + title + close -->
        <div class="p-6 border-b border-slate-100 flex items-start justify-between gap-4">
          <div class="flex items-center gap-3">
            <!-- Soft blue circular icon badge (DESIGN.md §VI.6) -->
            <div class="w-10 h-10 rounded-xl bg-blue-50 text-[#2E85D8] flex items-center justify-center flex-shrink-0">
              <Building2 :size="20" />
            </div>
            <div>
              <h2 class="text-lg font-bold text-[#1E293B] font-sans">
                {{ mode === 'create' ? 'Create New Department' : 'Edit Department' }}
              </h2>
              <p class="text-xs text-[#8D8B8F] mt-0.5 font-sans">
                {{ mode === 'create' ? 'Add a new department to your organization' : 'Update department name and description' }}
              </p>
            </div>
          </div>
          <button
            @click="closeModal"
            class="p-2 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100 transition-all cursor-pointer flex-shrink-0"
          >
            <X :size="18" />
          </button>
        </div>

        <!-- Full-width divider already from border-b above -->

        <!-- Form Body -->
        <form @submit.prevent="handleSubmit" class="p-6 space-y-5">
          <!-- Inline error alert -->
          <div
            v-if="error"
            class="p-3.5 rounded-xl bg-red-50 border border-red-100 flex items-center gap-2.5 text-red-700 text-xs font-semibold"
          >
            <AlertCircle :size="16" class="flex-shrink-0" />
            <span>{{ error }}</span>
          </div>

          <!-- Department Name -->
          <div class="space-y-2">
            <label class="text-[11px] font-bold text-[#94A3B8] uppercase tracking-wider font-sans">
              Department Name
            </label>
            <input
              v-model="form.name"
              type="text"
              required
              placeholder="e.g. Engineering"
              class="flex h-11 w-full rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm text-[#1E293B] placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-[#2E85D8]/20 focus:border-[#2E85D8] transition-all"
            />
          </div>

          <!-- Description -->
          <div class="space-y-2">
            <label class="text-[11px] font-bold text-[#94A3B8] uppercase tracking-wider font-sans">
              Description
            </label>
            <textarea
              v-model="form.description"
              rows="3"
              placeholder="What does this department do?"
              class="flex w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-[#1E293B] placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-[#2E85D8]/20 focus:border-[#2E85D8] transition-all resize-none"
            ></textarea>
          </div>

          <!-- Right-aligned action footer (DESIGN.md §VI.6) -->
          <div class="pt-2 flex items-center justify-end gap-3">
            <button
              type="button"
              @click="closeModal"
              class="px-5 py-2.5 rounded-xl border border-slate-200 bg-white text-[#1E293B] text-sm font-semibold hover:bg-slate-50 transition-all cursor-pointer"
            >
              Cancel
            </button>
            <button
              type="submit"
              :disabled="submitting || !form.name.trim()"
              class="px-5 py-2.5 rounded-xl bg-[#252578] hover:bg-[#1e1e66] text-white text-sm font-semibold shadow-sm hover:shadow-md transition-all flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer active:scale-[0.98]"
            >
              <Loader2 v-if="submitting" class="animate-spin h-4 w-4" />
              <span>{{ submitting ? 'Saving...' : (mode === 'create' ? 'Create Department' : 'Save Changes') }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </Teleport>
</template>
