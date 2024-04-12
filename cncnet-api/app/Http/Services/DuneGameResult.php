<?php

namespace App\Http\Services;

class DuneGameResult
{
    const GES_ENDEDNORMALLY = 0;
    const GES_ISURRENDERED = 1;
    const GES_OPPONENTSURRENDERED = 2;
    const GES_OUTOFSYNC = 3;
    const GES_CONNECTIONLOST = 4;
    const GES_WASHGAME = 5;
    const GES_DRAWGAME = 6;
    const GES_UNKNOWNENDSTATE = 7;
}
