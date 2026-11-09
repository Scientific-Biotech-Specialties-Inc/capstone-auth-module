<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
// RolePermissionSeeder.php

    public function run(): void
    {
        $roles = [
            ['name' => 'Super Admin',         'description' => 'System wide access',                       'nav_group' => 'admin'],
            ['name' => 'IT Admin',            'description' => 'IT infrastructure and user management',    'nav_group' => 'admin'],
            ['name' => 'Admin',               'description' => 'General administration',                   'nav_group' => 'admin'],
            ['name' => 'Manager',             'description' => 'Management of specific department',        'nav_group' => 'manager'],
            ['name' => 'Sales',               'description' => 'Sales staff access',                        'nav_group' => 'staff'],
            ['name' => 'Supervisor',          'description' => 'Department supervisor access',              'nav_group' => 'manager'],
            ['name' => 'Finance',             'description' => 'Finance staff access',                      'nav_group' => 'staff'],
            ['name' => 'Employee',            'description' => 'Regular staff access',                      'nav_group' => 'staff'],
            ['name' => 'CEO',                 'description' => 'Chief Executive Officer with executive authority across workflows and systems', 'nav_group' => 'admin'],
            ['name' => 'Accountant',          'description' => 'Accounting staff access',                   'nav_group' => 'staff'],
            ['name' => 'Engineer',            'description' => 'Field and Service Engineer access',         'nav_group' => 'staff'],
        ];

        // insertOrIgnore: skips duplicates instead of crashing
        // insertOrIgnore: skips duplicates instead of crashing
        DB::table('roles')->insertOrIgnore($roles);

        $permissions = [
            // Auth Service Internal Permissions
            ['name' => 'View Dashboard',       'slug' => 'view-dashboard',       'system' => 'auth'],
            ['name' => 'Manage Users',          'slug' => 'manage-users',         'system' => 'auth'],
            ['name' => 'Manage Roles',          'slug' => 'manage-roles',         'system' => 'auth'],
            ['name' => 'View Audit Logs',       'slug' => 'view-audit-logs',      'system' => 'auth'],
            ['name' => 'Manage Departments',    'slug' => 'manage-departments',   'system' => 'auth'],

            // CMS Feature Permissions (legacy / internal)
            ['name' => 'Manage CMS Roles',     'slug' => 'cms.roles.manage',         'system' => 'cms'],
            ['name' => 'Manage Templates',     'slug' => 'cms.templates.manage',     'system' => 'cms'],
            ['name' => 'Use Templates',        'slug' => 'cms.templates.use',        'system' => 'cms'],
            ['name' => 'OCR Upload',           'slug' => 'cms.ocr.upload',           'system' => 'cms'],
            ['name' => 'OCR Process',          'slug' => 'cms.ocr.process',          'system' => 'cms'],
            ['name' => 'OCR Review',           'slug' => 'cms.ocr.review',           'system' => 'cms'],
            ['name' => 'Generate Draft',       'slug' => 'cms.contracts.generate',   'system' => 'cms'],
            ['name' => 'Run Risk Assessment',  'slug' => 'cms.risk.assess',          'system' => 'cms'],
            ['name' => 'View Risk Highlights', 'slug' => 'cms.risk.view',            'system' => 'cms'],
            ['name' => 'Approve/Override Risk','slug' => 'cms.risk.approve',         'system' => 'cms'],

            // CMS CRUD Permissions ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â Contracts (maps to "Contracts" UI category)
            ['name' => 'View Contracts',   'slug' => 'cms.contracts.view',   'system' => 'cms'],
            ['name' => 'Create Contracts', 'slug' => 'cms.contracts.create', 'system' => 'cms'],
            ['name' => 'Edit Contracts',   'slug' => 'cms.contracts.edit',   'system' => 'cms'],
            ['name' => 'Delete Contracts', 'slug' => 'cms.contracts.delete', 'system' => 'cms'],
            ['name' => 'Approve Contracts', 'slug' => 'cms.contracts.approve', 'system' => 'cms'],

            // CMS CRUD Permissions ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â User Management (maps to "User Management" UI category)
            ['name' => 'View Users',   'slug' => 'cms.users.view',   'system' => 'cms'],
            ['name' => 'Create Users', 'slug' => 'cms.users.create', 'system' => 'cms'],
            ['name' => 'Edit Users',   'slug' => 'cms.users.edit',   'system' => 'cms'],
            ['name' => 'Delete Users', 'slug' => 'cms.users.delete', 'system' => 'cms'],

            // CMS CRUD Permissions ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â Partners (maps to "Business Partners & Suppliers" UI category)
            ['name' => 'View Partners',   'slug' => 'cms.partners.view',   'system' => 'cms'],
            ['name' => 'Create Partners', 'slug' => 'cms.partners.create', 'system' => 'cms'],
            ['name' => 'Edit Partners',   'slug' => 'cms.partners.edit',   'system' => 'cms'],
            ['name' => 'Delete Partners', 'slug' => 'cms.partners.delete', 'system' => 'cms'],


            // PRS Permissions
            ['name' => 'View PRS Dashboard', 'slug' => 'prs.dashboard.view', 'system' => 'prs'],
            ['name' => 'View PRS Activity Log', 'slug' => 'prs.activity-log.view', 'system' => 'prs'],
            ['name' => 'Create PRS Submissions', 'slug' => 'prs.submissions.create', 'system' => 'prs'],
            ['name' => 'Validate PRS Submissions', 'slug' => 'prs.submission.validate', 'system' => 'prs'],
            ['name' => 'Manage PRS Itineraries', 'slug' => 'prs.itineraries.manage', 'system' => 'prs'],
            ['name' => 'View PRS Leaderboard', 'slug' => 'prs.leaderboard.view', 'system' => 'prs'],
            ['name' => 'View PRS Reports', 'slug' => 'prs.reports.view', 'system' => 'prs'],
            ['name' => 'Manage PRS Users', 'slug' => 'prs.settings.users.manage', 'system' => 'prs'],
            ['name' => 'Manage PRS Departments', 'slug' => 'prs.settings.departments.manage', 'system' => 'prs'],
            ['name' => 'Manage PRS Products', 'slug' => 'prs.settings.products.manage', 'system' => 'prs'],
            ['name' => 'Manage PRS Institutions', 'slug' => 'prs.settings.institutions.manage', 'system' => 'prs'],
            ['name' => 'View PRS Files', 'slug' => 'prs.files.view', 'system' => 'prs'],
            // SERMS Permissions
            ['name' => 'Manage Reimbursements', 'slug' => 'serms.reimbursements.manage', 'system' => 'serms'],
            ['name' => 'Manage Liquidations',   'slug' => 'serms.liquidations.manage',   'system' => 'serms'],
            ['name' => 'Manage Cash Advances',  'slug' => 'serms.cash_advances.manage',  'system' => 'serms'],
        ];

        foreach ($permissions as $permission) {
            \App\Models\Permission::updateOrCreate(
                ['name' => $permission['name']],
                $permission
            );
        }

        // --- Role Assignment Logic ---

        // 1. IT Admin / Super Admin ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â full auth management
        $authAdminRoles = \App\Models\Role::whereIn('name', ['Super Admin', 'IT Admin'])->get();
        $authPermissions = \App\Models\Permission::where('system', 'auth')->get();
        foreach ($authAdminRoles as $role) {
            $role->permissions()->syncWithoutDetaching($authPermissions->pluck('id'));
        }

        // 2. CMS Admin ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â full access to everything
        $cmsAdmin = \App\Models\Role::where('name', 'Admin')->first();
        if ($cmsAdmin) {
            $adminPerms = \App\Models\Permission::whereIn('slug', [
                'cms.roles.manage',
                'cms.templates.manage',
                'cms.templates.use',
                'cms.ocr.upload',
                'cms.ocr.process',
                'cms.ocr.review',
                'cms.contracts.generate',
                'cms.risk.assess',
                'cms.risk.view',
                'cms.risk.approve',
                // CRUD
                'cms.contracts.view', 'cms.contracts.create', 'cms.contracts.edit', 'cms.contracts.delete', 'cms.contracts.approve',
                'cms.users.view',     'cms.users.create',     'cms.users.edit',     'cms.users.delete',
                'cms.partners.view',  'cms.partners.create',  'cms.partners.edit',  'cms.partners.delete',
                'manage-users',
                'serms.reimbursements.manage', 'serms.liquidations.manage', 'serms.cash_advances.manage',
                'prs.dashboard.view', 'prs.activity-log.view', 'prs.submissions.create', 'prs.submission.validate',
                'prs.itineraries.manage', 'prs.leaderboard.view', 'prs.reports.view', 'prs.settings.users.manage',
                'prs.settings.departments.manage', 'prs.settings.products.manage', 'prs.settings.institutions.manage', 'prs.files.view',
            ])->get();
            $cmsAdmin->permissions()->syncWithoutDetaching($adminPerms->pluck('id'));
        }

        // 2b. CEO — executive authority, all CMS permissions for workflows and approvals
        $ceoRole = \App\Models\Role::where('name', 'CEO')->first();
        if ($ceoRole) {
            $ceoPerms = \App\Models\Permission::whereIn('slug', [
                'cms.roles.manage',
                'cms.templates.manage',
                'cms.templates.use',
                'cms.ocr.upload',
                'cms.ocr.process',
                'cms.ocr.review',
                'cms.contracts.generate',
                'cms.risk.assess',
                'cms.risk.view',
                'cms.risk.approve',
                // CRUD
                'cms.contracts.view', 'cms.contracts.create', 'cms.contracts.edit', 'cms.contracts.delete', 'cms.contracts.approve',
                'cms.users.view',
                'cms.partners.view',  'cms.partners.create',  'cms.partners.edit',  'cms.partners.delete',
                'view-dashboard',
            ])->get();
            $ceoRole->permissions()->syncWithoutDetaching($ceoPerms->pluck('id'));
        }

        // 3. CMS Manager ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â broad access, cannot delete users or manage roles
        $cmsManager = \App\Models\Role::where('name', 'Manager')->first();
        if ($cmsManager) {
            $managerPerms = \App\Models\Permission::whereIn('slug', [
                'cms.templates.use',
                'cms.ocr.upload',
                'cms.ocr.process',
                'cms.ocr.review',
                'cms.contracts.generate',
                'cms.risk.assess',
                'cms.risk.view',
                'cms.risk.approve',
                // CRUD
                'cms.contracts.view', 'cms.contracts.create', 'cms.contracts.edit', 'cms.contracts.approve',
                'cms.users.view',
                'cms.partners.view',  'cms.partners.create',  'cms.partners.edit',
                'serms.reimbursements.manage', 'serms.liquidations.manage', 'serms.cash_advances.manage',
                'prs.dashboard.view', 'prs.activity-log.view', 'prs.submissions.create', 'prs.submission.validate',
                'prs.itineraries.manage', 'prs.leaderboard.view', 'prs.reports.view', 'prs.files.view',
            ])->get();
            $cmsManager->permissions()->syncWithoutDetaching($managerPerms->pluck('id'));
        }

        // 3b. Finance Manager — same default access as CMS Manager, but scoped to Finance department
        $financeManager = \App\Models\Role::where('name', 'Finance Manager')->first();
        if ($financeManager) {
            $managerPerms = \App\Models\Permission::whereIn('slug', [
                'cms.templates.use',
                'cms.ocr.upload',
                'cms.ocr.process',
                'cms.ocr.review',
                'cms.contracts.generate',
                'cms.risk.assess',
                'cms.risk.view',
                'cms.risk.approve',
                // CRUD
                'cms.contracts.view', 'cms.contracts.create', 'cms.contracts.edit', 'cms.contracts.approve',
                'cms.users.view',
                'cms.partners.view',  'cms.partners.create',  'cms.partners.edit',
                'serms.reimbursements.manage', 'serms.liquidations.manage', 'serms.cash_advances.manage',
                'prs.dashboard.view', 'prs.activity-log.view', 'prs.submissions.create', 'prs.submission.validate',
                'prs.itineraries.manage', 'prs.leaderboard.view', 'prs.reports.view', 'prs.settings.users.manage',
                'prs.settings.departments.manage', 'prs.settings.products.manage', 'prs.settings.institutions.manage', 'prs.files.view',
            ])->get();
            $financeManager->permissions()->syncWithoutDetaching($managerPerms->pluck('id'));
        }


        // PRS role assignments
        $prsAllPerms = \App\Models\Permission::where('system', 'prs')->get();
        $prsBasicPerms = \App\Models\Permission::whereIn('slug', [
            'prs.dashboard.view',
            'prs.activity-log.view',
            'prs.submissions.create',
            'prs.leaderboard.view',
            'prs.files.view',
        ])->get();

        $prsValidationPerms = \App\Models\Permission::whereIn('slug', [
            'prs.dashboard.view',
            'prs.activity-log.view',
            'prs.submission.validate',
            'prs.itineraries.manage',
            'prs.leaderboard.view',
            'prs.files.view',
        ])->get();

        foreach (\App\Models\Role::whereIn('name', ['Super Admin', 'IT Admin', 'Admin'])->get() as $role) {
            $role->permissions()->syncWithoutDetaching($prsAllPerms->pluck('id'));
        }

        foreach (\App\Models\Role::whereIn('name', ['Manager', 'Finance Manager', 'Supervisor'])->get() as $role) {
            $role->permissions()->syncWithoutDetaching($prsValidationPerms->pluck('id'));
        }

        foreach (\App\Models\Role::whereIn('name', ['Sales', 'Employee', 'Finance', 'Finance Employee', 'Accountant', 'Engineer'])->get() as $role) {
            $role->permissions()->syncWithoutDetaching($prsBasicPerms->pluck('id'));
        }

        // 4. CMS Sales ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â limited, view-only on most; own-record access enforced by app logic
        $cmsSales = \App\Models\Role::where('name', 'Sales')->first();
        if ($cmsSales) {
            $salesPerms = \App\Models\Permission::whereIn('slug', [
                'cms.templates.use',
                'cms.ocr.upload',
                'cms.ocr.process',
                'cms.ocr.review',
                'cms.contracts.generate',
                'cms.risk.assess',
                'cms.risk.view',
                'cms.risk.approve',
                // CRUD
                'cms.contracts.view',
                'cms.contracts.approve',
                'cms.users.view',
                'cms.partners.view',
            ])->get();
            $cmsSales->permissions()->syncWithoutDetaching($salesPerms->pluck('id'));
        }
    }
}
