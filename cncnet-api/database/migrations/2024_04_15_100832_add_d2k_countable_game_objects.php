<?php

use App\Models\CountableGameObject;
use App\Models\GameObjectSchema;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        //
        $gameObjectSchema = GameObjectSchema::find(4); // d2k
        $gameObjects = CountableGameObject::where("game_object_schema_id", 4)->delete();

        $d2k_units = [
            "Units" => [
                "Trike" => [5 => 'trike'],
                "Raider" => [6 => 'raider'],
                "Quad" => [7 => 'quad'],
                "Harvester" => [8 => 'harvester'],
                "Combat Tank" => [9 => 'combattank', 10 => 'combattank', 11 => 'combattank'],
                "MCV" => [12 => 'mcv'],
                "Missile Tank" => [13 => 'missiletank'],
                "Deviator" => [14 => 'deviator'],
                "Siege Tank" => [15 => 'siegetank'],
                "Sonic Tank" => [16 => 'sonictank'],
                "Devastator" => [17 => 'devastator'],
                "Stealth Raider" => [25 => 'stealthraider'],
                "Death Hand" => [23 => 'deathhand'],
            ],
            "Infantry" => [
                "Light Infantry" => [0 => 'lightinfantry'],
                "Trooper" => [1 => 'trooper'],
                "Engineer" => [2 => 'engineer'],
                "Thumper" => [3 => 'thumper'],
                "Sardaukar" => [4 => 'sardaukar', 26 => 'sardaukar'],
                "Fremen" => [21 => 'fremen'],
                "Saboteur" => [22 => 'saboteur'],
                "Grenadier" => [24 => 'grenadier'],
            ],
            "Planes" => [
                "Carryall" => [18 => 'carryall', 19 => 'carryall'],
                "Ornithopter" => [20 => 'ornithopter'],
            ],
        ];

        // Units bought
        foreach ($d2k_units["Units"] as $name => $heapIdArr)
        {
            foreach ($heapIdArr as $heapId => $cameoName)
            {
                $this->createNew($gameObjectSchema->id, "UNB", $heapId, $cameoName, $name);
            }
            /*
            // Doesn't exist in the spawner
            foreach ($heapIdArr as $heapId => $cameoName)
            {
                $this->createNew($gameObjectSchema->id, "UNK", $heapId, $cameoName, $name);
            }
            foreach ($heapIdArr as $heapId => $cameoName)
            {
                $this->createNew($gameObjectSchema->id, "UNL", $heapId, $cameoName, $name);
            }
            */
        }
        foreach ($d2k_units["Infantry"] as $name => $heapIdArr)
        {
            foreach ($heapIdArr as $heapId => $cameoName)
            {
                $this->createNew($gameObjectSchema->id, "UNB", $heapId, $cameoName, $name);
            }
            /* 
            // Doesn't exist in the spawner
            foreach ($heapIdArr as $heapId => $cameoName)
            {
                $this->createNew($gameObjectSchema->id, "INB", $heapId, $cameoName, $name);
            }
            foreach ($heapIdArr as $heapId => $cameoName)
            {
                $this->createNew($gameObjectSchema->id, "INK", $heapId, $cameoName, $name);
            }
            foreach ($heapIdArr as $heapId => $cameoName)
            {
                $this->createNew($gameObjectSchema->id, "INL", $heapId, $cameoName, $name);
            }
            */
        }
        foreach ($d2k_units["Planes"] as $name => $heapIdArr)
        {
            foreach ($heapIdArr as $heapId => $cameoName)
            {
                $this->createNew($gameObjectSchema->id, "PLB", $heapId, $cameoName, $name);
            }
            /*
            // Doesn't exist in the spawner
            foreach ($heapIdArr as $heapId => $cameoName)
            {
                $this->createNew($gameObjectSchema->id, "PLK", $heapId, $cameoName, $name);
            }
            foreach ($heapIdArr as $heapId => $cameoName)
            {
                $this->createNew($gameObjectSchema->id, "PLL", $heapId, $cameoName, $name);
            }
            */
        }

        $d2k_buildings = [
            "Construction Yard" => [0 => 'constructionyard', 1 => 'constructionyard', 2 => 'constructionyard'],
            "Concrete" => [4 => 'concrete', 5 => 'concrete', 6 => 'concrete'],
            "Large Concrete" => [7 => 'largeconcrete', 8 => 'largeconcrete', 9 => 'largeconcrete'],
            "Windtrap" => [10 => 'windtrap', 11 => 'windtrap', 12 => 'windtrap'],
            "Barracks" => [13 => 'barracks', 14 => 'barracks', 15 => 'barracks'],
            "Wall" => [17 => 'wall', 18 => 'wall', 19 => 'wall'],
            "Refinery" => [20 => 'refinery', 21 => 'refinery', 22 => 'refinery'],
            "Gun Turret" => [23 => 'gunturret', 24 => 'gunturret', 25 => 'gunturret'],
            "Outpost" => [26 => 'outpost', 27 => 'outpost', 28 => 'outpost'],
            "Missile Turret" => [29 => 'missileturret', 30 => 'missileturret', 31 => 'missileturret'],
            "High-Tech Factory" => [32 => 'hightechfactory', 33 => 'hightechfactory', 34 => 'hightechfactory'],
            "Light Factory" => [35 => 'lightfactory', 36 => 'lightfactory', 37 => 'lightfactory'],
            "Silo" => [38 => 'silo', 39 => 'silo', 40 => 'silo'],
            "Heavy Factory" => [41 => 'heavyfactory', 42 => 'heavyfactory', 43 => 'heavyfactory'],
            "Starport" => [46 => 'starport', 47 => 'starport', 48 => 'starport'],
            "Repair Pad" => [50 => 'repairpad', 51 => 'repairpad', 52 => 'repairpad'],
            "IX Research Centre" => [53 => 'ixresearchcentre', 54 => 'ixresearchcentre', 55 => 'ixresearchcentre'],
            "Atreides Palace" => [56 => 'atreidespalace'],
            "Harkonnen Palace" => [57 => 'harkonnenpalace'],
            "Ordos Palace" => [58 => 'ordospalace']
        ];

        foreach ($d2k_buildings as $name => $heapIdArr)
        {
            foreach ($heapIdArr as $heapId => $cameoName)
            {
                $this->createNew($gameObjectSchema->id, "BLB", $heapId, $cameoName, $name);
            }
            foreach ($heapIdArr as $heapId => $cameoName)
            {
                $this->createNew($gameObjectSchema->id, "BLK", $heapId, $cameoName, $name);
            }
            foreach ($heapIdArr as $heapId => $cameoName)
            {
                $this->createNew($gameObjectSchema->id, "BLC", $heapId, $cameoName, $name);
            }
            foreach ($heapIdArr as $heapId => $cameoName)
            {
                $this->createNew($gameObjectSchema->id, "BLL", $heapId, $cameoName, $name);
            }
        }
    }

    private function createNew($schemaId, $heapName, $heapId, $cameoName, $uiName)
    {
        $countableGameObject = new CountableGameObject();
        $countableGameObject->game_object_schema_id = $schemaId;
        $countableGameObject->heap_name = $heapName;
        $countableGameObject->heap_id = $heapId;
        $countableGameObject->cameo = $cameoName;
        $countableGameObject->ui_name = $uiName;
        $countableGameObject->name = $uiName;
        $countableGameObject->cost = 0;
        $countableGameObject->value = 0;
        $countableGameObject->save();
        return $countableGameObject;
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
