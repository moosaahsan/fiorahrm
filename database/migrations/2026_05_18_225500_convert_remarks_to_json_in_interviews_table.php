<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Convert any existing plain text remarks into a JSON array string
        // so that the model 'array' cast does not fail on decode.
        $interviews = DB::table('interviews')->get();
        foreach ($interviews as $interview) {
            if (!empty($interview->remarks) && !is_array(json_decode($interview->remarks, true))) {
                DB::table('interviews')->where('id', $interview->id)->update([
                    'remarks' => json_encode([$interview->remarks])
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No schema changes to reverse.
    }
};
