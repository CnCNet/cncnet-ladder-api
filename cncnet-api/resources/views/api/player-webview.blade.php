<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Player webview config</title>
    <link href="https://fonts.googleapis.com/css2?family=Righteous&display=swap" rel="stylesheet">
    <style>
        html,
        body {
            font-family: Righteous, sans-serif;
            background: transparent;
            color: #fff;
        }

        .player-card canvas {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 0;
        }

        .player-card * {
            z-index: 1;
        }

        .player-card {
            position: relative;
            display: flex;
            align-items: center;
            flex-grow: 1;
            max-width: 100%;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            background: rgb(0 0 0 / 70%);
            overflow: hidden;
        }

        .player-card .player-badge img {
            max-width: 100%;
            height: 75px;
            margin-top: 1rem;
        }

        .player-card .player-profile {
            display: flex;
            flex-direction: column;
            margin-left: 1.5rem;
        }

        .player-card .player-name h1 {
            margin: 0;
            font-size: 3.2rem;
        }

        .player-card .player-stats {
            display: flex;
            color: #8f8f8f;
            text-transform: uppercase;
        }

        .player-card .player-stats div {
            margin-right: 1rem;
        }

        .player-card .player-rank {
            position: absolute;
            top: 0;
            right: 2%;
            font-size: 5rem;
        }

    </style>
</head>

<body>
    <div class="player-card">
        <div class="player-badge">
            <img src="/images/badges/{{ $player->badge->badge }}.png" alt="Player badge" />
        </div>

        <div class="player-profile">
            <div class="player-name">
                <h1>{{ $player->username }}</h1>
            </div>
            <div class="player-stats">
                <div>Wins: {{ $player->games_won }}</div>
                <div>Lost: {{ $player->games_lost }}</div>
                <div>Points: {{ $player->points }}</div>
            </div>
        </div>

        <div class="player-rank">
            #{{ $player->rank }}
        </div>
    </div>
    <script>
        setTimeout(function() {
            document.location.reload()
        }, 30000);
    </script>
</body>

</html>
