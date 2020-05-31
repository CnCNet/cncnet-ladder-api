
$(".close-countdown").click( function(){ $(".countdown").hide(400); });

$(".countdown-fill").each(
    function ()
    {
        var endDate = new Date($(this).attr("data-countdown-target"));
        var elem = this;

        var timing = setInterval(
            function () {
                var currentDate = new Date().getTime();
                var timeLeft = endDate - currentDate;

                if (timeLeft < 86400 * 2 * 1000)
                    $(".collapse.countdown").show();

                var days = Math.floor(timeLeft / (1000 * 60 * 60 * 24));
                var hours = Math.floor((timeLeft % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                var minutes = Math.floor((timeLeft % (1000 * 60 * 60)) / (1000 * 60));
                var seconds = Math.floor((timeLeft % (1000 * 60)) / 1000);
                if (seconds < 10) seconds="0"+seconds;

                var str = "";
                if (days > 0)     str += days + "d ";
                if (hours > 0)    str += hours + "h ";
                if (minutes > 0)  str += minutes + "m ";

                elem.innerHTML = str + seconds + "s";

                if (timeLeft <= 0) {
                    clearInterval(timing);
                    elem.innerHTML = "It's over";
                }
            }, 1000);
    }
);

