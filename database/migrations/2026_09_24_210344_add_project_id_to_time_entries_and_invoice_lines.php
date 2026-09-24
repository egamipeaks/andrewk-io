<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('time_entries', function (Blueprint $table) {
            $table->foreignId('project_id')->nullable()->after('client_id')->constrained()->nullOnDelete();
        });

        Schema::table('invoice_lines', function (Blueprint $table) {
            $table->foreignId('project_id')->nullable()->after('invoice_id')->constrained()->nullOnDelete();
        });
    }
};
