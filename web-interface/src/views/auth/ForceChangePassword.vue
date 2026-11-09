<script setup lang="ts">
import { reactive, ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuth } from '@/composables/useAuth'
import { Loader2, Eye, EyeOff, ArrowRight, LogOut, CheckCircle } from 'lucide-vue-next'
import { useToast } from '@/composables/useToast'

const router = useRouter()
const { addToast } = useToast()
const {
  changePassword,
  changePasswordLoading: isLoading,
  changePasswordSuccess: isSuccess,
  changePasswordErrors: errors,
  changePasswordGeneralError: generalError,
  logout
} = useAuth()

const form = reactive({
  current_password: '',
  new_password: '',
  new_password_confirmation: ''
})

const showCurrentPassword = ref(false)
const showNewPassword = ref(false)
const showConfirmPassword = ref(false)

onMounted(() => {
  // If user doesn't exist, kick them to login
  const userStr = localStorage.getItem('user')
  if (!userStr) {
    router.push('/login')
  }
})

const toggleCurrentPassword = () => {
  showCurrentPassword.value = !showCurrentPassword.value
}

const toggleNewPassword = () => {
  showNewPassword.value = !showNewPassword.value
}

const toggleConfirmPassword = () => {
  showConfirmPassword.value = !showConfirmPassword.value
}

const reqLength = computed(() => form.new_password.length >= 12)
const reqNumber = computed(() => /[0-9]/.test(form.new_password))
const reqSymbol = computed(() => /[^A-Za-z0-9]/.test(form.new_password))
const reqUpper = computed(() => /[A-Z]/.test(form.new_password))

const passwordScore = computed(() => {
  let score = 0
  if (reqLength.value) score++
  if (reqNumber.value) score++
  if (reqSymbol.value) score++
  if (reqUpper.value) score++
  return score
})

const strengthProgressWidth = computed(() => `${(passwordScore.value / 4) * 100}%`)
const strengthProgressColor = computed(() => {
  if (passwordScore.value <= 1) return '#ef4444' // red-500
  if (passwordScore.value <= 2) return '#f97316' // orange-500
  if (passwordScore.value === 3) return '#eab308' // yellow-500
  return '#059669' // emerald-600
})

const handleSubmit = async () => {
  // Client-side quick check
  if (!reqLength.value || !reqNumber.value || !reqSymbol.value || !reqUpper.value) {
    addToast({
      message: 'Please meet all password requirements.',
      type: 'error',
      duration: 3000
    })
    return
  }
  if (form.new_password !== form.new_password_confirmation) {
    addToast({
      message: 'New passwords do not match.',
      type: 'error',
      duration: 3000
    })
    return
  }

  const result = await changePassword(form)
  
  if (result?.success && isSuccess.value) {
    addToast({
      message: result.message || 'Password updated successfully. Redirecting...',
      type: 'success',
      duration: 2000
    })
    setTimeout(() => {
      router.push('/home')
    }, 2000)
    return
  }

  addToast({
    message: result?.message || generalError.value || 'Password update failed.',
    type: 'error',
    duration: 4000
  })
}

const handleLogout = async () => {
  await logout()
  router.push({ name: 'login', query: { message: 'Successfully logged out.' } })
}
</script>

<template>
  <div class="login-container fade-in">

    <!-- ── LEFT PANEL (From LoginView) ── -->
    <div class="left-panel">
      <img class="bg-image" src="@/assets/login.png" alt="Login background" />
      <div class="overlay" />
      <div class="left-text">
        <h1>25 Years of Innovating Diagnostics Solutions</h1>
        <p>ISO 9001:2015 Certified</p>
      </div>
    </div>

    <!-- ── RIGHT PANEL (Update Password Form) ── -->
    <div class="right-panel">
      <div class="w-full max-w-[380px] animate-in slide-in-from-right-4 duration-700 mx-auto px-6">
        <header class="mb-8 text-center sm:text-left">

          <h2 class="text-2xl font-bold text-[#252578] mb-1.5 tracking-tight">Update Password</h2>
          <p class="text-[13px] text-[#464651]">A mandatory password rotation is required for your role.</p>
        </header>

        <div v-if="generalError" class="login-error" role="alert">
          {{ generalError }}
        </div>

        <form class="space-y-6" @submit.prevent="handleSubmit">
          
          <!-- Current Password -->
          <div class="space-y-1.5">
            <label class="text-[13px] text-[#464651] font-semibold block" for="current-password">Current Password</label>
            <div class="relative">
              <input 
                id="current-password" 
                v-model="form.current_password"
                :type="showCurrentPassword ? 'text' : 'password'"
                class="input-field" 
                :class="{ 'input-field--error': errors.current_password }"
                placeholder="Enter current password" 
                required 
              />
              <button 
                type="button"
                class="eye-toggle" 
                @click="toggleCurrentPassword"
              >
                <Eye v-if="!showCurrentPassword" :size="18" />
                <EyeOff v-else :size="18" />
              </button>
            </div>
            <p v-if="errors.current_password" class="field-error">
              {{ errors.current_password[0] }}
            </p>
          </div>

          <!-- New Password -->
          <div class="space-y-1.5">
            <label class="text-[13px] text-[#464651] font-semibold block" for="new-password">New Password</label>
            <div class="relative">
              <input 
                id="new-password" 
                v-model="form.new_password"
                :type="showNewPassword ? 'text' : 'password'"
                class="input-field" 
                :class="{ 'input-field--error': errors.new_password }"
                placeholder="New password" 
                required 
              />
              <button 
                type="button"
                class="eye-toggle" 
                @click="toggleNewPassword"
              >
                <Eye v-if="!showNewPassword" :size="18" />
                <EyeOff v-else :size="18" />
              </button>
            </div>
            <p v-if="errors.new_password" class="field-error">
              {{ errors.new_password[0] }}
            </p>
            
            <!-- Password Strength & Requirements -->
            <div v-if="form.new_password" class="mt-4 animate-in slide-in-from-top-2 duration-300">
              <!-- Strength Line -->
              <div class="h-1.5 w-full bg-[#e2e8f0] rounded-full overflow-hidden mb-3">
                <div class="h-full transition-all duration-300" :style="{ width: strengthProgressWidth, backgroundColor: strengthProgressColor }"></div>
              </div>

              <!-- Requirements Checklist -->
              <div class="grid grid-cols-2 gap-2.5">
                <div class="requirement-item" :class="{ 'met': reqLength }">
                  <CheckCircle :size="14" :stroke-width="reqLength ? 3 : 2" />
                  <span>12+ characters</span>
                </div>
                <div class="requirement-item" :class="{ 'met': reqNumber }">
                  <CheckCircle :size="14" :stroke-width="reqNumber ? 3 : 2" />
                  <span>One number</span>
                </div>
                <div class="requirement-item" :class="{ 'met': reqSymbol }">
                  <CheckCircle :size="14" :stroke-width="reqSymbol ? 3 : 2" />
                  <span>Special symbol</span>
                </div>
                <div class="requirement-item" :class="{ 'met': reqUpper }">
                  <CheckCircle :size="14" :stroke-width="reqUpper ? 3 : 2" />
                  <span>Uppercase letter</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Confirm Password -->
          <div class="space-y-1.5">
            <label class="text-[13px] text-[#464651] font-semibold block" for="confirm-password">Confirm New Password</label>
            <div class="relative">
              <input 
                id="confirm-password" 
                v-model="form.new_password_confirmation"
                :type="showConfirmPassword ? 'text' : 'password'"
                class="input-field" 
                placeholder="Re-enter new password" 
                required 
              />
              <button 
                type="button"
                class="eye-toggle" 
                @click="toggleConfirmPassword"
              >
                <Eye v-if="!showConfirmPassword" :size="18" />
                <EyeOff v-else :size="18" />
              </button>
            </div>
          </div>

          <!-- Action Button -->
          <div class="pt-4">
            <button 
              type="submit" 
              class="login-btn group"
              :disabled="isLoading || passwordScore < 4"
            >
              <template v-if="isLoading">
                <Loader2 class="btn-spinner" :size="20" />
                <span>UPDATING...</span>
              </template>
              <template v-else>
                <span>UPDATE CREDENTIALS</span>
                <ArrowRight class="transition-transform group-hover:translate-x-1" :size="20" />
              </template>
            </button>
          </div>
        </form>

        <footer class="mt-12 pt-8 border-t border-[#c7c5d3]/40 flex items-center justify-between">
          <button 
            type="button"
            class="text-[13px] text-[#2E85D8] font-medium hover:underline flex items-center gap-2" 
            @click="handleLogout"
          >
            <LogOut :size="16" />
            <span>Sign out instead</span>
          </button>
          <p class="text-[11px] text-[#464651]/60">
            © 2026 SBSI
          </p>
        </footer>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* ════════════════════════════════════════
   UPDATE PASSWORD PAGE (SPLIT PANE)
════════════════════════════════════════ */

.login-container {
  display: flex;
  height: 100vh;
  background: #f2f7fb;
  font-family: "Poppins", sans-serif;
}

.fade-in {
  animation: fadeIn 0.6s ease-out forwards;
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

/* ── LEFT SIDE ── */
.left-panel {
  position: relative;
  width: 50%;
  overflow: hidden;
  display: none;
}
@media (min-width: 768px) {
  .left-panel {
    display: block;
  }
}

.bg-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.overlay {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(37, 37, 120, 0.6);
}

.left-text {
  position: absolute;
  bottom: 60px;
  left: 40px;
  color: white;
}

.left-text h1 {
  font-size: 38px;
  font-weight: 800;
  max-width: 380px;
  line-height: 1.15;
  margin: 0 0 10px 0;
}

.left-text p {
  font-size: 16px;
  font-weight: 400;
  margin: 0;
  opacity: 0.9;
}

/* ── RIGHT SIDE ── */
.right-panel {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #ffffff;
}
@media (min-width: 768px) {
  .right-panel {
    width: 50%;
  }
}

/* ── FORM ELEMENTS ── */
.login-error {
  background: #fff5f5;
  border: 1px solid #f8c0c0;
  color: #c0392b;
  border-radius: 8px;
  padding: 10px 14px;
  font-size: 13px;
  font-weight: 400;
  margin-bottom: 24px;
}

.field-error {
  font-size: 12px;
  color: #c0392b;
  margin-top: 6px;
}

.input-field {
  width: 100%;
  height: 44px;
  padding: 0 44px 0 14px;
  background: #ffffff;
  border: 1px solid #c7c5d3;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 400;
  color: #1b1b21;
  font-family: "Poppins", sans-serif;
  box-sizing: border-box;
  outline: none;
  transition: all 0.2s;
}

.input-field::placeholder {
  color: rgba(0, 0, 0, 0.35);
}

.input-field:focus {
  border-color: #252578;
  box-shadow: 0 0 0 2px rgba(37, 37, 120, 0.1);
}

.input-field--error {
  border-color: #e74c3c;
}
.input-field--error:focus {
  box-shadow: 0 0 0 2px rgba(231, 76, 60, 0.1);
}

.eye-toggle {
  position: absolute;
  right: 16px;
  top: 50%;
  transform: translateY(-50%);
  background: none;
  border: none;
  cursor: pointer;
  padding: 4px;
  display: flex;
  align-items: center;
  color: #888;
  transition: color 0.2s;
}

.eye-toggle:hover {
  color: #252578;
}

/* ── REQUIREMENTS CHECKLIST ── */
.requirement-item {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 11px;
  font-weight: 500;
  color: #86898b;
  transition: color 0.3s;
}

.requirement-item.met {
  color: #059669;
}

/* ── BUTTON ── */
.login-btn {
  width: 100%;
  height: 46px;
  background: #252578;
  border: none;
  border-radius: 8px;
  color: white;
  font-size: 12px;
  letter-spacing: 1px;
  font-weight: 700;
  font-family: "Poppins", sans-serif;
  cursor: pointer;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  box-shadow: 0 10px 25px -5px rgba(37, 37, 120, 0.3);
}

.login-btn:hover:not(:disabled) {
  background: #1e1e62;
  transform: translateY(-1px);
}

.login-btn:active:not(:disabled) {
  transform: translateY(1px);
  box-shadow: 0 4px 10px -2px rgba(37, 37, 120, 0.3);
}

.login-btn:disabled {
  background: #9999bb;
  cursor: not-allowed;
  box-shadow: none;
}

.btn-spinner {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

.animate-in {
  animation: slideIn 0.5s ease-out forwards;
}

@keyframes slideIn {
  from { transform: translateX(20px); opacity: 0; }
  to { transform: translateX(0); opacity: 1; }
}
</style>
