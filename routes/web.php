<?php

use App\Http\Controllers\sk_pres\AnnouncementController;
use App\Http\Controllers\sk_pres\CalendarController;
use App\Http\Controllers\sk_pres\ConsolidationController;
use App\Http\Controllers\sk_pres\DashboardController;
use App\Http\Controllers\sk_pres\HomeController;
use App\Http\Controllers\sk_pres\MeetingsController;
use App\Http\Controllers\sk_pres\ModuleController;
use App\Http\Controllers\sk_pres\PlaceholderController;
use App\Http\Controllers\sk_pres\ChatController as SkPresidentChatController;
use App\Http\Controllers\sk_pres\RankingController as SkPresidentRankingController;
use App\Http\Controllers\sk_pres\LeadershipController as SkPresidentLeadershipController; 
use App\Http\Controllers\sk_pres\ArchiveController as SkPresidentArchiveController;
use App\Http\Controllers\sk_pres\UserManagementController as SkPresidentUserManagementController;

use App\Http\Controllers\sk_chairman\RankingController as SkChairmanRankingController;
use App\Http\Controllers\sk_chairman\HomeController as SkChairmanHomeController;
use App\Http\Controllers\sk_chairman\ChatController as SkChairmanChatController;
use App\Http\Controllers\sk_chairman\AnnouncementController as SkChairmanAnnouncementController;
use App\Http\Controllers\sk_chairman\CalendarController as SkChairmanCalendarController;
use App\Http\Controllers\sk_chairman\MeetingsController as SkChairmanMeetingsController;
use App\Http\Controllers\sk_chairman\ReportController as SkChairmanReportController;
use App\Http\Controllers\sk_chairman\BudgetController as SkChairmanBudgetController;
use App\Http\Controllers\sk_chairman\LeadershipController as SkChairmanLeadershipController;
use App\Http\Controllers\sk_secretary\RankingController as SkSecretaryRankingController;
use App\Http\Controllers\sk_secretary\HomeController as SkSecretaryHomeController;
use App\Http\Controllers\sk_secretary\AnnouncementController as SkSecretaryAnnouncementController;
use App\Http\Controllers\sk_secretary\CalendarController as SkSecretaryCalendarController;
use App\Http\Controllers\sk_secretary\MeetingsController as SkSecretaryMeetingsController;
use App\Http\Controllers\sk_secretary\ReportController as SkSecretaryReportController;
use App\Http\Controllers\sk_secretary\BudgetController as SkSecretaryBudgetController;
use App\Http\Controllers\sk_secretary\LeadershipController as SkSecretaryLeadershipController;
use App\Http\Controllers\sk_secretary\ChatController as SkSecretaryChatController;

use App\Http\Controllers\Youth\AnnouncementController as YouthAnnouncementController;
use App\Http\Controllers\Youth\CalendarController as YouthCalendarController;
use App\Http\Controllers\Youth\HomeController as YouthHomeController;
use App\Http\Controllers\Youth\LeadershipController as YouthLeadershipController;
use App\Http\Controllers\Youth\ProfileController as YouthProfileController;
use App\Http\Controllers\Youth\RankingController as YouthRankingController;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileSettingsController;
use App\Http\Controllers\WallPostController;
use Illuminate\Http\Request;
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
Route::middleware('auth')->get('/notifications/feed', [NotificationController::class, 'feed'])->name('notifications.feed');
Route::middleware('auth')->post('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
Route::middleware('auth')->post('/wall/posts', [WallPostController::class, 'store'])->name('wall.posts.store');


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
    Route::get('/consolidation/download', [ConsolidationController::class, 'download'])->name('consolidation.download');

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
    Route::get('/chat', [SkPresidentChatController::class, 'index'])->name('chat');
    Route::get('/chat/users', [SkPresidentChatController::class, 'searchUsers'])->name('chat.users');

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

    //  LEADERSHIP ROUTE
    Route::get('/leadership', [SkPresidentLeadershipController::class, 'index'])->name('leadership');

    Route::get('/archive', [SkPresidentArchiveController::class, 'index'])->name('archive');
    Route::get('/archive/download/bulk', [SkPresidentArchiveController::class, 'bulkDownload'])->name('archive.bulk-download');
    Route::get('/archive/download/{sourceType}/{sourceId}', [SkPresidentArchiveController::class, 'download'])->name('archive.download');

    Route::get('/user-management', [SkPresidentUserManagementController::class, 'index'])->name('user-management');
    Route::post('/user-management/officials', [SkPresidentUserManagementController::class, 'storeOfficial'])->name('user-management.store-official');
    Route::get('/profile', fn (ProfileSettingsController $controller) => $controller->show('sk_president'))->name('profile');
    Route::post('/profile', fn (Request $request, ProfileSettingsController $controller) => $controller->update($request, 'sk_president'))->name('profile.update');
    Route::post('/profile/password', fn (Request $request, ProfileSettingsController $controller) => $controller->updatePassword($request, 'sk_president'))->name('profile.password');
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

Route::middleware('auth')->get('/sk_chairman/home', [SkChairmanHomeController::class, 'index'])->name('sk_chairman.home');
Route::middleware('auth')->get('/sk_secretary/home', [SkSecretaryHomeController::class, 'index'])->name('sk_secretary.home');

Route::middleware('auth')->get('/sk_chairman/reports', [SkChairmanReportController::class, 'index'])->name('sk_chairman.reports');
Route::middleware('auth')->post('/sk_chairman/reports', [SkChairmanReportController::class, 'store'])->name('sk_chairman.reports.store');
Route::middleware('auth')->get('/sk_chairman/budget', [SkChairmanBudgetController::class, 'index'])->name('sk_chairman.budget');
Route::middleware('auth')->post('/sk_chairman/budget', [SkChairmanBudgetController::class, 'store'])->name('sk_chairman.budget.store');
Route::middleware('auth')->get('/sk_chairman/budget/template', [SkChairmanBudgetController::class, 'createTemplate'])->name('sk_chairman.budget.template.create');
Route::middleware('auth')->post('/sk_chairman/budget/template', [SkChairmanBudgetController::class, 'storeTemplate'])->name('sk_chairman.budget.template.store');
Route::middleware('auth')->get('/sk_chairman/budget/template/{budgetReportId}/view', [SkChairmanBudgetController::class, 'viewTemplate'])->name('sk_chairman.budget.template.view');
Route::middleware('auth')->get('/sk_chairman/budget/template/{budgetReportId}/download', [SkChairmanBudgetController::class, 'downloadTemplate'])->name('sk_chairman.budget.template.download');
Route::middleware('auth')->get('/sk_chairman/announcements', [SkChairmanAnnouncementController::class, 'index'])->name('sk_chairman.announcements');
Route::middleware('auth')->get('/sk_chairman/calendar', [SkChairmanCalendarController::class, 'index'])->name('sk_chairman.calendar');
Route::middleware('auth')->get('/sk_chairman/chat', [SkChairmanChatController::class, 'index'])->name('sk_chairman.chat');
Route::middleware('auth')->get('/sk_chairman/chat/users', [SkChairmanChatController::class, 'searchUsers'])->name('sk_chairman.chat.users');
Route::middleware('auth')->get('/sk_chairman/meetings', [SkChairmanMeetingsController::class, 'index'])->name('sk_chairman.meetings');
Route::middleware('auth')->get('/sk_chairman/meetings/{meeting}/call', [SkChairmanMeetingsController::class, 'call'])->name('sk_chairman.meetings.call');
Route::middleware('auth')->post('/sk_chairman/meetings/{meeting}/agora-token', [SkChairmanMeetingsController::class, 'token'])->name('sk_chairman.meetings.agora.token');
Route::middleware('auth')->get('/sk_chairman/rankings', [SkChairmanRankingController::class, 'index'])->name('sk_chairman.rankings');
Route::middleware('auth')->get('/sk_chairman/leadership', [SkChairmanLeadershipController::class, 'index'])->name('sk_chairman.leadership');
Route::middleware('auth')->post('/sk_chairman/leadership', [SkChairmanLeadershipController::class, 'store'])->name('sk_chairman.leadership.store');
Route::middleware('auth')->post('/sk_chairman/leadership/{councilId}/delete', [SkChairmanLeadershipController::class, 'destroy'])->name('sk_chairman.leadership.destroy');
Route::middleware('auth')->get('/sk_chairman/archive', [App\Http\Controllers\sk_chairman\ArchiveController::class, 'index'])->name('sk_chairman.archive');
Route::middleware('auth')->get('/sk_chairman/archive/download/bulk', [App\Http\Controllers\sk_chairman\ArchiveController::class, 'bulkDownload'])->name('sk_chairman.archive.bulk-download');
Route::middleware('auth')->get('/sk_chairman/archive/download/{sourceType}/{sourceId}', [App\Http\Controllers\sk_chairman\ArchiveController::class, 'download'])->name('sk_chairman.archive.download');
Route::middleware('auth')->get('/sk_chairman/profile', fn (ProfileSettingsController $controller) => $controller->show('sk_chairman'))->name('sk_chairman.profile');
Route::middleware('auth')->post('/sk_chairman/profile', fn (Request $request, ProfileSettingsController $controller) => $controller->update($request, 'sk_chairman'))->name('sk_chairman.profile.update');
Route::middleware('auth')->post('/sk_chairman/profile/password', fn (Request $request, ProfileSettingsController $controller) => $controller->updatePassword($request, 'sk_chairman'))->name('sk_chairman.profile.password');

Route::middleware('auth')->get('/sk_secretary/reports', [SkSecretaryReportController::class, 'index'])->name('sk_secretary.reports');
Route::middleware('auth')->post('/sk_secretary/reports', [SkSecretaryReportController::class, 'store'])->name('sk_secretary.reports.store');
Route::middleware('auth')->get('/sk_secretary/budget', [SkSecretaryBudgetController::class, 'index'])->name('sk_secretary.budget');
Route::middleware('auth')->post('/sk_secretary/budget', [SkSecretaryBudgetController::class, 'store'])->name('sk_secretary.budget.store');
Route::middleware('auth')->get('/sk_secretary/budget/template', [SkSecretaryBudgetController::class, 'createTemplate'])->name('sk_secretary.budget.template.create');
Route::middleware('auth')->post('/sk_secretary/budget/template', [SkSecretaryBudgetController::class, 'storeTemplate'])->name('sk_secretary.budget.template.store');
Route::middleware('auth')->get('/sk_secretary/budget/template/{budgetReportId}/view', [SkSecretaryBudgetController::class, 'viewTemplate'])->name('sk_secretary.budget.template.view');
Route::middleware('auth')->get('/sk_secretary/budget/template/{budgetReportId}/download', [SkSecretaryBudgetController::class, 'downloadTemplate'])->name('sk_secretary.budget.template.download');
Route::middleware('auth')->get('/sk_secretary/announcements', [SkSecretaryAnnouncementController::class, 'index'])->name('sk_secretary.announcements');
Route::middleware('auth')->get('/sk_secretary/calendar', [SkSecretaryCalendarController::class, 'index'])->name('sk_secretary.calendar');
Route::middleware('auth')->get('/sk_secretary/chat', [SkSecretaryChatController::class, 'index'])->name('sk_secretary.chat');
Route::middleware('auth')->get('/sk_secretary/chat/users', [SkSecretaryChatController::class, 'searchUsers'])->name('sk_secretary.chat.users');
Route::middleware('auth')->get('/sk_secretary/meetings', [SkSecretaryMeetingsController::class, 'index'])->name('sk_secretary.meetings');
Route::middleware('auth')->get('/sk_secretary/meetings/{meeting}/call', [SkSecretaryMeetingsController::class, 'call'])->name('sk_secretary.meetings.call');
Route::middleware('auth')->post('/sk_secretary/meetings/{meeting}/agora-token', [SkSecretaryMeetingsController::class, 'token'])->name('sk_secretary.meetings.agora.token');
Route::middleware('auth')->get('/sk_secretary/rankings', [SkSecretaryRankingController::class, 'index'])->name('sk_secretary.rankings');
Route::middleware('auth')->get('/sk_secretary/leadership', [SkSecretaryLeadershipController::class, 'index'])->name('sk_secretary.leadership');
Route::middleware('auth')->get('/sk_secretary/profile', fn (ProfileSettingsController $controller) => $controller->show('sk_secretary'))->name('sk_secretary.profile');
Route::middleware('auth')->post('/sk_secretary/profile', fn (Request $request, ProfileSettingsController $controller) => $controller->update($request, 'sk_secretary'))->name('sk_secretary.profile.update');
Route::middleware('auth')->post('/sk_secretary/profile/password', fn (Request $request, ProfileSettingsController $controller) => $controller->updatePassword($request, 'sk_secretary'))->name('sk_secretary.profile.password');
