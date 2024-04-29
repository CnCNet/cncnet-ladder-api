import axios, { isCancel, AxiosError, AxiosResponse } from 'axios';

export interface CnCNetOnlineCount 
{
    cncnet5: number;
    cncnet5_d2: number;
    cncnet5_dta: number;
    cncnet5_mo: number;
    cncnet5_ra: number;
    cncnet5_td: number;
    cncnet5_ts: number;
    cncnet5_yr: number;
}

export class OnlineCount 
{
    private static API_GAME_COUNTS: string = "https://api.cncnet.org/status";

    constructor()
    {
        this.fetchPlayerCounts();
    }

    public async fetchPlayerCounts(): Promise<void> 
    {
        try
        {
            const response: AxiosResponse<CnCNetOnlineCount> = await axios.get(OnlineCount.API_GAME_COUNTS);
            this.updateUI(response.data);
        }
        catch (error)
        {
            console.error(error);
        }
    }

    public updateUI(onlineCounts: CnCNetOnlineCount): void 
    {
        let selectorPrefix = ".js-game-count-";
        Object.keys(onlineCounts).forEach(key =>
        {
            let selector = `${selectorPrefix}${key}`;
            let countElements = document.querySelectorAll(selector);

            countElements.forEach((el) => 
            {
                let playerCount = onlineCounts[key];
                if (playerCount < 10)
                {
                    playerCount = "< 10";
                }
                el.innerHTML = playerCount.toString();
            });
        });
    }
}