export class CountdownTimer 
{
    private countDown: HTMLElement | null;
    private countDownFill: NodeListOf<HTMLElement>;

    constructor()
    {
        this.countDown = document.querySelector(".js-countdown");
        this.countDownFill = document.querySelectorAll(".js-countdown-fill");
        this.initializeCountdown();
    }

    private showCountDown(): void
    {
        if (this.countDown)
            this.countDown.classList.remove("hidden");
    }

    private ladderOver(): void
    {
        if (this.countDown)
            this.countDown.innerHTML = "Viewing previous ladder";
    }

    private initializeCountdown(): void
    {
        for (let i = 0; i < this.countDownFill.length; i++)
        {
            let elem: HTMLElement = this.countDownFill[i];
            let date: string | null = elem.getAttribute("data-countdown-target");
            if (date)
            {
                let endDate: Date = new Date(date);
                this.startTimer(elem, endDate);
            }
        }
    }

    private startTimer(elem: HTMLElement, endDate: Date): void
    {
        let timing = setInterval(() =>
        {
            let currentDate: number = new Date().getTime();
            let timeLeft: number = endDate.getTime() - currentDate;

            if (timeLeft < 86400 * 2 * 1000)
            {
                this.showCountDown();
            }

            let days: number = Math.floor(timeLeft / (1000 * 60 * 60 * 24));
            let hours: number = Math.floor((timeLeft % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            let minutes: number = Math.floor((timeLeft % (1000 * 60 * 60)) / (1000 * 60));
            let seconds: number = Math.floor((timeLeft % (1000 * 60)) / 1000);
            if (seconds < 10) seconds = "0" + seconds;

            let str: string = "";
            if (days > 0) str += days + "d ";
            if (hours > 0) str += hours + "h ";
            if (minutes > 0) str += minutes + "m ";

            elem.innerHTML = str + seconds + "s";

            if (timeLeft <= 0)
            {
                clearInterval(timing);
                elem.innerHTML = "";
                this.ladderOver();
            }
        }, 1000);
    }
}

