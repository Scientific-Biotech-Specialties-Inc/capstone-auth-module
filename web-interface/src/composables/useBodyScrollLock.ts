import { watch, onUnmounted, type Ref } from 'vue'

let activeLocksCount = 0

/**
 * Composable to prevent background scrolling when a modal or overlay is active.
 * Uses reference counting so nested or sequentially opened modals keep the body locked
 * until all modals are closed.
 */
export function useBodyScrollLock(isOpen: Ref<boolean>) {
  let isCurrentlyLocked = false

  const lock = () => {
    if (isCurrentlyLocked) return
    isCurrentlyLocked = true
    activeLocksCount++
    if (activeLocksCount === 1) {
      document.body.style.overflow = 'hidden'
      document.body.classList.add('overflow-hidden')
    }
  }

  const unlock = () => {
    if (!isCurrentlyLocked) return
    isCurrentlyLocked = false
    if (activeLocksCount > 0) {
      activeLocksCount--
    }
    if (activeLocksCount === 0) {
      document.body.style.overflow = ''
      document.body.classList.remove('overflow-hidden')
    }
  }

  watch(
    isOpen,
    (val) => {
      if (val) {
        lock()
      } else {
        unlock()
      }
    },
    { immediate: true }
  )

  onUnmounted(() => {
    unlock()
  })
}
