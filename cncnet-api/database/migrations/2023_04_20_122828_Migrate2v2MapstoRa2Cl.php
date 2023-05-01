<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Migrate2v2MapstoRa2Cl extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 2v2 maps arr
        $map_hashes = [
            "2f11a7459c9951fbfd6b5bbd73cccd7cf9b0bf5c",
            "ffe59ccf68fd743ee8849c615af84180396c8e67",
            "8a815dd293f6eabe4e12721932ce47a38509b57d",
            "3e1a6efe2370f1b26419ece66672b52bf98dc131",
            "3b7a5dbc8c79eeb4507e1f17786db55b90157ee9",
            "7f496ead3cc793c47c6ee3218d19953038582f16",
            "b7d13fa9706265523ada6a123c4038d8cca5869c",
            "de8095bac1a1bd7b0b652172d1c61699a0a5f800",
            "b8acc906f300442a1ca6939f0b1585baa6fa9ec1",
            "9799edc96b087fe93ececf6b75512d29fdce33ff",
            "6098312323be166d4f4093606040d07da4186671",
            "878efaaac9cfec1f587ca6951dd9f13f419faf98",
            "b8e05586d1d383222484f2e1f8ce9e5d926492fc",
            "5f9289c6eb5c062be6d2f4fe5eded3a873274f18",
            "3daa7723f4635e8b3545e7c1281243c784d2f31a",
            "6af6f312ef8e923801792ad2e1d58d5a1922719a",
            "4674ccbc839e83249998477129d509541697d033",
            "eb43ccaa28a1d0569fd7bc9e22ca64f244abb570",
            "14486db097fdae778a0bd4dadeaad541f33117c0",
            "7240698e5fbd774735178edded065ebd8378dde4",
            "aad8f210dd82b3e01efc3235f8040c647d5a625a",
            "42d72fb244f49d6f9a797a103c84d6fa1912f4df",
            "e67aac49606a6f8e89a3e56b69b9c991372e090f",
            "3408d95f805db3710d910206669e856e7216e588",
            "84d6a66e52767d985cb5325107482f6714663add",
            "6ea9aecd81f54c8c29d6eeae74b9ee8f27a02e86",
            "7e725c92f08baaeb7d4b39fab54b3cfa04c8309f",
            "e9536a6edff4dcff7ccb1f2ff94268ee2c5d5bde"
        ];

        $maps = \App\Map::whereIn('hash', $map_hashes)->where('ladder_id', 5)->get();

        $clanLadder = \App\Ladder::where('abbreviation', 'ra2-cl')->first();

        foreach ($maps as $map)
        {
            $newMap = $map->replicate()->fill([
				'ladder_id' => $clanLadder->id,
			]);
			$newMap->save();
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
