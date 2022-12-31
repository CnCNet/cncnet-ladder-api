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
        $qmMap->allowed_sides = implode(",", $request->allowed_sides);
        $qmMap->spawn_order = $request->spawn_order;
        $qmMap->team1_spawn_order = $request->team1_spawn_order;
        $qmMap->team2_spawn_order = $request->team2_spawn_order;

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
        if ($mapFile != null)
        {
            if (!($this->str_ends_with($mapFile->getClientOriginalName(), ".map") || $this->str_ends_with($mapFile->getClientOriginalName(), ".mpr")))
            {
                $request->session()->flash('error', "Map file does not end in .map");
                return redirect()->back();
            }

            $hash = sha1_file($mapFile);
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
            $filename = $map->hash . ".png";
            $filepath = config('filesystems')['map_images'] . "/" . $map->ladder->abbreviation;

            $request->file('mapImage')->move($filepath, $filename);
        }

        if ($mapFile != null)
            $errMessage = $this->parseMapHeaders($mapFile->getPathName(), $map->id, $request->ladder_id);

        if (isset($errMessage) && $errMessage != null && !empty($errMessage))
        {
            $request->session()->flash('error', $errMessage);
            return redirect()->back();
        }

        return redirect()->back()->withInput();
    }

    private function parseMapHeaders($fileName, $mapId, $ladderId)
    {
        try 
        {
            $ini = parse_ini_file($fileName, true, INI_SCANNER_RAW); //parse the map file, map files are INI files
        } catch(ErrorException $e) 
        {
            return "Failed to parse map file, error: " . $e->getMessage();
        }

        if ($ini == null)
            return "Failure parsing INI from file";

        $ladderGame = \App\Ladder::where('id', $ladderId)->first()->game;

        if ($ladderGame == "ra")
            $this->parseRaMapHeaders($ini, $mapId);
        else if ($ladderGame == "ts")
            $this->parseTsMapHeaders($ini, $mapId);
        else if ($ladderGame == "yr")
            $this->parseYrMapHeaders($ini, $mapId);
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

        //create the map waypoints
        for ($i = 0; $i <= 7; $i++)
        {
            $wayPointValue = $waypoints[$i];
            $x = intval($wayPointValue % 128);
            $y = intval($wayPointValue / 128);

            $mapWaypoint = new \App\MapWaypoint();
            $mapWaypoint->x = $x;
            $mapWaypoint->y = $y;
            $mapWaypoint->bit_idx = $i;
            $mapWaypoint->map_header_id = $mapHeader->id;
            $mapWaypoint->save();
        }
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

        //create the map waypoints
        for ($i = 0; $i <= 7; $i++)
        {
            $wayPointValue = $waypoints[$i];
            $x = intval($wayPointValue % 1000);
            $y = intval($wayPointValue / 1000);

            $mapWaypoint = new \App\MapWaypoint();
            $mapWaypoint->x = $x;
            $mapWaypoint->y = $y;
            $mapWaypoint->bit_idx = $i;
            $mapWaypoint->map_header_id = $mapHeader->id;
            $mapWaypoint->save();
        }
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

        return view(
            "admin.edit-map-pool",
            [
                'ladderUrl' => "/admin/setup/{$ladder->id}/edit",
                'mapPool' => $mapPool,
                'ladderAbbrev' => $ladder->abbreviation,
                'maps' => $mapPool->maps()->orderBy('bit_idx')->get(),
                'ladder' => $ladder,
                'sides' => $ladder->sides,
                'ladderMaps' => $ladder->maps,
                'spawnOptions' =>  \App\SpawnOption::all(),
                'allLadders' => \App\Ladder::all(),
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
}
