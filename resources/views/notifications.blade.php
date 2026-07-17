<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .soft-card { box-shadow: 0 8px 30px rgba(15,23,42,.07); }
    </style>
</head>
<body class="bg-[#F5F5F3] min-h-screen text-slate-900">

@include('partials.sidebar')

@php
    $typeMeta = [
        'job_description_submitted' => ['icon' => '📋', 'label' => 'Job Description'],
        'appraisal_submitted'       => ['icon' => '📝', 'label' => 'Appraisal'],
    ];
    $unreadCount = collect($notifications)->where('is_read', false)->count();
@endphp

<main id="mainContent" class="ml-[230px] min-h-screen">

{{-- ═══════ HEADER (sticky) ═══════ --}}
<div class="sticky top-0 z-30 px-4 pt-4 pb-2 bg-[#F5F5F3]">
    <div class="relative overflow-hidden rounded-[18px] bg-gradient-to-r from-[#1A0A0A] to-[#7A0019] text-white px-6 py-5 shadow-[0_10px_35px_rgba(122,0,25,0.45)] flex flex-col md:flex-row md:items-center md:justify-between gap-3">
        <div class="absolute top-0 left-0 right-0 h-[2px] bg-gradient-to-r from-[#D4AF37] via-[#D4AF37] to-[#D4AF37]/10"></div>
        <div class="relative">
            <h1 class="text-xl font-black tracking-tight leading-tight">
                Notifications
                @if($unreadCount > 0)
                    <span class="align-middle ml-1.5 text-[11px] font-black bg-[#D4AF37] text-[#1a1a1a] px-2 py-0.5 rounded-full">{{ $unreadCount }} new</span>
                @endif
            </h1>
            <p class="text-[11px] text-white/60 mt-1">When someone on your team submits their Job Description or a quarterly appraisal, it shows up here</p>
        </div>
        @if($unreadCount > 0)
        <form method="POST" action="{{ route('notifications.read-all') }}" class="relative">
            @csrf
            <button type="submit" class="text-xs font-black bg-white/10 hover:bg-white/20 text-white px-3.5 py-2 rounded-xl border border-white/20 transition">
                Mark all as read
            </button>
        </form>
        @endif
    </div>
</div>

<div class="px-4 pb-4">

    @if(empty($notifications))
        <div class="bg-white rounded-2xl soft-card border border-[#E5E7EB] p-12 text-center">
            <div class="text-4xl mb-3">🔔</div>
            <p class="text-slate-500 font-bold text-sm">No notifications yet</p>
            <p class="text-slate-400 text-xs mt-1 max-w-sm mx-auto">You'll see something here as soon as someone who reports to you submits their Job Description or a quarterly appraisal self-assessment.</p>
        </div>
    @else
        <div class="space-y-2">
            @foreach($notifications as $n)
                @php
                    $meta   = $typeMeta[$n['type']] ?? ['icon' => '🔔', 'label' => 'Update'];
                    $unread = !($n['is_read'] ?? false);
                @endphp
                <div class="notif-row bg-white rounded-2xl soft-card border border-[#E5E7EB] {{ $unread ? 'border-l-[4px] border-l-[#D4AF37]' : '' }} p-4 flex items-start gap-3"
                     data-id="{{ $n['id'] }}" data-link="{{ $n['link'] ?? '' }}">
                    <div class="w-10 h-10 rounded-xl {{ $unread ? 'bg-[#D4AF37]/10' : 'bg-slate-50' }} flex items-center justify-center text-lg shrink-0">
                        {{ $meta['icon'] }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-start justify-between gap-2">
                            <p class="text-[13px] {{ $unread ? 'font-black text-slate-900' : 'font-bold text-slate-600' }} leading-snug">
                                {{ $n['title'] }}
                            </p>
                            <span class="text-[10px] text-slate-400 shrink-0 whitespace-nowrap">{{ \Carbon\Carbon::parse($n['created_at'])->diffForHumans() }}</span>
                        </div>
                        @if(!empty($n['message']))
                            <p class="text-[11px] text-slate-500 mt-0.5">{{ $n['message'] }}</p>
                        @endif
                        <div class="flex items-center gap-2 mt-2">
                            <span class="text-[9px] font-black uppercase tracking-wide px-2 py-0.5 rounded-full bg-slate-100 text-slate-500">{{ $meta['label'] }}</span>
                            @if(!empty($n['quarter']))
                                <span class="text-[9px] font-black uppercase tracking-wide px-2 py-0.5 rounded-full bg-[#D4AF37]/10 text-[#B8860B]">{{ $n['quarter'] }} {{ $n['financial_year'] }}</span>
                            @endif
                            @if($unread)
                                <span class="text-[9px] font-black uppercase tracking-wide px-2 py-0.5 rounded-full bg-red-50 text-red-600">New</span>
                            @endif
                        </div>
                    </div>
                    @if(!empty($n['link']))
                        <span class="shrink-0 self-center text-[11px] font-black text-[#7A0019] hover:text-[#C8102E] transition">Open →</span>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

</div>
</main>

<script>
document.querySelectorAll('.notif-row').forEach(function (row) {
    row.style.cursor = 'pointer';
    row.addEventListener('click', function () {
        var id = row.dataset.id;
        var link = row.dataset.link;

        fetch('/notifications/' + id + '/read', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            },
        }).finally(function () {
            if (link) window.location.href = link;
        });
    });
});
</script>

</body>
</html>
