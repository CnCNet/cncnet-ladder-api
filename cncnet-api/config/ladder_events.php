<?php

/*
|--------------------------------------------------------------------------
| Ladder events
|--------------------------------------------------------------------------
|
| Award rules for community ladder events. Rules are code-reviewed here;
| named groups, manual winners, overrides and frozen results live in the
| ladder_events* tables and change at runtime.
|
| Award types:
|   standings  - sum of player_caches.points across the event histories
|   count      - count eligible games (metric=games) or wins (metric=wins)
|   streaming  - estimated streaming hours (roles: player, observer)
|   manual     - winner entered by organisers (optionally with candidate wins)
|
| Count filters: days, faction, opponent_group, partner_group, partner_outside_top
| Streaming filters: days
|
| Description placeholder ":members" is replaced with the display names of the
| award's opponent_group / partner_group / candidates group.
|
*/

$faction = fn (string $key, string $title, int $localId) => [
    'key' => $key, 'section' => 'faction', 'title' => $title,
    'description' => "Most wins playing as {$title} across both months.",
    'type' => 'count', 'metric' => 'wins', 'filters' => ['faction' => $localId], 'prizes' => [25],
];

return [
    'ra2-2v2-holiday-2026' => [
        'name' => 'RA2 2v2 Holiday Championship',
        'ladder' => 'ra2-2v2',
        // Optional prize-list graphic shown on the dashboard (path under resources/, served through Vite)
        'prize_image' => 'resources/images/events/ra2-2v2-holiday-2026-prizes.webp',
        'histories' => ['12-2026', '1-2027'],
        'starts' => '2026-12-01 00:00:00',
        'ends' => '2027-01-31 23:59:59',
        'days' => [
            'xmas' => '2026-12-25',
            'nye' => '2027-01-01',
        ],
        'groups' => [
            's_class', 'partner_mikoz', 'partner_zedd_beta', 'partner_niek_dayi',
            'selector_gino', 'selector_edd', 'selector_steve', 'selector_sander', 'selector_andy',
        ],
        'sections' => [
            'overall' => ['title' => 'Overall Ladder Standings', 'emoji' => '🏆'],
            'performance' => ['title' => 'Performance', 'emoji' => '⚔️'],
            'christmas' => ['title' => 'Christmas Day', 'emoji' => '🎄'],
            'new_year' => ['title' => "New Year's Day", 'emoji' => '🎆'],
            'combined_holiday' => ['title' => 'Combined Holiday', 'emoji' => '🎁'],
            'player_based' => ['title' => 'Player-Based', 'emoji' => '🤝'],
            'streaming' => ['title' => 'Streaming', 'emoji' => '📺'],
            'faction' => ['title' => 'Faction', 'emoji' => '🚩'],
        ],
        'awards' => [
            // Overall
            ['key' => 'standings', 'section' => 'overall', 'title' => 'Overall Ladder Standings',
             'description' => 'Combined ladder points from the December and January ladders.',
             'type' => 'standings', 'prizes' => [300, 150, 100, 50, 40, 20, 15, 10, 10, 5]],

            // Performance
            ['key' => 'most_games', 'section' => 'performance', 'title' => 'Most Games',
             'description' => 'Most ladder games played.',
             'type' => 'count', 'metric' => 'games', 'filters' => [], 'prizes' => [50, 40, 30, 20]],
            ['key' => 'most_wins', 'section' => 'performance', 'title' => 'Most Wins',
             'description' => 'Most ladder wins.',
             'type' => 'count', 'metric' => 'wins', 'filters' => [], 'prizes' => [60]],
            ['key' => 'chase_the_stars', 'section' => 'performance', 'title' => 'Chase the Stars',
             'description' => 'Most wins against S-Class players: :members.',
             'type' => 'count', 'metric' => 'wins', 'filters' => ['opponent_group' => 's_class'], 'prizes' => [70]],
            ['key' => 'handicap_carrier', 'section' => 'performance', 'title' => 'Handicap Carrier',
             'description' => "Most wins with a partner outside that month's top 20.",
             'type' => 'count', 'metric' => 'wins', 'filters' => ['partner_outside_top' => 20], 'prizes' => [70]],
            ['key' => 'giant_killer', 'section' => 'performance', 'title' => 'Giant Killer',
             'description' => 'Biggest upset of the event, decided by community vote.',
             'type' => 'manual', 'decided_by' => 'community vote', 'prizes' => [50]],

            // Christmas Day
            ['key' => 'santa_cubaus', 'section' => 'christmas', 'title' => 'Santa Cubaus',
             'description' => 'Most wins as Cuba on Christmas Day (UTC).',
             'type' => 'count', 'metric' => 'wins', 'filters' => ['days' => ['xmas'], 'faction' => 7], 'prizes' => [25]],
            ['key' => 'snipe_the_reindeer', 'section' => 'christmas', 'title' => 'Snipe the Reindeer',
             'description' => 'Most wins as Great Britain on Christmas Day (UTC).',
             'type' => 'count', 'metric' => 'wins', 'filters' => ['days' => ['xmas'], 'faction' => 4], 'prizes' => [25]],
            ['key' => 'santas_eyeball', 'section' => 'christmas', 'title' => "Santa's Eyeball",
             'description' => 'Most hours streamed, playing or observing, on Christmas Day (UTC).',
             'type' => 'streaming', 'roles' => ['player', 'observer'], 'filters' => ['days' => ['xmas']], 'prizes' => [25]],

            // New Year's Day
            ['key' => 'new_year_flyby', 'section' => 'new_year', 'title' => 'New Year Flyby',
             'description' => "Most wins as Korea on New Year's Day (UTC).",
             'type' => 'count', 'metric' => 'wins', 'filters' => ['days' => ['nye'], 'faction' => 1], 'prizes' => [25]],
            ['key' => 'new_year_streamer', 'section' => 'new_year', 'title' => 'New Year Streamer',
             'description' => "Most hours streamed, playing or observing, on New Year's Day (UTC).",
             'type' => 'streaming', 'roles' => ['player', 'observer'], 'filters' => ['days' => ['nye']], 'prizes' => [25]],
            ['key' => 'libya_dominance', 'section' => 'new_year', 'title' => 'Libya Dominance',
             'description' => "Most wins as Libya on New Year's Day (UTC).",
             'type' => 'count', 'metric' => 'wins', 'filters' => ['days' => ['nye'], 'faction' => 5], 'prizes' => [25]],

            // Combined Holiday
            ['key' => 'more_than_mamma', 'section' => 'combined_holiday', 'title' => 'I Love RA2 More Than Mamma',
             'description' => "Most games played on Christmas Day and New Year's Day combined (UTC).",
             'type' => 'count', 'metric' => 'games', 'filters' => ['days' => ['xmas', 'nye']], 'prizes' => [40]],

            // Player-Based
            ['key' => 'dogs_out', 'section' => 'player_based', 'title' => 'Who Let the Dogs Out',
             'description' => 'Most wins partnered with :members.',
             'type' => 'count', 'metric' => 'wins', 'filters' => ['partner_group' => 'partner_mikoz'], 'prizes' => [40]],
            ['key' => 'beta_to_zedd', 'section' => 'player_based', 'title' => 'From Beta to Zedd',
             'description' => 'Most wins partnered with :members.',
             'type' => 'count', 'metric' => 'wins', 'filters' => ['partner_group' => 'partner_zedd_beta'], 'prizes' => [40]],
            ['key' => 'dayi_of_nikoz', 'section' => 'player_based', 'title' => 'The Dayi of the Nikoz',
             'description' => 'Most wins partnered with :members.',
             'type' => 'count', 'metric' => 'wins', 'filters' => ['partner_group' => 'partner_niek_dayi'], 'prizes' => [40]],
            ['key' => 'ginocologist', 'section' => 'player_based', 'title' => 'Go See the Ginocologist',
             'description' => 'Best win partnered with Gino, selected by Gino.',
             'type' => 'manual', 'candidates' => 'selector_gino', 'selector_label' => 'Gino', 'prizes' => [40]],
            ['key' => 'gatekeeper_edd', 'section' => 'player_based', 'title' => 'A-Class Gatekeeper Edd',
             'description' => 'Best win partnered with Edd, selected by Edd.',
             'type' => 'manual', 'candidates' => 'selector_edd', 'selector_label' => 'Edd', 'prizes' => [40]],
            ['key' => 'monster_bhead', 'section' => 'player_based', 'title' => 'The Monster Under Ur Bhead',
             'description' => 'Best win partnered with Steve, selected by Steve.',
             'type' => 'manual', 'candidates' => 'selector_steve', 'selector_label' => 'Steve', 'prizes' => [40]],
            ['key' => 'sandles', 'section' => 'player_based', 'title' => "You Can't Wear These Sandles",
             'description' => 'Best win partnered with Sander, selected by Sander.',
             'type' => 'manual', 'candidates' => 'selector_sander', 'selector_label' => 'Sander', 'prizes' => [40]],
            ['key' => 'andicap', 'section' => 'player_based', 'title' => 'The Andicap Award',
             'description' => 'Best win partnered with Andy, selected by Andy.',
             'type' => 'manual', 'candidates' => 'selector_andy', 'selector_label' => 'Andy', 'prizes' => [40]],

            // Streaming
            ['key' => 'eye_of_beholder', 'section' => 'streaming', 'title' => 'Eye of the Beholder',
             'description' => 'Most hours streamed while observing QM games.',
             'type' => 'streaming', 'roles' => ['observer'], 'filters' => [],
             'exclusive_with' => 'let_me_see', 'prizes' => [50]],
            ['key' => 'let_me_see', 'section' => 'streaming', 'title' => 'Let Me See! Let Me See!',
             'description' => 'Most hours streamed while playing QM games.',
             'type' => 'streaming', 'roles' => ['player'], 'filters' => [],
             'exclusive_with' => 'eye_of_beholder', 'exclusive_wins_ties' => true, 'prizes' => [50]],

            // Faction
            $faction('faction_france', 'France', 2),
            $faction('faction_korea', 'Korea', 1),
            $faction('faction_america', 'America', 0),
            $faction('faction_great_britain', 'Great Britain', 4),
            $faction('faction_germany', 'Germany', 3),
            $faction('faction_cuba', 'Cuba', 7),
            $faction('faction_russia', 'Russia', 8),
            $faction('faction_libya', 'Libya', 5),
        ],
    ],
];
