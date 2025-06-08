export class NavbarMenu 
{
    private navbar: HTMLElement;

    constructor()
    {
        this.navbar = document.querySelector(".js-navbar") as HTMLElement;

        if (window.scrollY > 0)
        {
            this.navbar.classList.add("not-top");
        }

        window.addEventListener("scroll", this.onWindowScrolled.bind(this), false);
    }

    private lastScrollY: number = window.scrollY;

    private onWindowScrolled(): void
    {
        const currentScrollY = window.scrollY;
        const navbarHeight = this.navbar.getBoundingClientRect().height;

        if (currentScrollY == 0)
        {
            this.navbar.classList.remove("not-top");
        }

        if (currentScrollY > this.lastScrollY && currentScrollY >= navbarHeight)
        {
            // Scrolling down
            // Always sticky, uncomment if we want it to hide on scroll and reappear on up
            // this.navbar.classList.add("scrolling");
            this.navbar.classList.add("not-top");
        }
        else
        {
            // Scrolling up
            this.navbar.classList.remove("scrolling");
        }

        this.lastScrollY = currentScrollY;
    }
}

