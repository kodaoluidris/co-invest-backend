<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class Usertypes extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $userTypes = array(
            array('id' => 1, 'name' => 'admin'),
            array('id' => 2, 'name' => 'superadmin'),
            array('id' => 3, 'name' => 'user')
        );
        DB::table('user_types')->insert($userTypes);
    }
}
