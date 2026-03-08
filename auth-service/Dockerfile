FROM node:22.12-alpine

WORKDIR /srv/auth

COPY auth-service/package.json auth-service/package-lock.json ./
RUN npm ci --ignore-scripts

COPY auth-service .

RUN chmod +x ./scripts/entrypoint.sh

EXPOSE 3001

ENTRYPOINT ["./scripts/entrypoint.sh"]
