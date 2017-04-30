<?php 
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use \App\Http\Services\LadderService;

class ApiLadderController extends Controller 
{
    private $ladderService;

    public function __construct()
    {
        $this->ladderService = new LadderService();
    }
    
    public function pingLadder(Request $request)
    {
        return "pong";
    }

    public function getLadder(Request $request, $game = null)
    {
        return $this->ladderService->getLadderByGameAbbreviation($game);
    }

    // Credit: https://github.com/dkeetonx
    public function postLadder(Request $request)
    {
        // TODO - handle request and saving

        $file = "stats_ts.dmp";
        $fh = fopen($file, "r");

        $data = fread($fh, 4);

        if (!$data) {
           return;
        }

        $offset = 4;
        $stats_ver = unpack("V", $data)[1];

        print "Stats version = $stats_ver\n";

        $pad = 0;

        while (!feof($fh)) 
        {
            $data = fread($fh, 8);
            if (!$data) 
            {
                // exit loop here
                print "\$data filed";
                break;
            }

            $ttl = unpack("A4tag/ntype/nlength", $data);
            $pad = ($ttl["length"] % 4) ? 4 - ($ttl["length"] %  4) : 0;

            print "$ttl[tag] $ttl[type] $ttl[length]";

            if ($ttl["length"] > 0) 
            {
                $data = fread($fh, $ttl["length"]);

                if ($pad > 0 ) 
                {
                    fread($fh, $pad);
                }

                switch ($ttl["type"]) 
                {
                    case 20:
                        $v = unpack("C$ttl[length]", $data);
                        print "\t";
                        print "<<raw data HERE>>";
                        break;
                    case 1:
                        $v = unpack("C", $data);
                        print "\t$v[1]";
                        break;
                    case 2:
                        $v = unpack("C", $data);
                        if ($v[1] == 0) {
                            print "\tFalse";
                        }
                        else {
                            print "\tTrue";
                        }
                        break;
                    case 3:
                        $v = unpack("n", $data);
                        print "\t$v[1]";
                        break;
                    case 4:
                        $v = unpack("n", $data);
                        print "\t$v[1]";
                        break;
                    case 5:
                        $v = unpack("N", $data);
                        print "\t$v[1]";
                        break;
                    case 6:
                        $v = unpack("N", $data);
                        print "\t$v[1]";
                        break;
                    case 7:
                        $ttl["length"] -= 1;
                        $v = unpack("a$ttl[length]", $data);
                        print "\t$v[1]";
                        break;
                }
            }

            print "\n";
        }
    }

    public function getLadderGame(Request $request, $game = null, $gameId = null)
    {
        return $this->ladderService->getLadderGameById($game, $gameId);
    }

    public function getLadderPlayer(Request $request, $game = null, $player = null)
    {
        return $this->ladderService->getLadderPlayer($game, $player);
    }
}