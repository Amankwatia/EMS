<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('imports', function (Blueprint $table) {
            $table->string('generated_pins_path')->nullable()->after('failed_rows_path');
            $table->timestamp('generated_pins_expires_at')->nullable()->after('generated_pins_path');
        });
    }

    public function down(): void
    {
        Schema::table('imports', function (Blueprint $table) {
            $table->dropColumn(['generated_pins_path', 'generated_pins_expires_at']);
        });
    }
};
