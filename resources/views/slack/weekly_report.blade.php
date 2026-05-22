来週（{{ $start->format('n/j') }}〜）の予定一覧
@php
    $groupedMembers = collect($members)->groupBy('role');
    $roles = ['インターン', '社員'];
@endphp
@foreach($roles as $role)
@if($groupedMembers->has($role))
*{{ $role }}*
@foreach($groupedMembers[$role] as $member)
@php
    $schedules = [];
    foreach ($member['events'] as $event) {
        $start = $event->getStart()?->getDateTime();
        $end = $event->getEnd()?->getDateTime();
        if (!$start || !$end) continue;
        $startDt = \Illuminate\Support\Carbon::parse($start)->timezone('Asia/Tokyo');
        $endDt = \Illuminate\Support\Carbon::parse($end)->timezone('Asia/Tokyo');
        $status = null;
        if ($event->eventType === 'outOfOffice') {
            $status = $event->getSummary() ?: '不在';
        } elseif ($event->eventType === 'workingLocation' && $role === 'インターン') {
            $status = ($event->getWorkingLocationProperties()?->getType() === 'homeOffice') ? 'リモート' : '出社';
        }
        if ($status) {
            $schedules[] = [
                'start' => $startDt,
                'text' => $startDt->translatedFormat('n/j(D) H:i-') . $endDt->format('H:i') . ' ' . $status
            ];
        }
    }
    usort($schedules, fn($a, $b) => $a['start'] <=> $b['start']);
@endphp
@if(!empty($schedules))
・{{ $member['name'] }}
```
{{ implode("\n", array_column($schedules, 'text')) }}
```
@endif
@endforeach
@endif
@endforeach
