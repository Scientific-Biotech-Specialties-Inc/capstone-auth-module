# SETUP-01 — capstone-auth-module (One-Shot Subagent Guide)

> **How to use this file:** This guide ships **inside the repo** at `docs/agent-setup/SETUP-01-capstone-auth-module.md` — the repo is already cloned to the user's machine. Feed this entire file to a subagent (or AI agent) and say _"Follow this guide end-to-end starting from the repo root."_ The subagent must execute every phase in order and handle everything itself. It must **STOP and ask the user** only at the marked `USER-INPUT-GATE`s (secret `.env` values). Nothing else requires user intervention.
> **Last verified:** Sept 6, 2026 · **Guide version:** 1.1.0 (in-repo edition — assumes the already-cloned repo) · **Covers repo:** `capstone-auth-module` (`dev` branch default)

---

## 1. Subagent Directive (read first, obey strictly)

You are the **setup subagent** for `capstone-auth-module`. Your job is a fully working local stack on the user's machine, starting from a possibly **bare machine** (no Git, Docker, Node, or PHP installed).

Rules:

1. Execute phases **0 → 8 in order**. Do not skip verification phases.
2. **Assume Windows 11 + Windows PowerShell 5.1** unless `uname` proves otherwise. All commands below are PowerShell-first; Bash/WSL variants are noted where they differ.
3. Prefer **Docker Compose (golden path)**. Use the bare-metal appendix only if the user explicitly says "no Docker".
4. **Never invent secrets.** At each `USER-INPUT-GATE`, stop, show the exact keys needed, accept user values, then continue. If the user says "use safe local defaults", you may keep the documented local defaults — except `INTERNAL_ENCRYPTION_KEY`, which must always be a real 32-character string.
5. Quote every path containing spaces (`"C:\My Projects\..."`). Before creating any directory/file, verify the parent with `Test-Path -LiteralPath "<parent>"`.
6. After every destructive command (`down -v`, volume wipe), confirm with the user first.
7. Finish only when the **Definition of Done (§9)** is fully green. Report evidence (command + output), never claims.
8. **Do not create or add new files, and do not change the codebase, unless it is necessary to finish setup.** Setup legitimately creates: `.env` files copied from `.env.example`, the external Docker network, containers/volumes, applied migrations/seeds/keys, and cache clears this guide explicitly orders. Anything beyond that — new source files, edits to app code or configs, dependency changes — is out of scope: STOP, explain why you believe it is necessary, and ask the user first.
9. **Use the repo's other docs as references whenever you need them.** If a step is ambiguous or fails, consult `AGENTS.md` and the `docs/` hub (`Quickstart.md`, `DOCKER.md`, `SHARED_GATEWAY_GUIDE.md`, `Onboarding.md`) before improvising — and cite which doc resolved it in your report. If any doc conflicts with this guide, STOP and ask the user instead of guessing.

---

## 2. What You Are Setting Up

| Item               | Value (verified from repo)                                                                                                                                                  |
| :----------------- | :-------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Repo URL           | `https://github.com/Zanti00/capstone-auth-module.git`                                                                                                                       |
| Default local path | This repo — you are already inside the clone (this guide ships at `docs/agent-setup/` in it; typical path `C:\Projects\capstone-auth-module`)                               |
| Backend            | Laravel `^13.7` on PHP `^8.3` (platform `8.3.13`), Laravel Passport, `predis/predis`, `symfony/http-client` — see `auth-service/composer.json`                              |
| Frontend           | Vue `^3.5.34`, Vite `^8.0.12`, Tailwind `3.4`, `shadcn-vue`, TypeScript — see `web-interface/package.json`                                                                  |
| Infra              | Docker Compose V2: `auth-service` (Laravel `serve :8000`), `queue-worker`, `web-interface` (Vite), `nginx-proxy` (gateway), `db` (MySQL `8.0`), `auth-redis` (Redis Alpine) |
| Host ports         | UI gateway `5173` (also `80→5173`), API direct `8000`, MySQL `33066→3306`, web-interface internal `5000`, Redis internal only                                               |
| External network   | `shared-capstone-network` (external, must exist before `up`)                                                                                                                |
| Time / disk        | 15–30 min first build (plus Docker Desktop install if missing); ~4–6 GB images + volumes                                                                                    |

> **Port-conflict warning:** Both `capstone-auth-module` and SERMS map host port `8000`. Do **not** run both API stacks on the same host at the same time without remapping. For this guide, if `8000` is already bound (e.g., SERMS is up), stop the other stack first (`docker compose down` in that repo) or remap one side. Container-to-container traffic (`http://auth-service:8000` over `shared-capstone-network`) is unaffected — only host-port binding collides.

---

## 3. Phase 0 — Host Triage (never skip, never fails)

Run these read-only probes first. Record what is missing; Phase 1 installs only what is missing.

```powershell
# OS + shell
$PSVersionTable.PSVersion; [Environment]::OSVersion.VersionString

# Core tools (each line must not abort the script if missing)
Get-Command git -ErrorAction SilentlyContinue; git --version
Get-Command docker -ErrorAction SilentlyContinue; docker --version; docker compose version
Get-Command node -ErrorAction SilentlyContinue; node --version; npm --version
Get-Command php -ErrorAction SilentlyContinue; php --version
Get-Command composer -ErrorAction SilentlyContinue; composer --version
Get-Command winget -ErrorAction SilentlyContinue; winget --version
Test-Path -LiteralPath "C:\Projects"
docker network ls | Select-String "shared-capstone-network"
docker ps --format "{{.Names}} ({{.Status}})"
```

**Interpretation:**

- `docker ...` missing → install Docker Desktop (Phase 1A).
- `git` missing → install Git (Phase 1B).
- `node` missing or `< v20` → install Node 20 LTS (Phase 1C). Needed only for bare-metal frontend work; Docker path still benefits from it for host-side checks.
- `php`/`composer` missing → only required for bare-metal backend work; Docker path does not need them on the host. Install only if user wants no-Docker fallback.
- `shared-capstone-network` absent → you will create it in Phase 3.

---

## 4. Phase 1 — Zero-State Prerequisite Installer

> Goal: a machine that can run `docker compose up`. Do only the sub-steps needed per Phase 0.

### 1A. Docker Desktop (required for golden path)

```powershell
# Preferred: winget (Windows 10 1709+ / 11)
winget install --id Docker.DockerDesktop -e --accept-package-agreements --accept-source-agreements

# Fallback (no winget): download https://www.docker.com/products/docker-desktop/,
# install, enable "Use the WSL 2 based engine", reboot if prompted.
```

Then:

1. Start **Docker Desktop** from the Start Menu. Wait until the tray icon is steady and the bottom-left status is green.
2. Verify in a **fresh** PowerShell window (PATH refreshes after install):
   ```powershell
   docker --version; docker compose version; docker info
   ```
3. If you see `open //./pipe/dockerDesktopLinuxEngine: The system cannot find the file specified`, Docker Desktop is not running — start/restart it, then retry. See `DOCKER.md` § Troubleshooting.

### 1B. Git (required)

```powershell
winget install --id Git.Git -e --accept-package-agreements --accept-source-agreements
# Fallback: https://git-scm.com/download/win
git --version
```

### 1C. Node.js 20 LTS (required for host-side checks + bare-metal fallback)

```powershell
winget install --id OpenJS.NodeJS.LTS -e --accept-package-agreements --accept-source-agreements
# Fallback: https://nodejs.org/en/download (20.x LTS)
# Close + reopen PowerShell, then:
node --version  # expect v20.x or higher
npm --version
```

### 1D. PHP 8.3 + Composer (bare-metal only — skip if using Docker)

```powershell
# Only if user explicitly wants to run auth-service without Docker:
winget install --id PHP.PHP.8.3 -e --accept-package-agreements --accept-source-agreements
winget install --id Composer.Composer -e --accept-package-agreements --accept-source-agreements
# Fallbacks: https://windows.php.net/download/ (8.3.x, Thread Safe) + https://getcomposer.org/download/
php --version  # expect 8.3.x
composer --version
```

> After any install, open a **new** PowerShell window before continuing — the old one has a stale `PATH`.

---

## 5. Phase 2 — Locate the Repo (already cloned — verify, do not re-clone)

This guide ships inside the clone. Start from the repo root (the directory containing `docker-compose.yml`, `auth-service/`, and `web-interface/`):

```powershell
# Verify you are at the repo root:
Test-Path -LiteralPath ".\docker-compose.yml"
Test-Path -LiteralPath ".\auth-service"
Test-Path -LiteralPath ".\web-interface"
git status -sb; git branch --show-current; git log --oneline -3
```

If the working tree has uncommitted work, do **not** wipe or reset it. Reuse it and note the dirty state in your final report.

> **Fallback only:** if the repo is somehow missing from this machine, clone it with `git clone https://github.com/Zanti00/capstone-auth-module.git` and start over from Phase 0 in the fresh clone.

---

## 6. Phase 3 — Shared Network + Environment Files

### 3A. External network (idempotent)

```powershell
docker network ls | Select-String "shared-capstone-network"
# If no output:
docker network create shared-capstone-network
docker network ls | Select-String "shared-capstone-network"
```

All services in `docker-compose.yml` attach to `shared-capstone-network` (external) — `up` fails without it.

### 3B. USER-INPUT-GATE — `.env` values (STOP here if secrets are missing)

Two files are needed. The entrypoint auto-copies `.env.example` when `.env` is missing and auto-generates `APP_KEY` + `INTERNAL_ENCRYPTION_KEY`, but you must still resolve secrets correctly.

**File A — root `.env`** (Docker Compose variables; template: `.env.example`):

| Key                                                                        | What to do                                                                                                                                                                                                                                                                                                |
| :------------------------------------------------------------------------- | :-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `INTERNAL_ENCRYPTION_KEY`                                                  | **Must be exactly 32 chars** and **identical** in File A and File B. If empty, generate one (see below) and write it to **both** files. Example generation: `openssl rand -hex 16` (Git Bash) or `[guid]::NewGuid().ToString("N").Substring(0,32)` (PowerShell fallback — prefer OpenSSL when available). |
| `DB_*` / `MYSQL_*`                                                         | Keep local defaults (`capstone_auth` / `capstone_user` / `capstone_password`) unless user overrides. Root and backend DB names/passwords must match.                                                                                                                                                      |
| `MAIL_*`                                                                   | Local dev: keep `smtp`/`sandbox.smtp.mailtrap.io` placeholders. For real mail, ask user for Mailtrap sandbox `MAIL_USERNAME` + `MAIL_PASSWORD` + `MAIL_FROM_ADDRESS`.                                                                                                                                     |
| `BREVO_*` (`BREVO_API_KEY`, `BREVO_WEBHOOK_SECRET`, 4× `BREVO_TEMPLATE_*`) | Ask user. If they have no Brevo account, leave empty — transactional mail stays on `log`/`smtp` fallback and auth flows still work locally. Never invent fake Brevo keys.                                                                                                                                 |

**File B — `auth-service/.env`** (Laravel; template: `auth-service/.env.example`): same `INTERNAL_ENCRYPTION_KEY` as File A, plus `APP_KEY` (auto-generated if blank), `DB_HOST=db`, `REDIS_HOST` per compose, `PASSPORT_*` (auto-generated as `passport:keys` on first boot).

Commands:

```powershell
Test-Path -LiteralPath ".\.env"; Test-Path -LiteralPath ".\auth-service\.env"
# If either is False:
Copy-Item -LiteralPath ".\.env.example" -Destination ".\.env" -ErrorAction SilentlyContinue
Copy-Item -LiteralPath ".\auth-service\.env.example" -Destination ".\auth-service\.env" -ErrorAction SilentlyContinue
```

Then open both files, set the 32-char key identically in both, fill any user-supplied Mail/Brevo values, and save. Restart containers after any `.env` edit (`docker compose down; docker compose up -d`).

> **STOP condition:** If `INTERNAL_ENCRYPTION_KEY` cannot be agreed (user has a shared team key vs. fresh local key), ask. Mismatched keys between root and backend cause login/decryption failures (`PayloadSecurityMiddleware` errors) — see §8.

---

## 7. Phase 4 — Build, Start, Initialize

```powershell
# From repo root:
docker compose up -d --build
docker compose ps
docker compose logs --tail=80 auth-service
```

What the `auth-service` entrypoint (`auth-service/docker-entrypoint.sh`) does automatically — **do not run these manually unless the logs prove they were skipped**:

1. Waits for `db:3306` (`nc -z` loop).
2. `composer install --optimize-autoloader`.
3. Copies `.env.example` → `.env` if missing; generates `APP_KEY` if blank; generates `INTERNAL_ENCRYPTION_KEY` if empty/placeholder.
4. `php artisan migrate --force`; seeds **only if users table is empty** (`db:seed --force`); generates Passport keys + personal-access client if missing; `cache:clear` + `optimize:clear`.
5. `exec "$@"` → `php artisan serve --host=0.0.0.0 --port=8000`.

**Manual fallback** (only if logs show migrations/keys were skipped or failed):

```powershell
docker exec -it auth-service php artisan key:generate --force
docker exec -it auth-service php artisan migrate --seed
docker compose logs -f auth-service
```

Wait until `docker compose ps` shows `auth-service`, `queue-worker`, `web-interface`, `shared-nginx-proxy`, `mysql-db`, `auth-redis` as `Up`/`running`, and `db` healthcheck is `healthy` (up to ~60s on first boot).

---

## 8. Phase 5 — Verify (must all pass before declaring done)

```powershell
docker compose ps
docker compose exec -T auth-service php artisan --version
# Health endpoint (PowerShell):
Invoke-WebRequest -Uri "http://localhost:8000/up" -UseBasicParsing | Select-Object StatusCode, Content
# Or: curl.exe http://localhost:8000/up
docker compose logs --tail=30 auth-service
```

| Check                                                     | Expected                                                                              |
| :-------------------------------------------------------- | :------------------------------------------------------------------------------------ |
| `docker compose ps`                                       | 6 services `Up`; `mysql-db` `healthy`                                                 |
| `GET http://localhost:8000/up`                            | `200 OK`                                                                              |
| `GET http://localhost:5173` in browser                    | Login UI renders (Vue) — gateway routes `/` → web-interface, `/api` → backend         |
| `GET http://localhost:8000` (direct API)                  | Reachable (JSON or Laravel landing — not connection-refused)                          |
| `docker compose logs auth-service`                        | No `MissingAppKeyException`, no `Encryption mismatch`, no DB `SQLSTATE[HY000] [2002]` |
| Optional: `docker exec -it auth-service php artisan test` | PHPUnit suite passes (run if time permits)                                            |

If the browser shows the login page and `/up` returns 200, the stack is functionally done. Note any seeded admin credentials from `auth-service/database/seeders/*` in your report (do not print password hashes).

---

## 9. Phase 6 — Troubleshooting (consult before retrying blindly)

| Symptom                                                                 | Cause → Fix                                                                                                                                                                           |
| :---------------------------------------------------------------------- | :------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| `//./pipe/dockerDesktopLinuxEngine ... not found`                       | Docker Desktop not running → start/restart it, wait for green status, retry.                                                                                                          |
| `network shared-capstone-network ... not found` / `needs to be created` | Missed §6-3A → `docker network create shared-capstone-network`, then `up -d`.                                                                                                         |
| `port is already allocated` on `:8000`                                  | SERMS (or another stack) holds 8000 → `docker compose down` in the other repo first, or remap one compose port.                                                                       |
| `MissingAppKeyException`                                                | Entrypoint keygen skipped → `docker exec -it auth-service php artisan key:generate --force`; `docker compose restart auth-service`.                                                   |
| Login/decrypt errors, `PayloadSecurityMiddleware` failures              | `INTERNAL_ENCRYPTION_KEY` mismatch or wrong length → make root `.env` and `auth-service/.env` byte-identical, exactly 32 chars; `docker compose down; docker compose up -d`.          |
| `SQLSTATE[HY000] [2002] Connection refused` / auth-service restart loop | `mysql-db` not healthy yet → `docker compose ps`, `docker compose logs db`, wait, then `docker compose up -d --force-recreate db`.                                                    |
| Stale `vendor/` after branch switch                                     | Named volume `auth-service-vendor` cached old deps → entrypoint `composer install` normally heals it; else `docker compose up -d --build --force-recreate auth-service queue-worker`. |
| Mail never arrives (password reset, verification)                       | Expected with placeholder Mail/Brevo keys → check `docker compose logs -f queue-worker auth-service` for queued mail; supply real Mailtrap/Brevo creds and `up -d` again.             |

Useful daily commands:

```powershell
docker compose ps
docker compose logs -f            # all
docker compose logs -f auth-service
docker exec -it auth-service sh
docker exec -it mysql-db mysql -u root -p
docker compose down               # stop, keep volumes
# docker compose down -v          # DESTRUCTIVE: wipes MySQL + Redis — ask user first
```

---

## 10. Phase 7 — Stop / Reset

- **Stop (safe):** `docker compose down` — containers stop, volumes (DB, Redis, vendor) preserved.
- **Full reset (destructive):** `docker compose down -v` — wipes database and Redis. Only with explicit user approval.
- **Rebuild one service:** `docker compose up -d --build --force-recreate <service>`.

---

## 11. Definition of Done (all boxes must be checked)

- [ ] Docker Desktop running, `shared-capstone-network` exists.
- [ ] Operating from the repo root of this clone, branch/commit recorded in report.
- [ ] Root `.env` + `auth-service/.env` present; `INTERNAL_ENCRYPTION_KEY` identical, exactly 32 chars.
- [ ] `docker compose ps` shows all 6 services `Up`, `mysql-db` healthy.
- [ ] `GET http://localhost:8000/up` returns `200`.
- [ ] `GET http://localhost:5173` renders the login UI.
- [ ] Logs show migrations applied, no `MissingAppKeyException` / decryption / DB-connection errors.
- [ ] User was asked for Mail/Brevo secrets (or explicitly accepted local placeholders).
- [ ] Report lists: repo path, branch/commit, container states, health-check outputs, credentials source, and any deviations.

---

## 12. Appendix A — Bare-Metal (No Docker) Fallback

Use only if the user refuses Docker. Requires Phase 1C+1D plus MySQL 8.0 + Redis locally.

```powershell
# Backend:
Set-Location -LiteralPath "C:\Projects\capstone-auth-module\auth-service"
Copy-Item -LiteralPath ".\.env.example" -Destination ".\.env" -ErrorAction SilentlyContinue
composer install
php artisan key:generate
php artisan migrate --seed
php artisan serve --host=0.0.0.0 --port=8000

# Frontend (second terminal):
Set-Location -LiteralPath "C:\Projects\capstone-auth-module\web-interface"
npm install
npm run dev -- --host
```

Point `DB_HOST=127.0.0.1`, `REDIS_HOST=127.0.0.1` in `auth-service/.env` for host-local services. Bare-metal is **unsupported for grading parity** — prefer Docker whenever possible.

---

## 13. Appendix B — File Map (where things live)

```text
.
├── auth-service/          # Laravel API (Dockerfile, docker-entrypoint.sh, .env.example)
├── web-interface/         # Vue 3 + Vite + Tailwind + shadcn-vue (Dockerfile)
├── nginx/nginx.conf       # Shared gateway (routes / → UI, /api → backend)
├── docker-compose.yml     # 6 services, external shared-capstone-network
├── .env.example           # Root compose vars (DB, Redis, Mail, Brevo, INTERNAL_ENCRYPTION_KEY)
├── DOCKER.md              # Full Docker reference (troubleshooting source)
└── docs/Quickstart.md     # Short-form quickstart (this guide is the executable superset)
```
