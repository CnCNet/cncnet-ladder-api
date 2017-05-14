// Fetch Players Online
(function ()
{
    function onGetGames()
    {
        $.ajax({ url: "https://api.cncnet.org/status", dataType: 'jsonp', })
            .done((games) => onGamesReceived(games));
    }

    function onGamesReceived(games)
    {
        for (game in games)
        {
            var prefix = game.replace("cncnet5_", "");

            var gameText = document.querySelector(".status-" + prefix);
            if (gameText != null)
            {
                gameText.querySelector(".online").innerText = games[game];
            }

            var gameDetailText = document.querySelector(".online .status-" + prefix);
            if(gameDetailText != null)
            {
                gameDetailText.innerText = games[game];
            }

            var gameDetailTotalText = document.querySelector(".status-cncnet.online .number");
            if(gameDetailTotalText != null)
            {
                gameDetailTotalText.innerText = games[game];
            }

            var totalText = document.querySelector(".status-text .online");
            if(totalText != null)
            {
                totalText.innerText = games[game];
            }
        }
    }
    
    onGetGames();
    setInterval(onGetGames, 60000);
})();