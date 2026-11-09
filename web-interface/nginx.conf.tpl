# Rendered at container start by the nginx image entrypoint (envsubst) using the
# AUTH_API_URL env var injected by the Container App (includes its http:// scheme).
server {
    listen 5000;
    server_name _;

    root /usr/share/nginx/html;
    index index.html;

    gzip on;
    gzip_types text/css application/javascript application/json image/svg+xml;

    # Static SPA: all routes fall back to index.html for client-side routing
    location / {
        try_files $uri $uri/ /index.html;
    }

    # Auth API gateway (same-origin /api -> auth-api, token from header or cookie)
    location /api/ {
        set $auth_header $http_authorization;
        if ($cookie_access_token) {
            set $auth_header "Bearer $cookie_access_token";
        }
        proxy_set_header Authorization $auth_header;
        proxy_set_header Cookie $http_cookie;
        proxy_set_header Host $proxy_host;
        proxy_set_header X-Forwarded-Host $host;
        proxy_pass ${AUTH_API_URL};
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Content-Type $content_type;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_pass_request_body on;
    }
}
