<template>
  <ToastViewport class="outline-none">
    <TransitionGroup name="toast">
      <Toast 
        v-for="toast in toasts" 
        :key="toast.id"
        class="toast-item outline-none"
        :role="toast.type === 'error' ? 'alert' : 'status'"
        @mouseenter="pauseToast(toast.id)"
        @mouseleave="resumeToast(toast.id)"
      >
        <!-- Circular Thematic Icon Badge (No artificial left accent bar) -->
        <div 
          class="w-10 h-10 rounded-full flex items-center justify-center shrink-0"
          :class="[
            toast.type === 'success' ? 'bg-emerald-50 text-emerald-600' : '',
            toast.type === 'error' ? 'bg-red-50 text-red-500' : '',
            toast.type === 'info' ? 'bg-blue-50 text-[#2E85D8]' : ''
          ]"
        >
          <CircleCheck v-if="toast.type === 'success'" :size="22" class="stroke-[2.2]" />
          <X v-else-if="toast.type === 'error'" :size="20" class="stroke-[2.2]" />
          <Info v-else :size="20" class="stroke-[2.2]" />
        </div>

        <!-- Toast Message Text (Medium weight, natural casing) -->
        <ToastTitle>
          {{ toast.message }}
        </ToastTitle>

        <!-- Dismiss Button using shadcn-vue ToastClose primitive -->
        <ToastClose @click="removeToast(toast.id)" />
      </Toast>
    </TransitionGroup>
  </ToastViewport>
</template>

<script setup lang="ts">
import { CircleCheck, X, Info } from 'lucide-vue-next'
import { useToast } from '@/composables/useToast'
import {
  ToastViewport,
  Toast,
  ToastTitle,
  ToastClose
} from '@/components/ui/toast'

const { toasts, removeToast, pauseToast, resumeToast } = useToast()
</script>

<style scoped>
.toast-item {
  border: 1px solid rgba(226, 232, 240, 0.8) !important;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.10) !important;
}

.toast-enter-active,
.toast-leave-active {
  transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

.toast-enter-from {
  opacity: 0;
  transform: translateX(40px) scale(0.95);
}

.toast-leave-to {
  opacity: 0;
  transform: translateX(20px) scale(0.92);
}

.toast-move {
  transition: transform 0.3s ease;
}

@media (prefers-reduced-motion: reduce) {
  .toast-enter-active,
  .toast-leave-active,
  .toast-move {
    transition: none !important;
  }
}
</style>
