(function ()
{

    let countDown = document.querySelector(".countdown");
    let closeCountDownBtn = document.querySelector(".close-countdown");
    closeCountDownBtn.addEventListener("click", function () { hideCountDown(); }, false);

    function hideCountDown()
    {
        countDown.classList.add("countdown-hidden")
        window.localStorage.setItem("hideCountDown", true);
    }

    function shouldShowCountDown()
    {
        let key = window.localStorage.getItem("hideCountDown");
        if (key == null)
        {
            return true;
        }
        return key == "false";
    }

    function showCountDown()
    {
        countDown.classList.remove("countdown-hidden");
    }

    let countDownFill = document.querySelectorAll(".countdown-fill");
    for (let i = 0; i < countDownFill.length; i++)
    {
        let elem = countDownFill[i];
        let date = elem.getAttribute("data-countdown-target");
        let endDate = new Date(date);

        let timing = setInterval(
            function ()
            {
                let currentDate = new Date().getTime();
                let timeLeft = endDate - currentDate;

                if (shouldShowCountDown() == false)
                {
                    clearInterval(timing);
                    return;
                }

                if (timeLeft < 86400 * 2 * 1000)
                {
                    showCountDown();
                }

                let days = Math.floor(timeLeft / (1000 * 60 * 60 * 24));
                let hours = Math.floor((timeLeft % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                let minutes = Math.floor((timeLeft % (1000 * 60 * 60)) / (1000 * 60));
                let seconds = Math.floor((timeLeft % (1000 * 60)) / 1000);
                if (seconds < 10) seconds = "0" + seconds;

                let str = "";
                if (days > 0) str += days + "d ";
                if (hours > 0) str += hours + "h ";
                if (minutes > 0) str += minutes + "m ";

                elem.innerHTML = str + seconds + "s";

                if (timeLeft <= 0)
                {
                    clearInterval(timing);
                    elem.innerHTML = "It's over";
                }
            }, 1000);
    }

})();
