# Template rendered by the nginx image entrypoint (envsubst) using the env vars
# injected by the azure Container App. Placeholders are substituted at container
# start; the upstream hostnames are resolved at nginx config load.
#
# Env vars provided by apps.bicep (each already includes its http:// scheme),
# so the proxy_pass lines below must NOT add another scheme:
#   ${AUTH_WEB_URL}   http://auth-web.<env>.<region>.azurecontainerapps.io
#   ${AUTH_API_URL}   http://auth-api.<env>.<region>.azurecontainerapps.io
#   ${SERMS_WEB_URL}  http://serms-web.<env>.<region>.azurecontainerapps.io
#   ${SERMS_API_URL}  http://serms-api.<env>.<region>.azurecontainerapps.io
#
# PRS / CMS are NOT part of this deployment, so their routes return 503.

server {
    listen 5173;
    server_name _;

    # Auth module web UI at the root (login page, callback handler)
    location / {
        proxy_pass ${AUTH_WEB_URL};
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host $proxy_host;
        proxy_set_header X-Forwarded-Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }

    # Auth API gateway
    location /api/ {
        # php2shell hardening (auth API only): JSON-only API, so cap body size
        # and reject multipart. /api/serms/ matches a longer prefix first.
        client_max_body_size 2m;
        if ($content_type ~* "multipart/form-data") {
            return 415;
        }

        set $auth_header $http_authorization;
        if ($cookie_access_token) {
            set $auth_header "Bearer $cookie_access_token";
        }
        proxy_set_header Authorization $auth_header;
        proxy_set_header Cookie $http_cookie;
        proxy_pass ${AUTH_API_URL};
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host $proxy_host;
        proxy_set_header X-Forwarded-Host $host;
        proxy_set_header Content-Type $content_type;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_pass_request_body on;
    }

    # SERMS API gateway  (/api/serms/* -> /api/*)
    location /api/serms/ {
        set $auth_header $http_authorization;
        if ($cookie_access_token) {
            set $auth_header "Bearer $cookie_access_token";
        }
        proxy_set_header Authorization $auth_header;
        proxy_set_header Cookie $http_cookie;
        proxy_pass ${SERMS_API_URL}/api/;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host $proxy_host;
        proxy_set_header X-Forwarded-Host $host;
        proxy_set_header Content-Type $content_type;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_pass_request_body on;
    }

    # Bare /serms (no trailing slash) -> /serms/ so the frontend block matches
    location = /serms { return 302 https://$host/serms/; }

    # SERMS frontend at /serms/ (strips the /serms prefix for the static bundle)
    location /serms/ {
        proxy_pass ${SERMS_WEB_URL}/;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host $proxy_host;
        proxy_set_header X-Forwarded-Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }

    # --- Non-deployed subsystems (PRS, CMS) ---
    location /api/prs/ { return 503 "PRS not deployed in this environment"; }
    location /cms/     { return 503 "CMS not deployed in this environment"; }
    location ~ ^/api/(vendor|contract|search|notification) {
        return 503 "CMS not deployed in this environment";
    }
}