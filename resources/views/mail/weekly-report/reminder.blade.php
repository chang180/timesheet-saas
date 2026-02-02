<x-mail::message>
# 週報填寫提醒

{{ $user->name }} 您好，

本週（{{ $workYear }} 年第 {{ $workWeek }} 週）週報尚未填寫或提交，請記得於週末前完成。

<x-mail::button :url="$url">
填寫週報
</x-mail::button>

如有任何問題，請聯繫您的主管或系統管理員。

謝謝，<br>
{{ $company->name }}
</x-mail::message>
