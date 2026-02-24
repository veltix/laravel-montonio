<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('montonio_payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('method_code');
            $table->string('name');
            $table->string('country')->nullable();
            $table->string('currency')->nullable();
            $table->json('metadata');
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique(['method_code', 'country']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('montonio_payment_methods');
    }
};
