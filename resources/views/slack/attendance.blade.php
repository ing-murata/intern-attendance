@php
    $dateLine = $today->format('Y年n月j日') . '(' . ['日', '月', '火', '水', '木', '金', '土'][$today->dayOfWeek] . ')';
    $roles = ['インターン', '社員'];

    $parsedMembers = [];
    foreach ($members as $member) {
        $status = null;
        $workTime = null;

        foreach ($member['events'] as $event) {
            if ($event->eventType === 'outOfOffice') {
                $status = $event->getSummary() ?: '不在';
                break;
            }

            if ($event->eventType === 'workingLocation' && $member['role'] === 'インターン') {
                $type = $event->getWorkingLocationProperties()?->getType();
                $status = ($type === 'homeOffice') ? 'リモート' : '出社';

                $start = $event->getStart()?->getDateTime();
                $end = $event->getEnd()?->getDateTime();
                if ($start && $end) {
                    $startDt = \Illuminate\Support\Carbon::parse($start)->timezone('Asia/Tokyo');
                    $endDt = \Illuminate\Support\Carbon::parse($end)->timezone('Asia/Tokyo');
                    $workTime = $startDt->format('H:i') . '-' . $endDt->format('H:i');
                }
            }
        }

        if ($status) {
            $parsedMembers[] = [
                'name' => $member['name'],
                'role' => $member['role'],
                'status' => $status,
                'workTime' => $workTime,
            ];
        }
    }

    $grouped = collect($parsedMembers)->groupBy('role');
@endphp
@if(!empty($parsedMembers))*{{ $dateLine }}*

@foreach($roles as $role)
@if($grouped->has($role))
{{ $role }}
```
@foreach($grouped[$role] as $m)
{{ $m['name'] }} @if($m['workTime']){{ $m['workTime'] }} @endif{{ $m['status'] }}
@endforeach
```
@endif
@endforeach
@endif
