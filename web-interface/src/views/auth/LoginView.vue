<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuth } from '@/composables/useAuth'
import { useToast } from '@/composables/useToast'
import { isAllowedRedirectUrl } from '@/utils/redirectValidation'
import { Eye, EyeOff, Loader2, ArrowLeft, Mail, Lock, ShieldCheck } from 'lucide-vue-next'
import ForgotPasswordModal from '@/views/auth/ForgotPassword.vue'

const router = useRouter()
const route = useRoute()
const { addToast } = useToast()

const {
  login,
  loginLoading: isLoading,
  loginErrors: errors,
  loginGeneralError: generalError,
} = useAuth()

onMounted(() => {
  if (route.query.error) {
    addToast({
      message: route.query.error as string,
      type: 'error',
      duration: 5000,
    })

    // Clean up the URL so it doesn't persist on refresh
    const query = { ...route.query }
    delete query.error
    router.replace({ query })
  }

  if (route.query.message) {
    addToast({
      message: route.query.message as string,
      type: 'success',
      duration: 5000,
    })

    const query = { ...route.query }
    delete query.message
    router.replace({ query })
  }
})

const form = reactive({
  email: '',
  password: '',
})

const showPassword = ref(false)
const showForgot = ref(false)
const hasRedirected = ref(false)

const togglePassword = () => {
  showPassword.value = !showPassword.value
}

const handleLogin = async () => {
  form.email = form.email.trim()
  const result = await login(form)

  if (result.success && result.user) {
    // Idempotent SSO bounce: fire at most once.
    if (hasRedirected.value) {
      return
    }
    const redirectUri = router.currentRoute.value.query.redirect_uri
    if (typeof redirectUri === 'string' && redirectUri && isAllowedRedirectUrl(redirectUri)) {
      hasRedirected.value = true
      let url = redirectUri
      const state = router.currentRoute.value.query.state
      if (typeof state === 'string' && state) {
        url += (url.includes('?') ? '&' : '?') + 'state=' + encodeURIComponent(state)
      }
      url += (url.includes('?') ? '&' : '?') + 'message=' + encodeURIComponent('Successfully logged in')
      window.location.href = url
    } else {
      router.push('/home')
    }
  } else if (generalError.value) {
    addToast({
      message: generalError.value,
      type: 'error',
      duration: 5000,
    })
  }
}
</script>

<template>
  <div class="login-container">
    <!-- ── LEFT PANEL (Branding & Enterprise Identity) ── -->
    <div class="left-panel">
      <!-- Background Image & Gradient Overlay -->
      <div class="bg-image"></div>
      <div class="overlay"></div>

      <!-- Top Bar: Back to Home -->
      <div class="left-top">
        <button @click="router.push('/')" class="back-btn" aria-label="Back to Home">
          <ArrowLeft :size="16" class="back-icon" />
          <span>Back to Home</span>
        </button>
      </div>

      <!-- Bottom Narrative & Certified Identity -->
      <div class="left-content">
        <h1 class="left-title">
          25 Years of Innovating Diagnostics Solutions
        </h1>

        <div class="left-badge">
          <span class="badge-dot"></span>
          <span>ISO 9001:2015 Certified</span>
        </div>
      </div>
    </div>

    <!-- ── RIGHT PANEL (ONE UI Compliant Form Surface) ── -->
    <div class="right-panel">
      <div class="login-card">
        <!-- Header -->
        <div class="card-head">
          <h2 class="title">Welcome Back!</h2>
          <p class="subtitle">Sign in to access your unified enterprise operations.</p>
        </div>

        <!-- General Error Banner -->
        <div v-if="generalError" class="login-alert" role="alert">
          <span>{{ generalError }}</span>
        </div>

        <!-- Login Form -->
        <form class="form-body" @submit.prevent="handleLogin">
          <!-- EMAIL FIELD -->
          <div class="form-group">
            <label for="email" class="field-label">Email Address</label>
            <div class="input-wrapper" :class="{ 'has-error': errors.email }">
              <Mail :size="18" class="input-icon" />
              <input
                id="email"
                v-model="form.email"
                type="email"
                placeholder="name@sbsi.com.ph"
                class="input-field"
                required
                maxlength="255"
                autocomplete="email"
              />
            </div>
            <p v-if="errors.email" class="field-error">{{ errors.email[0] }}</p>
          </div>

          <!-- PASSWORD FIELD -->
          <div class="form-group">
            <div class="password-label-row">
              <label for="password" class="field-label">Password</label>
              <button type="button" class="forgot-link" @click="showForgot = true">
                Forgot password?
              </button>
            </div>
            <div class="input-wrapper" :class="{ 'has-error': errors.password }">
              <Lock :size="18" class="input-icon" />
              <input
                id="password"
                v-model="form.password"
                :type="showPassword ? 'text' : 'password'"
                placeholder="Enter your password"
                class="input-field input-field--password"
                required
                maxlength="255"
                autocomplete="current-password"
              />
              <button
                type="button"
                class="eye-toggle"
                @click="togglePassword"
                aria-label="Toggle password visibility"
              >
                <Eye v-if="!showPassword" :size="18" />
                <EyeOff v-else :size="18" />
              </button>
            </div>
            <p v-if="errors.password" class="field-error">{{ errors.password[0] }}</p>
          </div>

          <!-- SUBMIT CTA BUTTON -->
          <button
            type="submit"
            class="login-btn"
            :disabled="isLoading"
          >
            <Loader2 v-if="isLoading" class="btn-spinner" :size="18" />
            <span>{{ isLoading ? 'Signing in...' : 'Sign In' }}</span>
          </button>
        </form>

        <!-- SECURITY NOTICE -->
        <div class="security-footer">
          <ShieldCheck :size="16" class="security-icon" />
          <span>Protected by SBSI Central Identity Authority</span>
        </div>
      </div>
    </div>

    <!-- FORGOT PASSWORD MODAL -->
    <ForgotPasswordModal v-if="showForgot" @close="showForgot = false" />
  </div>
</template>

<style scoped>
/* ==========================================================================
   ONE UI DESIGN SYSTEM ENFORCEMENT (DESIGN.md)
   Brand Primary: #252578 | Brand Accent: #2E85D8 | Brand Deep: #3A3A85
   Dark Slate: #1E293B | Muted Slate: #8D8B8F | Page Background: #F8F9FA
   ========================================================================== */

* {
  box-sizing: border-box;
  margin: 0;
  padding: 0;
}

.login-container {
  display: flex;
  min-height: 100vh;
  width: 100%;
  background-color: #f8f9fa;
  font-family: 'Poppins', 'Montserrat', -apple-system, BlinkMacSystemFont, sans-serif;
  color: #1e293b;
}

/* --------------------------------------------------------------------------
   LEFT PANEL (Branding & Identity)
   -------------------------------------------------------------------------- */
.left-panel {
  position: relative;
  width: 50%;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  padding: 48px;
  overflow: hidden;
  background-color: #0e0e2e;
}

.bg-image {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  background-image: url('/images/SBSI-bg.webp');
  background-size: cover;
  background-position: center;
  background-repeat: no-repeat;
  z-index: 1;
}

.overlay {
  position: absolute;
  inset: 0;
  background: linear-gradient(
    180deg,
    rgba(37, 37, 120, 0.86) 0%,
    rgba(25, 25, 90, 0.80) 50%,
    rgba(14, 14, 46, 0.94) 100%
  );
  z-index: 2;
}

.left-top {
  position: relative;
  z-index: 3;
}

.back-btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  background: rgba(255, 255, 255, 0.12);
  border: 1px solid rgba(255, 255, 255, 0.22);
  color: #ffffff;
  padding: 8px 18px;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 500;
  cursor: pointer;
  backdrop-filter: blur(8px);
  transition: all 0.2s ease;
}

.back-btn:hover {
  background: rgba(255, 255, 255, 0.22);
  transform: translateX(-2px);
}

.back-icon {
  transition: transform 0.2s ease;
}

.back-btn:hover .back-icon {
  transform: translateX(-2px);
}

.left-content {
  position: relative;
  z-index: 3;
  width: fit-content;
  max-width: 520px;
  display: flex;
  flex-direction: column;
  align-items: flex-start;
}

.left-badge {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 6px 16px;
  background: rgba(255, 255, 255, 0.14);
  border: 1px solid rgba(255, 255, 255, 0.24);
  border-radius: 9999px;
  color: #ffffff;
  font-size: 12px;
  font-weight: 600;
  align-self: flex-start;
  backdrop-filter: blur(8px);
}

.badge-dot {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background-color: #10b981;
  box-shadow: 0 0 8px #10b981;
}

.left-title {
  font-size: clamp(28px, 3.2vw, 42px);
  font-weight: 800;
  color: #ffffff;
  line-height: 1.2;
  letter-spacing: -0.01em;
  margin-bottom: 20px;
  text-align: left;
}

/* --------------------------------------------------------------------------
   RIGHT PANEL (Form Surface & Inputs)
   -------------------------------------------------------------------------- */
.right-panel {
  width: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  background-color: #f8f9fa;
  padding: 48px;
}

.login-card {
  width: 100%;
  max-width: 500px;
  background-color: #ffffff;
  border-radius: 24px;
  padding: 48px;
  box-shadow: 0 20px 45px -10px rgba(0, 0, 0, 0.07), 0 0 0 1px rgba(0, 0, 0, 0.04);
}

.card-head {
  text-align: center;
  margin-bottom: 32px;
}

.title {
  font-size: 30px;
  font-weight: 800;
  color: #1e293b;
  letter-spacing: -0.015em;
  margin-bottom: 8px;
  text-align: center;
}

.subtitle {
  font-size: 14px;
  color: #8d8b8f;
  line-height: 1.5;
  text-align: center;
}

.login-alert {
  background-color: #fef2f2;
  border: 1px solid #fca5a5;
  color: #ef4444;
  border-radius: 8px;
  padding: 10px 14px;
  font-size: 13px;
  font-weight: 500;
  margin-bottom: 24px;
}

/* Form Groups */
.form-body {
  display: flex;
  flex-direction: column;
  gap: 22px;
}

.form-group {
  display: flex;
  flex-direction: column;
}

.field-label {
  font-size: 11.5px;
  font-weight: 700;
  color: #8086a3;
  letter-spacing: 0.05em;
  text-transform: uppercase;
  margin-bottom: 8px;
}

.password-label-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 8px;
}

.forgot-link {
  background: none;
  border: none;
  padding: 0;
  font-size: 12.5px;
  font-weight: 600;
  color: #2e85d8;
  cursor: pointer;
  text-decoration: none;
  transition: color 0.2s ease;
}

.forgot-link:hover {
  color: #252578;
  text-decoration: underline;
}

/* Input Wrapper & Styled Controls */
.input-wrapper {
  position: relative;
  display: flex;
  align-items: center;
}

.input-icon {
  position: absolute;
  left: 16px;
  color: #94a3b8;
  pointer-events: none;
}

.input-field {
  width: 100%;
  height: 50px;
  padding: 12px 16px 12px 46px;
  background-color: #ffffff;
  border: 1.5px solid #e2e8f0;
  border-radius: 10px;
  font-size: 14.5px;
  font-family: inherit;
  color: #1e293b;
  outline: none;
  transition: all 0.2s ease;
}

.input-field--password {
  padding-right: 46px;
}

.input-field::placeholder {
  color: #94a3b8;
}

.input-field:focus {
  border-color: #2e85d8;
  box-shadow: 0 0 0 3px rgba(46, 133, 216, 0.15);
}

.input-wrapper.has-error .input-field {
  border-color: #ef4444;
  background-color: #fffbfb;
}

.field-error {
  font-size: 12px;
  font-weight: 500;
  color: #ef4444;
  margin-top: 6px;
}

.eye-toggle {
  position: absolute;
  right: 16px;
  background: none;
  border: none;
  padding: 0;
  cursor: pointer;
  display: flex;
  align-items: center;
  color: #94a3b8;
  transition: color 0.2s ease;
}

.eye-toggle:hover {
  color: #252578;
}

/* Primary Button adhering to DESIGN.md Interaction Matrix */
.login-btn {
  width: 100%;
  height: 52px;
  margin-top: 8px;
  background-color: #252578;
  color: #ffffff;
  border: none;
  border-radius: 10px;
  font-size: 16px;
  font-weight: 600;
  font-family: inherit;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  box-shadow: 0 2px 6px rgba(37, 37, 120, 0.2);
  transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}

.login-btn:hover:not(:disabled) {
  background-color: #2f2f8e;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(37, 37, 120, 0.3);
}

.login-btn:active:not(:disabled) {
  background-color: #1e1e66;
  transform: scale(0.98);
}

.login-btn:disabled {
  background-color: #94a3b8;
  opacity: 0.7;
  cursor: not-allowed;
  transform: none;
  box-shadow: none;
}

.btn-spinner {
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

/* Security Notice */
.security-footer {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  font-size: 12px;
  color: #8d8b8f;
  margin-top: 28px;
}

.security-icon {
  color: #10b981;
}

/* --------------------------------------------------------------------------
   RESPONSIVE LAYOUT
   -------------------------------------------------------------------------- */
@media (max-width: 960px) {
  .login-container {
    flex-direction: column;
  }

  .left-panel {
    width: 100%;
    min-height: 240px;
    padding: 32px 24px;
  }

  .left-title {
    font-size: 20px;
    margin-bottom: 8px;
  }

  .right-panel {
    width: 100%;
    padding: 32px 16px;
  }

  .login-card {
    padding: 32px 20px;
    box-shadow: none;
    background: transparent;
  }
}
</style>
