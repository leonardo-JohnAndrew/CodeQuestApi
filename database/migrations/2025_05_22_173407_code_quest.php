<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Profiles table
        Schema::create('profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained(); // FK: Profile belongs to User
            $table->enum('level',['Beginner', 'Intermmediate'])->default('Beginner');
          // $table->text('avatar_url')->nullable();
             $table->string('character_name')->nullable(); 
            $table->timestamps();
        });

        // Code problems table
        Schema::create('code_problems', function (Blueprint $table) {
            $table->id();
            $table->string('difficulty');
            $table->string('category');
            $table->text('problem');
            $table->text('output');
            $table->json('solution_blocks')->nullable();
            $table->json('decoy_blocks')->nullable();
            $table->boolean('is_auto_generated')->default(false);
            $table->timestamps();
        });

        // Problem attempts table
        Schema::create('problem_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('code_problem_id')->constrained();
            $table->text('submitted_code');
            $table->boolean('is_correct');
            $table->float('efficiency_score');
            $table->timestamps();
        });

        // User weaknesses table
        Schema::create('user_wrongattempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->string('category');
            $table->integer('wrong_attempts')->default(0);
            $table->timestamps();
        });

        // Powerups table
        Schema::create('powerups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description');
            $table->string('effect_type');
            $table->integer('value');
            $table->integer('duration_seconds');
            $table->timestamps();
        });

        // Inventory table
        Schema::create('inventory', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('powerup_id')->constrained();
            $table->integer('quantity')->default(1);
            $table->timestamps();
        });

        // Enemies table
        Schema::create('enemies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type'); // regular or boss
            $table->string('difficulty');
            $table->integer('hp');
            $table->text('attack_pattern')->nullable();
            $table->text('abilities')->nullable();
            $table->timestamps();
        });

        // Settings table
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->integer('music_volume')->default(100);
            $table->integer('sound_effects_volume')->default(100);
            $table->integer('brightness_level')->default(100);
            $table->integer('font_size')->default(14);
            $table->timestamps();
        });

        // Dictionary table
        Schema::create('dictionary_entries', function (Blueprint $table) {
            $table->id();
            $table->string('term');
            $table->text('description');
            $table->text('example_code');
            $table->string('language');
            $table->timestamps();
        });

        // Bug reports table
        Schema::create('bug_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->string('title');
            $table->text('description');
            $table->string('status')->default('pending');
            $table->timestamps();
        });

        // Achievement types table
        Schema::create('achievement_types', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description');
            $table->timestamps();
        });

        // Achievements table
        Schema::create('achievements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('achievement_type_id')->constrained();
            $table->timestamp('unlocked_at')->useCurrent();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('achievements');
        Schema::dropIfExists('achievement_types');
        Schema::dropIfExists('bug_reports');
        Schema::dropIfExists('dictionary_entries');
        Schema::dropIfExists('settings');
        Schema::dropIfExists('enemies');
        Schema::dropIfExists('inventory');
        Schema::dropIfExists('powerups');
        Schema::dropIfExists('user_weaknesses');
        Schema::dropIfExists('problem_attempts');
        Schema::dropIfExists('code_problems');
        Schema::dropIfExists('profiles');
        Schema::dropIfExists('users');
    }
};
