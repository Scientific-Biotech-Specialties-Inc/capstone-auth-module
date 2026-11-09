<script setup lang="ts">
import { ref, watch, nextTick, toRef, onMounted, onUnmounted } from 'vue'
import { Loader2, Eye, EyeOff, Lock, ShieldAlert } from 'lucide-vue-next'
import { useBodyScrollLock } from '@/composables/useBodyScrollLock'

const props = withDefaults(
  defineProps<{
    show: boolean
    title: string
    description?: string
    confirmLabel?: string
    cancelLabel?: string
    isSubmitting?: boolean
    error?: string
    variant?: 'danger' | 'primary'
  }>(),
  {
    description: 'Please confirm your identity by entering your password.',
    confirmLabel: 'Confirm',
    cancelLabel: 'Cancel',
    isSubmitting: false,
    error: '',
    variant: 'danger'
  }
)

const emit = defineEmits<{
  (e: 'close'): void
  (e: 'confirm', password: string): void
}>()

useBodyScrollLock(toRef(props, 'show'))

const password = ref('')
const showPassword = ref(false)
const inputRef = ref<HTMLInputElement | null>(null)

// Focus input on open
watch(
  () => props.show,
  (newVal) => {
    if (newVal) {
      password.value = ''
      showPassword.value = false
      nextTick(() => {
        inputRef.value?.focus()
      })
    }
  }
)

const handleCancel = () => {
  if (props.isSubmitting) return
  emit('close')
}

const handleConfirm = () => {
  if (!password.value || props.isSubmitting) return
  emit('confirm', password.value)
}

const togglePasswordVisibility = () => {
  showPassword.value = !showPassword.value
}

const handleKeydown = (e: KeyboardEvent) => {
  if (!props.show || props.isSubmitting) return
  if (e.key === 'Escape') {
    handleCancel()
  }
}

onMounted(() => {
  window.addEventListener('keydown', handleKeydown)
})

onUnmounted(() => {
  window.removeEventListener('keydown', handleKeydown)
})
</script>

<template>
  <Teleport to="body">
    <div v-if="show" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
      <!-- Backdrop with blur -->
      <div 
        class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm transition-opacity duration-300"
        @click="handleCancel"
      ></div>

      <!-- Modal Card -->
      <div 
        class="relative bg-white w-full max-w-sm sm:max-w-md rounded-3xl shadow-2xl overflow-hidden border border-slate-100 p-7 sm:p-8 animate-in fade-in zoom-in duration-200"
        role="dialog"
        aria-modal="true"
      >
        <!-- Centered Thematic Badge -->
        <div 
          class="mx-auto mb-4 sm:mb-5 w-14 h-14 rounded-full flex items-center justify-center"
          :class="variant === 'danger' ? 'bg-rose-50 text-[#ef4444]' : 'bg-indigo-50 text-[#252578]'"
        >
          <ShieldAlert v-if="variant === 'danger'" :size="26" stroke-width="2" />
          <Lock v-else :size="26" stroke-width="2" />
        </div>

        <!-- Title & Subtitle -->
        <div class="text-center">
          <h3 class="text-lg sm:text-xl font-bold text-slate-900 tracking-tight">{{ title }}</h3>
          <p class="mt-2 text-xs sm:text-sm text-slate-500 leading-relaxed max-w-xs sm:max-w-sm mx-auto">
            {{ description }}
          </p>
        </div>

        <!-- Form -->
        <form @submit.prevent="handleConfirm" class="mt-6 space-y-4">
          <div class="space-y-1.5 text-left">
            <label for="verification-password" class="text-xs font-semibold text-slate-700 ml-0.5">
              Enter your password to confirm
            </label>
            <div class="relative">
              <Lock class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" :size="16" />
              <input 
                ref="inputRef"
                id="verification-password"
                v-model="password"
                :type="showPassword ? 'text' : 'password'"
                placeholder="Enter your current password" 
                required
                :disabled="isSubmitting"
                class="w-full h-10 pl-10 pr-10 rounded-xl bg-slate-50 border border-slate-200 text-xs sm:text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-900 transition-all placeholder:text-slate-400"
              />
              <button 
                type="button"
                @click="togglePasswordVisibility"
                :disabled="isSubmitting"
                class="absolute right-2.5 top-1/2 -translate-y-1/2 p-1.5 rounded-lg text-slate-400 hover:text-slate-900 transition-colors"
              >
                <EyeOff v-if="showPassword" :size="16" />
                <Eye v-else :size="16" />
              </button>
            </div>
          </div>

          <!-- Error feedback -->
          <div v-if="error" class="text-xs font-medium text-red-600 bg-red-50 border border-red-100 px-3.5 py-2.5 rounded-xl flex items-start gap-2">
            <span>{{ error }}</span>
          </div>

          <!-- Actions -->
          <div class="pt-2 flex items-center justify-center gap-3">
            <button 
              type="button"
              @click="handleCancel"
              :disabled="isSubmitting"
              class="min-w-[96px] px-5 h-10 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs sm:text-sm font-semibold transition-all disabled:opacity-50 cursor-pointer"
            >
              {{ cancelLabel }}
            </button>
            <button 
              type="submit"
              :disabled="isSubmitting || !password"
              class="min-w-[96px] px-5 h-10 rounded-xl text-white text-xs sm:text-sm font-semibold transition-all flex items-center justify-center gap-2 shadow-sm disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer"
              :class="variant === 'danger' ? 'bg-[#ef4444] hover:bg-red-600 shadow-red-200' : 'bg-[#252578] hover:bg-[#1e1e66] shadow-indigo-200'"
            >
              <Loader2 v-if="isSubmitting" class="animate-spin" :size="16" />
              <span>{{ isSubmitting ? 'Confirming...' : confirmLabel }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </Teleport>
</template>
