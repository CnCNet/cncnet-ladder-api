// Fetch Recent Ladder Games Example
(function ()
{
    var baseUrl = "//staging.cnc-comm.com/api/v1/ladder/yr/games/recent/5";

    function onGetRecentGames($game)
    {
        $.ajax({ url: baseUrl, dataType: "json", crossDomain: true })
            .done((games) => onRecentGamesReceived(games))
            .fail((error) => onRecentGamesErrorReceived(error));
    }

    function onRecentGamesReceived(response)
    {
        var games = response;
        var gamesList = document.getElementById("recent-games-list");

        for (var i = 0; i < games.length; i++)
        {
            var game = games[i];

            // Create list item
            var gameBoxContainer = document.createElement("li");
            gameBoxContainer.classList.add("game-box");
            
            // Create players container
            var playersContainer = document.createElement("div");
            playersContainer.classList.add("players");
            
            updateGamesList(gameBoxContainer, game.scen, "h4", "title");

            for (var j = 0; j < game.players.length; j++)
            {
                var player = game.players[j];
                var pointsContainer;

                if (player.won)
                {
                    var points = updateGamesList(gameBoxContainer, "+" + player.points, "span", "points");
                    pointsContainer = updateGamesList(gameBoxContainer, player.username, "h5", "player won");
                }
                else
                {
                    var points = updateGamesList(gameBoxContainer, player.points, "span", "points");
                    pointsContainer = updateGamesList(gameBoxContainer, player.username, "h5", "player lost");
                }

                pointsContainer.appendChild(points);
                playersContainer.appendChild(pointsContainer);
            }

            gameBoxContainer.appendChild(playersContainer);
            gamesList.appendChild(gameBoxContainer);
        }
    }

    function onRecentGamesErrorReceived(error)
    {
        console.log("Error - ", error);
    }

    function updateGamesList(gameListElement, text, type, className)
    {
        var gamesList = document.createElement(type);
        if (text != null)
        {
            gamesList.innerText = text;
            if (className != null)
            {
                gamesList.className = className;
            }
            gameListElement.appendChild(gamesList);
        }
        return gamesList;
    }

    onGetRecentGames();
})();
