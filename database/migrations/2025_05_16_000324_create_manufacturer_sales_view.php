<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("DROP VIEW IF EXISTS manufacturer_sales_view");

        DB::statement("
            CREATE VIEW manufacturer_sales_view AS
            SELECT
                drugs.manufacturer_id,
                manufacturers.name AS manufacturer_name,
                DATE(invoices.created_at) AS sale_date,
                SUM(invoice_items.quantity) AS total_quantity_sold,
                SUM(invoice_items.quantity * invoice_items.profit_amount) AS total_profit
            FROM invoice_items
            JOIN invoices ON invoice_items.invoice_id = invoices.id
            JOIN drugs ON invoice_items.drug_id = drugs.id
            JOIN manufacturers ON drugs.manufacturer_id = manufacturers.id
            GROUP BY drugs.manufacturer_id, manufacturers.name, DATE(invoices.created_at)
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS manufacturer_sales_view");
    }
};
