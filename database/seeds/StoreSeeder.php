<?php

use Illuminate\Database\Seeder;
use Faker\Factory as FakerFactory;
use Illuminate\Support\Facades\DB;

class StoreSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = FakerFactory::create();

        $data = [
            // small tree
            ['name' => $faker->name, 'lft' => 1, 'rgt' => 4],
            ['name' => $faker->name, 'lft' => 2, 'rgt' => 3],

            // big tree
            ['name' => $faker->name, 'lft' => 5, 'rgt' => 20],
            ['name' => $faker->name, 'lft' => 6, 'rgt' => 9],
            ['name' => $faker->name, 'lft' => 7, 'rgt' => 8],
            ['name' => $faker->name, 'lft' => 10, 'rgt' => 17],
            ['name' => $faker->name, 'lft' => 11, 'rgt' => 14],
            ['name' => $faker->name, 'lft' => 12, 'rgt' => 13],
            ['name' => $faker->name, 'lft' => 15, 'rgt' => 16],
            ['name' => $faker->name, 'lft' => 18, 'rgt' => 19],
        ];

        DB::table('stores')->insert($data);
    }
}


