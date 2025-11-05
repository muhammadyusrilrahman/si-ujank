<?php

namespace App\Http\Controllers;

use App\Models\DigitalBook;
use App\Models\Feedback;
use App\Models\Pegawai;
use App\Models\Skpd;
use App\Models\User;
use App\Models\VideoTutorial;
use App\Models\LoginActivity;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    public function showLogin(Request $request)
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        $this->generateCaptcha($request);

        return view('auth.login', [
            'skpdOptions' => Skpd::cachedOptions(),
        ]);
    }

    public function login(Request $request)
    {
        $skpdIds = Skpd::pluck('id')->all();

        $validated = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
            'skpd_id' => ['nullable', Rule::in($skpdIds)],
            'captcha' => ['required', 'string'],
        ], [
            'skpd_id.in' => 'Pilihan SKPD tidak valid.',
        ]);

        if (! $this->captchaMatches($request, $validated['captcha'])) {
            $this->generateCaptcha($request);

            return back()
                ->withErrors(['captcha' => 'Captcha tidak sesuai.'])
                ->withInput($request->except(['password', 'captcha']));
        }

        $user = User::where('username', $validated['username'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            $this->generateCaptcha($request);

            return back()
                ->withErrors(['username' => 'Kredensial tidak sesuai.'])
                ->withInput($request->except(['password', 'captcha']));
        }

        if (! $user->isSuperAdmin()) {
            if (empty($validated['skpd_id'])) {
                $this->generateCaptcha($request);

                return back()
                    ->withErrors(['skpd_id' => 'SKPD wajib dipilih.'])
                    ->withInput($request->except(['password', 'captcha']));
            }

            if ((string) $user->skpd_id !== (string) $validated['skpd_id']) {
                $this->generateCaptcha($request);

                return back()
                    ->withErrors(['skpd_id' => 'Anda tidak memiliki akses ke SKPD tersebut.'])
                    ->withInput($request->except(['password', 'captcha']));
            }
        } elseif (! empty($validated['skpd_id']) && ! Skpd::whereKey($validated['skpd_id'])->exists()) {
            $this->generateCaptcha($request);

            return back()
                ->withErrors(['skpd_id' => 'Pilihan SKPD tidak valid.'])
                ->withInput($request->except(['password', 'captcha']));
        }

        Auth::login($user);
        $request->session()->regenerate();

        if (Schema::hasTable('login_activities')) {
            LoginActivity::create([
                'user_id' => $user->id,
                'ip_address' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 500),
                'created_at' => now(),
            ]);
        }

        return redirect()->route('dashboard');
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    public function dashboard(Request $request)
    {
        $user = $request->user();
        $skpd = $user->skpd;
        $skpdId = $skpd?->id;

        if ($user->isSuperAdmin() && ! $skpdId) {
            $scopeLabel = 'Semua SKPD';

            $userCount = User::count();
            $pegawaiPnsCpnsCount = Pegawai::whereIn('status_asn', ['1', '3'])->count();
            $pegawaiPppkCount = Pegawai::where('status_asn', '2')->count();
        } else {
            $scopeLabel = $skpd?->name ?? 'SKPD Anda';

            $userCount = User::where('skpd_id', $skpdId)->count();
            $pegawaiPnsCpnsCount = Pegawai::where('skpd_id', $skpdId)
                ->whereIn('status_asn', ['1', '3'])
                ->count();
            $pegawaiPppkCount = Pegawai::where('skpd_id', $skpdId)
                ->where('status_asn', '2')
                ->count();
        }

        $digitalBooks = collect();
        if (Schema::hasTable('digital_books')) {
            $digitalBooks = DigitalBook::query()
                ->where('is_active', true)
                ->latest()
                ->limit(5)
                ->get(['id', 'title', 'file_url', 'description']);
        }

        $videoTutorials = collect();
        if (Schema::hasTable('video_tutorials')) {
            $videoTutorials = VideoTutorial::query()
                ->where('is_active', true)
                ->latest()
                ->limit(5)
                ->get(['id', 'title', 'video_url', 'description']);
        }

        $loginActivities = collect();
        if (Schema::hasTable('login_activities')) {
            $loginHistoryQuery = LoginActivity::query()->with('user:id,name');

            if (! $user->isSuperAdmin()) {
                $loginHistoryQuery->where('user_id', $user->id);
            }

            $loginActivities = $loginHistoryQuery
                ->latest('created_at')
                ->limit(3)
                ->get();
        }

        $feedbacks = collect();

        if (Schema::hasTable('feedbacks')) {
            if ($user->isSuperAdmin()) {
                $feedbacks = Feedback::query()
                    ->with(['author:id,name,skpd_id', 'author.skpd:id,name', 'replier:id,name'])
                    ->latest('created_at')
                    ->limit(10)
                    ->get();
            } elseif ($user->isAdminUnit()) {
                $feedbacks = Feedback::query()
                    ->with(['replier:id,name'])
                    ->where('user_id', $user->id)
                    ->where('is_active', true)
                    ->latest('created_at')
                    ->limit(10)
                    ->get();
            }
        }

        return view('welcome', [
            'stats' => [
                'users' => $userCount,
                'pegawai_pns_cpns' => $pegawaiPnsCpnsCount,
                'pegawai_pppk' => $pegawaiPppkCount,
            ],
            'skpdLabel' => $scopeLabel,
            'digitalBooks' => $digitalBooks,
            'videoTutorials' => $videoTutorials,
            'loginActivities' => $loginActivities,
            'feedbacks' => $feedbacks,
            'isAdminUnit' => $user->isAdminUnit(),
            'isSuperAdmin' => $user->isSuperAdmin(),
        ]);
    }

    public function loginActivities(Request $request)
    {
        $user = $request->user();

        if (! Schema::hasTable('login_activities')) {
            $loginActivities = new LengthAwarePaginator(
                collect(),
                0,
                15,
                $request->integer('page', 1),
                [
                    'path' => $request->url(),
                    'query' => $request->query(),
                ]
            );
        } else {
            $loginHistoryQuery = LoginActivity::query()->with('user:id,name');

            if (! $user->isSuperAdmin()) {
                $loginHistoryQuery->where('user_id', $user->id);
            }

            $loginActivities = $loginHistoryQuery
                ->latest('created_at')
                ->paginate(15)
                ->withQueryString();
        }

        return view('login-activities.index', [
            'loginActivities' => $loginActivities,
            'isSuperAdmin' => $user->isSuperAdmin(),
        ]);
    }

    public function captcha(Request $request)
    {
        $code = $this->generateCaptcha($request);

        $width = 180;
        $height = 60;

        $lines = collect(range(1, 6))->map(function () use ($width, $height) {
            $x1 = rand(0, $width);
            $y1 = rand(0, $height);
            $x2 = rand(0, $width);
            $y2 = rand(0, $height);
            $color = sprintf('rgba(%d,%d,%d,0.4)', rand(0, 255), rand(0, 255), rand(0, 255));

            return "<line x1='$x1' y1='$y1' x2='$x2' y2='$y2' stroke='$color' stroke-width='2' />";
        })->implode('');

        $characters = collect(str_split($code))->map(function ($char, $index) use ($width, $height) {
            $x = 20 + ($index * 30) + rand(-5, 5);
            $y = rand(30, 50);
            $rotate = rand(-20, 20);
            $color = sprintf('rgb(%d,%d,%d)', rand(0, 150), rand(0, 150), rand(0, 150));

            return "<text x='$x' y='$y' fill='$color' font-size='30' font-family='sans-serif' transform='rotate($rotate $x $y)'>$char</text>";
        })->implode('');

        $noise = collect(range(1, 20))->map(function () use ($width, $height) {
            $cx = rand(0, $width);
            $cy = rand(0, $height);
            $r = rand(1, 3);
            $color = sprintf('rgba(%d,%d,%d,0.3)', rand(0, 255), rand(0, 255), rand(0, 255));

            return "<circle cx='$cx' cy='$cy' r='$r' fill='$color' />";
        })->implode('');

        $svg = <<<SVG
<?xml version="1.0" encoding="UTF-8"?>
<svg xmlns="http://www.w3.org/2000/svg" width="$width" height="$height" viewBox="0 0 $width $height">
    <rect width="100%" height="100%" fill="#f8f9fa" />
    $noise
    $lines
    $characters
</svg>
SVG;

        return response($svg, 200, ['Content-Type' => 'image/svg+xml']);
    }

    protected function generateCaptcha(Request $request): string
    {
        $code = strtoupper(Str::random(5));
        $request->session()->put('captcha_code', $code);

        return $code;
    }

    protected function captchaMatches(Request $request, string $input): bool
    {
        $expected = (string) $request->session()->get('captcha_code', '');

        return $expected !== '' && hash_equals($expected, strtoupper($input));
    }
}
