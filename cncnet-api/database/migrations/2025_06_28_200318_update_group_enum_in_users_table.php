<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE users MODIFY `group` ENUM('User', 'Moderator', 'Admin', 'God', 'Observer') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE users MODIFY `group` ENUM('User', 'Moderator', 'Admin', 'God') NOT NULL");
    }
};
