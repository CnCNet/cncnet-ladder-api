## Player ratings

- `player_ratings` table contains elo rating. 
- `player_histories` contains past `ladder_history_id`, `player_id` and `tier`.
- `PlayerRatingService` has a `calculatePlayerTier()` to assign player tiers based on ladder settings. 
Currently only Blitz will assign tiers, other ladders will only have 1 tier.


### How player tier is updated:

1. `player_caches` is updated every hour via cron, see `UpdatePlayerCache`. In that it calls.
```php
$pc = \App\PlayerCache;
$playerHistory = $player->playerHistory($history);
$pc->tier = $playerHistory->tier;
// ...
$pc-save();
```

2. It calls `playerHistory()` on `\App\Player`. This checks for player_history for the the current ladder. 
If its empty, it will create a new one for the month and assign a tier. 

3. By default it will check  the following before assigning a tier:

- Check for other `player_ratings` a user owns across that game type (E.g yr) and take the highest rating from that. This stops experienced players from RA2/YR coming into the wrong league in Blitz if they've never played before.

- Checks the players rating against the `Tier 2 If Rating Below` admin setting in the ladder. If a players elo rating is below this, they are moved to Tier 2, otherwise assigned Tier 1. 
