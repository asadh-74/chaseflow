<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reminder_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('step'); // index into the reminder ladder
            $table->string('template');
            $table->string('subject');
            $table->string('message_id')->nullable(); // AWS SES message ID
            $table->json('meta')->nullable();
            $table->timestamp('sent_at')->useCurrent();
            $table->timestamps();

            $table->unique(['invoice_id', 'step']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reminder_logs');
    }
};
