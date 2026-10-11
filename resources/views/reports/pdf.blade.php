<!doctype html>
<html>
<head>
<meta charset="utf-8">
<style>
    @page { margin: 70px 45px 60px 45px; }
    body { font-family: Helvetica, Arial, sans-serif; font-size: 10.5pt; color: #222; line-height: 1.4; }
    .header { position: fixed; top: -45px; left: 0; right: 0; text-align: center; font-size: 8pt; color: #666; }
    .footer { position: fixed; bottom: -40px; left: 0; right: 0; text-align: center; font-size: 8pt; color: #666; }
    .pagenum:before { content: counter(page); }
    .title { text-align: center; margin-bottom: 14px; }
    .title img { width: 54px; height: 53px; }
    .title h1 { margin: 4px 0 0; font-size: 20pt; color: #{{ $brand }}; }
    .title h2 { margin: 2px 0 0; font-size: 14pt; color: #555; }
    .title p { margin: 2px 0 0; font-size: 11pt; color: #555; }
    h3 { font-size: 13pt; color: #{{ $brand }}; margin: 20px 0 6px; page-break-after: avoid; }
    p { margin: 0 0 7px; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 4px; }
    th, td { border: 1px solid #bbb; padding: 5px 7px; text-align: left; vertical-align: top; font-size: 10pt; }
    th { background: #{{ $brand }}; color: #fff; }
    thead { display: table-header-group; }
    tr { page-break-inside: avoid; }
    td.label { width: 30%; background: #{{ $tint }}; color: #{{ $brand }}; font-weight: bold; }
    tr.total td { background: #{{ $tint }}; font-weight: bold; }
    ul { margin: 0 0 6px; padding-left: 20px; }
    li { margin-bottom: 3px; }
    .ok { color: #{{ $brand }}; font-weight: bold; }
    .bad { color: #B42318; font-weight: bold; }
</style>
</head>
<body>
<div class="header">{{ $dept }} · SERVICE REPORT · {{ mb_strtoupper($d['service_short']) }} · {{ mb_strtoupper($date) }}</div>
<div class="footer">{{ config('report.organisation') }} · {{ config('report.footer_label') }} · Page <span class="pagenum"></span></div>

<div class="title">
    @if ($logo)<img src="{{ $logo }}" alt=""><br>@endif
    <h1>{{ $dept }}</h1>
    <h2>SERVICE REPORT</h2>
    <p>{{ mb_strtoupper($d['service_type']) }}</p>
</div>

<table>
    @foreach ([
        'Date' => $d['service_date_label'],
        'Service' => $d['service_type'],
        'Service start time' => $time($d['start_time']),
        'Service end time' => $time($d['end_time']),
        'Total duration' => $d['duration'],
        'Broadcast status' => $d['broadcast_status'],
        'Report prepared by' => $d['prepared_by'],
    ] as $label => $value)
        <tr><td class="label">{{ $label }}</td><td>{{ $value }}</td></tr>
    @endforeach
</table>

<h3>1. Service Report Summary</h3>
@foreach (preg_split('/\R+/', trim($d['summary'])) as $para)
    @if (trim($para) !== '')<p>{{ trim($para) }}</p>@endif
@endforeach

<h3>2. Service Tasks &amp; Assignments</h3>
<table>
    <thead><tr><th style="width:7%">#</th><th style="width:36%">Service task</th><th style="width:20%">Handled by</th><th>Output / remarks</th></tr></thead>
    <tbody>
    @foreach ($d['tasks'] as $t)
        <tr><td>{{ $t['number'] }}</td><td>{{ $t['title'] }}</td><td>{{ $t['handled_by'] }}</td><td>{{ $t['remarks'] }}</td></tr>
    @endforeach
    </tbody>
</table>

<h3>3. Online Viewership</h3>
<table>
    <thead><tr><th>Platform</th><th style="width:30%">Viewers</th></tr></thead>
    <tbody>
    @foreach ($d['viewership'] as $v)
        <tr><td>{{ $v['platform'] }}</td><td>{{ $v['viewers'] === null ? '' : number_format($v['viewers']) }}</td></tr>
    @endforeach
        <tr class="total"><td>Total online viewership</td><td>{{ number_format($d['total_viewers']) }}</td></tr>
    </tbody>
</table>

<h3>4. Workers’ Attendance</h3>
<p>Present: {{ count($d['attendees']) }} workers. Duties are listed under Service Tasks &amp; Assignments.</p>
<ul>
    @foreach ($d['attendees'] as $a)
        <li>{{ $a['name'] }}@if ($a['role']) ({{ $a['role'] }})@endif</li>
    @endforeach
</ul>

<h3>5. Operational Observations</h3>
<ul>
    @foreach ($d['observations'] as $o)
        <li>@if (! empty($o['label']))<strong>{{ rtrim($o['label'], '. ') }}.</strong> @endif{{ $o['text'] }}</li>
    @endforeach
</ul>

<h3>6. Workspace Status</h3>
<p>Workspace left clean:
    @if ($d['workspace_clean'] === true)<span class="ok">Yes – the workspace is clean</span>
    @elseif ($d['workspace_clean'] === false)<span class="bad">No – the workspace is not clean</span>
    @else<span class="ok">Not recorded</span>@endif
</p>
@if (trim($d['workspace_notes']) !== '')<p>Remarks: {{ trim($d['workspace_notes']) }}</p>@endif

<h3>7. Incident Report</h3>
<p>Incidents encountered during the service, the response taken and the current status.</p>
<table>
    <thead><tr><th style="width:9%">Time</th><th style="width:26%">Incident</th><th style="width:12%">Severity</th><th>Action taken</th><th style="width:15%">Status</th></tr></thead>
    <tbody>
    @forelse ($d['incidents'] as $i)
        <tr><td>{{ $i['time'] }}</td><td>{{ $i['incident'] }}</td><td>{{ $i['severity'] }}</td><td>{{ $i['action_taken'] }}</td><td>{{ $i['status'] }}</td></tr>
    @empty
        <tr><td></td><td>No incidents recorded</td><td></td><td></td><td></td></tr>
    @endforelse
    </tbody>
</table>

<h3>8. Conclusion</h3>
@foreach (preg_split('/\R+/', trim($d['conclusion'])) as $para)
    @if (trim($para) !== '')<p>{{ trim($para) }}</p>@endif
@endforeach
</body>
</html>
