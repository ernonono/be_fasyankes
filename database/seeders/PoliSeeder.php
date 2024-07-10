<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PoliSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('polis')->insert([
            [
                'name' => "Poli Gigi",
                "location" => "Lantai 2"
            ],
            [
                'name' => "Poli Syaraf",
                "location" => "Lantai 3 "
            ],
            [
                'name' => "Poli Jantung",
                "location" => "Lantai 2 "
            ],
            [
                'name' => "Poli Mata",
                "location" => "Lantai 4 "
            ],
            [
                'name' => "Poli Anak",
                "location" => "Lantai 2 "
            ]
        ]
    );
    }
}
