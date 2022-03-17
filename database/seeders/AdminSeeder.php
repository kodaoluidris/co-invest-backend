<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('users')->insert([
            'fname' => 'Admin',
            'lname' => 'AtLifecard',
            'email' => 'admin@admin.com',
            'user_type_id' => 2,
            'password' => Hash::make(12345678)
        ]);
    }
}
