## Yii2 Albums API - Local Run Instructions (Docker)

### What you get
- REST JSON endpoints:
  - `GET /users` (paginated)
  - `GET /users/{id}`
  - `GET /albums` (paginated)
  - `GET /albums/{id}`
- Demo data seeded via console commands
- Static demo images served from `/static-images/`

### Prerequisites
- Docker + Docker Compose v2
- `docker compose up` uses:
  - Nginx on `http://localhost:8080`
  - MySQL on host port `3307`

### 1) Configure seed password (do not commit)
1. Copy the example:
   ```bash
   cp config/seed_password_local.php.example config/seed_password_local.php
   ```
2. Edit `config/seed_password_local.php` to set your real password.
3. Ensure you do NOT commit `config/seed_password_local.php` (it is gitignored).

### 2) Run everything with one script

**Execute permission (Linux / macOS):** after `git clone`, the shell script may not be executable. Either run it explicitly with Bash (no `chmod` needed):

```bash
bash scripts/run-local-docker.sh
```

or mark it executable once:

```bash
chmod +x scripts/run-local-docker.sh
./scripts/run-local-docker.sh
```

On Unix you can also run `make run-local` (calls `bash scripts/run-local-docker.sh` so the script does not need the execute bit).

**Windows:** use PowerShell from the project root:

```powershell
pwsh scripts/run-local-docker.ps1
```

Optional tests:

```powershell
pwsh scripts/run-local-docker.ps1 --with-tests
```

From `/var/www/yii2-albums-api` (or project root):

```bash
bash scripts/run-local-docker.sh
```

This script runs:
- `docker compose up -d --build`
- `composer install` (only if `vendor/autoload.php` is missing in container)
- Yii2 migrations for the main DB
- `yii seed/all` (10 users, 100 albums, 1000 photos)
- Yii2 migrations for the test DB (`yii2basic_test`, for Codeception)
- Unit tests + functional API tests (when `--with-tests` is provided)

Optional: run tests too
```bash
bash scripts/run-local-docker.sh --with-tests
```

### Seeder commands (optional parameters)
All seeders read the password from `config/seed_password_local.php`.

- Users only (defaults to `user_1..user_10`):
  ```bash
  SEED_USERS_PREFIX=user_ SEED_USERS_COUNT=10 bash scripts/run-local-docker.sh --seed-users
  ```
- Albums only (defaults to `10` per user, expects users `user_1..user_10` already exist):
  ```bash
  SEED_ALBUMS_PER_USER=10 bash scripts/run-local-docker.sh --seed-albums
  ```
- Photos only (defaults to `10` per album):
  ```bash
  SEED_PHOTOS_PER_ALBUM=10 bash scripts/run-local-docker.sh --seed-photos
  ```

### API endpoints (examples)
- List users:
  ```bash
  curl "http://localhost:8080/users?page=1&per-page=10"
  ```
- User detail:
  ```bash
  curl "http://localhost:8080/users/1"
  ```
- List albums:
  ```bash
  curl "http://localhost:8080/albums?page=1&per-page=10"
  ```
- Album detail:
  ```bash
  curl "http://localhost:8080/albums/1"
  ```

Pagination query params:
- `page` (default `1`)
- `per-page` (default `10`, max `50`)

### Static images
Photos expose a virtual `url` computed as:
- `/static-images/demo-NN.png` where `NN` is picked deterministically based on `photo.id`.

Example:
```bash
curl "http://localhost:8080/static-images/demo-01.png"
```

