<?php

use App\Http\Controllers\Api\MobileSyncController;
use Illuminate\Support\Facades\Route;

Route::prefix('mobile')->group(function () {
    Route::get('/barangays', [MobileSyncController::class, 'barangays'])->name('mobile.barangays');
    Route::post('/login', [MobileSyncController::class, 'login'])->name('mobile.login');
    Route::post('/register', [MobileSyncController::class, 'register'])->name('mobile.register');
    Route::post('/verify', [MobileSyncController::class, 'verifyRegistration'])->name('mobile.verify');
    Route::post('/verify/resend', [MobileSyncController::class, 'resendVerificationCode'])->name('mobile.verify.resend');
    Route::post('/password/reset/request', [MobileSyncController::class, 'requestPasswordReset'])->name('mobile.password.reset.request');
    Route::post('/password/reset/verify', [MobileSyncController::class, 'verifyPasswordReset'])->name('mobile.password.reset.verify');

    Route::middleware('mobile.auth')->group(function () {
        Route::post('/logout', [MobileSyncController::class, 'logout'])->name('mobile.logout');
        Route::get('/me', [MobileSyncController::class, 'me'])->name('mobile.me');
        Route::post('/profile', [MobileSyncController::class, 'updateProfile'])->name('mobile.profile.update');
        Route::post('/profile/password', [MobileSyncController::class, 'updatePassword'])->name('mobile.profile.password');
        Route::get('/sync', [MobileSyncController::class, 'sync'])->name('mobile.sync');
        Route::post('/wall/posts', [MobileSyncController::class, 'storeWallPost'])->name('mobile.wall.posts.store');
        Route::post('/wall/posts/{announcementId}/like', [MobileSyncController::class, 'toggleWallLike'])->name('mobile.wall.posts.like');
        Route::post('/events', [MobileSyncController::class, 'storeEvent'])->name('mobile.events.store');
        Route::post('/meetings', [MobileSyncController::class, 'storeMeeting'])->name('mobile.meetings.store');
        Route::get('/meetings/{meeting}/join-url', [MobileSyncController::class, 'meetingJoinUrl'])->name('mobile.meetings.join-url');
        Route::post('/meetings/{meeting}/agora-token', [MobileSyncController::class, 'meetingAgoraToken'])->name('mobile.meetings.agora-token');
        Route::get('/chat/users', [MobileSyncController::class, 'chatUsers'])->name('mobile.chat.users');
        Route::post('/leadership/council', [MobileSyncController::class, 'storeCouncilMember'])->name('mobile.leadership.council.store');
        Route::post('/official-submissions', [MobileSyncController::class, 'storeOfficialSubmission'])->name('mobile.official-submissions.store');
        Route::get('/submission-slots', [MobileSyncController::class, 'submissionSlots'])->name('mobile.submission-slots.index');
        Route::post('/submission-slots', [MobileSyncController::class, 'storeSubmissionSlot'])->name('mobile.submission-slots.store');
        Route::delete('/submission-slots/{slotId}', [MobileSyncController::class, 'deleteSubmissionSlot'])->name('mobile.submission-slots.destroy');
        Route::get('/consolidation', [MobileSyncController::class, 'consolidation'])->name('mobile.consolidation');
        Route::post('/notifications/{notificationId}/read', [MobileSyncController::class, 'markNotificationRead'])->name('mobile.notifications.read');
    });
});
