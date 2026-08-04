<?php
namespace Database\Seeders;

use App\Enums\Status;
use App\Models\Language;
use Illuminate\Database\Seeder;

class LanguageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $languages = [
            [
                'name'      => 'English',
                'code'      => 'en',
                'flag_icon' => '🇬🇧',
                'status'    => Status::ACTIVE,
            ],
            [
                'name'      => 'German',
                'code'      => 'de',
                'flag_icon' => '🇩🇪',
                'status'    => Status::ACTIVE,
            ],
            [
                'name'      => 'Arabic',
                'code'      => 'sa',
                'flag_icon' => '🇸🇦',
                'status'    => Status::ACTIVE,
            ]
        ];

        Language::insert($languages);
    }
}
