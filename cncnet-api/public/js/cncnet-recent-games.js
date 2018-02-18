// Fetch Recent Ladder Games Example
(function ()
{
    var filteredGame = null;

    // Endpoints
    var baseLadderUrl = "//ladder.cncnet.org/";
    var baseApiUrl = "//staging.cnc-comm.com/api/v1/ladder/";
    var recentGamesEndpoint = "/games/recent/5";

    // Filters
    var ladderGameSelector = document.getElementById("ladder-game-selector");
    var ladderGameContainer = document.querySelector(".ladderWidget .dropdown-list");
    ladderGameSelector.addEventListener("click", (e) => onToggleFilter(e), false);

    var filterChange = document.querySelector(".ladderWidget .dropdown-list select");
    filterChange.addEventListener("change", (e) => onFilterChanged(e), false);

    function onFilterChanged(e)
    {
        e.preventDefault();

        var index = e.target.options.selectedIndex;
        var option = e.target.options[e.target.options.selectedIndex];
        filteredGame = option.value;

        getRecentGames();
    }

    function onToggleFilter(e)
    {
        e.preventDefault();
        ladderGameContainer.classList.toggle("hidden")
    }

    function getRecentGames()
    {
        if (filteredGame == null)
        {
            filteredGame = "ra"; // First in list
        }

        var gamesList = document.getElementById("recent-games-list");
        gamesList.innerHTML = "";

        var url = baseApiUrl + filteredGame + recentGamesEndpoint;

        $.ajax(
            { 
                url: url, 
                dataType: "json"
            })
            .done((games) => onRecentGamesReceived(games))
            .fail((error) => onRecentGamesError(error));
    }

    function onRecentGamesReceived(response)
    {
        var games = response;
        if (games == null)
        {
            return;
        }
        render(games);
        
        // IPB Specific
        document.querySelector(".ipsLayout_sidebarUsed .ladderWidget").classList.remove("hidden");
    }

    function onRecentGamesError(error)
    {
        console.log("Error - ", error);
    }

    function render(games)
    {
        var gamesList = document.getElementById("recent-games-list");

        for (var i = 0; i < games.length; i++)
        {
            var game = games[i];
            var item = "";

            // List container & Link
            item += "<li class='game-box'><a href='" + baseLadderUrl + game.url + "'>";
            item += "<div class='preview' style='background-image:url(" + baseLadderUrl + game.map_url + ")'>";
            item += "</div>";

            // Map
            item += "<h4>" + game.scen + "</h4>";

            // Players won/lost & points
            item += "<div class='players'>";
            for (var j = 0; j < game.players.length; j++)
            {
                var player = game.players[j];
            
                if (player.won)
                {
                    item += "<h5 class='player won'>";
                    item += player.username + "<span class='points'> +" + player.points + "</span>";
                    item += "</h5>";
                }
                else
                {
                    item += "<h5 class='player lost'>";
                    item += player.username + "<span class='points'>" + player.points + "</span>";
                    item += "</h5>";
                }

                if (j == 0)
                {
                    item += "<span class='vs'>VS</span>";
                }
            }
            item += "</div>";

            item += "</a></li>";
            gamesList.innerHTML += item;
        }
    }

    getRecentGames();
})();