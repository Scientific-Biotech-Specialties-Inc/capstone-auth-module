<script setup lang="ts">
import { onMounted, onUnmounted } from 'vue'
import { clearClientAuthState, hasAuthStatusCookie, hasLocalUser } from '@/utils/authState'
import ToastNotification from '@/components/common/ToastNotification.vue'

let authCheckInterval: ReturnType<typeof setInterval>

onMounted(() => {
  // Check every 2 seconds if the user is supposed to be logged in but the auth cookie is gone
  authCheckInterval = setInterval(() => {
    if (hasLocalUser()) {
      // User is locally marked as authenticated. 
      // If the 'is_authenticated' cookie is missing, they cleared cookies.
      if (!hasAuthStatusCookie()) {
        clearClientAuthState()
        // We use window.location.href to force a full reload and router evaluation
        window.location.href = '/login'
      }
    }
  }, 2000)
})

onUnmounted(() => {
  clearInterval(authCheckInterval)
})
</script>

<template>
  <router-view />
  <ToastNotification />
</template>
