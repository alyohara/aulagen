# Production operation

`docker-compose.yml` is for local development. Use `docker-compose.production.yml` only as a self-hosted baseline, behind an HTTPS reverse proxy that forwards requests to `127.0.0.1:8000`.

## First deployment

1. Copy `.env.production.example` to `.env.production` and set unique, production-only secrets.
2. Generate an application key with `php artisan key:generate --show` and store its result as `APP_KEY`.
3. Build and apply migrations as an explicit maintenance operation:

   ```bash
   docker compose --env-file .env.production -f docker-compose.production.yml --profile maintenance run --rm migrate
   ```

4. Start the application and worker:

   ```bash
   docker compose --env-file .env.production -f docker-compose.production.yml up -d --build
   ```

The production profile has no source bind mounts, does not publish database or Redis ports, disables debug mode, and never runs migrations automatically.

## Health and monitoring

- Check application health at `GET /up`; the Compose application health check uses this endpoint.
- Monitor `docker compose ... ps`, application logs, queue/Horizon logs, disk capacity, PostgreSQL health, and failed jobs.
- Alert on a non-healthy application container, sustained queue backlog, repeated failed jobs, and failed backups.

## Backup and recovery

Back up PostgreSQL and the persistent application storage before upgrades. Test restoration regularly in an isolated environment.

```bash
docker compose --env-file .env.production -f docker-compose.production.yml exec -T db \
  sh -c 'pg_dump -U "$POSTGRES_USER" "$POSTGRES_DB"' > aulagen-$(date +%F).sql
```

Store backups encrypted outside the deployment host. Define and enforce a retention period for uploaded materials, generated content, and AI generation history according to institutional policy.
