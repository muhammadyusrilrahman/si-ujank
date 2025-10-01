<?php

namespace App\Http\Controllers;

use App\Models\Skpd;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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
            'skpdOptions' => Skpd::orderBy('name')->get(),
        ]);
    }

    public function login(Request $request)
    {
        $skpdIds = Skpd::pluck('id')->all();

        $validated = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
            'skpd_id' => ['required', Rule::in($skpdIds)],
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

        if (! $user->isSuperAdmin() && (string) $user->skpd_id !== (string) $validated['skpd_id']) {
            $this->generateCaptcha($request);

            return back()
                ->withErrors(['skpd_id' => 'Anda tidak memiliki akses ke SKPD tersebut.'])
                ->withInput($request->except(['password', 'captcha']));
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    public function dashboard()
    {
        return view('welcome');
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
