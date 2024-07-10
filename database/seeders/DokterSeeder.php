<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;


class DokterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('doctors')->insert([
            [
                'name' => "Dr. Ryan Indrajaya",
                "email" => "ryan123@gmail.com",
                "password" => "1234567890",
                "poli_id" => 1
            ],
            [
                'name' => "Dr. Nadiah Hatta",
                "email" => "nadin123@gmail.com",
                "password" => "1234567890",
                "poli_id" => 1
            ],
            [
                'name' => "Dr. Mustika Dewi",
                "email" => "mustika123@gmail.com",
                "password" => "1234567890",
                "poli_id" => 1
            ],
            [
                'name' => "Dr. Yusuf Ainurrofiq",
                "email" => "ryan123@gmail.com",
                "password" => "1234567890",
                "poli_id" => 2
            ],
            [
                'name' => "Dr. Bariq FAwwazi",
                "email" => "ryan123@gmail.com",
                "password" => "1234567890",
                "poli_id" => 2
            ],
            [
                'name' => "Dr. Sahirul Prasojo",
                "email" => "ryan123@gmail.com",
                "password" => "1234567890",
                "poli_id" => 2
            ],
            [
                'name' => "Dr. Radiana Rofiq",
                "email" => "ryan123@gmail.com",
                "password" => "1234567890",
                "poli_id" => 1
            ],
        ]
    );
    }
}
