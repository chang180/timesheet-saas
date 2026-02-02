<x-mail::message>
# 週報已提交

{{ $manager->name }} 您好，

{{ $submittedBy->name }} 已提交第 {{ $weeklyReport->work_week }} 週的週報。

**提交時間：** {{ $weeklyReport->submitted_at?->format('Y-m-d H:i') }}

<x-mail::button :url="$url">
查看週報
</x-mail::button>

謝謝，<br>
{{ $company->name }}
</x-mail::message>
