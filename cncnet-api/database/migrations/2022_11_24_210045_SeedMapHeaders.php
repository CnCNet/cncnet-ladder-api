<?php

use Illuminate\Database\Migrations\Migration;

class SeedMapHeaders extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $json = file_get_contents('./database/seed/blitz_map_headers.json');
        $json_data = json_decode($json, true);
        $this->createData($json_data);

        $json = file_get_contents('./database/seed/yr_map_headers.json');
        $json_data = json_decode($json, true);
        $this->createData($json_data);

        $json = file_get_contents('./database/seed/ra2_map_headers.json');
        $json_data = json_decode($json, true);
        $this->createData($json_data);
    }

    public function createData($json_data)
    {
        foreach ($json_data as $hash => $headers)
        {
            $map = \App\Models\Map::where('hash', '=', $hash)->first();

            if ($map == null)
            {
                echo "No map found for hash " . $hash . "\n";
                continue;
            }

            $mapHeader = new \App\Models\MapHeader();
            $mapHeader->map_id = $map->id;
            $mapHeader->width = $headers["width"];
            $mapHeader->height = $headers["height"];
            $mapHeader->startX = $headers["startX"];
            $mapHeader->startY = $headers["startY"];
            $mapHeader->numStartingPoints = $headers["numStartingPoints"];
            $mapHeader->save();

            foreach ($headers["waypoints"] as $waypointData)
            {
                $mapWaypoint = new \App\Models\MapWaypoint();
                $mapWaypoint->x = $waypointData["x"];
                $mapWaypoint->y = $waypointData["y"];
                $mapWaypoint->bit_idx = $waypointData["index"];
                $mapWaypoint->map_header_id = $mapHeader->id;
                $mapWaypoint->save();
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
