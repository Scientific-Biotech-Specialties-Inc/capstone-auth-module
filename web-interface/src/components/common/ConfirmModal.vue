<script setup lang="ts">
import { toRef, onMounted, onUnmounted } from 'vue'
import { Trash2, AlertCircle, Check, FileText, Loader2, LogOut } from 'lucide-vue-next'
import { useBodyScrollLock } from '@/composables/useBodyScrollLock'

const props = withDefaults(
  defineProps<{
    show: boolean
    title?: string
    description?: string
    confirmLabel?: string
    cancelLabel?: string
    variant?: 'danger' | 'warning' | 'success' | 'info' | 'logout'
    isSubmitting?: boolean
  }>(),
  {
    title: 'Delete Record',
    description: 'Are you sure you want to permanently delete this record? This action cannot be undone and the record data will be removed from the system.',
    confirmLabel: 'Delete',
    cancelLabel: 'Cancel',
    variant: 'danger',
    isSubmitting: false
  }
)

const emit = defineEmits<{
  (e: 'close'): void
  (e: 'confirm'): void
}>()

useBodyScrollLock(toRef(props, 'show'))

const handleCancel = () => {
  if (props.isSubmitting) return
  emit('close')
}

const handleConfirm = () => {
  if (props.isSubmitting) return
  emit('confirm')
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
        class="relative bg-white w-full max-w-sm sm:max-w-md rounded-3xl shadow-2xl overflow-hidden border border-slate-100 p-7 sm:p-8 text-center animate-in fade-in zoom-in duration-200"
        role="dialog"
        aria-modal="true"
      >
        <!-- Centered Thematic Icon Badge -->
        <div 
          class="mx-auto mb-4 sm:mb-5 w-14 h-14 rounded-full flex items-center justify-center transition-colors"
          :class="{
            'bg-rose-50 text-[#ef4444]': variant === 'danger' || variant === 'logout',
            'bg-amber-50 text-amber-500': variant === 'warning',
            'bg-emerald-50 text-emerald-500': variant === 'success',
            'bg-indigo-50 text-[#252578]': variant === 'info'
          }"
        >
          <LogOut v-if="variant === 'logout'" :size="26" stroke-width="2" />
          <Trash2 v-else-if="variant === 'danger'" :size="26" stroke-width="2" />
          <AlertCircle v-else-if="variant === 'warning'" :size="26" stroke-width="2" />
          <Check v-else-if="variant === 'success'" :size="26" stroke-width="2.5" />
          <FileText v-else :size="26" stroke-width="2" />
        </div>

        <!-- Title -->
        <h3 class="text-lg sm:text-xl font-bold text-slate-900 tracking-tight font-sans">
          {{ title }}
        </h3>

        <!-- Description / Content -->
        <div class="mt-2 text-xs sm:text-sm text-slate-500 leading-relaxed font-normal max-w-xs sm:max-w-sm mx-auto font-sans">
          <slot>
            <p>{{ description }}</p>
          </slot>
        </div>

        <!-- Action Buttons -->
        <div class="mt-6 sm:mt-7 flex items-center justify-center gap-3">
          <button 
            type="button"
            @click="handleCancel"
            :disabled="isSubmitting"
            class="min-w-[96px] px-5 h-10 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs sm:text-sm font-semibold transition-all disabled:opacity-50 cursor-pointer"
          >
            {{ cancelLabel }}
          </button>
          
          <button 
            type="button"
            @click="handleConfirm"
            :disabled="isSubmitting"
            class="min-w-[96px] px-5 h-10 rounded-xl text-white text-xs sm:text-sm font-semibold transition-all flex items-center justify-center gap-2 shadow-sm disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer"
            :class="{
              'bg-[#ef4444] hover:bg-red-600 shadow-red-200': variant === 'danger' || variant === 'logout',
              'bg-amber-500 hover:bg-amber-600 shadow-amber-200': variant === 'warning',
              'bg-emerald-600 hover:bg-emerald-700 shadow-emerald-200': variant === 'success',
              'bg-[#252578] hover:bg-[#1e1e66] shadow-indigo-200': variant === 'info'
            }"
          >
            <Loader2 v-if="isSubmitting" class="animate-spin" :size="16" />
            <span>{{ isSubmitting ? (variant === 'logout' ? 'Logging out...' : 'Processing...') : confirmLabel }}</span>
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>
