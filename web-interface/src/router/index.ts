import { createRouter, createWebHistory } from "vue-router";
import LandingPage from "../views/LandingPage.vue";
import LoginView from "../views/auth/LoginView.vue";
import { clearClientAuthState, isAuthenticatedClientSide } from "@/utils/authState";
import { isItAdmin } from "@/utils/role";
import { useAuth } from "@/composables/useAuth";
import type { FetchCurrentUserResult } from "@/composables/useAuth";
import { toast } from "@/composables/useToast";

const routes = [
  {
    path: "/",
    name: "landing",
    component: LandingPage,
  },
  {
    path: "/login",
    name: "login",
    component: LoginView,
  },
  {
    path: "/logout",
    name: "logout",
    component: () => import("../views/auth/LogoutView.vue"),
  },
  {
    path: "/forgot-password",
    name: "forgot-password",
    component: () => import("../views/auth/ForgotPassword.vue"),
  },
  {
    path: "/reset-password/:token?",
    name: "reset-password",
    component: () => import("../views/auth/ResetPassword.vue"),
  },
  {
    path: "/verify-email",
    name: "verify-email",
    component: () => import("../views/auth/EmailVerification.vue"),
  },
  {
    path: "/force-change-password",
    name: "force-change-password",
    component: () => import("../views/auth/ForceChangePassword.vue"),
    meta: { requiresAuth: true },
  },
  {
    path: "/home",
    name: "home",
    component: () => import("../views/HomePage.vue"),
    meta: { requiresAuth: true },
  },
  {
    path: "/admin",
    component: () => import("../layouts/AdminLayout.vue"),
    meta: { requiresAuth: true, requiresAdmin: true },
    children: [
      {
        path: "",
        name: "admin-dashboard",
        component: () => import("../components/dashboard/AdminDashboard.vue"),
      },
      {
        path: "users",
        name: "admin-user-list",
        component: () => import("../components/users/UserManagement.vue"),
      },
      {
        path: "users/create",
        name: "admin-user-create",
        component: () => import("../components/users/UserCreate.vue"),
      },
      {
        path: "roles",
        name: "admin-role-management",
        component: () => import("../components/roles/RoleManagement.vue"),
      },
      {
        path: 'roles/:id/permissions',
        name: 'admin-role-permissions',
        component: () => import('../components/roles/RolePermissionsPage.vue')
      },
      {
        path: "permissions",
        name: "admin-permission-management",
        component: () =>
          import("../components/permissions/PermissionManagement.vue"),
      },
      {
        path: "departments",
        name: "admin-department-management",
        component: () =>
          import("../components/departments/DepartmentManagement.vue"),
      },
      {
        path: "branches",
        name: "admin-branch-management",
        component: () =>
          import("../components/branches/BranchManagement.vue"),
      },
      {
        path: "audit-trail",
        name: "admin-audit-trail",
        component: () =>
          import("../components/audit/AuditTrailManagement.vue"),
      },
    ],
  },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

// Only fetchCurrentUser is used here; calling the composable at module scope is
// safe because it is stateless aside from local refs.
const { fetchCurrentUser } = useAuth();

// Dedupe concurrent in-flight hydration calls so a burst of navigations only
// triggers one network request.
let hydrationPromise: Promise<FetchCurrentUserResult> | null = null;

function hydrateCurrentUser() {
  if (!hydrationPromise) {
    hydrationPromise = fetchCurrentUser().finally(() => {
      hydrationPromise = null;
    });
  }
  return hydrationPromise;
}

function isEmptyUser(user: unknown): boolean {
  if (!user || typeof user !== "object") return true;
  return Object.keys(user).length === 0;
}

router.beforeEach(async (to, _from, next) => {
  const userStr = localStorage.getItem("user");
  let user: unknown = null;
  if (userStr) {
    try {
      user = JSON.parse(userStr);
    } catch {
      user = null;
    }
  }
  const isAuthenticated = isAuthenticatedClientSide();

  const requiresAuth = to.matched.some((record) => record.meta.requiresAuth);
  const requiresAdmin = to.matched.some((record) => record.meta.requiresAdmin);

  const guestOnlyRoutes = [
    "landing",
    "login",
    "forgot-password",
    "reset-password",
    "verify-email",
  ];

  if (guestOnlyRoutes.includes(to.name as string) && isAuthenticated) {
    next({ name: "home" });
    return;
  }

  if (requiresAuth && !isAuthenticated) {
    next({ name: "login" });
    return;
  }

  // Hydrate the authoritative user only when the local snapshot is unusable:
  //  - the snapshot is missing/empty but the session cookie says we're logged in
  //    (needed for the null-safe password-change check), or
  //  - the route requires IT Admin and the snapshot isn't IT Admin.
  const needsHydration =
    (requiresAuth && isAuthenticated && isEmptyUser(user)) ||
    (requiresAdmin && !isItAdmin(user));

  let hydration: FetchCurrentUserResult | null = null;
  if (needsHydration) {
    hydration = await hydrateCurrentUser();
    if (hydration.success && hydration.user) {
      user = hydration.user;
    }
  }

  // Handle hydration outcomes for all authenticated routes before the admin gate
  // so first-login and expired-session users are routed correctly even when the
  // local snapshot was empty (e.g. navigating straight to /home).
  if (isAuthenticated && hydration) {
    if (hydration.passwordChangeRequired) {
      next({ name: "force-change-password" });
      return;
    }

    if (hydration.sessionExpired) {
      // The api interceptor already redirected to "/" after a failed refresh;
      // do not fire deny logic on the same tick.
      next();
      return;
    }
  }

  // Force password change (null-safe; evaluates from the hydrated user when the
  // snapshot was empty).
  if (
    isAuthenticated &&
    user &&
    (user as Record<string, unknown>).is_password_changed === false &&
    to.name !== "force-change-password" &&
    to.name !== "logout"
  ) {
    next({ name: "force-change-password" });
    return;
  }

  // IT Admin gate.
  if (requiresAdmin && !isItAdmin(user)) {
    if (hydration) {
      if (hydration.success) {
        // Hydrated, but the backend says this user is not an IT Admin.
        toast.error(
          "Access Denied: Only IT Admin can access the authentication module interface.",
        );
        clearClientAuthState();
        next({ name: "login" });
        return;
      }

      // Network/other failure — fall back to the snapshot. Backend
      // can:manage-users remains the real authorization boundary.
      if (isItAdmin(user)) {
        next();
        return;
      }
      toast.error(
        "Access Denied: Only IT Admin can access the authentication module interface.",
      );
      clearClientAuthState();
      next({ name: "login" });
      return;
    }

    // Defensive: requiresAdmin without hydration performed (should not happen).
    toast.error(
      "Access Denied: Only IT Admin can access the authentication module interface.",
    );
    clearClientAuthState();
    next({ name: "login" });
    return;
  }

  next();
});

const TITLE_MAP: Record<string, string> = {
  landing: 'Welcome',
  login: 'Login',
  logout: 'Logging out',
  'forgot-password': 'Forgot Password',
  'reset-password': 'Reset Password',
  'verify-email': 'Verify Email',
  'force-change-password': 'Change Password',
  home: 'Home',
  'admin-user-list': 'User Management',
  'admin-user-create': 'Create User',
  'admin-role-management': 'Role Management',
  'admin-permission-management': 'Permission Management',
  'admin-department-management': 'Department Management',
};

router.afterEach((to) => {
  const pageName = (to.name && TITLE_MAP[String(to.name)]) || 'Auth Portal';
  document.title = `${pageName} – SBSI`;
});

export default router;

