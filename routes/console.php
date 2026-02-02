<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// 週報提醒 - 每週五下午 4 點發送
Schedule::command('weekly-report:send-reminders')
    ->weeklyOn(5, '16:00')
    ->description('Send weekly report reminders to users');

// 週報匯總摘要 - 每週一上午 9 點發送
Schedule::command('weekly-report:send-digest')
    ->weeklyOn(1, '09:00')
    ->description('Send weekly summary digest to managers');

// 假期資料同步 - 每月 1 日凌晨 3 點同步
Schedule::command('holidays:sync')
    ->monthlyOn(1, '03:00')
    ->description('Sync holiday data from NTPC Open Data');
