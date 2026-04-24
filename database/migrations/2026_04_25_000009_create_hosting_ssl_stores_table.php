<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hosting_ssl_stores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hosting_id')->constrained()->cascadeOnDelete();
            $table->string('key_type', 16)->nullable();
            $table->longText('private_key_pem')->nullable();
            $table->longText('csr_pem')->nullable();
            $table->longText('certificate_pem')->nullable();
            $table->longText('certificate_chain_pem')->nullable();
            $table->json('san_hostnames')->nullable();
            $table->timestamps();

            $table->unique('hosting_id');
        });

        if (Schema::hasColumn('hostings', 'ssl_san_hostnames')) {
            $rows = DB::table('hostings')->select('id', 'ssl_san_hostnames')->whereNotNull('ssl_san_hostnames')->get();
            foreach ($rows as $row) {
                $raw = $row->ssl_san_hostnames;
                if ($raw === null || $raw === '') {
                    continue;
                }
                $arr = is_string($raw) ? json_decode($raw, true) : $raw;
                if (! is_array($arr) || $arr === []) {
                    continue;
                }
                DB::table('hosting_ssl_stores')->insert([
                    'hosting_id' => $row->id,
                    'san_hostnames' => json_encode(array_values($arr)),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            Schema::table('hostings', function (Blueprint $table) {
                $table->dropColumn('ssl_san_hostnames');
            });
        }
    }

    public function down(): void
    {
        Schema::table('hostings', function (Blueprint $table) {
            $table->json('ssl_san_hostnames')->nullable()->after('domain');
        });

        if (Schema::hasTable('hosting_ssl_stores')) {
            $stores = DB::table('hosting_ssl_stores')->select('hosting_id', 'san_hostnames')->get();
            foreach ($stores as $s) {
                DB::table('hostings')
                    ->where('id', $s->hosting_id)
                    ->update(['ssl_san_hostnames' => $s->san_hostnames]);
            }
        }

        Schema::dropIfExists('hosting_ssl_stores');
    }
};
