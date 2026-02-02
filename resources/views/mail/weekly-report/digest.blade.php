<x-mail::message>
# 週報匯總

{{ $manager->name }} 您好，

以下是 {{ $workYear }} 年第 {{ $workWeek }} 週的週報匯總報告：

<x-mail::panel>
- **總工時：** {{ $summary['total_hours'] ?? 0 }} 小時
- **計費工時：** {{ $summary['billable_hours'] ?? 0 }} 小時
- **週報數量：** {{ $summary['report_count'] ?? 0 }} 份
- **已提交：** {{ $summary['submitted_count'] ?? 0 }} 份
- **草稿：** {{ $summary['draft_count'] ?? 0 }} 份
- **成員數：** {{ $summary['member_count'] ?? 0 }} 人
</x-mail::panel>

<x-mail::button :url="$url">
查看詳細報告
</x-mail::button>

謝謝，<br>
{{ $company->name }}
</x-mail::message>
