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

use App\Http\Controllers\public_portal\HomeController as PublicHomeController;
use App\Http\Controllers\public_portal\AnnouncementInteractionController as PublicAnnouncementInteractionController;
use App\Http\Controllers\public_portal\AnnouncementController as PublicAnnouncementController;
use App\Http\Controllers\public_portal\CalendarController as PublicCalendarController;
use App\Http\Controllers\public_portal\LeadershipController as PublicLeadershipController;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileSettingsController;
use App\Http\Controllers\WallPostController;
use App\Http\Controllers\Api\MobileSyncController as MobileApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


/*
|--------------------------------------------------------------------------
| PUBLIC ROUTES
|--------------------------------------------------------------------------
*/
Route::get('/', function(){
    if(auth()->check()){
        return redirect()->to(match(auth()->user()->role){
            'sk_president'=>route('sk_pres.home'),
            'sk_chairman'=>route('sk_chairman.home'),
            'sk_secretary'=>route('sk_secretary.home'),
            default=>route('login'),
        });
    }

    return view('welcome');
});

Route::get('/mobile/meetings/{meeting}/call', [MobileApiController::class, 'mobileMeetingCall'])
    ->middleware('signed')
    ->name('mobile.meetings.call');
Route::post('/mobile/meetings/{meeting}/agora-token', [MobileApiController::class, 'mobileMeetingToken'])
    ->middleware('signed')
    ->name('mobile.meetings.agora.token');

Route::controller(AuthController::class)->group(function () {
    Route::get('/login', 'showLogin')->name('login');
    Route::post('/login', 'login')->name('login.submit');
    Route::post('/logout', 'logout')->name('logout');

    Route::get('/password/request', [AuthController::class, 'showForgotPassword'])->name('password.request');
    Route::post('/password/email', [AuthController::class, 'sendPasswordReset'])->name('password.email');
    Route::get('/password/reset/{token}', [AuthController::class, 'showResetPassword'])->name('password.reset');
    Route::post('/password/reset', [AuthController::class, 'resetPassword'])->name('password.store');
    Route::post('/password/verify-email', [AuthController::class, 'verifyEmailReset'])->name('password.verify-email');
    Route::post('/password/verify-phone', [AuthController::class, 'verifyPhoneReset'])->name('password.verify-phone');

    Route::get('/set-password/{token}', [AuthController::class, 'showSetPassword'])->name('password.setup');
    Route::post('/set-password', [AuthController::class, 'setPassword'])->name('password.setup.store');
});

Route::middleware('auth')->group(function(){
    Route::get('/notifications/feed',[NotificationController::class,'feed'])->name('notifications.feed');
    Route::post('/notifications/{notification}/read',[NotificationController::class,'markRead'])->name('notifications.read');
    Route::post('/wall/posts',[WallPostController::class,'store'])->name('wall.posts.store');
    Route::post('/wall/posts/{announcement}/like',[WallPostController::class,'toggleLike'])->name('wall.posts.like');
});



/*
|--------------------------------------------------------------------------
| SK PRESIDENT
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->prefix('sk_pres')->name('sk_pres.')->group(function(){

    Route::get('/home',[HomeController::class,'index'])->name('home');
    Route::get('/dashboard',[DashboardController::class,'index'])->name('dashboard');

    Route::get('/consolidation',[ConsolidationController::class,'index'])->name('consolidation');
    Route::get('/consolidation/download',[ConsolidationController::class,'download'])->name('consolidation.download');

    Route::get('/module',[ModuleController::class,'index'])->name('module');
    Route::get('/module/live',[ModuleController::class,'live'])->name('module.live');
    Route::post('/module/live',[ModuleController::class,'storeLive'])->name('module.live.store');
    Route::delete('/module/live/{slotId}',[ModuleController::class,'destroyLive'])->name('module.live.destroy');
    Route::post('/module',[ModuleController::class,'store'])->name('module.store');
    Route::post('/module/{slotId}/delete',[ModuleController::class,'destroy'])->name('module.destroy');

    Route::get('/announcements',[AnnouncementController::class,'index'])->name('announcements');
    Route::post('/announcements',[AnnouncementController::class,'store'])->name('announcements.store');

    Route::get('/calendar',[CalendarController::class,'index'])->name('calendar');
    Route::get('/calendar/live',[CalendarController::class,'live'])->name('calendar.live');
    Route::post('/calendar/live',[CalendarController::class,'storeLive'])->name('calendar.live.store');
    Route::post('/calendar',[CalendarController::class,'store'])->name('calendar.store');

    Route::get('/chat',[SkPresidentChatController::class,'index'])->name('chat');
    Route::get('/chat/users',[SkPresidentChatController::class,'searchUsers'])->name('chat.users');

    Route::get('/meetings',[MeetingsController::class,'index'])->name('meetings');
    Route::post('/meetings',[MeetingsController::class,'store'])->name('meetings.store');
    Route::patch('/meetings/{meeting}/end',[MeetingsController::class,'end'])->name('meetings.end');
    Route::get('/meetings/{meeting}/call',[MeetingsController::class,'call'])->name('meetings.call');
    Route::post('/meetings/{meeting}/agora-token',[MeetingsController::class,'token'])->name('meetings.agora.token');
    Route::get('/video',fn()=>redirect()->route('sk_pres.meetings'))->name('video');

    Route::get('/rankings',[SkPresidentRankingController::class,'index'])->name('rankings');
    Route::get('/rankings/live',[SkPresidentRankingController::class,'live'])->name('rankings.live');

    Route::get('/leadership',[SkPresidentLeadershipController::class,'index'])->name('leadership');

    /*
    |--------------------------------------------------------------------------
    | DOCUMENT / REPORT ARCHIVE
    |--------------------------------------------------------------------------
    */
    Route::get('/archive',[SkPresidentArchiveController::class,'index'])->name('archive');
    Route::get('/archive/download/bulk',[SkPresidentArchiveController::class,'bulkDownload'])->name('archive.bulk-download');
    Route::get('/archive/download/{sourceType}/{sourceId}',[SkPresidentArchiveController::class,'download'])->name('archive.download');

    /*
    |--------------------------------------------------------------------------
    | USER MANAGEMENT
    |--------------------------------------------------------------------------
    */
    Route::get('/user-management',[SkPresidentUserManagementController::class,'index'])->name('user-management');
    Route::post('/user-management/officials',[SkPresidentUserManagementController::class,'storeOfficial'])->name('user-management.store-official');
    Route::post('/user-management/bulk-officials',[SkPresidentUserManagementController::class,'storeBulkOfficials'])->name('user-management.store-bulk-officials');
    Route::post('/user-management/import',[SkPresidentUserManagementController::class,'import'])->name('user-management.import');
    Route::get('/user-management/csv-template',[SkPresidentUserManagementController::class,'downloadCsvTemplate'])->name('user-management.csv-template');
    Route::post('/user-management/{userId}/resend-setup-link',[SkPresidentUserManagementController::class,'resendSetupLink'])->middleware('throttle:3,10')->name('user-management.resend-setup-link');
    Route::put('/user-management/{userId}',[SkPresidentUserManagementController::class,'update'])->name('user-management.update');
    Route::patch('/user-management/{userId}/toggle-status',[SkPresidentUserManagementController::class,'toggleStatus'])->name('user-management.toggle-status');
    Route::patch('/user-management/{userId}/archive',[SkPresidentUserManagementController::class,'archive'])->name('user-management.archive');
    Route::delete('/user-management/{userId}',[SkPresidentUserManagementController::class,'destroy'])->name('user-management.destroy');
    Route::post('/user-management/start-new-term',[SkPresidentUserManagementController::class,'startNewTerm'])->name('user-management.start-new-term');
    Route::post('/user-management/history/{officialTermId}/reappoint',[SkPresidentUserManagementController::class,'reappoint'])->name('user-management.reappoint');
    /*
    |--------------------------------------------------------------------------
    | PROFILE
    |--------------------------------------------------------------------------
    */
    Route::get('/profile',fn(ProfileSettingsController $c)=>$c->show('sk_president'))
        ->name('profile');

    Route::post('/profile',fn(Request $r,ProfileSettingsController $c)=>$c->update($r,'sk_president'))
        ->name('profile.update');

    Route::post('/profile/password',fn(Request $r,ProfileSettingsController $c)=>$c->updatePassword($r,'sk_president'))
        ->name('profile.password');
});



/*
|--------------------------------------------------------------------------
| SK CHAIRMAN
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->prefix('sk_chairman')->name('sk_chairman.')->group(function () {

    Route::get('/home', [SkChairmanHomeController::class, 'index'])->name('home');

    Route::get('/reports', [SkChairmanReportController::class, 'index'])->name('reports');
    Route::post('/reports', [SkChairmanReportController::class, 'store'])->name('reports.store');

    Route::get('/budget', [SkChairmanBudgetController::class, 'index'])->name('budget');
    Route::post('/budget', [SkChairmanBudgetController::class, 'store'])->name('budget.store');

    Route::get('/budget/template', [SkChairmanBudgetController::class, 'createTemplate'])->name('budget.template.create');
    Route::post('/budget/template', [SkChairmanBudgetController::class, 'storeTemplate'])->name('budget.template.store');
    Route::get('/budget/template/{budgetReportId}/view', [SkChairmanBudgetController::class, 'viewTemplate'])->name('budget.template.view');
    Route::get('/budget/template/{budgetReportId}/download', [SkChairmanBudgetController::class, 'downloadTemplate'])->name('budget.template.download');

    Route::get('/announcements', [SkChairmanAnnouncementController::class, 'index'])->name('announcements');
    Route::get('/calendar', [SkChairmanCalendarController::class, 'index'])->name('calendar');

    Route::get('/chat', [SkChairmanChatController::class, 'index'])->name('chat');
    Route::get('/chat/users', [SkChairmanChatController::class, 'searchUsers'])->name('chat.users');

    Route::get('/meetings', [SkChairmanMeetingsController::class, 'index'])->name('meetings');
    Route::get('/meetings/{meeting}/call', [SkChairmanMeetingsController::class, 'call'])->name('meetings.call');
    Route::post('/meetings/{meeting}/agora-token', [SkChairmanMeetingsController::class, 'token'])->name('meetings.agora.token');

    Route::get('/rankings', [SkChairmanRankingController::class, 'index'])->name('rankings');
    Route::get('/rankings/live', [SkChairmanRankingController::class, 'live'])->name('rankings.live');

    Route::get('/leadership', [SkChairmanLeadershipController::class, 'index'])->name('leadership');
    Route::post('/leadership/councilor', [SkChairmanLeadershipController::class, 'store'])->name('leadership.store');
    Route::post('/leadership/councilors/bulk', [SkChairmanLeadershipController::class, 'storeBulkCouncilors'])->name('leadership.bulk-councilors');
    Route::post('/leadership/treasurer', [SkChairmanLeadershipController::class, 'storeTreasurer'])->name('leadership.treasurer.store');
    Route::post('/leadership/secretary', [SkChairmanLeadershipController::class, 'storeSecretary'])->name('leadership.secretary.store');
    Route::put('/leadership/secretary/{userId}', [SkChairmanLeadershipController::class, 'updateSecretary'])->name('leadership.secretary.update');
    Route::post('/leadership/secretary/{userId}/resend-setup-link', [SkChairmanLeadershipController::class, 'resendSecretarySetupLink'])->middleware('throttle:3,10')->name('leadership.secretary.resend');
    Route::patch('/leadership/secretary/{userId}/toggle-status', [SkChairmanLeadershipController::class, 'toggleSecretaryStatus'])->name('leadership.secretary.toggle-status');
    Route::delete('/leadership/secretary/{userId}', [SkChairmanLeadershipController::class, 'destroySecretary'])->name('leadership.secretary.destroy');
    Route::put('/leadership/treasurer/{councilId}', [SkChairmanLeadershipController::class, 'updateTreasurer'])->name('leadership.treasurer.update');
    Route::put('/leadership/councilor/{councilId}', [SkChairmanLeadershipController::class, 'updateCouncilor'])->name('leadership.councilor.update');
    Route::post('/leadership/{councilId}/delete', [SkChairmanLeadershipController::class, 'destroy'])->name('leadership.destroy');
    Route::post('/leadership/secretary/{officialTermId}/reappoint',[SkChairmanLeadershipController::class,'reappointSecretary'])->name('leadership.secretary.reappoint');
    Route::post('/leadership/council/{councilId}/reappoint',[SkChairmanLeadershipController::class,'reappointCouncilMember'])->name('leadership.council.reappoint');

    Route::get('/archive', [App\Http\Controllers\sk_chairman\ArchiveController::class, 'index'])->name('archive');
    Route::get('/archive/download/bulk', [App\Http\Controllers\sk_chairman\ArchiveController::class, 'bulkDownload'])->name('archive.bulk-download');
    Route::get('/archive/download/{sourceType}/{sourceId}', [App\Http\Controllers\sk_chairman\ArchiveController::class, 'download'])->name('archive.download');

    Route::get('/profile', fn (ProfileSettingsController $c) => $c->show('sk_chairman'))->name('profile');
    Route::post('/profile', fn (Request $r, ProfileSettingsController $c) => $c->update($r, 'sk_chairman'))->name('profile.update');
    Route::post('/profile/password', fn (Request $r, ProfileSettingsController $c) => $c->updatePassword($r, 'sk_chairman'))->name('profile.password');
});



/*
|--------------------------------------------------------------------------
| SK SECRETARY
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->prefix('sk_secretary')->name('sk_secretary.')->group(function () {

    Route::get('/home', [SkSecretaryHomeController::class, 'index'])->name('home');

    Route::get('/reports', [SkSecretaryReportController::class, 'index'])->name('reports');
    Route::post('/reports', [SkSecretaryReportController::class, 'store'])->name('reports.store');

    Route::get('/budget', [SkSecretaryBudgetController::class, 'index'])->name('budget');
    Route::post('/budget', [SkSecretaryBudgetController::class, 'store'])->name('budget.store');

    Route::get('/budget/template', [SkSecretaryBudgetController::class, 'createTemplate'])->name('budget.template.create');
    Route::post('/budget/template', [SkSecretaryBudgetController::class, 'storeTemplate'])->name('budget.template.store');
    Route::get('/budget/template/{budgetReportId}/view', [SkSecretaryBudgetController::class, 'viewTemplate'])->name('budget.template.view');
    Route::get('/budget/template/{budgetReportId}/download', [SkSecretaryBudgetController::class, 'downloadTemplate'])->name('budget.template.download');

    Route::get('/announcements', [SkSecretaryAnnouncementController::class, 'index'])->name('announcements');
    Route::get('/calendar', [SkSecretaryCalendarController::class, 'index'])->name('calendar');

    Route::get('/chat', [SkSecretaryChatController::class, 'index'])->name('chat');
    Route::get('/chat/users', [SkSecretaryChatController::class, 'searchUsers'])->name('chat.users');

    Route::get('/meetings', [SkSecretaryMeetingsController::class, 'index'])->name('meetings');
    Route::get('/meetings/{meeting}/call', [SkSecretaryMeetingsController::class, 'call'])->name('meetings.call');
    Route::post('/meetings/{meeting}/agora-token', [SkSecretaryMeetingsController::class, 'token'])->name('meetings.agora.token');

    Route::get('/rankings', [SkSecretaryRankingController::class, 'index'])->name('rankings');
    Route::get('/rankings/live', [SkSecretaryRankingController::class, 'live'])->name('rankings.live');
    Route::get('/leadership', [SkSecretaryLeadershipController::class, 'index'])->name('leadership');

    Route::get('/profile', fn (ProfileSettingsController $c) => $c->show('sk_secretary'))->name('profile');
    Route::post('/profile', fn (Request $r, ProfileSettingsController $c) => $c->update($r, 'sk_secretary'))->name('profile.update');
    Route::post('/profile/password', fn (Request $r, ProfileSettingsController $c) => $c->updatePassword($r, 'sk_secretary'))->name('profile.password');
});


/*
|--------------------------------------------------------------------------
| PUBLIC PORTAL
|--------------------------------------------------------------------------
*/
Route::prefix('public-portal')->name('public.')->group(function(){

    Route::get('/',[PublicHomeController::class,'index'])->name('home');

    Route::get('/announcements',[PublicAnnouncementController::class,'index'])->name('announcements');
    Route::get('/calendar',[PublicCalendarController::class,'index'])->name('calendar');
    Route::get('/leadership',[PublicLeadershipController::class,'index'])->name('leadership');

    Route::post('/announcements/{announcementId}/like',[PublicAnnouncementInteractionController::class,'toggleLike'])->middleware('throttle:30,1')->name('announcements.like');
    Route::post('/announcements/{announcementId}/view',[PublicAnnouncementInteractionController::class,'trackView'])->middleware('throttle:120,1')->name('announcements.view');
    Route::get('/announcements/{announcementId}/feedback',[PublicAnnouncementInteractionController::class,'feedbackList'])->middleware('throttle:120,1')->name('announcements.feedback-list');
    Route::post('/announcements/{announcementId}/feedback',[PublicAnnouncementInteractionController::class,'submitFeedback'])->middleware('throttle:public-feedback-submit')->name('announcements.feedback');

    Route::post('/feedback/{feedbackId}/verify',[PublicAnnouncementInteractionController::class,'verifyFeedback'])->middleware('throttle:public-feedback-verify')->name('feedback.verify');
    Route::post('/feedback/{feedbackId}/resend',[PublicAnnouncementInteractionController::class,'resendFeedback'])->middleware('throttle:public-feedback-resend')->name('feedback.resend');

});

