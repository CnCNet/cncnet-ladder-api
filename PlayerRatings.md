## Player ratings

- `player_ratings` table contains elo rating. 
- `player_histories` contains past `ladder_history_id`, `player_id` and `tier`.


### How player tier is updated:

1. Monthly, `recalculatePlayersTiersByLadderHistory` is called.
    - It gets all `player_histories` for the previous month.
    - It takes the player ratings from that month. 
    - It checks the tier 2 rating value set by the ladder admin page and compares it against their rating. If its below X, they're assigned tier 2.

2. New players registering to the ladder will get 1200 as a base player_rating elo. It is also the ladder value that the ladder checks for Blitz. 

3. If a user has a `player_rating` from a game like RA2 or YR, but does not have any for Blitz, it will take the highest rating from either game. This stops experienced players from RA2/YR coming into the wrong tier, if they've never played the ladder type before.

### Player ratings 
1. Can be seen in the admin `/admin/players/ratings`
2. In here, there is a "Update player ratings" button which will trigger the `recalculatePlayersTiersByLadderHistory` method.

