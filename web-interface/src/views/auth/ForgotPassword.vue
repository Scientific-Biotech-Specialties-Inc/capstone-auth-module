<script setup lang="ts">
import { ref } from 'vue'
import { useAuth } from '@/composables/useAuth'
import { CheckCircle2, Mail, X, Loader2 } from 'lucide-vue-next'

// ── Emits ─────────────────────────────────────────────────────────────────────
const emit = defineEmits<{ (e: 'close'): void }>()
const closeModal = () => emit('close')

// ── State ─────────────────────────────────────────────────────────────────────
const email = ref('')
const {
  forgotPassword,
  forgotLoading: isLoading,
  forgotMessage: message,
  forgotError: error,
} = useAuth()

const handleSubmit = async () => {
  if (!email.value) return
  await forgotPassword(email.value.trim())
}
</script>

<template>
  <!-- Backdrop -->
  <div class="modal-backdrop" @click.self="closeModal">
    <!-- Modal box -->
    <div class="modal-box" role="dialog" aria-modal="true">
      <!-- Close button -->
      <button class="modal-close" @click="closeModal" aria-label="Close modal">
        <X :size="18" />
      </button>

      <!-- ── Success state ── -->
      <template v-if="message">
        <div class="modal-content modal-content--left">
          <div class="success-icon-wrap">
            <CheckCircle2 :size="32" class="success-icon" />
          </div>
          <h2 class="modal-title">Reset Link Sent!</h2>
          <p class="modal-subtitle">{{ message }}</p>
          <div class="success-actions">
            <button class="modal-btn modal-btn--auto" @click="closeModal">
              Back to Login
            </button>
          </div>
        </div>
      </template>

      <!-- ── Email entry ── -->
      <template v-else>
        <div class="modal-content">
          <!-- Mail icon -->
          <div class="otp-icon-wrap">
            <Mail :size="32" class="otp-icon" />
          </div>

          <h2 class="modal-title">Forgot Password?</h2>
          <p class="modal-subtitle">
            Enter your registered email address and we'll send you a password reset link.
          </p>

          <div v-if="error" class="modal-error" role="alert">
            {{ error }}
          </div>

          <div class="input-group">
            <label for="fp-email" class="modal-label">Email Address</label>
            <input
              id="fp-email"
              v-model="email"
              type="email"
              placeholder="name@sbsi.com.ph"
              class="modal-input"
              :class="{ 'modal-input--error': error }"
              required
              @keydown.enter.prevent="handleSubmit"
            />
          </div>

          <button
            class="modal-btn"
            :disabled="isLoading || !email"
            @click="handleSubmit"
          >
            <Loader2 v-if="isLoading" class="btn-spinner" :size="18" />
            <span>{{ isLoading ? 'Sending Link...' : 'Send Reset Link' }}</span>
          </button>
        </div>
      </template>
    </div>
  </div>
</template>

<style scoped>
/* ── Backdrop ── */
.modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(14, 14, 46, 0.65);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  backdrop-filter: blur(4px);
  animation: fadeIn 0.18s ease;
  padding: 16px;
}

@keyframes fadeIn {
  from { opacity: 0; }
  to   { opacity: 1; }
}

/* ── Modal box adhering to ONE UI ── */
.modal-box {
  background: #ffffff;
  border-radius: 20px;
  box-shadow: 0 20px 60px rgba(14, 14, 46, 0.22), 0 0 0 1px rgba(0, 0, 0, 0.04);
  width: 100%;
  max-width: 440px;
  position: relative;
  padding: 40px;
  box-sizing: border-box;
  animation: slideUp 0.22s cubic-bezier(0.16, 1, 0.3, 1);
  font-family: 'Poppins', 'Montserrat', -apple-system, BlinkMacSystemFont, sans-serif;
  color: #1e293b;
}

@keyframes slideUp {
  from { transform: translateY(16px); opacity: 0; }
  to   { transform: translateY(0);    opacity: 1; }
}

/* ── Close button ── */
.modal-close {
  position: absolute;
  top: 18px;
  right: 18px;
  background: #f1f5f9;
  border: none;
  color: #64748b;
  cursor: pointer;
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  transition: all 0.15s ease;
}

.modal-close:hover {
  background: #e2e8f0;
  color: #1e293b;
}

/* ── Content ── */
.modal-content {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
}

.modal-content--left {
  align-items: flex-start;
  text-align: left;
}

/* ── Mail icon ── */
.otp-icon-wrap {
  width: 68px;
  height: 68px;
  border-radius: 50%;
  background: rgba(37, 37, 120, 0.08);
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 20px;
}

.otp-icon {
  color: #252578;
}

/* ── Title / subtitle ── */
.modal-title {
  font-size: 22px;
  font-weight: 700;
  color: #1e293b;
  margin: 0 0 8px 0;
  line-height: 1.25;
}

.modal-subtitle {
  font-size: 13.5px;
  color: #8d8b8f;
  margin: 0 0 24px 0;
  line-height: 1.55;
}

/* ── Error ── */
.modal-error {
  width: 100%;
  background: #fef2f2;
  border: 1px solid #fca5a5;
  color: #ef4444;
  border-radius: 8px;
  padding: 10px 14px;
  font-size: 13px;
  margin-bottom: 16px;
  text-align: left;
  box-sizing: border-box;
}

/* ── Input ── */
.input-group {
  width: 100%;
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  margin-bottom: 20px;
}

.modal-label {
  font-size: 11px;
  font-weight: 700;
  color: #8086a3;
  letter-spacing: 0.05em;
  text-transform: uppercase;
  margin-bottom: 6px;
}

.modal-input {
  width: 100%;
  height: 46px;
  padding: 10px 14px;
  border: 1.5px solid #e2e8f0;
  border-radius: 8px;
  font-size: 14px;
  font-family: inherit;
  color: #1e293b;
  outline: none;
  box-sizing: border-box;
  transition: all 0.2s ease;
  background-color: #ffffff;
}

.modal-input::placeholder {
  color: #94a3b8;
}

.modal-input:focus {
  border-color: #2e85d8;
  box-shadow: 0 0 0 3px rgba(46, 133, 216, 0.15);
}

.modal-input--error {
  border-color: #ef4444;
  background-color: #fffbfb;
}

/* ── Primary button ── */
.modal-btn {
  width: 100%;
  height: 48px;
  padding: 0 20px;
  background-color: #252578;
  border: none;
  border-radius: 8px;
  color: #ffffff;
  font-size: 15px;
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

.modal-btn:hover:not(:disabled) {
  background-color: #2f2f8e;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(37, 37, 120, 0.3);
}

.modal-btn:active:not(:disabled) {
  background-color: #1e1e66;
  transform: scale(0.98);
}

.modal-btn:disabled {
  background-color: #94a3b8;
  opacity: 0.7;
  cursor: not-allowed;
  transform: none;
  box-shadow: none;
}

.modal-btn--auto {
  width: auto;
  height: 44px;
  padding: 0 28px;
}

/* ── Spinner ── */
.btn-spinner {
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to   { transform: rotate(360deg); }
}

/* ── Success ── */
.success-icon-wrap {
  width: 60px;
  height: 60px;
  border-radius: 50%;
  background: rgba(16, 185, 129, 0.12);
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 16px;
}

.success-icon {
  color: #10b981;
}

.success-actions {
  width: 100%;
  display: flex;
  justify-content: flex-end;
  margin-top: 20px;
}
</style>