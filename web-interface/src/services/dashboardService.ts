// ─── Dashboard & Audit Trail Service ─────────────────────────────────────────
import api from '@/lib/api'

export interface AuditLogActor {
  id: number
  email: string
  first_name?: string
  last_name?: string
  department?: string
  role?: string
}

export interface AuditLogEntry {
  id: number
  action: string
  description?: string
  ip_address?: string
  user_agent?: string
  performed_at?: string
  actor?: AuditLogActor | null
}

export interface DashboardStats {
  kpis: {
    total_users: number
    active_users: number
    total_roles: number
    total_departments: number
    total_branches: number
    total_permissions: number
  }
  recent_audit_logs: AuditLogEntry[]
}

export interface AuditLogParams {
  page?: number
  search?: string
  action?: string
  date?: string
  per_page?: number
}

export const dashboardService = {
  fetchDashboardStats() {
    return api.get<{ data: DashboardStats }>('/api/admin/dashboard-stats')
  },

  fetchAuditLogs(params: AuditLogParams) {
    return api.get<{
      data: AuditLogEntry[]
      meta: {
        current_page: number
        last_page: number
        per_page: number
        total: number
        from: number
        to: number
      }
    }>('/api/admin/audit-logs', { params })
  },
}
