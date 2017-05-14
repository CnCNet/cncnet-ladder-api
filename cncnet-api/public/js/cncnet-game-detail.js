// Initiailize FB & Twitter API
// TODO - redo this mess at some point (this is copied from old site)

window.fbAsyncInit = function () {
  FB.init({
    appId: '1494530694154070',
    xfbml: true,
    version: 'v2.2'
  });
};

(function (d, s, id) {
  var js, fjs = d.getElementsByTagName(s)[0];
  if (d.getElementById(id)) {
    return;
  }
  js = d.createElement(s);
  js.id = id;
  js.src = "//connect.facebook.net/en_US/sdk.js";
  fjs.parentNode.insertBefore(js, fjs);
}(document, 'script', 'facebook-jssdk'));

window.twttr = (function (d, s, id) {
  var t, js, fjs = d.getElementsByTagName(s)[0];
  if (d.getElementById(id)) return;
  js = d.createElement(s);
  js.id = id;
  js.src = "https://platform.twitter.com/widgets.js";
  fjs.parentNode.insertBefore(js, fjs);
  return window.twttr || (t = {
      _e: [], ready: function (f) {
        t._e.push(f)
      }
    });
}(document, "script", "twitter-wjs"));


// Game Detail
(function ()
{
    var currentUrl = window.location.href;
    var preview = document.querySelector(".preview");
    var videoContainer = document.querySelector(".video");

    if (preview != null)
    {
        // Listen for Video Preview CLick
        preview.addEventListener("click", (e) => onVideoPreviewClicked(e), false);

        function onVideoPreviewClicked(e)
        {
            e.preventDefault();
            preview.classList.add("hidden");
            videoContainer.classList.remove("hidden");
            $("#video")[0].src += "&autoplay=1";
        }
    }

    function getShareCounts()
    {
        // Facebook share count
        $.ajax({
            url: '//graph.facebook.com/?ids=' + currentUrl,
            dataType: 'json',
            success: (response) => onShareCountReceived(response)
        });
    }

    function onShareCountReceived(response)
    {
        // The original
        var httpCount = $(".share .number").data("shares");

        // Original + Https response
        if (response[currentUrl] && response[currentUrl].hasOwnProperty('share'))
        {
            var httpsCount = response[currentUrl]['share']['share_count'] || 0;
            var totalCount = parseInt(httpCount) + parseInt(httpsCount);
            $(".share .number").html(totalCount);
        }
        else
        {
            $(".share .number").html(httpCount);
        }

    }

    // Facebook share ui
    $('#facebook-ui-button').click('on', function (e)
    {
        e.preventDefault();
        FB.ui({
            method: 'share',
            href: currentUrl
        });
    });

    // Twitter share ui
    twttr.ready(function (twttr) {
      twttr.events.bind('tweet');
    });

    getShareCounts();
})();