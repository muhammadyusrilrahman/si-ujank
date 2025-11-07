@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@php
    use Illuminate\Support\Str;

    $stats = $stats ?? [
        'users' => 0,
        'pegawai_pns_cpns' => 0,
        'pegawai_pppk' => 0,
    ];

    $skpdLabel = $skpdLabel ?? 'SKPD Anda';
    $isSuperAdmin = $isSuperAdmin ?? auth()->user()->isSuperAdmin();
    $isAdminUnit = $isAdminUnit ?? auth()->user()->isAdminUnit();
    $loginActivities = $loginActivities ?? collect();
    $digitalBooks = $digitalBooks ?? collect();
    $videoTutorials = $videoTutorials ?? collect();
    $feedbacks = $feedbacks ?? collect();

    $loginActivitiesData = $loginActivities->map(function ($activity) use ($isSuperAdmin) {
        return [
            'id' => $activity->id,
            'userName' => $isSuperAdmin ? (optional($activity->user)->name ?? 'Pengguna tidak diketahui') : 'Anda',
            'ipAddress' => $activity->ip_address ?? 'IP tidak tercatat',
            'userAgent' => $activity->user_agent ? Str::limit($activity->user_agent, 60) : null,
            'timestamp' => optional($activity->created_at)->diffForHumans(),
        ];
    })->values()->all();

    $digitalBooksData = $digitalBooks->map(function ($book) {
        return [
            'id' => $book->id,
            'title' => $book->title,
            'description' => $book->description ? Str::limit($book->description, 120) : null,
            'fileUrl' => $book->file_url,
        ];
    })->values()->all();

    $videoTutorialsData = $videoTutorials->map(function ($video) {
        return [
            'id' => $video->id,
            'title' => $video->title,
            'description' => $video->description ? Str::limit($video->description, 120) : null,
            'videoUrl' => $video->video_url,
        ];
    })->values()->all();

    $adminFeedbackItems = $feedbacks->map(function ($feedback) {
        return [
            'id' => $feedback->id,
            'status' => $feedback->reply ? 'Sudah dibalas' : 'Menunggu balasan',
            'statusVariant' => $feedback->reply ? 'success' : 'secondary',
            'messageHtml' => nl2br(e($feedback->message)),
            'createdDiff' => optional($feedback->created_at)->diffForHumans(),
            'reply' => $feedback->reply ? [
                'bodyHtml' => nl2br(e($feedback->reply)),
                'diff' => optional($feedback->replied_at)->diffForHumans(),
            ] : null,
        ];
    })->values()->all();

    $superAdminFeedbackItems = $feedbacks->map(function ($feedback) {
        $author = $feedback->author;
        $skpdName = optional(optional($author)->skpd)->name;

        return [
            'id' => $feedback->id,
            'authorName' => optional($author)->name ?? 'Pengguna tidak diketahui',
            'authorSkpd' => $skpdName,
            'createdDiff' => optional($feedback->created_at)->diffForHumans(),
            'isActive' => (bool) $feedback->is_active,
            'messageHtml' => nl2br(e($feedback->message)),
            'reply' => $feedback->reply ? [
                'body' => $feedback->reply,
                'bodyHtml' => nl2br(e($feedback->reply)),
                'diff' => optional($feedback->replied_at)->diffForHumans(),
                'replierName' => optional($feedback->replier)->name ?? 'Super Admin',
            ] : null,
            'routes' => [
                'reply' => route('feedbacks.reply', $feedback),
                'toggle' => route('feedbacks.toggle', $feedback),
            ],
        ];
    })->values()->all();

    $dashboardProps = [
        'statusMessage' => session('status'),
        'stats' => [
            'users' => number_format($stats['users'] ?? 0),
            'pegawai_pns_cpns' => number_format($stats['pegawai_pns_cpns'] ?? 0),
            'pegawai_pppk' => number_format($stats['pegawai_pppk'] ?? 0),
        ],
        'skpdLabel' => $skpdLabel,
        'links' => [
            'usersIndex' => route('users.index'),
            'pegawaisIndex' => route('pegawais.index'),
            'loginActivitiesIndex' => route('login-activities.index'),
            'digitalBooksIndex' => $isSuperAdmin ? route('digital-books.index') : null,
            'videoTutorialsIndex' => $isSuperAdmin ? route('video-tutorials.index') : null,
        ],
        'loginActivities' => $loginActivitiesData,
        'digitalBooks' => $digitalBooksData,
        'videoTutorials' => $videoTutorialsData,
        'feedback' => [
            'mode' => $isSuperAdmin ? 'super-admin' : ($isAdminUnit ? 'admin-unit' : 'hidden'),
            'csrfToken' => csrf_token(),
            'adminUnit' => [
                'storeUrl' => ($isAdminUnit && ! $isSuperAdmin) ? route('feedbacks.store') : null,
                'oldMessage' => old('message', ''),
                'error' => $errors->first('message'),
                'items' => $adminFeedbackItems,
                'summary' => $feedbacks->isNotEmpty()
                    ? 'Menampilkan ' . $feedbacks->count() . ' feedback terbaru.'
                    : null,
                'emptyMessage' => 'Belum ada feedback aktif. Feedback yang dinonaktifkan oleh super admin tidak ditampilkan.',
            ],
            'superAdmin' => [
                'items' => $superAdminFeedbackItems,
                'oldReply' => old('reply', ''),
                'oldFeedbackId' => old('feedback_id'),
                'error' => $errors->first('reply'),
                'summary' => 'Menampilkan 10 feedback terbaru dari seluruh admin unit.',
                'emptyMessage' => 'Belum ada feedback yang masuk.',
            ],
        ],
        'authUserName' => auth()->user()->name,
        'isSuperAdmin' => $isSuperAdmin,
        'isAdminUnit' => $isAdminUnit,
    ];
@endphp

@section('content')
    <div id="dashboard-root"
         data-props='@json($dashboardProps, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP)'>
    </div>

    <noscript>
        <div class="alert alert-warning mt-3">
            Dashboard memerlukan JavaScript agar dapat tampil. Silakan aktifkan JavaScript pada peramban Anda.
        </div>
    </noscript>
@endsection

