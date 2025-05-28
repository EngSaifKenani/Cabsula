<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CleanDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // تعطيل التحقق من المفاتيح الخارجية
        Schema::disableForeignKeyConstraints();

        // جلب جميع أسماء الجداول
        $tables = DB::select('SHOW TABLES');

        // اسم العمود الذي يحتوي على أسماء الجداول (يعتمد على اسم قاعدة البيانات)
        $dbName = env('DB_DATABASE');
        $key = "Tables_in_{$dbName}";

        foreach ($tables as $table) {
            if ($table->$key !== 'users') {
                DB::table($table->$key)->truncate();
            }
        }

        // تفعيل المفاتيح الخارجية
        Schema::enableForeignKeyConstraints();
    }
}

