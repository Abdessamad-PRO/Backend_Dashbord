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
        Schema::table('users', function (Blueprint $table) {
            $table->string('prenom')->nullable()->after('name'); 
            $table->string('role')->default('user')->after('password');    // user, manager, admin
            $table->string('email_utilisateur')->after('role')->nullable();
            $table->string('telephone')->nullable();
            $table->string('photo_de_profile')->nullable();
            $table->text('bio')->nullable();
          

        }); 
    } 

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
             $table->dropColumn([
            'prenom',
            'email_utilisateur',
            'telephone',
            'photo_de_profile',
            'bio',
            'role'
        ]);
        });
    }
};
