<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class SeedTSmaps extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('maps', function(Blueprint $table)
        {
            //
        });
        DB::table('maps')->insert([
            ["hash" => "6a2e289d78d82e77e521c0ce9f917a04e38c8e5f", "name" => "z TS No where to run" ],
            ["hash" => "57bb8a7049de924050919144b476770656a1a0c2", "name" => "z TS Theme Park" ],
            ["hash" => "98c0fb0f9841a8fcc39da73ec53d98467852468a", "name" => "z TS Hidden Valley" ],
            ["hash" => "096862ccc3088a6e72b9b7a65d5d2321dc37a312", "name" => "z TS Tiberium Forest" ],
            ["hash" => "af44df3e39c8881af129569549b7bceb3809f7fc", "name" => "z TS Limited Access" ],
            ["hash" => "a876cb4aa93c02b2e3643122464a60cf838abd71", "name" => "z TS A River Runs Near It" ],
            ["hash" => "b820db56422ce4b7ecc1ecd53e9baf97aaaede7c", "name" => "z TS Forest Fires (Remake 1.45)(Air Fix)(Ally)(Vet.1.5)" ],
            ["hash" => "be2348a867214fdb1962df6634ea92df976d0790", "name" => "z TS The Pit [2-4p]" ],
            ["hash" => "e3ed1adc3c5654999559558f6bd40b18e66243cd", "name" => "z TS Casey's Canyon" ],
            ["hash" => "e1dc86b2a002158727044f8b271ced0a7f86ac24", "name" => "z TS Cityscape" ],
            ["hash" => "abc9488a734d7aa74f379bf28ccfc8db78bb5633", "name" => "z TS Cliffs of Insanity" ],
            ["hash" => "7d9c280f5de3e5312bb4e20bfe7794077be05b1c", "name" => "z TS Desolation Redux" ],
            ["hash" => "fab406ccf2e71b4b50c2c0c05b34ed04a14f0c12", "name" => "z TS Drawbridges" ],
            ["hash" => "445e0f1043165087ba1532123cc188b5e3f801b0", "name" => "z TS Dueling Islands" ],
            ["hash" => "e04d895cc33511b53d4add4e4862fc879a4d035f", "name" => "z TS Terrace(2-4)(TL Fix+Auto Ally+Veteran 1.6)" ],
            ["hash" => "e0d6c985a13769b62734abd06f51eab192d00c87", "name" => "z TS Dueling Islands (TS .033)(Vet1.5)" ],
            ["hash" => "f464755dd5722af2e5b8965236cc7298a0d020f1", "name" => "z TS A River Runs Near It (Cliffs Fixed) (Air Fix)" ],
            ["hash" => "9c20d66f951e720540f823fa311d61aa398e1518", "name" => "z TS They All Float" ],
            ["hash" => "759904e072762066195aa234f52ac807d0fd7d38", "name" => "z TS Forest Fires" ],
            ["hash" => "2ec7a60e01dc1b63a9cef1e210e65d962710040c", "name" => "z TS Grand Canyon" ],
            ["hash" => "4c9bb842b6bc3fe191188f2925473771e646bd28", "name" => "z TS Grassy Knoll" ],
            ["hash" => "e9efed31f8ab0c1ebfddf1bdd598158ca3b10615", "name" => "z TS Hex-Treme!" ],
            ["hash" => "038d6d4a991e126bd928a492cd6c4b7e1bf33ced", "name" => "z TS Ice Cliffs" ],
            ["hash" => "ee5aa7ca1d9ef9e3400903e5def5c52a28594505", "name" => "z TS The Ice Must Floe" ],
            ["hash" => "6f19e8fe8e55d193d1cd48a1865714cef6c61269", "name" => "z TS Night of the Mutants" ],
            ["hash" => "0ada05b3685ddbcb008cbd1c15afb50e80f0fff3", "name" => "z TS Narrow River" ],
            ["hash" => "dacd37e40630f010521fa7c559a89febcb3f73a6", "name" => "z TS Pentagram" ],
            ["hash" => "3bc2ce35d4e24da690d0bf050d7d5d30747ef2d1", "name" => "z TS Permafrost (2-4)" ],
            ["hash" => "417cc341a0e2b4dfe0f8d375743cb9502d2403e8", "name" => "z TS Pit Or Plateau" ],
            ["hash" => "0a3c59378536f44c21590ea9c67195383880abb4", "name" => "z TS Pockets" ],
            ["hash" => "0b28e21f194fbf9ba436eb073236aabd152687ce", "name" => "z TS River Raid" ],
            ["hash" => "1dc4ba19f22d502c7e643a1e22833a2cafe3caab", "name" => "z TS Crater" ],
            ["hash" => "250a10b29638a5329fb4cfba92753053ba5c02c7", "name" => "z TS Sinkholes" ],
            ["hash" => "913006fa90c86bd7d0fa6f14d224df3ddac3245f", "name" => "z TS Hot Springs" ],
            ["hash" => "74562352ac268cadeaa9f46a8e5420b5fd8d8945", "name" => "z TS Storms" ],
            ["hash" => "933122161be97d1e5be7d1a684cc238897c2f748", "name" => "z TS Stormy Valley" ],
            ["hash" => "39ed7032b9887639198af18045bd9cc6ef6f2189", "name" => "z TS Super Bridgehead Redux" ],
            ["hash" => "072bac8a58e34540ed5cb281613fb9ed979cd9fc", "name" => "z TS Tiberium Garden Redux" ],
            ["hash" => "a399692bf1919e37674cf969864ef15ad6839c12", "name" => "z TS Tactical" ],
            ["hash" => "1403752eac08ad2218311f82ffffd6abbf8e84f9", "name" => "z TS Terraces" ],
            ["hash" => "161ef72c45e40be8c3d1c907643b9d677a7b6350", "name" => "z TS Tiers of Sorrow" ],
            ["hash" => "1fb683ba5801406ed15abf8bc9c23c3d4ee217db", "name" => "z TS Tread Lightly" ],
            ["hash" => "118560c04869c73935289bc26884adece0afc18f", "name" => "z TS Tunnel Train-ing" ],
            ["hash" => "28f5fc7fdf91af940a05d35dc9a8224cd7c4a07c", "name" => "z TS Xcapades" ]
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('maps', function(Blueprint $table)
        {
            //
        });
        DB::table('maps')->where('name', 'like', 'z TS%')->delete();
    }
}
