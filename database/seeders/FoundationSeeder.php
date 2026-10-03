<?php

namespace Database\Seeders;

use App\Models\DonationCategory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class FoundationSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'activity-log-view',
            'system-manager',
            'google indexing request',
            'biodata-manage',
            'user-manage',
            'designation-manage',
            'role-manage',
            'permission-manage',
            'work-manage',
            'work-vote',
            'contact-manage',
            'donation-category-manage',
            'donation-manage',
        ];

        foreach ($permissions as $name) {
            Permission::firstOrCreate(['name' => $name]);
        }

        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $memberRole = Role::firstOrCreate(['name' => 'member']);

        $adminRole->syncPermissions(Permission::all());
        $memberRole->syncPermissions(['work-vote']);

        $adminEmail = strtolower(trim((string) env('FOUNDATION_ADMIN_EMAIL', 'shaangi.com@gmail.com')));
        $adminName = trim((string) env('FOUNDATION_ADMIN_NAME', 'System Admin'));
        $adminPassword = (string) env('FOUNDATION_ADMIN_PASSWORD', '');

        $admin = User::where('email', $adminEmail)->first();

        if (!$admin) {
            if (blank($adminPassword)) {
                throw new \RuntimeException('FOUNDATION_ADMIN_PASSWORD must be configured before creating the initial admin user.');
            }

            $admin = User::create([
                'name' => $adminName,
                'email' => $adminEmail,
                'password' => Hash::make($adminPassword),
                'status' => 'active',
                'email_verified_at' => now(),
            ]);
        }

        $admin->forceFill([
            'name' => $admin->name ?: $adminName,
            'status' => 'active',
            'email_verified_at' => $admin->email_verified_at ?: now(),
        ])->save();

        $admin->syncRoles([$adminRole]);

        $admin->profile()->firstOrCreate(
            ['user_id' => $admin->id],
            ['status' => 'active', 'priority' => 1]
        );

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

        $this->command?->info('Foundation roles, permissions, admin user, profile, and donation categories are ready.');
    }
}
