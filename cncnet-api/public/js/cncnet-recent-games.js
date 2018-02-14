// Fetch Recent Ladder Games Example
(function ()
{
    var baseUrl = "http://ladder.cncnet.org/api/v1/ladder/yr/games/recent/5";

    function onGetRecentGames($game)
    {
        $.ajax
        ({ 
            url: baseUrl 
        })
        .complete((response) => onRecentGamesReceived(response))
        .error((err) => onRecentGamesErrorReceived(err));
    }

    function onRecentGamesReceived(response)
    {
        var games = response;
        var el = document.getElementById("recent-games");

        for (var i = 0; i < games.length; i++)
        {
            var game = games[i];
            var li = document.createElement("li");
            li.classList.add("game-box");

            updateList(li, game.scen, "h4", "title");

            for (var j = 0; j < game.players.length; j++)
            {
                var player = game.players[j];
                var pElement;

                if (player.won)
                {
                    var points = updateList(li, "+" + player.points, "span", "points");
                    pElement = updateList(li, player.username, "h5", "player won");
                }
                else
                {
                    var points = updateList(li, player.points, "span", "points");
                    pElement = updateList(li, player.username, "h5", "player lost");
                }

                pElement.appendChild(points);
            }

            el.appendChild(li);
        }
    }

    function onRecentGamesErrorReceived(error)
    {
        console.log("Error - ", error);
    }

    function updateList(element, text, type, className)
    {
        var el = document.createElement(type);
        if (text != null)
        {
            el.innerText = text;
            if (className != null)
            {
                el.className = className;
            }
            element.appendChild(el);
        }
        return el;
    }

    onGetRecentGames();
})();
