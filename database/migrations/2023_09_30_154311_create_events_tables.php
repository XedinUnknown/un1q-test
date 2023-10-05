<?php

declare(strict_types=1);

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
        $eventsTableName = 'events';
        Schema::create($eventsTableName, function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->dateTime('start');
            $table->dateTime('end');
            $table->enum('frequency', [
                'daily',
                'weekly',
                'monthly',
                'yearly',
            ])->nullable();
            $table->integer('interval')->nullable();
            $table->dateTime('until')->nullable();
            $table->string('title', 255);
            $table->mediumText('description');
        });

        Schema::create('event_occurrences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id', )
                ->nullable();
            $table->dateTime('start');
            $table->dateTime('end');

            $table->index(['event_id', 'start']);
            $table->index(['event_id', 'end']);

            $table->foreign('event_id')
                ->references('id')
                ->on('events')
                // When an event is deleted, it has no occurrences
                ->onDelete('cascade')
                // When an event is modified, its occurrences are invalidated,
                // and must be regenerated; cleanup elsewhere
                ->onUpdate('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_occurrences');
        Schema::dropIfExists('events');
    }
};
