# Custom Lobby Player Tracking - Design Document

## Overview

The CnCNet Ladder API currently supports competitive ladder play for various C&C games. This document outlines the approach for adding player tracking, statistics, and game history for the **Custom Lobby** feature using the existing ladder infrastructure.

## Requirements

Custom Lobby needs to track:
- All games played by players
- Maps played on
- Player statistics (units built, kills, resources, etc.)
- Wins and losses
- Game history and performance metrics

## Recommended Approach: Leverage Existing Ladder System

**Treat Custom Lobby as a non-competitive ladder** within the existing infrastructure rather than creating separate models and tables.

### Why This Approach?

The existing ladder system already provides **all required functionality**:

| Feature | Already Available |
|---------|-------------------|
| Game tracking | `Game` and `GameReport` models |
| Map tracking | `scen` field in games table |
| Player stats | `stats2` table (comprehensive unit/economy stats) |
| Win/Loss tracking | `PlayerGameReport` model (`won`, `defeated`, `draw`) |
| Game metadata | Duration, FPS, disconnects, OOS tracking |
| Side/Country usage | Built-in methods on Player model |
| Achievement system | `GameObjectCounts` and `AchievementProgress` |
| Historical queries | `lastFiveGames()`, `totalGames()`, etc. |

## Technical Implementation

### 1. Database Setup

Create a new `Ladder` record for Custom Lobby:

```sql
INSERT INTO ladders (name, abbreviation, game, private)
VALUES ('Custom Lobby', 'CUSTOM', 'custom', 0);
```

**Note:** Record the generated `id` - this is your `customLobbyLadderId`

### 2. Player Creation

**No new API needed.** Use existing endpoint:

```
POST /api/v1/player/create
Headers: Authorization: Bearer <jwt_token>
Body: {
  "ladder_id": <customLobbyLadderId>,
  "username": "PlayerName"
}
```

**Controller:** `ApiPlayerController::createPlayer()`

### 3. Game Result Submission

**No new API needed.** Use existing endpoint:

```
POST /api/v1/result/ladder/{customLobbyLadderId}/game/{gameId}/player/{playerId}/pings/{sent}/{received}
Headers: Authorization: Bearer <jwt_token>
Body: <stats dump file upload>
```

**Processing Flow:**
1. `SaveLadderResultJob` queued automatically
2. Stats dump parsed via `GameService::processStatsDmp()`
3. `GameReport` and `PlayerGameReport` created
4. Statistics extracted and stored in `stats2` table
5. Win/loss/draw status recorded
6. Player cache updated

### 4. Querying Custom Lobby Data

Filter by `ladder_id` in all queries:

```php
// Get all custom lobby players
$customPlayers = Player::where('ladder_id', $customLobbyLadderId)->get();

// Get custom lobby games
$customGames = Game::where('ladder_id', $customLobbyLadderId)
    ->orderBy('created_at', 'desc')
    ->get();

// Player stats in custom lobby
$player = Player::where('ladder_id', $customLobbyLadderId)
    ->where('username', 'PlayerName')
    ->first();

$wins = $player->wins($history);
$totalGames = $player->totalGames($history);
$sideUsage = $player->sideUsage($history);
```

### 5. UI/API Endpoints

Create custom lobby-specific endpoints if needed:

```php
// routes/api.php
Route::get('/custom-lobby/players', [ApiCustomLobbyController::class, 'getPlayers']);
Route::get('/custom-lobby/games/recent', [ApiCustomLobbyController::class, 'getRecentGames']);
Route::get('/custom-lobby/player/{username}', [ApiCustomLobbyController::class, 'getPlayerStats']);
```

## Architecture Benefits

### Advantages

1. **Zero New Infrastructure**
   - No new models, migrations, or database tables
   - No new queue jobs or processing logic
   - Reuse 70+ existing models and relationships

2. **Battle-Tested Code**
   - Game result processing is mature and reliable
   - Stats parsing handles edge cases (disconnects, OOS, disputes)
   - Achievement tracking already implemented

3. **Development Speed**
   - Implementation in days vs. weeks/months
   - Focus on UI/UX instead of backend logic
   - Leverage existing admin tools

4. **Data Consistency**
   - Single source of truth for game data
   - Unified player accounts across competitive and casual play
   - Consistent stats schema

5. **Future Flexibility**
   - Easy to add competitive features later (rankings, ELO)
   - Can share achievements between ladder and custom lobby
   - Unified analytics and reporting

### Handling Competitive Features

Custom Lobby likely **doesn't need** competitive features like:
- ELO ratings and matchmaking
- Tiered rankings (Bronze/Silver/Gold)
- Point-based leaderboards
- Monthly ladder resets

**Two Options:**

#### Option A: Ignore Competitive Data (Simplest)
- Data gets tracked in database but not displayed in Custom Lobby UI
- Zero code changes required
- Custom Lobby UI only shows games, wins, losses, stats

#### Option B: Add Ladder Type Flag (Cleaner)
- Add `is_competitive` boolean column to `ladders` table:
  ```sql
  ALTER TABLE ladders ADD COLUMN is_competitive BOOLEAN DEFAULT TRUE;
  UPDATE ladders SET is_competitive = FALSE WHERE abbreviation = 'CUSTOM';
  ```
- Modify `SaveLadderResultJob` to skip ELO/points/tier calculations when `$ladder->is_competitive === false`
- Prevents unnecessary calculations and keeps database cleaner

## Considerations

### Data Isolation

Custom Lobby games are **isolated by ladder_id**:
- Won't appear in competitive ladder rankings
- Won't affect competitive ladder statistics
- Queries must filter by `ladder_id` to separate data

### Storage

Custom Lobby games share database with ladder games:
- Monitor database growth if custom lobby becomes high-volume
- Existing backup and archival processes apply
- Consider adding indexes if custom lobby has different query patterns

### Authentication

Custom Lobby uses same auth system:
- Players need verified email (if `VerifiedEmailMiddleware` applied)
- JWT tokens for API access
- Same user accounts as competitive ladders

## Implementation Checklist

- [ ] Insert Custom Lobby ladder record in database
- [ ] Record `ladder_id` for Custom Lobby in application config
- [ ] Update client to call `/api/v1/player/create` with Custom Lobby `ladder_id`
- [ ] Update client to submit game results to existing result API
- [ ] Create Custom Lobby UI to display player stats
- [ ] Create API endpoints for Custom Lobby listings (optional)
- [ ] Add filtering in admin panel to separate custom lobby data
- [ ] Test game result submission and stats processing
- [ ] Decide on Option A vs B for competitive features
- [ ] Document Custom Lobby ladder ID in environment config

## Configuration

Add to `.env` or `config/ladders.php`:

```php
// config/ladders.php
return [
    'custom_lobby_id' => env('CUSTOM_LOBBY_LADDER_ID', 7), // Update with actual ID
];
```

Then reference in code:
```php
$customLobbyId = config('ladders.custom_lobby_id');
```

## API Documentation

### Create Custom Lobby Player

```
POST /api/v1/player/create
Authorization: Bearer {jwt_token}
Content-Type: application/json

{
  "ladder_id": 7,  // Custom Lobby ladder ID
  "username": "PlayerName"
}

Response 200:
{
  "id": 12345,
  "username": "PlayerName",
  "ladder_id": 7,
  "wins_count": 0,
  "loss_count": 0,
  "games_count": 0
}
```

### Submit Custom Lobby Game Result

```
POST /api/v1/result/ladder/{customLobbyId}/game/{gameId}/player/{playerId}/pings/{sent}/{received}
Authorization: Bearer {jwt_token}
Content-Type: multipart/form-data

Body: <stats dump file>

Response 200:
{
  "success": true,
  "game_report_id": 67890
}
```

## Future Enhancements

Possible additions if needed:
- Custom lobby-specific map pool management
- Game browser/lobby listing API
- Custom lobby-specific achievements
- Private game support
- Custom game modes/variants

## Questions & Decisions

1. **Should custom lobby games award achievements?**
   - Recommendation: Yes, using existing system

2. **Should users see combined stats (ladder + custom) or separate?**
   - Recommendation: Separate views, filtered by ladder_id

3. **Should custom lobby have its own ladder history snapshots?**
   - Recommendation: Not needed unless monthly resets desired

## Alternative Considered

**Creating separate CustomLobbyPlayer model** was considered but **rejected** because:
- Duplicates all game tracking infrastructure
- Requires new database tables and migrations
- Requires new game processing jobs
- Months of additional development
- Doesn't provide value over ladder approach given requirements

## Summary

By treating Custom Lobby as a non-competitive ladder within the existing infrastructure, we gain:
- **Fast implementation** - Days instead of weeks/months
- **Reliable tracking** - Battle-tested game processing
- **All required features** - Games, maps, stats, wins/losses
- **Future flexibility** - Easy to add competitive features later
- **Zero new infrastructure** - Leverage existing models and APIs

This approach maximizes code reuse while maintaining clean separation between competitive ladder and casual custom lobby play.

---

**Document Version:** 1.0
**Date:** 2026-04-07
**Author:** Technical Design Review
