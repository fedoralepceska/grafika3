<?php

use App\Enums\InvoiceStatus;
use App\Enums\MachineCut;
use App\Enums\MachinePrint;
use App\Enums\Material;
use App\Enums\MaterialSmall;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('jobs', function (Blueprint $table) {
            $table->id();
            $table->float('width');
            $table->float('height');
            $table->string('file');
            $table->string('originalFile');
            $table->integer('estimatedTime');
            $table->string('shippingInfo');
            $table->integer('quantity');
            $table->integer('copies');
            $table->double('price');
            $table->enum('materials', Material::values())->nullable(); // Assuming you have an enum for Material
            $table->enum('materialsSmall', MaterialSmall::values())->nullable(); // Assuming you have an enum for MaterialSmall
            $table->enum('machineCut', MachineCut::values()); // Assuming you have an enum for MachineCut
            $table->enum('machinePrint', MachinePrint::values()); // Assuming you have an enum for MachinePrint
            $table->enum('status', InvoiceStatus::values()); // Assuming you have an enum for InvoiceStatus

            // Add a column to associate each job with a job action
            $table->unsignedBigInteger('job_action_id')->nullable();
            $table->foreign('job_action_id')->references('id')->on('job_actions');

            $table->unsignedBigInteger('invoice_id');
            $table->foreign('invoice_id')->references('id')->on('invoices')->onDelete('cascade');

            // Add a column to store the status of each job action
            $table->string('job_action_status')->nullable();

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('jobs');
    }
};
