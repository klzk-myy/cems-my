@component('mail::message')
# {{ $levelLabel }} System Health Alert

{{ $message }}

@if($source)
**Source:** {{ $source }}
@endif

**Level:** {{ $levelLabel }}
**Time:** {{ $createdAt->format('Y-m-d H:i:s') }}

@if(! empty($metadata))
**Details:**

@foreach($metadata as $key => $value)
@if(! in_array($key, ['email_sent', 'email_sent_at', 'email_recipients']))
- **{{ ucfirst(str_replace('_', ' ', (string) $key)) }}:** {{ is_scalar($value) ? $value : json_encode($value) }}
@endif
@endforeach
@endif

@component('mail::button', ['url' => url('/system/alerts')])
View Alerts
@endcomponent

Thank you,<br>
{{ config('app.name') }}
@endcomponent
