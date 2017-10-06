<?php namespace App\Http\Services;

class GameResult
{
    const COMPLETION_BIT1              = 1;    //C&C95 for bit 1 prints "C&C95 - Completion status is player 2 resigned.\n"
    const COMPLETION_DISCONNECTED      = 2;    //Lost Connection To or Kicked/Autokicked CONFIRMED// for bit 2 C&C95 prints "C&C95 - Completion status is player 1 disconnected.\n"
    const COMPLETION_BIT4              = 4;
    const COMPLETION_NO_COMPLETION  = 8;    //Player didn't see game completion// //<CCHyper> 0x8 is i think when the player didn't see the end of the game Needs checking
    const COMPLETION_QUIT              = 16;   //Player Resigned/Quit it seems, Hitting Quit ingame sets this CONFIRMED /////Seems to be set when a player was specifically kicked via the Kick button instead of waiting for timeout
    const COMPLETION_BIT32             = 32;
    const COMPLETION_DRAW              = 64;   //CONFIRMED for Red Alert, unknown for TS/YR
    const COMPLETION_BIT128            = 128;
    const COMPLETION_WON               = 256;  //CONFIRMED
    const COMPLETION_DEFEATED          = 512;  //CONFIRMED
    const COMPLETION_BIT1024           = 1024;
    const COMPLETION_BIT2048           = 2048;
    const COMPLETION_BIT4096           = 4096;
    const COMPLETION_BIT8192           = 8192;
    const COMPLETION_BIT16384          = 16384;
    const COMPLETION_BIT32768          = 32768;
}
