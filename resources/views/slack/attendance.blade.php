{{ $dateLine }}

@foreach($order as $role)
    @if(!empty($groups[$role]))

{{ $role }}
```
@foreach($groups[$role] as $member)
{{ $member['name'] }} {{ $member['status_text'] }}
@endforeach
```
    @endif
@endforeach
