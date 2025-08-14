FROM node:20 AS builder
WORKDIR /app

# Install git + SSH client
RUN apt-get update && apt-get install -y git openssh-client && rm -rf /var/lib/apt/lists/*

RUN git clone --depth=1 https://github.com/tdmdevteam/onlymatch-reactapp.git repo
# Build frontend
WORKDIR /app/repo
RUN if [ -f package-lock.json ]; then npm ci --legacy-peer-deps; else npm install --legacy-peer-deps; fi
RUN npm run build

# Normalize build output
RUN if [ -d dist ]; then mv dist /app/static; \
    elif [ -d build ]; then mv build /app/static; \
    else echo "No dist/build directory found" && ls -la && exit 1; fi


FROM nginx:alpine
COPY --from=builder /app/static /usr/share/nginx/html
EXPOSE 80
