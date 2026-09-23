<?php

use App\Http\Controllers\Api\MobileSyncController;
use Illuminate\Support\Facades\Route;

Route::prefix('mobile')->group(function(){
    Route::get('/barangays',[MobileSyncController::class,'barangays'])->name('mobile.barangays');
    Route::post('/login',[MobileSyncController::class,'login'])->name('mobile.login');
    Route::post('/password/reset/request',[MobileSyncController::class,'requestPasswordReset'])->name('mobile.password.reset.request');
    Route::post('/password/reset/verify',[MobileSyncController::class,'verifyPasswordReset'])->name('mobile.password.reset.verify');

    Route::middleware('mobile.auth')->group(function(){
        Route::post('/logout',[MobileSyncController::class,'logout'])->name('mobile.logout');
        Route::get('/me',[MobileSyncController::class,'me'])->name('mobile.me');
        Route::post('/profile',[MobileSyncController::class,'updateProfile'])->name('mobile.profile.update');
        Route::post('/profile/contact/request',[MobileSyncController::class,'requestContactChange'])->middleware('throttle:5,10')->name('mobile.profile.contact.request');
        Route::post('/profile/contact/verify',[MobileSyncController::class,'verifyContactChange'])->middleware('throttle:10,10')->name('mobile.profile.contact.verify');
        Route::post('/profile/password',[MobileSyncController::class,'updatePassword'])->name('mobile.profile.password');
        Route::post('/profile/password/request',[MobileSyncController::class,'requestPasswordChange'])->name('mobile.profile.password.request');
        Route::post('/profile/password/verify',[MobileSyncController::class,'verifyPasswordChange'])->name('mobile.profile.password.verify');
        Route::get('/sync',[MobileSyncController::class,'sync'])->name('mobile.sync');

        Route::post('/wall/posts',[MobileSyncController::class,'storeWallPost'])->name('mobile.wall.posts.store');
        Route::patch('/wall/posts/{announcementId}',[MobileSyncController::class,'updateWallPost'])->name('mobile.wall.posts.update');
        Route::post('/wall/posts/{announcementId}/like',[MobileSyncController::class,'toggleWallLike'])->name('mobile.wall.posts.like');
        Route::patch('/feedback/{feedbackId}',[MobileSyncController::class,'updateFeedback'])->name('mobile.feedback.update');

        Route::post('/events',[MobileSyncController::class,'storeEvent'])->name('mobile.events.store');
        Route::patch('/events/{eventId}',[MobileSyncController::class,'updateEvent'])->name('mobile.events.update');

        Route::post('/meetings',[MobileSyncController::class,'storeMeeting'])->name('mobile.meetings.store');
        Route::patch('/meetings/{meetingId}',[MobileSyncController::class,'updateMeeting'])->name('mobile.meetings.update');
        Route::patch('/meetings/{meeting}/end',[MobileSyncController::class,'endMeeting'])->name('mobile.meetings.end');
        Route::get('/meetings/{meeting}/join-url',[MobileSyncController::class,'meetingJoinUrl'])->name('mobile.meetings.join-url');
        Route::post('/meetings/{meeting}/agora-token',[MobileSyncController::class,'meetingAgoraToken'])->name('mobile.meetings.agora-token');

        Route::get('/chat/users',[MobileSyncController::class,'chatUsers'])->name('mobile.chat.users');

        Route::post('/leadership/council',[MobileSyncController::class,'storeCouncilMember'])->name('mobile.leadership.council.store');
        Route::post('/leadership/council/{councilId}',[MobileSyncController::class,'updateCouncilMember'])->name('mobile.leadership.council.update');

        Route::post('/official-submissions',[MobileSyncController::class,'storeOfficialSubmission'])->name('mobile.official-submissions.store');

        Route::get('/submission-slots',[MobileSyncController::class,'submissionSlots'])->name('mobile.submission-slots.index');
        Route::get('/submission-slots/{slotId}/submissions',[MobileSyncController::class,'submissionSlotSubmissions'])->name('mobile.submission-slots.submissions');
        Route::patch('/submission-slots/{slotId}/toggle',[MobileSyncController::class,'toggleSubmissionSlot'])->name('mobile.submission-slots.toggle');
        Route::post('/submission-slots',[MobileSyncController::class,'storeSubmissionSlot'])->name('mobile.submission-slots.store');
        Route::delete('/submission-slots/{slotId}',[MobileSyncController::class,'deleteSubmissionSlot'])->name('mobile.submission-slots.destroy');

        Route::get('/consolidation',[MobileSyncController::class,'consolidation'])->name('mobile.consolidation');

        Route::post('/notifications/{notificationId}/read',[MobileSyncController::class,'markNotificationRead'])->name('mobile.notifications.read');
    });
});

?>
