FROM nginx:1.27-alpine

# The official nginx image auto-renders /etc/nginx/templates/*.template with
# envsubst on startup. Our four *_URL env vars set by the Container App are
# substituted here; all native nginx variables ($host, $uri, ...) pass through.
COPY deploy/nginx.conf.tpl /etc/nginx/templates/default.conf.template

EXPOSE 5173