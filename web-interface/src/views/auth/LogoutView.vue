<template>
  <div class="flex items-center justify-center min-h-screen bg-slate-50">
    <div class="text-center">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600 mx-auto mb-4"></div>
      <p class="text-slate-600 font-medium">Logging out...</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuth } from '@/composables/useAuth'
import { isAllowedRedirectUrl } from '@/utils/redirectValidation'

const route = useRoute()
const router = useRouter()
const { logout } = useAuth()

onMounted(async () => {
  await logout()

  const redirectUri = route.query.redirect_uri
  if (typeof redirectUri === 'string' && redirectUri && isAllowedRedirectUrl(redirectUri)) {
    const separator = redirectUri.includes('?') ? '&' : '?'
    window.location.href = `${redirectUri}${separator}message=${encodeURIComponent('Successfully logged out.')}`
  } else {
    router.push({ name: 'login', query: { message: 'Successfully logged out.' } })
  }
})
</script>
