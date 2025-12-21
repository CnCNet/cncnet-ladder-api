<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private $allowedObserverUserIds = [
        9466,   // MJ
        30045,  // Matt
        53431,  // Iver
        38417,  // Doofus
        332,    // Burg
        1,      // Grant
        48373,  // Root
        2152,   // Edd
        68318,  // Wu
        17221,  // Lloyd
        60350,  // shamou
        38610,  // Poppa
        19083   // Brian
    ];

    public function up(): void
    {
        DB::table('users')
            ->whereIn('id', $this->allowedObserverUserIds)
            ->whereNotIn('group', ['Moderator', 'Admin', 'God'])
            ->update(['group' => 'Observer']);
    }

    public function down(): void
    {
        DB::table('users')
            ->whereIn('id', $this->allowedObserverUserIds)
            ->where('group', 'Observer')
            ->update(['group' => 'User']);
    }
};
