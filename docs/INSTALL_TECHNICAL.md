# Technical installation guide

## Requirements

### Recommended: Docker

- Docker Desktop 20.10+ and Docker Compose v2
- Git
- Ports `8000`, `5432`, and `6379` available on the host

### Local development

- PHP 8.2+ and Composer
- Node.js 22+ and npm
- SQLite, or PostgreSQL and Redis if you prefer a production-like local environment
- PHP extensions used by the application: `pdo_sqlite` or `pdo_pgsql`, `mbstring`, `xml`, `zip`, `intl`, `bcmath`, `pcntl`, `sockets`, and `redis`

Ollama and a compatible model are optional when you want local generative AI. A GPU is optional.

## Docker setup

Docker Compose starts the web application, a Horizon worker, PostgreSQL with pgvector, and Redis.

```bash
git clone https://github.com/alyohara/aulagen.git
cd aulagen
cp .env.example .env
docker compose up -d --build
```

The entrypoint generates an application key and runs migrations when `RUN_MIGRATIONS=true`. If a key still needs to be generated, run:

```bash
docker compose exec app php artisan key:generate
```

Open <http://localhost:8000>.

### Optional Ollama service

Start the optional Ollama container with its Compose profile:

```bash
docker compose --profile ollama up -d
```

The container exposes Ollama at `http://localhost:11434`. Pull the model you configured before using it:

```bash
docker compose exec ollama ollama pull llama3.2
docker compose exec ollama ollama pull nomic-embed-text
```

### Useful Docker commands

```bash
# View running services and application logs
docker compose ps
docker compose logs -f app

# Run Laravel commands
docker compose exec app php artisan migrate --force
docker compose exec app php artisan tinker

# Stop the stack while keeping named volumes
docker compose down
```

## Local setup

The default `.env.example` uses SQLite and the local AI auto/fallback settings.

```powershell
git clone https://github.com/alyohara/aulagen.git
Set-Location aulagen

composer install
npm install
Copy-Item .env.example .env
php artisan key:generate
New-Item -ItemType File -Force database\database.sqlite
php artisan migrate --seed
npm run build
php artisan serve --host=127.0.0.1 --port=8000
```

Run the queue worker in another terminal when processing uploaded content:

```powershell
php artisan horizon
```

For an all-in-one development process, use:

```powershell
composer run dev
```

## Demo users

Database seeding creates the following accounts. The password is `password` for all of them.

| Role | Email |
| --- | --- |
| Administrator | `admin@aulagen.test` |
| Teacher | `docente@aulagen.test` |
| Student | `alumno@aulagen.test` |

These credentials are for local development only. Remove or replace them for every non-development environment.

## AI providers

Set `AI_PROVIDER` in `.env` to one of:

| Value | Use case |
| --- | --- |
| `auto` | Try `AI_AUTO_PRIMARY`, then use `AI_FALLBACK` if it fails |
| `ollama` | Local or self-hosted Ollama service |
| `gemini` | Google Gemini API |
| `openai` | OpenAI API |
| `custom` | An OpenAI-compatible endpoint |
| `local` | Built-in heuristic fallback without a remote model |

Example local Ollama configuration:

```dotenv
AI_PROVIDER=ollama
OLLAMA_HOST=http://127.0.0.1:11434
OLLAMA_MODEL=llama3.2
OLLAMA_EMBED_MODEL=nomic-embed-text
AI_EMBEDDING_DIM=768
AI_RAG_TOP_K=6
AI_RATE_LIMIT=10
AI_MAX_UPLOAD_MB=40
```

For Gemini or OpenAI, configure the corresponding API key in `.env`. Never commit `.env` or production keys.

## Background jobs and storage

Document processing, curriculum proposals, lesson content, and activity generation can be queued. Docker starts Horizon in the `queue` service; in local environments, run `php artisan horizon` or an appropriate Laravel queue worker.

Uploaded files use Laravel storage. Configure a persistent storage disk and run `php artisan storage:link` if your deployment serves public files from the local disk.

## Production checklist

Docker Compose is not a complete production deployment by itself. Before going live:

1. Set `APP_ENV=production`, `APP_DEBUG=false`, and a unique secure `APP_KEY`.
2. Use secure PostgreSQL, Redis, and AI-provider credentials.
3. Set a correct HTTPS `APP_URL`, use a TLS-capable reverse proxy, and configure trusted hosts.
4. Persist database and application storage volumes, and back them up.
5. Run a reliable queue worker or Horizon supervisor.
6. Review AI-provider data handling and ensure it meets your institution's privacy requirements.
7. Create non-demo administrator accounts and remove demo users.

## Validation and troubleshooting

```powershell
# Clear cached Laravel configuration after .env changes
php artisan optimize:clear

# Re-run migrations and seed local data
php artisan migrate:fresh --seed

# Build the frontend and check TypeScript
npm run build

# Execute tests
vendor\bin\phpunit
```

If Docker cannot connect to the database, wait for the `db` health check and inspect `docker compose logs db app`. If an AI provider fails, use the administrator AI settings page to test its connection, then check the provider URL, model name, and API key.
