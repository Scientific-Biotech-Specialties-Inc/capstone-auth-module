<script setup lang="ts">
import { ref, watch } from 'vue'
import { Loader2, MapPin, X, AlertCircle } from 'lucide-vue-next'

const props = defineProps<{
  modelValue: boolean
  mode: 'create' | 'edit'
  initialData?: { id: number | null; name: string; address: string }
  submitting: boolean
  error?: string | null
}>()

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  'submit': [data: { id: number | null; name: string; address: string }]
}>()

const form = ref({
  id: null as number | null,
  name: '',
  address: ''
})

watch(() => props.initialData, (newVal) => {
  if (newVal) {
    form.value = { ...newVal }
  } else {
    form.value = { id: null, name: '', address: '' }
  }
}, { immediate: true })

const close = () => {
  emit('update:modelValue', false)
}

const handleSubmit = () => {
  if (!form.value.name.trim() || !form.value.address.trim()) return
  emit('submit', { ...form.value })
}
</script>

<template>
  <Teleport to="body">
    <div 
      v-if="modelValue" 
      class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm animate-in fade-in duration-200"
      @click.self="close"
    >
      <!-- Modal Card (ONE UI standard form modal style) -->
      <div class="bg-white rounded-3xl border border-slate-200 shadow-2xl w-full max-w-lg overflow-hidden animate-in zoom-in-95 duration-200">
        <!-- Modal Header -->
        <div class="p-6 border-b border-slate-100 flex items-center justify-between">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-indigo-50 text-[#252578] flex items-center justify-center flex-shrink-0">
              <MapPin :size="20" />
            </div>
            <div>
              <h2 class="text-lg font-bold text-slate-900 font-sans">
                {{ mode === 'create' ? 'Create New Branch' : 'Edit Branch' }}
              </h2>
              <p class="text-xs text-slate-500 mt-0.5 font-sans">
                {{ mode === 'create' ? 'Add a new corporate or regional office branch' : 'Update branch name and physical address' }}
              </p>
            </div>
          </div>
          <button 
            @click="close"
            class="p-2 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100 transition-all cursor-pointer"
          >
            <X :size="18" />
          </button>
        </div>

        <!-- Form Body -->
        <form @submit.prevent="handleSubmit" class="p-6 space-y-5">
          <div v-if="error" class="p-3.5 rounded-xl bg-red-50 border border-red-100 flex items-center gap-2.5 text-red-700 text-xs font-semibold">
            <AlertCircle :size="16" class="flex-shrink-0" />
            <span>{{ error }}</span>
          </div>

          <div class="space-y-2">
            <label class="text-sm font-semibold text-slate-700">Branch Name</label>
            <input 
              v-model="form.name" 
              type="text" 
              placeholder="e.g. Cebu Regional Hub"
              class="flex h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-900 transition-all"
              required
              autofocus
            />
          </div>

          <div class="space-y-2">
            <label class="text-sm font-semibold text-slate-700">Physical Address</label>
            <textarea 
              v-model="form.address" 
              rows="3"
              placeholder="e.g. Cebu Business Park, Cebu City, Cebu"
              class="flex w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-900 transition-all resize-none"
              required
            ></textarea>
          </div>

          <!-- Action Buttons -->
          <div class="pt-2 flex items-center justify-end gap-3">
            <button 
              type="button" 
              @click="close"
              class="px-4 py-2.5 rounded-xl border border-slate-200 text-slate-700 text-sm font-semibold hover:bg-slate-50 transition-all cursor-pointer"
            >
              Cancel
            </button>

            <button 
              type="submit" 
              :disabled="submitting || !form.name.trim() || !form.address.trim()"
              class="px-5 py-2.5 rounded-xl bg-[#252578] hover:bg-[#1e1e66] text-white text-sm font-semibold shadow-sm hover:shadow-md transition-all flex items-center gap-2 disabled:opacity-50 cursor-pointer"
            >
              <Loader2 v-if="submitting" class="animate-spin h-4 w-4" />
              <span>{{ mode === 'create' ? 'Create Branch' : 'Save Changes' }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </Teleport>
</template>
