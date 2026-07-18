<?php

namespace Database\Seeders;

use App\Models\Candidate;
use App\Models\Election;
use App\Models\Position;
use App\Models\SystemSetting;
use App\Models\User;
use App\Models\Voter;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'manage elections',
            'manage positions',
            'manage candidates',
            'manage voters',
            'import voters',
            'import candidates',
            'view turnout',
            'view results',
            'publish results',
            'lock results',
            'view audit logs',
            'manage settings',
            'export reports',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission);
        }

        $roles = [
            'Super Admin' => $permissions,
            'Election Admin' => ['manage elections', 'manage positions', 'manage candidates', 'manage voters', 'import voters', 'import candidates', 'view turnout', 'view results', 'publish results'],
            'ICT Officer' => ['import voters', 'import candidates', 'view turnout', 'export reports'],
            'Electoral Officer' => ['view turnout'],
            'Observer/Auditor' => ['view turnout', 'view results', 'view audit logs'],
        ];

        foreach ($roles as $roleName => $rolePermissions) {
            Role::findOrCreate($roleName)->syncPermissions($rolePermissions);
        }

        $admin = User::updateOrCreate(
            ['email' => 'admin@example.com'],
            ['name' => 'Super Admin', 'password' => Hash::make('password')]
        );
        $admin->assignRole('Super Admin');

        foreach ([
            'school_name' => 'Demo School',
            'school_logo_path' => '',
            'public_results_enabled' => '0',
        ] as $key => $value) {
            SystemSetting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        $election = Election::updateOrCreate(
            ['title' => 'Sample Student Council Election'],
            [
                'description' => 'Demo election for setup and testing.',
                'academic_year' => '2026/2027',
                'start_at' => now()->subHour(),
                'end_at' => now()->addDay(),
                'status' => 'active',
                'created_by' => $admin->id,
            ]
        );

        $president = Position::updateOrCreate(
            ['election_id' => $election->id, 'name' => 'President'],
            ['display_order' => 1, 'max_choices' => 1, 'is_required' => true, 'allow_abstain' => true, 'is_active' => true]
        );

        $secretary = Position::updateOrCreate(
            ['election_id' => $election->id, 'name' => 'General Secretary'],
            ['display_order' => 2, 'max_choices' => 1, 'is_required' => true, 'allow_abstain' => false, 'is_active' => true]
        );

        foreach ([
            [$president, 'Ama Mensah', 'SC001', 'Form 3A', 'General Arts'],
            [$president, 'Kojo Boateng', 'SC002', 'Form 3B', 'Science'],
            [$secretary, 'Esi Owusu', 'SC003', 'Form 2A', 'Business'],
            [$secretary, 'Yaw Asare', 'SC004', 'Form 2B', 'Visual Arts'],
        ] as [$position, $name, $studentId, $className, $programme]) {
            Candidate::updateOrCreate(
                ['election_id' => $election->id, 'position_id' => $position->id, 'student_id' => $studentId],
                [
                    'candidate_name' => $name,
                    'class_name' => $className,
                    'programme' => $programme,
                    'status' => 'active',
                ]
            );
        }

        foreach ([
            ['2026001', 'Akua Example', 'Form 1A', 'Science', '1111'],
            ['2026002', 'Kofi Example', 'Form 1B', 'General Arts', '2222'],
            ['2026003', 'Abena Example', 'Form 2A', 'Business', '3333'],
        ] as [$studentId, $name, $className, $programme, $pin]) {
            Voter::updateOrCreate(
                ['election_id' => $election->id, 'student_id' => $studentId],
                [
                    'full_name' => $name,
                    'class_name' => $className,
                    'programme' => $programme,
                    'pin_hash' => Hash::make($pin),
                    'is_eligible' => true,
                    'has_voted' => false,
                ]
            );
        }
    }
}
