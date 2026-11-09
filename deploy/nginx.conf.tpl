# Template rendered by the nginx image entrypoint (envsubst) using the env vars
# injected by the azure Container App. Placeholders are substituted at container
# start; the upstream hostnames are resolved at nginx config load.
#
# Env vars provided by apps.bicep (each already includes its http:// or https:// scheme):
#   ${AUTH_WEB_URL}          http://auth-web.<env>.<region>.azurecontainerapps.io
#   ${AUTH_API_URL}          http://auth-api.<env>.<region>.azurecontainerapps.io
#   ${SERMS_WEB_URL}         http://serms-web.<env>.<region>.azurecontainerapps.io
#   ${SERMS_API_URL}         http://serms-api.<env>.<region>.azurecontainerapps.io
#   ${PRS_WEB_URL}           https://prs-web... or in-cluster http://...
#   ${PRS_API_URL}           https://prs-api... or in-cluster http://...
#   ${CMS_WEB_URL}           https://cms-web... or in-cluster http://... (Unified Gateway)
#   ${TICKETING_WEB_URL}     Ticketing UI
#   ${TICKETING_GATEWAY_URL} Ticketing Gateway / Storage

server {
    listen 5173;
    server_name _;

    client_max_body_size 50M;
    proxy_ssl_server_name on;
    proxy_ssl_protocols TLSv1.2 TLSv1.3;

    # Graceful offline response for un-deployed / hibernated subgroups
    error_page 502 503 504 = @subgroup_offline;
    location @subgroup_offline {
        default_type application/json;
        return 503 '{"status":"unavailable","message":"This subgroup service is currently not deployed, scaling up from idle, or offline. Please retry in a few moments."}\n';
    }

    # ------------------------------------------------------------------------
    # Auth module web UI at the root (login page, callback handler)
    # ------------------------------------------------------------------------
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

    # ------------------------------------------------------------------------
    # Auth API gateway
    # ------------------------------------------------------------------------
    location /api/ {
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

    # ------------------------------------------------------------------------
    # SERMS API gateway  (/api/serms/* -> /api/*)
    # ------------------------------------------------------------------------
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

    # Bare /serms (no trailing slash) -> /serms/
    location = /serms { return 302 https://$host/serms/; }

    # SERMS frontend at /serms/
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

    # ------------------------------------------------------------------------
    # PRS (Productivity Report System)
    # ------------------------------------------------------------------------
    location = /prs { return 302 https://$host/prs/; }

    location /prs/ {
        proxy_pass ${PRS_WEB_URL}/;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host $proxy_host;
        proxy_set_header X-Forwarded-Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }

    location /api/prs/ {
        set $auth_header $http_authorization;
        if ($cookie_access_token) {
            set $auth_header "Bearer $cookie_access_token";
        }
        proxy_set_header Authorization $auth_header;
        proxy_set_header Cookie $http_cookie;
        proxy_pass ${PRS_API_URL}/api/;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host $proxy_host;
        proxy_set_header X-Forwarded-Host $host;
        proxy_set_header Content-Type $content_type;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_pass_request_body on;
        proxy_read_timeout 180s;
    }

    # ------------------------------------------------------------------------
    # CMS (Contract Management System)
    # ------------------------------------------------------------------------
    location = /cms { return 302 https://$host/cms/; }

    # CMS frontend at /cms/ (Unified Gateway on CMS subscription)
    location /cms/ {
        proxy_pass ${CMS_WEB_URL}/cms/;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host $proxy_host;
        proxy_set_header X-Forwarded-Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_read_timeout 180s;
    }

    # CMS API gateways (/api/vendor, /api/contract, /api/search, /api/notification, /api/ai, /api/analytics)
    location ~ ^/api/(vendor|contract|search|notification|ai|analytics) {
        client_max_body_size 25m;
        set $auth_header $http_authorization;
        if ($cookie_access_token) {
            set $auth_header "Bearer $cookie_access_token";
        }
        proxy_set_header Authorization $auth_header;
        proxy_set_header Cookie $http_cookie;
        proxy_pass ${CMS_WEB_URL};
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host $proxy_host;
        proxy_set_header X-Forwarded-Host $host;
        proxy_set_header Content-Type $content_type;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_pass_request_body on;
        proxy_read_timeout 180s;
    }

    # ------------------------------------------------------------------------
    # TS (Ticketing System)
    # ------------------------------------------------------------------------
    location = /ticketing { return 302 https://$host/ticketing/; }

    location /ticketing/ {
        proxy_pass ${TICKETING_WEB_URL}/;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host $proxy_host;
        proxy_set_header X-Forwarded-Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }

    location /api/ticketing/ {
        set $auth_header $http_authorization;
        if ($cookie_access_token) { set $auth_header "Bearer $cookie_access_token"; }
        proxy_set_header Authorization $auth_header;
        proxy_set_header Cookie $http_cookie;
        proxy_pass ${TICKETING_GATEWAY_URL}/api/ticketing/;
        proxy_http_version 1.1;
        proxy_set_header Host $proxy_host;
        proxy_read_timeout 180s;
    }

    location /storage/ {
        proxy_pass ${TICKETING_GATEWAY_URL}/storage/;
        proxy_http_version 1.1;
        proxy_set_header Host $proxy_host;
    }
}