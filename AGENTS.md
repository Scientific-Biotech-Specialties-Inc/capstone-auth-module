# AGENTS.md

Capstone auth module: Laravel 13 `auth-service` (REST API, OAuth2/Passport) + Vue 3 `web-interface` (admin SPA), orchestrated with Docker Compose behind a shared Nginx gateway.

---

## 🤖 AI Agent Context & Ecosystem Overview

This repository is the central authentication & identity authority (**Auth Service**) for an enterprise ecosystem. There are **4 connected downstream subsystems** that integrate with and consume this authentication service:

| Subsystem Acronym | Full System Name                              | Description & Interaction Context                                                                 |
| :---------------- | :-------------------------------------------- | :------------------------------------------------------------------------------------------------ |
| **CRMS**          | Contract Management System                    | Consumes Auth API & OAuth2 tokens for contract management user roles and authorization            |
| **SERMS**         | Smart Expense Reimbursement Management System | Consumes Auth API & OAuth2 SSO for expense reimbursement claims and permission verification       |
| **PRS**           | Productivity Report System                    | Consumes Auth API for user session validation, reporting permissions, and activity metrics access |
| **TS**            | Ticketing System                              | Consumes Auth API & OAuth2 tokens for helpdesk/support ticketing workflow permissions             |

### ⚠️ Directives for AI Agents

When working on this repository, AI agents MUST take note that changes to authentication endpoints, Passport/OAuth2 client configurations, token payloads, RBAC roles/permissions, internal service secrets (`INTERNAL_SERVICE_SECRET`), encryption keys (`INTERNAL_ENCRYPTION_KEY`), or shared gateway routes directly affect all 4 connected subsystems (**CRMS**, **SERMS**, **PRS**, **TS**).

- **API Contracts & Compatibility**: Preserve API contract backward compatibility when refactoring or modifying auth/token endpoints.
- **Cross-Subsystem Integration**: Ensure gateway, CORS, and network settings facilitate communication over the shared Docker network (`shared-capstone-network`).
- **UI & Frontend Design Strict Guardrails**: All user interfaces, layouts, components, and views across the ecosystem MUST strictly comply with [`DESIGN.md`](./DESIGN.md) (derived from the canonical source [`docs/ONE UI DESIGN.pdf`](./docs/ONE%20UI%20DESIGN.pdf)). All AI agents and subagents (`@ui-designer`, `@frontend-developer`, etc.) MUST always consult [`DESIGN.md`](./DESIGN.md) before proposing, generating, or refactoring UI code. For live component inspection, subagents should leverage the Figma MCP server (`figma-dev-mode-mcp-server`) with project [PRS Figma](https://www.figma.com/design/ciWpnQzNBU3Zopv97v29nN/PRS?node-id=79-1078&t=vMI8xDhUbfkm564o-1).

---

## 🎨 UI & Frontend Design Guardrails (ONE UI Specification)

All frontend engineering and user interface work must strictly follow the rules in [`DESIGN.md`](./DESIGN.md). The canonical source of truth is [`docs/ONE UI DESIGN.pdf`](./docs/ONE%20UI%20DESIGN.pdf), representing the unified ONE UI standard across all 4 industry subsystems (**CRMS**, **SERMS**, **PRS**, **TS**) and the central Auth Module SPA.

### Mandatory Rules for AI Agents & Subagents

1. **Always Consult `DESIGN.md` First**: Before creating, editing, or evaluating any Vue component, view, dialog, or style in `web-interface/`, AI agents and subagents MUST read and follow [`DESIGN.md`](./DESIGN.md). Never invent arbitrary colors, margins, paddings, or component styles.
2. **Canonical Token Adherence**:
   - Primary: `#252578` (Sidebar background, page titles, main CTA buttons, badges)
   - Accent: `#2E85D8` (Active sidebar border, focus rings, link hovers, notification unread badge)
   - Brand Deep: `#3A3A85` (Active sidebar link background)
   - Brand Deep Border: `#4E4E91` (Active sidebar link background border)
   - Page Background: `#F8F9FA` (Master body, table header backgrounds)
   - Surface Pure White: `#FFFFFF` (Cards, modals, dropdowns, inputs, top header)
   - Dark Slate: `#1E293B` (Metric numbers, modal titles, body typography)
   - Muted Slate: `#8D8B8F` (Subtitles, table cells, helper copy)
   - Avatar Circle Fill: `#252578` (Navy) with `#A7A7C8` Header Border Ring | Eyebrow/Tracker: `#8086A3` | Table Headers: `#94A3B8`
3. **Strict 8px Spacing Grid**: All margins and paddings must use 8px increments (`8px`, `16px`, `24px`, `32px`, `48px`). 4px is reserved exclusively for micro-badges. Arbitrary values (`p-[13px]`, `m-[7px]`) are strictly prohibited. The top navigation header is standardized at exactly **`72px`** (`h-[72px]` / `h-18`, 9 × 8px) across all group subsystems (**CRMS**, **SERMS**, **PRS**, **TS**), aligning directly with the 72px sidebar brand lockup.
4. **Component Primitive Architecture**:
   - Base UI components must exclusively use **shadcn-vue** (Radix Vue headless primitives styled with Tailwind CSS).
   - Icons must exclusively be sourced from **Lucide Icons** (`lucide-vue-next`).
5. **Tailwind Version Pinning**: `web-interface` uses **Tailwind 3.4**. Do NOT upgrade to Tailwind v4 and do not use Tailwind v4 CSS syntax in the frontend SPA.
6. **Bifurcated Loading States**:
   - **Skeleton Loaders**: Mandatory for all data fetching and layout rendering (metric cards, activity tables, ranking views, schedule cards, profile shells).
   - **Spinner Graphics (`Loader2`)**: Reserved exclusively for action triggers, button submissions, login/logout transitions, and file upload progress.
7. **Badge Semantics**: Status badges must use **Title Case** (`Active`, `Pending`, `Approved`, `Rejected`), never all-caps. Maintain a single visual tier (bordered or low-emphasis) per table column.
8. **Figma MCP Integration**: AI agents must utilize the local Figma MCP server (`figma-dev-mode-mcp-server`) with canonical project [PRS Figma Component Library](https://www.figma.com/design/ciWpnQzNBU3Zopv97v29nN/PRS?node-id=79-1078&t=vMI8xDhUbfkm564o-1) to inspect nodes, verify measurements, check variants, and extract exact assets whenever designing UI.

### Proper Subagent Delegation for UI and Testing Tasks

When coordinating UI and testing development, AI orchestrators must route tasks to the appropriate specialized subagents:

- **`@ui-designer`**: Visual design specifications, color/typography token audits, Figma MCP node inspections, interaction state definitions (hover, active, disabled), and accessibility checks.
- **`@frontend-developer`**: Vue 3 SPA implementation (`web-interface/src/`), TypeScript typing, shadcn-vue integration, Tailwind 3.4 styling, router navigation, and reactive state management adhering to `DESIGN.md`.
- **`@architect-reviewer`**: Gateway route integration, API contract consistency, cross-subsystem auth token validation, and SPA architectural reviews.
- **`@code-reviewer`**: Pre-commit / pre-PR compliance audits checking that UI changes strictly adhere to `DESIGN.md` guardrails (no arbitrary styles, correct loading patterns, strict tokens) and that testing standards are met.
- **`@qa-expert` / `@test-engineer`**: Form validation testing, confirmation modal workflow checks, toast auto-dismiss verification, unit & integration test creation across both `auth-service` and `web-interface`, E2E test scenarios in Playwright, and edge-case fuzzing.

---

## 🧪 Mandatory Testing & Quality Assurance Standards

To guarantee enterprise-grade stability and prevent regressions across the 4 downstream subsystems (**CRMS**, **SERMS**, **PRS**, **TS**), **all AI agents and contributors must strictly enforce a Zero Untested Code policy**. Every pull request must introduce and maintain comprehensive test suites across all layers of the stack.

### 1. Test Suite Hierarchy & Conventions

| Test Level | Scope & Purpose | Location | Framework / Tool |
| :--- | :--- | :--- | :--- |
| **Backend Unit Tests** | Isolated business logic, crypto helpers, validation rules, service classes without DB | `auth-service/tests/Unit/` | PHPUnit 12.5 (`php artisan test --testsuite=Unit`) |
| **Backend Feature / Integration Tests** | Full HTTP API endpoints, database transactions, OAuth2 token grant flows, RBAC enforcement | `auth-service/tests/Feature/` | PHPUnit 12.5 with SQLite in-memory (`php artisan test --testsuite=Feature`) |
| **Frontend Unit & Component Tests** | Composables, utility functions (`authState`, `role`, `redirectValidation`), isolated UI component renders and emits | `web-interface/src/**/__tests__/*.spec.ts` | Vitest + `@vue/test-utils` + `happy-dom` (`npm run test:unit`) |
| **Frontend End-to-End (E2E) Tests** | Full user journeys in real browser engine: login, session invalidation, password reset, protected route guard redirects | `web-interface/e2e/*.spec.ts` | Playwright (`npm run test:e2e`) |

### 2. Mandatory Directives for AI Agents

1. **Zero Untested Code Policy**: AI agents MUST NOT submit or propose code changes without accompanying unit, integration, or E2E tests.
   - If adding or modifying a backend endpoint, service, or rule: create/update tests in `auth-service/tests/Feature/` or `auth-service/tests/Unit/`.
   - If adding or modifying a frontend utility, composable, or component: create/update tests in `web-interface/src/**/__tests__/`.
   - If adding or modifying an authentication, navigation, or authorization flow: add corresponding scenarios in `web-interface/e2e/`.
2. **Mandatory Pre-PR Verification Checklist**:
   Before proposing any PR, AI agents must run and verify that all of the following commands pass locally:
   - **Backend**:
     - `php artisan test` (all unit and feature test assertions pass)
     - `./vendor/bin/pint --test --dirty` (PSR-12 code style compliance on changed files)
   - **Frontend**:
     - `npm run test:unit` (Vitest unit and component tests pass)
     - `npm run build` (`vue-tsc -b && vite build` — strict TypeScript typecheck and production bundling)
     - `npm run test:e2e` (Playwright browser smoke and flow tests pass)
3. **CI/CD Failure Remediation**:
   - Every PR triggers `.github/workflows/pr-checks.yml` running parallel backend tests, frontend unit tests, typecheck/build, and Playwright E2E suites.
   - If any CI job fails, the PR cannot be merged. The agent must inspect the failure summary, replicate the issue locally, and resolve it immediately.

---

## Setup (host, one-time)

Prerequisites: Docker Desktop v4.x+ (WSL2 backend recommended on Windows), Git, Node.js v20+.

1. Create the shared network once: `docker network create shared-capstone-network` (created on the host, not in compose).
2. Copy `.env.example` → `.env` in **both** the repo root and `auth-service/`. The root `.env` feeds Compose and wins for shared values (e.g. `REDIS_HOST=auth-redis`); the service `.env.example` is only a fallback for non-Compose runs.
3. `docker compose up -d --build`
4. `docker exec -it auth-service php artisan key:generate`
5. `docker exec -it auth-service php artisan migrate --seed`

Endpoints: Web Interface via gateway `http://localhost:5173`, Auth Service direct `http://localhost:8000`, health check `http://localhost:8000/up`.

Windows note: after code edits, entrypoint fixes CRLF in Passport keys automatically (`sed -i 's/\r$//'` on `storage/oauth-*.key`); a mounted file with CRLF endings is the usual cause of "key file not readable" errors.

## Do not fight the entrypoint

`auth-service/docker-entrypoint.sh` runs on every `docker compose up`. Do not duplicate or bypass its work:

- Waits for DB (`nc -z "$DB_HOST" "$DB_PORT"`), runs `composer install`.
- Creates `.env` from `.env.example` if missing; runs `key:generate --force` only when `APP_KEY` is absent.
- Auto-generates `INTERNAL_ENCRYPTION_KEY` (`openssl rand -hex 16`) when empty or still `your_secure_32_char_key_here`.
- `migrate --force`; seeds only when `User::count() == 0` (idempotent — do not re-seed manually).
- Generates Passport keys (`passport:keys --force`) and a personal access client (`passport:client --personal`) only when missing.
- `cache:clear` + `optimize:clear`, then `exec "$@"`.

To skip migrations/seed on a service start: `SKIP_MIGRATIONS=true docker compose up -d auth-service`.

## Environment wiring (Compose)

- `docker-compose.yml` passes root-`.env` values via `${VAR}` interpolation into the container (`DB_HOST`, `REDIS_HOST`, `APP_ENV`, `APP_DEBUG`, all Brevo/MAIL\_\* vars, `INTERNAL_SERVICE_SECRET`, `VENDOR_MANAGEMENT_URL`, `INTERNAL_ENCRYPTION_KEY`).
- Hardcoded in compose (ignore `.env` for these): `DB_PORT=3306`, `PHP_CLI_SERVER_WORKERS=4`.
- `depends_on`: `db` (condition: `service_healthy`) and `auth-redis`.
- When adding a new env var, wire it in `docker-compose.yml` env block, `auth-service/.env.example`, **and** root `.env.example`, or it will silently be `null` in the container.

## Config / runs

- `composer run setup` = install → create `.env` → `key:generate` → `migrate --force` → `npm install --ignore-scripts` → `npm run build` (local, non-Compose workflow).
- `composer run dev` = concurrently runs `php artisan serve`, `queue:listen --tries=1 --timeout=0`, `pail`, `npm run dev`.
- Management: `docker compose ps`, `docker compose logs -f [service]`, `docker compose down` (preserves DB/redis volumes).
- Common failure: Docker daemon pipe error `open //./pipe/dockerDesktopLinuxEngine` → start/restart Docker Desktop.

## Stack specifics

- `auth-service`: PHP 8.3, Laravel 13, Passport OAuth, Predis (redis), `php artisan serve` on `0.0.0.0:8000` inside a `php:8.3-fpm-alpine` container; `INTERNAL_ENCRYPTION_KEY` must be a unique 32-char string.
- `web-interface`: Vue 3 SPA; build = `vue-tsc -b && vite build`; uses axios, vue-router v4, crypto-js + jsencrypt (client-side crypto), shadcn-vue/radix-vue, **Tailwind 3.4 (pinned — do not bump to v4)**.
- `auth-service` Vite is Tailwind 4 with `package.json` `"type": "module"` — do not copy Tailwind v4 syntax across the two packages.

## Architecture / code style

- Layered: Controllers → Services → Repositories; constructor injection for loose coupling (e.g. `UserService` injects `UserRepository`).
- PSR-12 (backend); `camelCase` variables/methods, `PascalCase` classes (PHP and TS/Vue).
- Eager load (`with()`) to avoid N+1; Redis caching; queue offloading; Form Request validation; Eloquent Resources for API responses.
- Vue components live under `web-interface/src/components` (folder-based); feature-based directory layout.
- DocBlocks explain rationale, not implementation.

## Contribution workflow (repo-enforced in Contributing.md)

- Every issue title starts with `[Bug]`, `[Feature]`, or `[Task]`; template fields: Description, Steps to Reproduce, Expected vs Actual, Environment, Supporting Evidence. Open/check for an issue **before** starting work.
- Commits: Conventional Commits, e.g. `feat: implement authentication service`.
- Sync: `git checkout main && git pull origin main && git checkout feat/your-feature-name && git merge main`.
- PR: title `[#<Issue_Number>] <Short, imperative-mood description>` (e.g. `[#105] Add session timeout logic to admin layout`); body = `### Summary`, `### Linked Issue` (`Closes #<Issue_Number>`), `### Changes`, `### Testing`; target `main`.
