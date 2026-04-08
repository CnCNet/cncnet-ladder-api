# Custom Lobby - Implementation Summary

## Problem

Need to track players, games, maps, stats, wins, and losses for Custom Lobby games.

## Solution

**Treat Custom Lobby as a non-competitive ladder** using existing infrastructure.

## Why This Approach?

The existing ladder system already does everything you need:

- ✅ Tracks all games played
- ✅ Tracks maps and game metadata
- ✅ Captures player stats (units, kills, resources, etc.)
- ✅ Records wins, losses, draws
- ✅ Stores game history
- ✅ Supports achievements

**No new models, tables, or APIs required.**

## Implementation (3 Steps)

### 1. Create Custom Lobby Ladder
```sql
INSERT INTO ladders (name, abbreviation, game, private)
VALUES ('Custom Lobby', 'CUSTOM', 'custom', 0);
```

### 2. Create Players (existing API)
```
POST /api/v1/player/create
{
  "ladder_id": <custom_lobby_id>,
  "username": "PlayerName"
}
```

### 3. Submit Game Results (existing API)
```
POST /api/v1/result/ladder/{customLobbyId}/game/{gameId}/player/{playerId}/pings/{sent}/{received}
```

**That's it.** Stats processing, tracking, and storage happen automatically.

## What About Rankings/ELO?

Custom Lobby doesn't need competitive features. Two options:

**Option A:** Don't display them in Custom Lobby UI (simplest)

**Option B:** Add `is_competitive` flag to skip calculations (cleaner)

## Benefits

| Benefit | Impact |
|---------|--------|
| **Fast Implementation** | Days instead of months |
| **Battle-Tested Code** | Reuse proven game processing |
| **Zero New Infrastructure** | No new models/tables/jobs |
| **Future Flexibility** | Easy to add features later |
| **Data Consistency** | Same schema across all games |

## Data Separation

Custom Lobby games are isolated by `ladder_id`:
- Won't appear in competitive rankings
- Won't affect ladder statistics
- Queries filter by `ladder_id` to separate data

## Quick Queries Example

```php
// Get custom lobby players
$players = Player::where('ladder_id', $customLobbyId)->get();

// Get recent games
$games = Game::where('ladder_id', $customLobbyId)
    ->orderBy('created_at', 'desc')
    ->get();

// Player stats
$player->wins($history);
$player->totalGames($history);
$player->sideUsage($history);
```
