<?php namespace App\Http\Controllers;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use \Carbon\Carbon;
use \App\User;
use \App\MapPool;
use \App\Ladder;
use \App\SpawnOptionString;

use Illuminate\Http\Request;

class MapPoolController extends Controller {

    public function postQuickMatchMap(Request $request)
    {
        if ($request->id == "new")
        {
            $qmMap = new \App\QmMap;
            $message = "Successfully created new map";
        }
        else {
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
        $qmMap->description = $request->description;
        $qmMap->admin_description = $request->admin_description;
        $qmMap->bit_idx = $request->bit_idx;
        $qmMap->valid = $request->valid;
        $qmMap->rejectable = $request->rejectable == "on" ? true : false;
        $qmMap->default_reject = $request->default_reject == "on" ? true : false;
        $qmMap->allowed_sides = implode(",", $request->allowed_sides);
        $qmMap->spawn_order = $request->spawn_order;
        $qmMap->team1_spawn_order = $request->team1_spawn_order;
        $qmMap->team2_spawn_order = $request->team2_spawn_order;
        $qmMap->save();

        $request->session()->flash('success', $message);
        return redirect()->back();
    }

    public function editMap(Request $request)
    {
        $this->validate($request, ['map_id' => 'required',
                                   'hash'   => 'required|min:40|max:40',
                                   'name'   => 'string',
                                   'mapImage' => 'image']);

        if ($request->map_id == 'new')
        {
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
        $map->hash = $request->hash;
        $map->name = $request->name;
        $map->save();
        $request->session()->flash('success', "Map Saved");

        if ($request->hasFile('mapImage'))
        {
            $filename = $map->hash . ".png";
            $filepath = config('filesystems')['map_images'] . "/" . $map->ladder->abbreviation;

            $request->file('mapImage')->move($filepath, $filename);
        }

        return redirect()->back()->withInput();
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

        return view("admin.edit-map-pool",
                    [ 'ladderUrl' => "/admin/setup/{$ladder->id}/edit",
                      'mapPool' => $mapPool,
                      'ladderAbbrev' => $ladder->abbreviation,
                      'maps' => $mapPool->maps()->orderBy('bit_idx')->get(),
                      'ladder' => $ladder,
                      'sides' => $ladder->sides,
                      'ladderMaps' => $ladder->maps,
                      'spawnOptions' =>  \App\SpawnOption::all(),
                      'allLadders' => \App\Ladder::all(),
                    ]);
    }

    public function cloneMapPool(Request $request, $ladderId)
    {
        $mapPool = new MapPool;
        $mapPool->name = $request->name;
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

    public function reorderMapPool(Request $request, $mapPoolId)
    {
        $mapPool = MapPool::find($mapPoolId);

        $maps = $mapPool->maps;
        $toSave = array();
        $count = $maps->count();

        for ($i = 0; $i < $count; ++$i)
        {
            $map_id = $request->input("bit_idx_{$i}");

            $map = \App\QmMap::find($map_id);
            if ($map !== null)
            {
                $map->bit_idx = $i;
                $toSave[] = $map;
            }
            else
            {
                $request->session()->flash('error', "Unabled to reorder the map pool");
                return redirect()->back();
            }
        }

        foreach ($toSave as $map)
        {
            $map->save();
        }

        $request->session()->flash('success', "Map Pool Reordered");
        return redirect()->back();
    }
}
