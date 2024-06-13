<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('tbl_sanluong', function (Blueprint $table) {
            $table->index('SanLuong_Tram');
            $table->index('SanLuong_Ngay');
        });
    }

    public function down()
    {
        Schema::table('tbl_sanluong', function (Blueprint $table) {
            $table->dropIndex(['SanLuong_Tram']);
            $table->dropIndex(['SanLuong_Ngay']);
        });
    }
};