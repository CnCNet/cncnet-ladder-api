# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is the **CnCNet Ladder API**, a Laravel 11-based competitive ladder/ranking system for classic Command & Conquer games (Red Alert, Yuri's Revenge, Tiberian Sun, Dune 2000). It provides 1v1, 2v2, and clan match support with automated Quick Match (QM) matchmaking, ELO ratings, player statistics, and achievement tracking.

**Tech Stack**: Laravel 11, PHP 8.3, FrankenPHP (Laravel Octane), MariaDB, Redis, Bootstrap 5, Vite

## Development Commands

### Initial Setup
```bash
# Build and start development containers
docker compose -f docker-compose.dev.yml build
docker compose -f docker-compose.dev.yml up -d

# Generate Laravel app key
docker exec dev_cncnet_ladder_app php artisan key:generate

# Migrate database (or restore from backup)
docker exec dev_cncnet_ladder_app php artisan migrate
```

### Common Development Tasks
```bash
# Clear cache after .env changes
docker exec dev_cncnet_ladder_app php artisan optimize:clear

# Run queue workers (manual in dev)
docker exec -it dev_cncnet_ladder_app php artisan queue:listen --queue=findmatch,saveladderresult

# Run scheduler/cron manually
docker exec dev_cncnet_ladder_app php artisan scheduler:run

# Start Vite dev server (hot reload)
docker exec -it dev_cncnet_ladder_app npm run dev

# Watch SCSS changes
npm run watch

# Open shell in container
docker exec -it dev_cncnet_ladder_app bash

# Access database
# Host: localhost, Port: 3307, User/Pass from .env
```

### Testing
```bash
# Run PHPUnit tests
docker exec dev_cncnet_ladder_app php artisan test
```

### Production Commands (CI/CD automated)
```bash
# Build production containers
docker compose build

# Clear config cache after deployment
docker exec cncnet_ladder_app php artisan config:cache
```

## Architecture & Code Structure

### Laravel Application Structure

The main application lives in `cncnet-api/`. Key architectural patterns:

**Controllers** (`app/Http/Controllers/`):
- `ApiLadderController`: REST API for ladder data, game results, player stats
- `ApiQuickMatchController`: QM matchmaking, map pools, rankings
- `LadderController`: Web UI views
- `AdminController`: Admin panel functionality
- API versioning: v1 (main), v2 (new endpoints)

**Services** (`app/Http/Services/`):
Business logic layer:
- `QuickMatchService`: Core matchmaking logic
- `EloService`: Rating calculations using ELO algorithm
- `GameService`: Game result processing and validation
- `LadderService`: Ladder operations and caching
- `PlayerService`: Player management and ratings
- `AchievementService`: Achievement tracking

**Queue System**:
Two separate queues processed by dedicated workers:
- `findmatch`: Handles opponent finding (`FindOpponentJob`)
- `saveladderresult`: Processes game results (`SaveLadderResultJob`)

Queue jobs live in `app/Jobs/Qm/`

**Extensions** (`app/Extensions/`):
Matchup handlers for different game modes:
- `PlayerMatchupHandler`: 1v1 matchmaking
- `TeamMatchupHandler`: 2v2+ matchmaking
- `ClanMatchupHandler`: Clan match logic

### Database Schema (70+ Models)

**Core Models**:
- `Ladder`: Game type definitions (RA2, YR, TS, etc.)
- `LadderHistory`: Monthly ladder snapshots
- `Player`: Player accounts (per-ladder)
- `User`: Global user accounts
- `Game`: Individual game records
- `GameReport`: Player-submitted game results (can have multiple per game)
- `PlayerGameReport`: Individual player performance in a game
- `QmMatch`: Quick Match game records
- `QmMatchPlayer`: Player participation in QM match
- `QmQueueEntry`: Active matchmaking queue entries
- `PlayerRating`/`UserRating`: ELO rating storage
- `Clan`: Clan/team entities with ratings
- `Ban`: Player ban records

**Important Relationships**:
- `Game` has many `GameReport`s (one is marked `best_report`)
- `GameReport` has many `PlayerGameReport`s
- `Player` belongs to `Ladder` and `User`
- `QmMatch` has many `QmMatchPlayer`s

### Quick Match Flow

1. Client requests match via `POST /api/v1/qm/{ladder}/{player}` → `MatchUpController`
2. Request validated through middleware: ClientUpToDate, ShadowBan, Ban, VerifiedEmail
3. `QmQueueEntry` created for player
4. `FindOpponentJob` dispatched to queue
5. Job selects appropriate handler (Player/Team/Clan MatchupHandler)
6. Matching algorithm considers:
   - ELO ratings and tier placement
   - Map preferences/vetoes
   - Faction policies (allowed pairings)
   - Connection quality/ping
7. `QmMatch` created with spawn parameters sent to clients
8. Game results submitted via `POST /api/v1/result/ladder/{ladderId}/game/{gameId}/player/{playerId}/pings/{sent}/{received}`
9. `SaveLadderResultJob` processes stats dump file
10. Points calculated (`awardPlayerPoints`, `awardTeamPoints`, or `awardClanPoints` methods)
11. ELO ratings updated via `EloService`
12. Player cache updated via `PlayerCache` model

### Middleware & Caching

**Custom Cache Middleware** (all public):
- `CacheUltraShortPublic`: 10 seconds (IRC endpoints)
- `CacheShortPublic`: 30 seconds
- `CachePublicMiddleware`: 1 minute (player stats, short-lived data)
- `CacheLongPublicMiddleware`: 60 minutes (ladder listings, static data)

**Restriction Middleware**:
- `Restrict`: Permission checking for admin actions
- `BanMiddleware`: Checks active bans before QM
- `VerifiedEmailMiddleware`: Requires verified email
- `ClientUpToDateMiddleware`: Client version validation

### Docker Architecture

**Development** (`docker-compose.dev.yml`):
- Single `app` container with volume mounts for hot reload
- MySQL exposed on port 3307
- PHPMyAdmin on port 8080
- Vite dev server on port 5173
- Queue/scheduler NOT running (manual start required)

**Production** (`docker-compose.yml`):
- `app`: FrankenPHP web server (port 3000→8000)
- `queue-findmatch`: Dedicated queue worker
- `queue-saveladderresult`: Dedicated queue worker
- `scheduler`: Laravel scheduler (cron tasks)
- `mysql`: MariaDB database
- `redis`: Cache and queue backend
- `db-backup`: Automated backups using tiredofit/db-backup
- `elogen`: ELO computation cron container

**Multi-stage Build**: Dockerfiles in `docker/frankenphp/` and `docker/workers/`

### Configuration Files

**Environment Files**:
- `.env`: Docker-specific (HOST_USER, HOST_UID, APP_TAG, ports)
- `.app.env`: Laravel application config (production)
- `.backup.env`: Backup container settings
- `cncnet-api/.env`: Development Laravel config

**Key Laravel Configs**:
- `config/types.php`: Game type definitions (28KB of metadata)
- `config/cameos.php`: Unit cameo mappings
- `config/jwt.php`: JWT authentication
- `config/octane.php`: FrankenPHP/Octane settings

### Scheduled Tasks

Defined in `bootstrap/app.php` (Laravel 11 structure):
- **Daily**: Log pruning, stats cleanup
- **Hourly**: Player cache updates (`UpdatePlayerCache` command)
- **Monthly**: QM data pruning, player rating updates
- **Every Minute**: Clear inactive queue entries

### Routes Structure

**Web Routes** (`routes/web.php`): 400+ lines
- Ladder views, player profiles, admin panel
- Grouped by authentication and permissions

**API Routes** (`routes/api.php`):
- `v1`: Main API (auth, ladder, QM, results)
- `v2`: New endpoints (bans, events, user accounts)
- Middleware groups for caching and authentication

### Game Result Processing

When game results are submitted:

1. Stats dump file uploaded and moved to `config('filesystems.dmp')` directory
2. `SaveLadderResultJob` queued
3. Job calls `GameService::processStatsDmp()` to parse binary stats
4. `GameReport` created or updated
5. Dispute handling: Multiple reports compared, best report selected based on:
   - Finished status (prefer finished over disconnected)
   - Duration (prefer longer games)
   - Ping difference (prefer better connection)
6. If both reports show disconnect/OOS: auto-wash game (create draw report)
7. Points awarded via `awardPlayerPoints()`, `awardTeamPoints()`, or `awardClanPoints()`
8. Achievement progress tracked (`updateAchievements()`)
9. Player cache updated via `LadderService::updateCache()`

### Achievement System

Two types:
- **CAREER**: Cumulative tracking (e.g., build 1000 tanks)
- **IMMEDIATE**: Single-game threshold (e.g., build 50 tanks in one game)

Tracked via `Achievement`, `AchievementProgress`, and `GameObjectCounts` models.

### Anti-Cheat & Moderation

- IP address tracking via `IpAddress` and `IpAddressHistory`
- Admin actions logged via Spatie ActivityLog
- Game "washing" (marking as draw) for:
  - Mutual disconnects
  - Mutual out-of-sync
  - Suspected abuse
- Shadow bans (queue but never match)
- Admin panel for manual intervention

### Important Code Patterns

**Finding Ladder by Abbreviation**:
```php
$ladder = Ladder::where('abbreviation', '=', $game)->first();
```

**Getting Current Ladder History** (monthly snapshot):
```php
$history = $ladder->currentHistory();
```

**Querying Player Games with Joins**:
Always join through `game_reports` and `games` tables, filtering on `valid` and `best_report`:
```php
PlayerGameReport::where('player_game_reports.player_id', '=', $playerId)
    ->join('game_reports', 'game_reports.id', '=', 'player_game_reports.game_report_id')
    ->join('games', 'games.id', '=', 'game_reports.game_id')
    ->where('game_reports.valid', '=', true)
    ->where('game_reports.best_report', '=', true)
```

**Table Prefixing**: Always prefix ambiguous columns (e.g., `player_game_reports.player_id` not `player_id`) when joining multiple tables.

## CI/CD Pipeline

**GitHub Actions** (`.github/workflows/build-and-deploy.yml`):

1. **Build Job**: Builds 3 images in parallel (app, queue, scheduler)
   - Tagged with: `latest`, branch name, short SHA
   - Pushed to GitHub Container Registry (ghcr.io)

2. **Deploy Job** (main branch only):
   - Uploads `docker-compose.yml` via SCP
   - SSH to server
   - Updates `.env` with new image tag
   - Pulls images and restarts containers
   - Runs `php artisan config:cache`

**Deployment is automatic** on merge to main branch.

## Development Notes

### Laravel 11 Changes
- No `app/Http/Kernel.php` - middleware in `bootstrap/app.php`
- Scheduling in `bootstrap/app.php` instead of `app/Console/Kernel.php`
- Slimmer directory structure

### FrankenPHP (Octane)
- High-performance PHP application server
- Uses Caddy web server under the hood
- Configured via `OCTANE_SERVER=frankenphp` in `.env`
- Startup script: `docker/frankenphp/octane.sh`

### Queue Workers
- Run in separate containers for horizontal scaling
- Each queue has dedicated worker (findmatch, saveladderresult)
- Worker script: `docker/workers/queue.sh`

### Database Access
- **Dev**: localhost:3307 (exposed)
- **Dev UI**: PHPMyAdmin at localhost:8080
- Migrations in `database/migrations/` (42 files)
- Schema dumps in `database/schema/`

### Assets
- Vite build system (`vite.config.ts`)
- SCSS in `resources/stylesheets/`
- TypeScript in `resources/typescript/`
- Build output to `public/build/`
- Dev server: port 5173 (hot reload)

### Elogen Container
Separate cron-based ELO computation system:
- Configured via `docker/elogen/crontab`
- Reads/writes to `storage/app/rating/`
- Independent calculation for validation

## Access URLs

- **Development Web UI**: http://localhost:3000
- **Development PHPMyAdmin**: http://localhost:8080
- **Development Vite**: http://localhost:5173
- **Production**: https://ladder.cncnet.org

## Troubleshooting

**"No supported encrypter found"**:
- Generate key: `docker exec dev_cncnet_ladder_app php artisan key:generate`
- Update .env then rebuild: `docker compose -f docker-compose.dev.yml up -d`

**"Base table or view not found"**:
- Restore from backup OR run migrations: `docker exec dev_cncnet_ladder_app php artisan migrate`

**Queue jobs not processing**:
- In dev, queue workers don't auto-start
- Manual start: `docker exec -it dev_cncnet_ladder_app php artisan queue:listen --queue=findmatch,saveladderresult`

**"Column 'X' is ambiguous" SQL errors**:
- Always prefix columns with table name when joining (e.g., `player_game_reports.player_id`)

**WSL2 Docker integration issues**:
- Check Docker Desktop → Settings → Resources → WSL Integration
- Ensure Ubuntu distro is enabled
- Restart Docker Desktop or run `wsl --shutdown` and restart
