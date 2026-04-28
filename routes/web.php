<?php

use App\Http\Controllers\sk_pres\AnnouncementController;
use App\Http\Controllers\sk_pres\CalendarController;
use App\Http\Controllers\sk_pres\ConsolidationController;
use App\Http\Controllers\sk_pres\DashboardController;
use App\Http\Controllers\sk_pres\HomeController;
use App\Http\Controllers\sk_pres\MeetingsController;
use App\Http\Controllers\sk_pres\ModuleController;
use App\Http\Controllers\sk_pres\PlaceholderController;
use App\Http\Controllers\sk_pres\RankingController as SkPresidentRankingController;
use App\Http\Controllers\sk_pres\LeadershipController as SkPresidentLeadershipController; 

use App\Http\Controllers\sk_chairman\RankingController as SkChairmanRankingController;
use App\Http\Controllers\sk_secretary\RankingController as SkSecretaryRankingController;

use App\Http\Controllers\Youth\AnnouncementController as YouthAnnouncementController;
use App\Http\Controllers\Youth\CalendarController as YouthCalendarController;
use App\Http\Controllers\Youth\HomeController as YouthHomeController;
use App\Http\Controllers\Youth\LeadershipController as YouthLeadershipController;
use App\Http\Controllers\Youth\ProfileController as YouthProfileController;
use App\Http\Controllers\Youth\RankingController as YouthRankingController;

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| PUBLIC ROUTES
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register'])->name('register.submit');

Route::get('/verify', [AuthController::class, 'showVerify'])->name('verify.notice');
Route::post('/verify', [AuthController::class, 'verifyCode'])->name('verify.submit');
Route::post('/verify/resend', [AuthController::class, 'resendVerificationCode'])->name('verify.resend');


/*
|--------------------------------------------------------------------------
| SK PRESIDENT ROUTES
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->prefix('sk_pres')->name('sk_pres.')->group(function () {

    // MAIN PAGES
    Route::get('/home', [HomeController::class, 'index'])->name('home');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/consolidation', [ConsolidationController::class, 'index'])->name('consolidation');

    // MODULE MANAGEMENT
    Route::get('/module', [ModuleController::class, 'index'])->name('module');
    Route::post('/module', [ModuleController::class, 'store'])->name('module.store');
    Route::post('/module/{slotId}/delete', [ModuleController::class, 'destroy'])->name('module.destroy');

    // ANNOUNCEMENTS
    Route::get('/announcements', [AnnouncementController::class, 'index'])->name('announcements');
    Route::post('/announcements', [AnnouncementController::class, 'store'])->name('announcements.store');

    // CALENDAR
    Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar');
    Route::post('/calendar', [CalendarController::class, 'store'])->name('calendar.store');

    // 💬 CHAT (placeholder)
    Route::get('/chat', fn (PlaceholderController $controller) =>
        $controller->show('Chat', 'The chat page has not been built yet.')
    )->name('chat');

    /*
    |--------------------------------------------------------------------------
    | MEETINGS
    |--------------------------------------------------------------------------
    */
    Route::get('/meetings', [MeetingsController::class, 'index'])->name('meetings');
    Route::post('/meetings', [MeetingsController::class, 'store'])->name('meetings.store');
    Route::get('/meetings/{meeting}/call', [MeetingsController::class, 'call'])->name('meetings.call');
    Route::post('/meetings/{meeting}/agora-token', [MeetingsController::class, 'token'])->name('meetings.agora.token');
    Route::get('/video', fn () => redirect()->route('sk_pres.meetings'))->name('video');

    // OTHER MENUS
    Route::get('/rankings', [SkPresidentRankingController::class, 'index'])->name('rankings');

    Route::get('/analytics', fn (PlaceholderController $controller) =>
        $controller->show('Analytics', 'The analytics page has not been built yet.')
    )->name('analytics');

    // ✅ FIXED LEADERSHIP ROUTE
    Route::get('/leadership', [SkPresidentLeadershipController::class, 'index'])->name('leadership');

    Route::get('/archive', fn (PlaceholderController $controller) =>
        $controller->show('Archive', 'The archive page has not been built yet.')
    )->name('archive');

    Route::get('/user-management', fn (PlaceholderController $controller) =>
        $controller->show('User Management', 'The user management page has not been built yet.')
    )->name('user-management');
});


/*
|--------------------------------------------------------------------------
| OTHER ROLES
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->get('/youth/home', [YouthHomeController::class, 'index'])->name('youth.home');
Route::middleware('auth')->get('/youth/announcements', [YouthAnnouncementController::class, 'index'])->name('youth.announcements');
Route::middleware('auth')->get('/youth/calendar', [YouthCalendarController::class, 'index'])->name('youth.calendar');
Route::middleware('auth')->get('/youth/rankings', [YouthRankingController::class, 'index'])->name('youth.rankings');
Route::middleware('auth')->get('/youth/leadership', [YouthLeadershipController::class, 'index'])->name('youth.leadership');
Route::middleware('auth')->get('/youth/profile', [YouthProfileController::class, 'show'])->name('youth.profile');
Route::middleware('auth')->post('/youth/profile', [YouthProfileController::class, 'update'])->name('youth.profile.update');
Route::middleware('auth')->post('/youth/profile/password', [YouthProfileController::class, 'updatePassword'])->name('youth.profile.password');

Route::view('/sk_chairman/home', 'sk_chairman.home')->name('sk_chairman.home');
Route::view('/sk_secretary/home', 'sk_secretary.home')->name('sk_secretary.home');

Route::middleware('auth')->get('/sk_chairman/rankings', [SkChairmanRankingController::class, 'index'])->name('sk_chairman.rankings');
Route::middleware('auth')->get('/sk_secretary/rankings', [SkSecretaryRankingController::class, 'index'])->name('sk_secretary.rankings');