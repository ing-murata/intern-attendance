来週の予定一覧

@foreach($groups as $role => $members)
{{ $role }}
```
@foreach($members as $member)
{{ $member['name'] }}
{{ $member['schedule'] }}

@endforeach
```
@endforeach
