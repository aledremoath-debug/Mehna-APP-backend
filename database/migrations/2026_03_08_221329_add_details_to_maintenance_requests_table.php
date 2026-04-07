<?php

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
        Schema::table('maintenance_requests', function (Blueprint $table) {
            $table->text('cancel_reason')->nullable()->after('status');
            $table->text('provider_notes')->nullable()->after('cancel_reason');
            $table->decimal('added_cost', 10, 2)->nullable()->default(0)->after('provider_notes');
            $table->string('cost_description')->nullable()->after('added_cost');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('maintenance_requests', function (Blueprint $table) {
            $table->dropColumn([
                'cancel_reason',
                'provider_notes',
                'added_cost',
                'cost_description'
            ]);
        });
    }
};
