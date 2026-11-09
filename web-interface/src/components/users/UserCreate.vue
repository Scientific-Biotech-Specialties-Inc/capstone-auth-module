<script setup lang="ts">
import { reactive, onMounted } from 'vue'
import { useUsers } from '@/composables/useUsers'
import { Loader2, UserPlus, AlertCircle, ArrowLeft } from 'lucide-vue-next'

const {
  roles,
  departments,
  branches,
  isLoadingData,
  isSubmitting,
  errors,
  fetchCreateFormData,
  createUser,
} = useUsers()

const form = reactive({
  first_name: '',
  last_name: '',
  email: '',
  role_id: '',
  department_id: '',
  branch_id: ''
})

onMounted(fetchCreateFormData)

const handleSubmit = async () => {
  const success = await createUser(form)
  if (success) {
    // Reset form
    Object.keys(form).forEach(key => (form[key as keyof typeof form] = ''))
  }
}
</script>

<template>
  <div class="space-y-8 animate-in fade-in duration-500">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div class="flex items-center gap-4">
        <router-link 
          to="/admin/users"
          class="inline-flex items-center gap-2 px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold shadow-sm transition-all"
        >
          <ArrowLeft :size="16" />
          <span>Back to Users</span>
        </router-link>
        <div>
          <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Create New User</h1>
          <p class="text-slate-500 mt-0.5 text-xs">Onboard a new employee by creating their account.</p>
        </div>
      </div>
      <div class="w-11 h-11 bg-[#252578] rounded-xl flex items-center justify-center text-white shadow-md">
        <UserPlus :size="20" />
      </div>
    </div>

    <div v-if="isLoadingData" class="flex items-center justify-center h-64 bg-white rounded-3xl border border-slate-200">
      <Loader2 class="h-8 w-8 text-slate-400 animate-spin" />
    </div>

    <form v-else @submit.prevent="handleSubmit" class="grid grid-cols-1 md:grid-cols-2 gap-8">
      <!-- Left Column: Personal Info -->
      <div class="bg-white p-8 rounded-3xl border border-slate-200 shadow-sm space-y-6">
        <h3 class="text-lg font-bold text-slate-900 flex items-center gap-2">
          <span class="w-1 h-5 bg-slate-900 rounded-full"></span>
          Personal Information
        </h3>
        
        <div class="grid grid-cols-2 gap-4">
          <div class="space-y-2">
            <label class="text-sm font-semibold text-slate-700">First Name</label>
            <input 
              v-model="form.first_name" 
              type="text" 
              placeholder="John"
              class="flex h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-900 transition-all"
              :class="{ 'border-red-300 bg-red-50': errors.first_name }"
              required
            />
            <p v-if="errors.first_name" class="text-[10px] font-bold text-red-500 uppercase tracking-wider">{{ errors.first_name[0] }}</p>
          </div>
          <div class="space-y-2">
            <label class="text-sm font-semibold text-slate-700">Last Name</label>
            <input 
              v-model="form.last_name" 
              type="text" 
              placeholder="Doe"
              class="flex h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-900 transition-all"
              :class="{ 'border-red-300 bg-red-50': errors.last_name }"
              required
            />
            <p v-if="errors.last_name" class="text-[10px] font-bold text-red-500 uppercase tracking-wider">{{ errors.last_name[0] }}</p>
          </div>
        </div>

        <div class="space-y-2">
          <label class="text-sm font-semibold text-slate-700">Email Address</label>
          <input 
            v-model="form.email" 
            type="email" 
            placeholder="john.doe@company.com"
            class="flex h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-900 transition-all"
            :class="{ 'border-red-300 bg-red-50': errors.email }"
            required
          />
          <p v-if="errors.email" class="text-[10px] font-bold text-red-500 uppercase tracking-wider">{{ errors.email[0] }}</p>
        </div>

      </div>

      <!-- Right Column: Role & Dept -->
      <div class="bg-white p-8 rounded-3xl border border-slate-200 shadow-sm space-y-6">
        <h3 class="text-lg font-bold text-slate-900 flex items-center gap-2">
          <span class="w-1 h-5 bg-slate-900 rounded-full"></span>
          Organization Access
        </h3>

        <div class="space-y-2">
          <label class="text-sm font-semibold text-slate-700">Role</label>
          <select 
            v-model="form.role_id"
            class="flex h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-900 transition-all appearance-none cursor-pointer"
            :class="{ 'border-red-300 bg-red-50': errors.role_id }"
            required
          >
            <option value="" disabled>Select a role</option>
            <option v-for="role in roles" :key="role.id" :value="role.id">{{ role.name }}</option>
          </select>
          <p v-if="errors.role_id" class="text-[10px] font-bold text-red-500 uppercase tracking-wider">{{ errors.role_id[0] }}</p>
        </div>

        <div class="space-y-2">
          <label class="text-sm font-semibold text-slate-700">Department</label>
          <select 
            v-model="form.department_id"
            class="flex h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-900 transition-all appearance-none cursor-pointer"
            :class="{ 'border-red-300 bg-red-50': errors.department_id }"
            required
          >
            <option value="" disabled>Select a department</option>
            <option v-for="dept in departments" :key="dept.id" :value="dept.id">{{ dept.name }}</option>
          </select>
          <p v-if="errors.department_id" class="text-[10px] font-bold text-red-500 uppercase tracking-wider">{{ errors.department_id[0] }}</p>
        </div>

        <div class="space-y-2">
          <label class="text-sm font-semibold text-slate-700">Branch Location</label>
          <select 
            v-model="form.branch_id"
            class="flex h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-900 transition-all appearance-none cursor-pointer"
            :class="{ 'border-red-300 bg-red-50': errors.branch_id }"
          >
            <option value="">Select branch assignment (Optional)</option>
            <option v-for="branch in branches" :key="branch.id" :value="branch.id">
              {{ branch.name }}
            </option>
          </select>
          <p v-if="errors.branch_id" class="text-[10px] font-bold text-red-500 uppercase tracking-wider">{{ errors.branch_id[0] }}</p>
        </div>

        <div class="p-4 rounded-2xl bg-slate-50 border border-slate-100 mt-4">
          <div class="flex gap-3">
            <AlertCircle class="text-slate-400 h-5 w-5 shrink-0 mt-0.5" />
            <p class="text-xs text-slate-500 leading-relaxed">
              Creating this account will automatically generate a temporary password and send a welcome email to the user. They will be required to change their password on first login.
            </p>
          </div>
        </div>

        <!-- Action -->
        <div class="pt-4">
          <button 
            type="submit" 
            :disabled="isSubmitting"
            class="w-full h-11 bg-[#252578] hover:bg-[#1e1e66] text-white rounded-lg text-sm font-semibold shadow-sm hover:shadow-md transition-all flex items-center justify-center gap-2 disabled:opacity-50 cursor-pointer"
          >
            <Loader2 v-if="isSubmitting" class="animate-spin h-5 w-5" />
            <span>{{ isSubmitting ? 'Creating Account...' : 'Create Account' }}</span>
          </button>
        </div>
      </div>
    </form>
  </div>
</template>
