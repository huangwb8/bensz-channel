FROM node:22.12-alpine

WORKDIR /srv/auth

COPY auth-service/package.json auth-service/package-lock.json ./

# 支持本地缓存目录挂载
ARG NPM_CACHE_DIR_AUTH
RUN --mount=type=cache,target=/tmp/npm-cache,sharing=locked \
    npm_config_cache=${NPM_CACHE_DIR_AUTH:-/tmp/npm-cache} \
    npm ci --ignore-scripts

COPY auth-service .
# 复制 app/config.toml（应用基础配置）和 config/.env（用户自定义配置）
COPY app/config.toml /srv/app/config.toml
COPY config /srv/config

RUN chmod +x ./scripts/entrypoint.sh

EXPOSE 3001

ENTRYPOINT ["./scripts/entrypoint.sh"]
