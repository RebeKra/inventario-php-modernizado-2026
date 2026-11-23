# 1) Compila la interfaz de React
FROM node:22-alpine AS interfaz
WORKDIR /app
COPY frontend/package.json frontend/package-lock.json ./
RUN npm ci
COPY frontend/ ./
RUN npm run build

# 2) nginx sirve la interfaz y enruta el resto
FROM nginx:1.27-alpine
COPY gateway/nginx.conf /etc/nginx/conf.d/default.conf
COPY --from=interfaz /app/dist /usr/share/nginx/html/productos
