import { ref } from 'vue'

export type ToastType = 'success' | 'error' | 'info'

export interface ToastOptions {
  message: string
  type?: ToastType
  duration?: number
}

export interface Toast {
  id: number
  message: string
  type: ToastType
  duration: number
  remaining: number
  startedAt: number
  timerId?: ReturnType<typeof setTimeout> | null
}

const DEFAULT_DURATION: Record<ToastType, number> = {
  success: 4000,
  info: 4000,
  error: 6000
}

const MAX_TOASTS = 3

// Global reactive state
const toasts = ref<Toast[]>([])
let nextId = 0

function clearToastTimer(toast: Toast) {
  if (toast.timerId) {
    clearTimeout(toast.timerId)
    toast.timerId = null
  }
}

function startToastTimer(toast: Toast) {
  clearToastTimer(toast)
  if (toast.remaining > 0) {
    toast.startedAt = Date.now()
    toast.timerId = setTimeout(() => {
      removeToast(toast.id)
    }, toast.remaining)
  }
}

export function removeToast(id: number) {
  const index = toasts.value.findIndex(t => t.id === id)
  if (index > -1) {
    const [removed] = toasts.value.splice(index, 1)
    if (removed) {
      clearToastTimer(removed)
    }
  }
}

export function pauseToast(id: number) {
  const toast = toasts.value.find(t => t.id === id)
  if (!toast || !toast.timerId) return

  clearToastTimer(toast)
  const elapsed = Date.now() - toast.startedAt
  toast.remaining = Math.max(0, toast.remaining - elapsed)
}

export function resumeToast(id: number) {
  const toast = toasts.value.find(t => t.id === id)
  if (!toast || toast.timerId) return

  if (toast.remaining <= 0) {
    removeToast(id)
  } else {
    startToastTimer(toast)
  }
}

export function addToast(options: ToastOptions): number {
  const type: ToastType = options.type || 'info'
  const duration = options.duration ?? DEFAULT_DURATION[type]

  // Deduplication: if exact message & type is already showing, reset its duration
  const existing = toasts.value.find(
    t => t.type === type && t.message.trim().toLowerCase() === options.message.trim().toLowerCase()
  )
  if (existing) {
    existing.remaining = duration
    startToastTimer(existing)
    return existing.id
  }

  // Enforce max toasts (drop oldest)
  while (toasts.value.length >= MAX_TOASTS) {
    removeToast(toasts.value[0].id)
  }

  const id = nextId++
  const toast: Toast = {
    id,
    message: options.message,
    type,
    duration,
    remaining: duration,
    startedAt: Date.now(),
    timerId: null
  }

  toasts.value.push(toast)
  startToastTimer(toast)
  return id
}

export const toast = {
  success: (message: string, duration?: number) => addToast({ message, type: 'success', duration }),
  error: (message: string, duration?: number) => addToast({ message, type: 'error', duration }),
  info: (message: string, duration?: number) => addToast({ message, type: 'info', duration }),
  add: addToast,
  remove: removeToast
}

export function useToast() {
  return {
    toasts,
    addToast,
    removeToast,
    pauseToast,
    resumeToast,
    toast
  }
}
