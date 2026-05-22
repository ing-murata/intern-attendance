@php
    $dateLine = $today->format('Y年n月j日') . '(' . ['日', '月', '火', '水', '木', '金', '土'][$today->dayOfWeek] . ')';
    $grouped = collect($members)->groupBy('role');
    $roles = ['インターン', '社員'];
@endphp
*{{ $dateLine }}*

@foreach($roles as $role)
@if($grouped->has($role))
{{ $role }}
```
@foreach($grouped[$role] as $member)
{{ $member['name'] }} @if(!empty($member['attendance']['work_time'])){{ $member['attendance']['work_time'] }} @endif{{ $member['attendance']['status'] }}
@endforeach
```
@endif
@endforeach
