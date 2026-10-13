<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $defaults = [
        'Guzape', 'Gwarimpa', 'Maraba', 'Maitama', 'Wuse Zone 5',
        'Kuje', 'Karu', 'Gwagwalada', 'Lugbe FHA', 'Kubwa',
    ];

    public function up(): void
    {
        Schema::create('church_branches', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $now = now();
        foreach ($this->defaults as $i => $name) {
            DB::table('church_branches')->insert([
                'name' => $name, 'sort_order' => $i + 1, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now,
            ]);
        }

        Schema::table('workers', function (Blueprint $table) {
            $table->foreignId('church_branch_id')->nullable()->after('role')
                ->constrained('church_branches')->nullOnDelete();
        });

        // Carry over any free-text branches entered before this list existed.
        $order = count($this->defaults);
        foreach (DB::table('workers')->whereNotNull('church_branch')->where('church_branch', '!=', '')->get() as $w) {
            $name = trim($w->church_branch);
            $id = DB::table('church_branches')->where('name', $name)->value('id')
                ?? DB::table('church_branches')->insertGetId([
                    'name' => $name, 'sort_order' => ++$order, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now,
                ]);
            DB::table('workers')->where('id', $w->id)->update(['church_branch_id' => $id]);
        }

        Schema::table('workers', function (Blueprint $table) {
            $table->dropColumn('church_branch');
        });
    }

    public function down(): void
    {
        Schema::table('workers', function (Blueprint $table) {
            $table->string('church_branch')->nullable()->after('role');
        });

        foreach (DB::table('workers')->whereNotNull('church_branch_id')->get() as $w) {
            DB::table('workers')->where('id', $w->id)->update([
                'church_branch' => DB::table('church_branches')->where('id', $w->church_branch_id)->value('name'),
            ]);
        }

        Schema::table('workers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('church_branch_id');
        });

        Schema::dropIfExists('church_branches');
    }
};
