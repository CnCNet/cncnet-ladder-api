<canvas id="gamesPlayed" width="450" height="340" style="margin-top: 15px;"></canvas>

<script>
    const config = {
        type: "bar",
        data: {
            labels: {!! json_encode($graphGamesPlayedByMonth['labels']) !!},
            datasets: [{
                    label: "Lost",
                    data: {!! json_encode($graphGamesPlayedByMonth['data_games_lost']) !!},
                    backgroundColor: "#ff0068",
                },
                {
                    label: "Won",
                    data: {!! json_encode($graphGamesPlayedByMonth['data_games_won']) !!},
                    backgroundColor: "rgba(0, 255, 138, 1)",
                },
            ]
        },
        options: {
            scales: {
                x: {
                    stacked: true,
                },
                y: {
                    stacked: true
                }
            },
            responsive: true,
            scale: {
                ticks: {
                    precision: 0
                },
            },
        }
    };
    const ctx = document.getElementById("gamesPlayed");
    const myChart = new Chart(ctx, config);
</script>
