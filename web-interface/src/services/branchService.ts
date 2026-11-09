// ─── Branch Service ──────────────────────────────────────────────────────────
// HTTP wrappers for branch CRUD and dropdown options.

import api from '@/lib/api'
import type { Branch } from '@/types'

export interface BranchPayload {
  name: string
  address: string
}

export const branchService = {
  fetchBranches(params?: { page?: number; per_page?: number; search?: string }) {
    return api.get('/api/admin/branches', { params })
  },

  fetchBranchOptions() {
    return api.get<Branch[]>('/api/admin/branch-options')
  },

  createBranch(payload: BranchPayload) {
    return api.post('/api/admin/branches', payload)
  },

  updateBranch(id: number, payload: BranchPayload) {
    return api.put(`/api/admin/branches/${id}`, payload)
  },

  deleteBranch(id: number) {
    return api.delete(`/api/admin/branches/${id}`)
  },
}
