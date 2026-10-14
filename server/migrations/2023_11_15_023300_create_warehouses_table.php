<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the pallet_warehouses table.
 *
 * Warehouse is a first-class WMS entity. It holds a place_uuid foreign key
 * to the Fleetbase 'places' table for geographic/address data, while all
 * WMS-specific attributes (type, capacity, operating hours, status, etc.)
 * live here in the dedicated table.
 *
 * This file is intentionally dated 2023_11_15_023300 so it runs before all
 * other Pallet migrations that reference pallet_warehouses.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pallet_warehouses', function (Blueprint $table) {
            $table->increments('id');
            $table->uuid('uuid')->nullable()->unique();
            $table->string('public_id')->nullable()->unique();

            // Ownership / authorship
            $table->foreignUuid('company_uuid')->nullable()->index()->references('uuid')->on('companies');
            $table->foreignUuid('created_by_uuid')->nullable()->index()->references('uuid')->on('users');

            // Geographic anchor — the Place record holds address, coordinates, and geocoding data
            $table->foreignUuid('place_uuid')->nullable()->index()->references('uuid')->on('places');

            // Core identity
            $table->string('name')->nullable()->comment('Human-readable warehouse name');
            $table->string('code')->nullable()->unique()->comment('Short warehouse code, e.g. WH-001');

            // Classification
            $table->string('type')->nullable()->default('standard')
                ->comment('standard | cold-storage | hazmat | bonded | cross-dock | fulfillment-center');
            $table->string('status')->nullable()->default('active')
                ->comment('active | inactive | under-maintenance | closed');

            // Physical capacity
            $table->unsignedInteger('capacity')->nullable()
                ->comment('Total storage capacity in arbitrary units (pallets, bins, m³, etc.)');
            $table->unsignedInteger('current_utilization')->nullable()->default(0)
                ->comment('Current used capacity in the same units as capacity');
            $table->decimal('floor_area_sqm', 10, 2)->nullable()
                ->comment('Total floor area in square metres');

            // Operations
            $table->json('operating_hours')->nullable()
                ->comment('JSON map of weekday → { open, close } operating hours');
            $table->string('timezone')->nullable()
                ->comment('IANA timezone identifier, e.g. America/New_York');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('manager_uuid')->nullable()->index()
                ->comment('UUID of the user responsible for this warehouse');

            // Dock count (denormalised for quick display; authoritative count via pallet_warehouse_docks)
            $table->unsignedTinyInteger('total_docks')->nullable()->default(0);

            // Flags
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false)
                ->comment('Whether this is the company default warehouse');

            // Flexible metadata
            $table->json('meta')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pallet_warehouses');
    }
};
