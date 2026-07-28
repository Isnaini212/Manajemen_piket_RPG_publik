<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable()->unique()->after('name');
        });

        // Fill existing users' username from email prefix or fallback to user_id
        $users = DB::table('users')->get();
        foreach ($users as $user) {
            if (empty($user->username)) {
                $baseUsername = Str::slug(explode('@', $user->email)[0], '');
                if (empty($baseUsername)) {
                    $baseUsername = 'user' . $user->id;
                }
                
                $username = $baseUsername;
                $counter = 1;
                while (DB::table('users')->where('username', $username)->where('id', '!=', $user->id)->exists()) {
                    $username = $baseUsername . $counter;
                    $counter++;
                }

                DB::table('users')->where('id', $user->id)->update(['username' => $username]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('username');
        });
    }
};
