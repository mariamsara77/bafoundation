<?php

namespace Database\Seeders;

use App\Models\DonationCategory;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class DonationSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['donation-category-manage', 'donation-manage'] as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $categories = [
            ['name' => 'শিক্ষা সহায়তা', 'slug' => 'education-support', 'description' => 'শিক্ষা উপকরণ, বৃত্তি ও শিক্ষার্থীদের সহায়তা।', 'sort_order' => 1],
            ['name' => 'চিকিৎসা সহায়তা', 'slug' => 'medical-support', 'description' => 'চিকিৎসা ও জরুরি স্বাস্থ্য সহায়তা।', 'sort_order' => 2],
            ['name' => 'দরিদ্র ও অসহায় সহায়তা', 'slug' => 'poor-support', 'description' => 'অসহায় মানুষের প্রয়োজনীয় সহায়তা।', 'sort_order' => 3],
            ['name' => 'দুর্যোগ ও জরুরি সহায়তা', 'slug' => 'disaster-support', 'description' => 'দুর্যোগকালীন ত্রাণ ও জরুরি সহায়তা।', 'sort_order' => 4],
            ['name' => 'সাধারণ ফান্ড', 'slug' => 'general-fund', 'description' => 'ফাউন্ডেশনের প্রয়োজন অনুযায়ী ব্যবহারযোগ্য সাধারণ অনুদান।', 'sort_order' => 5],
        ];

        foreach ($categories as $category) {
            DonationCategory::updateOrCreate(
                ['slug' => $category['slug']],
                [...$category, 'is_active' => true]
            );
        }
    }
}
