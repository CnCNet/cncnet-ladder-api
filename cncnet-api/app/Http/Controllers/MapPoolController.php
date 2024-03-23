<?php

namespace App\Http\Controllers;

use ErrorException;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use \Carbon\Carbon;
use \App\User;
use \App\MapPool;
use \App\Ladder;
use \App\SpawnOptionString;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use CurlFile;
use ZipArchive;

class MapPoolController extends Controller
{
    public function postQuickMatchMap(Request $request)
    {
        if ($request->id == "new" || $request->id == 0)
        {
            $qmMap = new \App\QmMap;
            $message = "Successfully created new map";
        }
        else
        {
            $qmMap = \App\QmMap::where('id', $request->id)->first();
            $message = "Sucessfully updated map";
        }

        if ($qmMap == null)
        {
            $request->session()->flash('error', 'Unable to update Map');
            return redirect()->back();
        }

        $qmMap->map_pool_id = $request->map_pool_id;
        $qmMap->map_id = $request->map_id;
        $qmMap->description = trim($request->description);
        $qmMap->admin_description = trim($request->admin_description);
        $qmMap->bit_idx = $request->bit_idx;
        $qmMap->valid = $request->valid;
        $qmMap->rejectable = $request->rejectable == "on" ? true : false;
        $qmMap->default_reject = $request->default_reject == "on" ? true : false;
        if ($request->allowed_sides)
            $qmMap->allowed_sides = implode(",", $request->allowed_sides);
        else
            $qmMap->allowed_sides = "";
        $qmMap->spawn_order = $request->spawn_order;
        $qmMap->team1_spawn_order = $request->team1_spawn_order;
        $qmMap->team2_spawn_order = $request->team2_spawn_order;
        $qmMap->random_spawns = $request->random_spawns == "on" ? true : false;
        $qmMap->map_tier = $request->map_tier;

        $mapTier = \App\MapTier::where('map_pool_id', $qmMap->map_pool_id)->where('tier', $qmMap->map_tier)->first();
        if (!$mapTier || $mapTier == null)
        {
            $request->session()->flash('error', "Map map_tier $request->map_tier does not exist");
            return redirect()->back();
        }

        // map weight
        if (!$qmMap->weight || $qmMap->weight < 1)
        {
            $qmMap->weight = 1;
        }
        else
        {
            $qmMap->weight = $request->weight;
        }

        $ladderRules = \App\MapPool::find($request->map_pool_id)->ladder->qmLadderRules;
        if ($ladderRules->use_ranked_map_picker && ($request->map_tier > 5 || $request->map_tier < 0))
        {
            $request->session()->flash('error', "Map map_tier $request->map_tier cannot be less than 0 or greater than 5");
            return redirect()->back();
        }

        if (empty($qmMap->description) || empty($qmMap->admin_description))
        {
            $request->session()->flash('error', "Empty name provided");
            return redirect()->back();
        }

        $qmMap->save();

        $request->session()->flash('success', $message);
        return redirect()->back();
    }

    public function editMap(Request $request)
    {
        $this->validate($request, [
            'map_id' => 'required',
            'name'   => 'string',
            'mapImage' => 'image'
        ]);

        $mapFile = $request->file('mapFile');
        $hash = null;

        if ($mapFile)
        {
            $mapFileName = strtolower($mapFile->getClientOriginalName());
            if ($mapFile != null)
            {
                if (!($this->str_ends_with($mapFileName, ".map") || $this->str_ends_with($mapFileName, ".mpr")))
                {
                    $request->session()->flash('error', "Map file does not end in .map or .mpr, for mapfile: " . $mapFileName);
                    return redirect()->back();
                }

                $hash = sha1_file($mapFile);
            }
        }

        if ($request->map_id == 'new')
        {
            if ($mapFile == null)
            {
                $request->session()->flash('error', "Map file is required for new maps");
                return redirect()->back();
            }

            //check if a map with this hash already exists, shouldn't be creating duplicate maps...
            $existingMapsWithHash = \App\Map::where('hash', $hash)->where('ladder_id', $request->ladder_id)->first();

            if ($existingMapsWithHash != null)
            {
                $request->session()->flash('error', "A map with this hash already exists: " . $existingMapsWithHash->name);
                return redirect()->back();
            }

            if (!$request->hasFile('mapImage'))
            {
                $request->session()->flash('error', "Map image is required for new maps");
                return redirect()->back();
            }

            if (!isset($request->name) || $request->name == null || empty($request->name))
            {
                $request->session()->flash('error', "Map name is required for new maps");
                return redirect()->back();
            }

            $map = new \App\Map;
        }
        else
        {
            $map = \App\Map::find($request->map_id);
            if ($map === null)
            {
                $request->session()->flash('error', "Map Not found");
                return redirect()->back();
            }
        }

        $map->ladder_id = $request->ladder_id;
        if ($hash != null)
            $map->hash = $hash;

        if (isset($request->name) && $request->name != null && !empty($request->name))
            $map->name = trim($request->name);

        if (empty($map->name))
        {
            $request->session()->flash('error', "Empty map name provided");
            return redirect()->back();
        }

        $map->save();
        $request->session()->flash('success', "Map Saved");

        if ($request->hasFile('mapImage'))
        {
            $map->image_hash = sha1_file($request->file('mapImage'));
            $imgFilename = $map->image_hash . ".png";
            $filepath = config('filesystems')['map_images'] . "/" . $map->ladder->game; //store map images in game directory
            $map->image_path = "/images/maps/" . $map->ladder->game . "/" . $imgFilename;
            $map->save();

            $request->file('mapImage')->move($filepath, $imgFilename);
        }

        if ($mapFile != null)
        {
            // parse map headers
            $game = $map->ladder->game;
            $errMessage = $this->parseMapHeaders($mapFile->getPathName(), $map->id, $game);

            if (isset($errMessage) && $errMessage != null && !empty($errMessage))
            {
                $request->session()->flash('error', $errMessage);
                return redirect()->back();
            }

            // upload map to cnc database
            $hash = sha1_file($mapFile);
         
            $mapUploaded = $this->uploadMapToCncDatabase($mapFile, $hash, $game);
            $request->session()->flash('success', "QM Map saved and Map Uploaded to the CnC Database!");

            if (!$mapUploaded)
            {
                $request->session()->flash('error', "Failed to upload map '$mapFileName' ('$hash') to the $game cnc database");
                return redirect()->back();
            }
        }

        return redirect()->back()->withInput();
    }

    private function uploadMapToCncDatabase($mapFile, $hash, $game)
    {
        Log::info("Beginning file upload for map " . $mapFile->getClientOriginalName());

        if (filesize($mapFile) > 800000)
        {
            Log::error("Map file is too large " . $mapFile->getClientOriginalName());
            return true;
        }

        if (filesize($mapFile) == 0)
        {
            Log::error("Map file has no data " . $mapFile->getClientOriginalName());
            return true;
        }

        if ($this->mapExists($hash, $game))
        {
            Log::info("Map file '$hash' already exists in $game cnc database " . $mapFile->getClientOriginalName());
            return true;
        }

        $fileExtension = $mapFile->getClientOriginalExtension();

        // move the map file
        $targetDir = config('filesystems')['map_files'];
        $targetFileName = $hash . "." . $fileExtension;
        $mapFile->move($targetDir, $targetFileName);

        // zip the map file
        $zipFileName = $targetDir . "/" . $hash . ".zip";
        $zip = new ZipArchive;
        $zip->open($zipFileName, ZipArchive::CREATE);
        $zip->addFile($targetDir . "/" . $targetFileName);
        $zip->close();
        unlink($targetDir . "/" . $targetFileName); // delete the map file

        $curl = curl_init();
        Log::info("Uploading $zipFileName to the $game cnc database.");
        curl_setopt($curl, CURLOPT_URL, "http://mapdb.cncnet.org/upload");
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);

        // Create a cURL file object
        $curlFile = new CurlFile($zipFileName);
        $postData = array(
            'file' => $curlFile,
            'game' => $game
        );
        curl_setopt($curl, CURLOPT_POSTFIELDS, $postData);

        $response = curl_exec($curl);
        $status_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        Log::info("Response after attempted map upload: [$status_code]: $response");
        unlink($zipFileName); // delete the temp zip

        if (curl_errno($curl))
        {
            Log::error('cURL Error: ' . curl_error($curl));
            return false;
        }

        curl_close($curl);
        if ($response === false)
        {
            Log::error('Error: Unable to send the POST request.');
            return false;
        }

        if (!$this->mapExists($hash, $game))
        {
            Log::info("Map does $hash not exist in cnc db after attempted upload");
            return false;
        }
        return true;
    }

    private function mapExists($hash, $game)
    {
        $targetUrl = "http://mapdb.cncnet.org/$game/$hash.zip";

        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, $targetUrl);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');

        Log::info($targetUrl);
        curl_exec($curl);

        $status_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);

        return $status_code === 200;
    }

    private function parseMapHeaders($fileName, $mapId, $ladderGame)
    {
        if ($ladderGame == "dune") // not an INI file, not yet supported
            return "";

        try
        {
            $ini = parse_ini_file($fileName, true, INI_SCANNER_RAW); //parse the map file, map files are INI files
        }
        catch (ErrorException $e)
        {
            return "Failed to parse map file, error: " . $e->getMessage();
        }

        if ($ini == null)
            return "Failure parsing INI from file";

        if ($ladderGame == "ra")
            return $this->parseRaMapHeaders($ini, $mapId);
        else if ($ladderGame == "ts")
            return $this->parseTsMapHeaders($ini, $mapId);
        else if ($ladderGame == "yr")
            return $this->parseYrMapHeaders($ini, $mapId);
        else
            return "Map headers not supported for ladder game: " . $ladderGame;
    }

    private function parseRaMapHeaders($ini, $mapId)
    {
        $header = $ini['Map']; //we want the map header data

        if ($header == null)
            return "No 'Map' section found from INI";

        $mapHeader = new \App\MapHeader();
        $mapHeader->map_id = $mapId;
        $mapHeader->width = $header["Width"];
        $mapHeader->height = $header["Height"];
        $mapHeader->startX = $header["X"];
        $mapHeader->startY = $header["Y"];
        $mapHeader->save();

        $waypoints = $ini['Waypoints'];
        if ($waypoints == null)
            return "No 'Waypoints' section found from INI";

        $spawnsArr = [];
        //create the map waypoints
        for ($i = 0; $i <= count($waypoints); $i++)
        {

            if (!isset($waypoints[$i]))
            {
                break;
            }

            $wayPointValue = $waypoints[$i];
            $x = intval($wayPointValue % 128);
            $y = intval($wayPointValue / 128);

            $mapWaypoint = new \App\MapWaypoint();
            $mapWaypoint->x = $x;
            $mapWaypoint->y = $y;
            $mapWaypoint->bit_idx = $i;
            $mapWaypoint->map_header_id = $mapHeader->id;
            $mapWaypoint->save();

            if (!in_array($wayPointValue, $spawnsArr) && $wayPointValue != 0)
                $spawnsArr[] = $wayPointValue;
        }

        $map = \App\Map::where('id', $mapId)->first();
        $map->spawn_count = count($spawnsArr);
        $map->save();
    }

    private function parseTsMapHeaders($ini, $mapId)
    {
        $header = $ini['Map']; //we want the map header data

        if ($header == null)
            return "No 'Map' section found from INI";

        $localSize = $header["LocalSize"];

        $mapHeader = new \App\MapHeader();
        $mapHeader->map_id = $mapId;
        $mapHeader->width = intval(explode(",", $localSize)[2]);
        $mapHeader->height = intval(explode(",", $localSize)[3]);
        $mapHeader->startX = intval(explode(",", $localSize)[0]);
        $mapHeader->startY = intval(explode(",", $localSize)[1]);
        $mapHeader->save();

        $waypoints = $ini['Waypoints'];
        if ($waypoints == null)
            return "No 'Waypoints' section found from INI";

        $spawnsArr = [];
        //create the map waypoints
        for ($i = 0; $i <= count($waypoints); $i++)
        {
            if (!isset($waypoints[$i]))
            {
                break;
            }

            $wayPointValue = $waypoints[$i];
            $x = intval($wayPointValue % 1000);
            $y = intval($wayPointValue / 1000);

            $mapWaypoint = new \App\MapWaypoint();
            $mapWaypoint->x = $x;
            $mapWaypoint->y = $y;
            $mapWaypoint->bit_idx = $i;
            $mapWaypoint->map_header_id = $mapHeader->id;
            $mapWaypoint->save();

            if (!in_array($wayPointValue, $spawnsArr) && $wayPointValue != 0)
                $spawnsArr[] = $wayPointValue;
        }

        $map = \App\Map::where('id', $mapId)->first();
        $map->spawn_count = count($spawnsArr);
        $map->save();
    }

    private function parseYrMapHeaders($ini, $mapId)
    {
        $header = $ini['Header']; //we want the map header data

        if ($header == null)
            return "No header section found from INI";

        $mapHeader = new \App\MapHeader();
        $mapHeader->map_id = $mapId;
        $mapHeader->width = intval($header["Width"]);
        $mapHeader->height = intval($header["Height"]);
        $mapHeader->startX = intval($header["StartX"]);
        $mapHeader->startY = intval($header["StartY"]);
        $mapHeader->numStartingPoints = intval($header["NumberStartingPoints"]);
        $mapHeader->save();

        //create the map waypoints
        for ($i = 1; $i <= 8; $i++)
        {
            $wayPointValue = $header['Waypoint' . $i];
            $x = intval(explode(',', $wayPointValue)[0]);
            $y = intval(explode(',', $wayPointValue)[1]);

            $mapWaypoint = new \App\MapWaypoint();
            $mapWaypoint->x = $x;
            $mapWaypoint->y = $y;
            $mapWaypoint->bit_idx = $i;
            $mapWaypoint->map_header_id = $mapHeader->id;
            $mapWaypoint->save();
        }

        $map = \App\Map::where('id', $mapId)->first();
        $map->spawn_count = $mapHeader->numStartingPoints;
        $map->save();
    }

    private function str_ends_with(string $string, string $substring): bool
    {
        $len = strlen($substring);

        if ($len == 0)
        {
            return true;
        }

        return substr($string, -$len) === $substring;
    }

    public function removeMapPool(Request $request, $ladderId, $mapPoolId)
    {
        $ladder = Ladder::find($ladderId);
        $mapPool = MapPool::find($mapPoolId);

        $mapPool->delete();
        $request->session()->flash('success', "Map Pool Deleted");
        return redirect("/admin/setup/{$ladder->id}/edit");
    }

    public function editMapPool(Request $request, $ladderId, $mapPoolId)
    {
        $ladder = Ladder::find($ladderId);
        $mapPool = MapPool::find($mapPoolId);
        $ladderRules = $ladder->qmLadderRules;

        return view(
            "admin.edit-map-pool",
            [
                'ladderUrl' => "/admin/setup/{$ladder->id}/edit",
                'mapPool' => $mapPool,
                'ladderAbbrev' => $ladder->abbreviation,
                'qmMaps' => $mapPool->maps()->orderBy('bit_idx')->get(),
                'mapTiers' => $mapPool->tiers,
                'ladder' => $ladder,
                'sides' => $ladder->sides,
                'ladderMaps' => $ladder->maps,
                'spawnOptions' =>  \App\SpawnOption::all(),
                'allLadders' => \App\Ladder::all(),
                'use_ranked_map_picker' => $ladderRules->use_ranked_map_picker
            ]
        );
    }

    public function cloneMapPool(Request $request, $ladderId)
    {
        $mapPool = new MapPool;
        $mapPool->name = trim($request->name);

        if (empty($mapPool->name))
        {
            $request->session()->flash('error', "Empty map pool name provided");
            return redirect()->back();
        }

        $mapPool->ladder_id = $ladderId;
        $mapPool->save();

        $prototype = MapPool::find($request->map_pool_id);

        foreach ($prototype->tiers as $tier)
        {
            $new_tier = $tier->replicate();
            $new_tier->map_pool_id = $mapPool->id;
            $new_tier->save();
        }

        foreach ($prototype->maps as $map)
        {
            $new_map = $map->replicate();
            $new_map->map_pool_id = $mapPool->id;
            $new_map->save();
        }
        $request->session()->flash('success', "Map Pool Cloned");
        return redirect("/admin/setup/{$ladderId}/mappool/{$mapPool->id}/edit");
    }

    public function newMapPool(Request $request, $ladderId)
    {
        $mapPool = new MapPool;
        $mapPool->name = $request->name;
        $mapPool->ladder_id = $ladderId;
        $mapPool->save();

        // create initial map tier 1
        $mapTier = new \App\MapTier();
        $mapTier->map_pool_id = $mapPool->id;
        $mapTier->tier = 1;
        $mapTier->max_vetoes = 0; // default 0 vetoes
        $mapTier->save();

        return redirect("/admin/setup/{$ladderId}/mappool/{$mapPool->id}/edit");
    }

    public function copyMaps(Request $request, $ladderId, $mapPoolId)
    {
        $ladder = \App\Ladder::find($ladderId);
        $mapPool = MapPool::find($mapPoolId);
        $copyFrom = \App\Ladder::find($request->clone_ladder_id);

        foreach ($copyFrom->maps as $map)
        {
            $new = $map->replicate();
            $new->ladder_id = $ladder->id;
            $new->save();
        }

        $request->session()->flash('success', "Maps Cloned");
        return redirect()->back();
    }

    public function changeMapPool(Request $request, $ladderId)
    {
        $ladder = \App\Ladder::find($ladderId);

        $ladder->map_pool_id = $request->map_pool_id;
        $ladder->save();

        $request->session()->flash('success', "Map Pool Changed");
        return redirect()->back();
    }

    public function renameMapPool(Request $request, $ladderId, $mapPoolId)
    {
        $mapPool = MapPool::find($mapPoolId);
        $mapPool->name = $request->name;
        $mapPool->save();

        $request->session()->flash('success', "Map Pool Renamed");
        return redirect()->back();
    }

    public function removeQuickMatchMap(Request $request, $ladderId, $mapPoolId)
    {
        $qmMap = \App\QmMap::find($request->map_id);
        $mapPool = MapPool::find($mapPoolId);

        if ($qmMap !== null)
        {
            $mapPool->maps()->where('bit_idx', '>', $qmMap->bit_idx)
                ->decrement('bit_idx');
            $qmMap->valid = false;
            $qmMap->save();
        }

        $request->session()->flash('success', "Map Deleted");
        return redirect()->back();
    }

    public function reorderMapPool(Request $request, $ladderId, $mapPoolId)
    {
        $mapPool = MapPool::find($mapPoolId);

        $maps = $mapPool->maps;
        $toSave = array();
        $count = $maps->count();

        for ($i = 0; $i < $count && $i < 1000; ++$i)
        {
            $map_id = $request->input("bit_idx_{$i}");

            $map = \App\QmMap::find($map_id);
            if ($map !== null)
            {
                $toSave[] = $map;
            }
            else
            {
                $count++;
            }
        }

        $i = 0;
        foreach ($toSave as $map)
        {
            $map->bit_idx = $i;
            $map->save();
            $i++;
        }

        $request->session()->flash('success', "Map Pool Reordered");
        return redirect()->back();
    }

    public function editMapTier(Request $request)
    {
        $mapTier = \App\MapTier::where('tier', $request->tier)->where('map_pool_id', $request->map_pool_id)->first();
        if (!$mapTier || $mapTier == null)
        {
            $mapTier = new \App\MapTier();
            $mapTier->map_pool_id = $request->map_pool_id;
        }
        
        //check for invalid tier
        if (!$request->tier || $request->tier < 1)
        {
            $request->session()->flash('error', "Map Tier " . $request->tier . " must be above zero.");
            return redirect()->back();
        }

        $mapTier->tier = $request->tier;
        $mapTier->name = $request->name;
        $mapTier->max_vetoes = $request->max_vetoes;

        $mapTier->save();

        $request->session()->flash('success', "Saved Map Tier '" . $mapTier->name. "'");
        return redirect()->back();
    }

    public function deleteMapTier(Request $request)
    {
        $mapTier = \App\MapTier::where('tier', $request->tier)->where('map_pool_id', $request->map_pool_id)->first();
        if (!$mapTier || $mapTier == null)
        {
            $request->session()->flash('error', "Map Tier not found " . $mapTier->tier);
            return redirect()->back();
        }

        // check if any maps in this pool belong to this tier
        $qmMaps = \App\QmMap::where('map_tier', $request->tier)->where('map_pool_id', $request->map_pool_id)->get();
        if ($qmMaps->count() > 0)
        {
            $request->session()->flash('error', "Cannot delete tier " . $request->tier . " because there are maps in this map pool assigned to this tier.");
            return redirect()->back();
        }

        $mapTier->delete();

        $request->session()->flash('success', "Deleted Map Tier '" . $mapTier->name . "'.");
        return redirect()->back();
    }
}
