<?php

namespace Database\Seeders\Tenant;

use App\Models\User;
use App\Models\WeeklyReport;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SampleCompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $now = now();
            $companySlug = 'acme-holdings';

            $existingCompany = DB::table('companies')
                ->where('slug', $companySlug)
                ->first();

            $companyData = [
                'name' => 'Acme Holdings',
                'status' => 'active',
                'user_limit' => 100,
                'timezone' => 'Asia/Taipei',
                'branding' => json_encode([
                    'primaryColor' => '#14532d',
                    'secondaryColor' => '#22c55e',
                    'logoUrl' => null,
                ], JSON_THROW_ON_ERROR),
                'updated_at' => $now,
            ];

            if ($existingCompany) {
                DB::table('companies')->where('id', $existingCompany->id)->update($companyData);
                $companyId = $existingCompany->id;
            } else {
                $companyId = DB::table('companies')->insertGetId(array_merge($companyData, [
                    'slug' => $companySlug,
                    'current_user_count' => 0,
                    'created_at' => $now,
                ]));
            }

            $existingSettings = DB::table('company_settings')
                ->where('company_id', $companyId)
                ->first();

            $settingsData = [
                'welcome_page' => json_encode([
                    'headline' => '歡迎加入 Acme Holdings 週報通',
                    'cta' => [
                        'primary' => ['label' => '登入', 'href' => '/login'],
                        'secondary' => ['label' => '申請帳號', 'href' => '/register'],
                    ],
                    'highlights' => [
                        '工時快速彙整',
                        '拖曳調整優先順序',
                        '主管即時掌握填寫狀態',
                    ],
                ], JSON_THROW_ON_ERROR),
                'login_ip_whitelist' => json_encode(['0.0.0.0/0'], JSON_THROW_ON_ERROR),
                'notification_preferences' => json_encode([
                    'weekly_reminder' => ['enabled' => true, 'weekday' => 'friday', 'time' => '09:00'],
                    'deadline_alert' => ['enabled' => true, 'hoursBefore' => 4],
                ], JSON_THROW_ON_ERROR),
                'default_weekly_report_modules' => json_encode([
                    'currentWeek',
                    'nextWeekPlan',
                    'roadblock',
                    'summary',
                ], JSON_THROW_ON_ERROR),
                'updated_at' => $now,
            ];

            if ($existingSettings) {
                DB::table('company_settings')
                    ->where('company_id', $companyId)
                    ->update($settingsData);
            } else {
                DB::table('company_settings')->insert(array_merge($settingsData, [
                    'company_id' => $companyId,
                    'created_at' => $now,
                ]));
            }

            $structure = Collection::make([
                [
                    'slug' => 'operations',
                    'name' => '營運事業群',
                    'description' => '負責跨單位協調與營運支援。',
                    'departments' => [
                        [
                            'slug' => 'project-management',
                            'name' => '專案管理部',
                            'description' => '專案進度追蹤與跨部門協調。',
                            'teams' => [
                                [
                                    'slug' => 'pm-office',
                                    'name' => '專案經理組',
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'slug' => 'product',
                    'name' => '產品研發群',
                    'description' => '打造 Timesheet SaaS 核心功能與體驗。',
                    'departments' => [
                        [
                            'slug' => 'engineering',
                            'name' => '工程部',
                            'description' => '後端、前端與 QA 維運產品功能。',
                            'teams' => [
                                [
                                    'slug' => 'backend',
                                    'name' => '後端小組',
                                ],
                                [
                                    'slug' => 'frontend',
                                    'name' => '前端小組',
                                ],
                            ],
                        ],
                        [
                            'slug' => 'design',
                            'name' => '設計部',
                            'description' => '使用者研究與介面設計。',
                            'teams' => [
                                [
                                    'slug' => 'ux',
                                    'name' => 'UX 小組',
                                ],
                            ],
                        ],
                    ],
                ],
            ]);

            $divisionIds = [];
            $departmentIds = [];
            $teamIds = [];

            $structure->each(function (array $division, int $divisionIndex) use (
                $companyId,
                $now,
                &$divisionIds,
                &$departmentIds,
                &$teamIds
            ): void {
                $existing = DB::table('divisions')
                    ->where('company_id', $companyId)
                    ->where('slug', $division['slug'])
                    ->first();

                $divisionPayload = [
                    'company_id' => $companyId,
                    'name' => $division['name'],
                    'description' => $division['description'] ?? null,
                    'sort_order' => $divisionIndex,
                    'is_active' => true,
                    'updated_at' => $now,
                ];

                if ($existing) {
                    DB::table('divisions')->where('id', $existing->id)->update($divisionPayload);
                    $divisionId = $existing->id;
                } else {
                    $divisionId = DB::table('divisions')->insertGetId(array_merge($divisionPayload, [
                        'slug' => $division['slug'],
                        'created_at' => $now,
                    ]));
                }

                $divisionIds[$division['slug']] = $divisionId;

                Collection::make($division['departments'] ?? [])
                    ->each(function (array $department, int $departmentIndex) use (
                        $companyId,
                        $divisionId,
                        $now,
                        &$departmentIds,
                        &$teamIds
                    ): void {
                        $existingDepartment = DB::table('departments')
                            ->where('company_id', $companyId)
                            ->where('slug', $department['slug'])
                            ->first();

                        $departmentPayload = [
                            'company_id' => $companyId,
                            'division_id' => $divisionId,
                            'name' => $department['name'],
                            'description' => $department['description'] ?? null,
                            'sort_order' => $departmentIndex,
                            'is_active' => true,
                            'updated_at' => $now,
                        ];

                        if ($existingDepartment) {
                            DB::table('departments')
                                ->where('id', $existingDepartment->id)
                                ->update($departmentPayload);
                            $departmentId = $existingDepartment->id;
                        } else {
                            $departmentId = DB::table('departments')->insertGetId(array_merge($departmentPayload, [
                                'slug' => $department['slug'],
                                'created_at' => $now,
                            ]));
                        }

                        $departmentIds[$department['slug']] = $departmentId;

                        Collection::make($department['teams'] ?? [])->each(function (
                            array $team,
                            int $teamIndex
                        ) use (
                            $companyId,
                            $divisionId,
                            $departmentId,
                            $now,
                            &$teamIds
                        ): void {
                            $existingTeam = DB::table('teams')
                                ->where('company_id', $companyId)
                                ->where('slug', $team['slug'])
                                ->first();

                            $teamPayload = [
                                'company_id' => $companyId,
                                'division_id' => $divisionId,
                                'department_id' => $departmentId,
                                'name' => $team['name'],
                                'description' => $team['description'] ?? null,
                                'sort_order' => $teamIndex,
                                'is_active' => true,
                                'updated_at' => $now,
                            ];

                            if ($existingTeam) {
                                DB::table('teams')->where('id', $existingTeam->id)->update($teamPayload);
                                $teamId = $existingTeam->id;
                            } else {
                                $teamId = DB::table('teams')->insertGetId(array_merge($teamPayload, [
                                    'slug' => $team['slug'],
                                    'created_at' => $now,
                                ]));
                            }

                            $teamIds[$team['slug']] = $teamId;
                        });
                    });
            });

            $users = [
                [
                    'email' => 'admin@acme.test',
                    'name' => '陳美玲',
                    'role' => 'company_admin',
                    'division_slug' => 'operations',
                    'department_slug' => 'project-management',
                    'team_slug' => 'pm-office',
                    'position' => '營運長',
                ],
                [
                    'email' => 'manager@acme.test',
                    'name' => '吳柏翰',
                    'role' => 'division_lead',
                    'division_slug' => 'product',
                    'department_slug' => 'engineering',
                    'team_slug' => 'backend',
                    'position' => '研發經理',
                ],
                [
                    'email' => 'member@acme.test',
                    'name' => '林雅雯',
                    'role' => 'member',
                    'division_slug' => 'product',
                    'department_slug' => 'engineering',
                    'team_slug' => 'frontend',
                    'position' => '前端工程師',
                ],
            ];

            $userIds = [];

            foreach ($users as $user) {
                $model = User::query()->firstOrNew(['email' => $user['email']]);

                $model->forceFill([
                    'company_id' => $companyId,
                    'division_id' => $divisionIds[$user['division_slug']] ?? null,
                    'department_id' => $departmentIds[$user['department_slug']] ?? null,
                    'team_id' => $teamIds[$user['team_slug']] ?? null,
                    'name' => $user['name'],
                    'role' => $user['role'],
                    'password' => Hash::make('Password!234'),
                    'position' => $user['position'],
                    'timezone' => 'Asia/Taipei',
                    'locale' => 'zh_TW',
                    'registered_via' => 'seed',
                    'email_verified_at' => $now,
                    'onboarded_at' => $now,
                ])->save();

                $userIds[$user['email']] = $model->id;
            }

            DB::table('companies')
                ->where('id', $companyId)
                ->update(['current_user_count' => count($userIds), 'updated_at' => $now]);

            $reportingWeek = now()->subWeek();
            $workYear = (int) $reportingWeek->isoFormat('GGGG');
            $workWeek = (int) $reportingWeek->isoWeek();

            $reports = [
                [
                    'email' => 'manager@acme.test',
                    'status' => WeeklyReport::STATUS_SUBMITTED,
                    'summary' => '完成 API Gateway 新版規格討論，與 QA 協調回歸測試時程。',
                    'items' => [
                        [
                            'title' => '完成多租戶 API 設計串接',
                            'content' => '與後端確認租戶 slug 規則，更新 Wayfinder route 定義。',
                            'hours' => 12.5,
                            'issue' => 'JIRA-1234',
                            'tags' => ['backend', 'architecture'],
                        ],
                        [
                            'title' => '主持產品開發例會',
                            'content' => '彙整開發進度，排除跨部門阻礙，更新甘特圖。',
                            'hours' => 6.0,
                            'issue' => 'MEETING',
                            'tags' => ['planning'],
                        ],
                    ],
                ],
                [
                    'email' => 'member@acme.test',
                    'status' => WeeklyReport::STATUS_SUBMITTED,
                    'summary' => '完成歡迎頁編輯器雛型，並修復 Tailwind dark mode 問題。',
                    'items' => [
                        [
                            'title' => '開發歡迎頁即時預覽功能',
                            'content' => '導入 React Hook Form + zod 驗證，改善錯誤訊息呈現。',
                            'hours' => 14.75,
                            'issue' => 'JIRA-1289',
                            'tags' => ['frontend', 'ui'],
                        ],
                        [
                            'title' => '修復多租戶佈景切換',
                            'content' => '調整 Tailwind v4 Token，支援租戶品牌色覆蓋。',
                            'hours' => 7.25,
                            'issue' => 'BUG-554',
                            'tags' => ['frontend', 'bugfix'],
                        ],
                    ],
                ],
            ];

            foreach ($reports as $report) {
                $userId = $userIds[$report['email']] ?? null;

                if (! $userId) {
                    continue;
                }

                $divisionId = DB::table('users')->where('id', $userId)->value('division_id');
                $departmentId = DB::table('users')->where('id', $userId)->value('department_id');
                $teamId = DB::table('users')->where('id', $userId)->value('team_id');

                $existingReport = DB::table('weekly_reports')
                    ->where('company_id', $companyId)
                    ->where('user_id', $userId)
                    ->where('work_year', $workYear)
                    ->where('work_week', $workWeek)
                    ->first();

                $reportPayload = [
                    'company_id' => $companyId,
                    'user_id' => $userId,
                    'division_id' => $divisionId,
                    'department_id' => $departmentId,
                    'team_id' => $teamId,
                    'status' => $report['status'],
                    'summary' => $report['summary'],
                    'metadata' => json_encode(['source' => 'seed'], JSON_THROW_ON_ERROR),
                    'submitted_at' => $now,
                    'submitted_by' => $userId,
                    'approved_at' => $now,
                    'approved_by' => $userIds['admin@acme.test'] ?? null,
                    'locked_at' => null,
                    'updated_at' => $now,
                ];

                if ($existingReport) {
                    DB::table('weekly_reports')
                        ->where('id', $existingReport->id)
                        ->update($reportPayload);
                    $reportId = $existingReport->id;
                } else {
                    $reportId = DB::table('weekly_reports')->insertGetId(array_merge($reportPayload, [
                        'work_year' => $workYear,
                        'work_week' => $workWeek,
                        'created_at' => $now,
                    ]));
                }

                DB::table('weekly_report_items')->where('weekly_report_id', $reportId)->delete();

                foreach ($report['items'] as $index => $item) {
                    DB::table('weekly_report_items')->insert([
                        'weekly_report_id' => $reportId,
                        'sort_order' => $index,
                        'title' => $item['title'],
                        'content' => $item['content'],
                        'hours_spent' => $item['hours'],
                        'issue_reference' => $item['issue'],
                        'is_billable' => false,
                        'tags' => json_encode($item['tags'], JSON_THROW_ON_ERROR),
                        'started_at' => $reportingWeek->copy()->startOfWeek()->addDays($index)->setTime(10, 0),
                        'ended_at' => $reportingWeek->copy()->startOfWeek()->addDays($index)->setTime(19, 0),
                        'metadata' => json_encode(['source' => 'seed'], JSON_THROW_ON_ERROR),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }

            DB::table('audit_logs')->updateOrInsert(
                [
                    'company_id' => $companyId,
                    'event' => 'company.seeded',
                    'auditable_type' => 'App\\Models\\Company',
                    'auditable_id' => $companyId,
                ],
                [
                    'user_id' => $userIds['admin@acme.test'] ?? null,
                    'description' => '初始化範例公司資料與週報記錄。',
                    'properties' => json_encode(['seed' => 'SampleCompanySeeder'], JSON_THROW_ON_ERROR),
                    'ip_address' => '127.0.0.1',
                    'user_agent' => 'seeder',
                    'occurred_at' => $now,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        });
    }
}
