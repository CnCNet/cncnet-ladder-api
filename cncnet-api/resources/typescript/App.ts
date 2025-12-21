import "bootstrap";
import { NavbarMenu } from "./components/NavbarMenu";
import { OnlineCount } from "./components/OnlineCount";
import { CountdownTimer } from "./components/CountdownTimer";

// https://laravel.com/docs/10.x/vite#blade-processing-static-assets
// @ts-ignore
import.meta.glob([
    '../images/**',
    '../fonts/**',
    '../videos/**',
]);

// Global
new NavbarMenu();
new OnlineCount();
new CountdownTimer();

// Add pages

