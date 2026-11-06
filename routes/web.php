<?php

use App\Http\Controllers\BusinessManagementController;
use App\Http\Controllers\BusinessProfileController;
use App\Http\Controllers\SmartPageController;
use App\Http\Controllers\BusinessCatalogController;
use App\Http\Controllers\BusinessCartController;
use App\Http\Controllers\BusinessInventoryController;
use App\Http\Controllers\BusinessOrderPaymentController;
use App\Http\Controllers\BusinessRegistrationController;
use App\Http\Controllers\BusinessQrController;
use App\Http\Controllers\BusinessSubscriptionController;
use App\Http\Controllers\BusinessSubscriptionPaymentController;
use App\Http\Controllers\QrAnalyticsController;
use App\Http\Controllers\SessionController;
use App\Http\Controllers\QrImageController;
use App\Http\Controllers\QrRedirectController;
use App\Http\Controllers\SmartPageInteractionController;
use App\Http\Controllers\BusinessInquiryController;
use App\Http\Controllers\BusinessReviewController;
use App\Http\Controllers\BusinessFavoriteController;
use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware('guest')->group(function (): void {
    Route::view('/register', 'auth.register')->name('register');
    Route::post('/register', BusinessRegistrationController::class)->name('register.store');
    Route::get('/login', [SessionController::class, 'create'])->name('login');
    Route::post('/login', [SessionController::class, 'store'])->name('login.store');
});

Route::post('/payments/tbc/callback', [BusinessSubscriptionPaymentController::class, 'callback'])->middleware('throttle:60,1')->name('payments.tbc.callback');


Route::middleware('auth')->group(function (): void {
    Route::get('/favorites', [BusinessFavoriteController::class, 'index'])->name('favorites.index');
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::patch('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
    Route::post('/p/{slug}/favorite', [BusinessFavoriteController::class, 'store'])->where('slug', '[A-Za-z0-9-]+')->middleware('throttle:20,1')->name('business.public.favorite.store');
    Route::delete('/p/{slug}/favorite', [BusinessFavoriteController::class, 'destroy'])->where('slug', '[A-Za-z0-9-]+')->name('business.public.favorite.destroy');

    Route::get('/dashboard', function () {
        $user = auth()->user();
        $businesses = $user->businesses()->withCount('qrCodes')->latest()->get();
        $teamMemberships = $user->memberships()
            ->where('status', 'active')
            ->with(['business', 'branch'])
            ->latest()
            ->get();
        $unreadNotifications = $user->unreadNotifications()->count();

        return view('dashboard', compact('businesses', 'teamMemberships', 'unreadNotifications'));
    })->name('dashboard');

    Route::get('/platform/subscription-plans', [\App\Http\Controllers\PlatformSubscriptionPlanController::class, 'index'])->name('platform.subscription-plans.index');
    Route::put('/platform/subscription-plans/{plan}', [\App\Http\Controllers\PlatformSubscriptionPlanController::class, 'update'])->whereNumber('plan')->name('platform.subscription-plans.update');
    Route::get('/platform/subscription-finance', [\App\Http\Controllers\PlatformSubscriptionFinanceController::class, 'index'])->name('platform.subscription-finance.index');
    Route::get('/platform/subscription-finance/export', [\App\Http\Controllers\PlatformSubscriptionFinanceController::class, 'export'])->name('platform.subscription-finance.export');
    Route::get('/platform/business-subscriptions', [\App\Http\Controllers\PlatformBusinessSubscriptionController::class, 'index'])->name('platform.business-subscriptions.index');
    Route::post('/platform/business-subscriptions/{business}', [\App\Http\Controllers\PlatformBusinessSubscriptionController::class, 'store'])->whereNumber('business')->name('platform.business-subscriptions.store');

    Route::get('/businesses/{business}/subscription', [BusinessSubscriptionController::class, 'index'])->name('business.subscription');
    Route::post('/businesses/{business}/subscription/{plan}/checkout', [BusinessSubscriptionPaymentController::class, 'checkout'])->whereNumber('plan')->middleware('throttle:5,1')->name('business.subscription.checkout');
    Route::get('/payments/tbc/return/{uuid}', [BusinessSubscriptionPaymentController::class, 'returned'])->middleware('throttle:10,1')->name('payments.tbc.return');
    Route::get('/businesses/{business}/manage', [BusinessManagementController::class, 'index'])->name('business.manage');
    Route::get('/businesses/{business}/inventory', [BusinessInventoryController::class, 'index'])->name('business.inventory.index');
    Route::post('/businesses/{business}/inventory/{item}/adjust', [BusinessInventoryController::class, 'adjust'])->whereNumber('item')->middleware('throttle:30,1')->name('business.inventory.adjust');
    Route::get('/businesses/{business}/orders', [BusinessCartController::class, 'orders'])->name('business.orders.index');
    Route::get('/businesses/{business}/kitchen', [BusinessCartController::class, 'kitchen'])->name('business.kitchen');
    Route::patch('/businesses/{business}/orders/{order}/status', [BusinessCartController::class, 'updateStatus'])->whereNumber('order')->name('business.orders.update-status');
    Route::get('/businesses/{business}/catalog', [BusinessCatalogController::class, 'index'])->name('business.catalog.index');
    Route::get('/businesses/{business}/inbox', [\App\Http\Controllers\BusinessInboxController::class, 'index'])->name('business.inbox.index');
    Route::patch('/businesses/{business}/inbox/{type}/{record}', [\App\Http\Controllers\BusinessInboxController::class, 'update'])->whereNumber('record')->name('business.inbox.update');
    Route::post('/businesses/{business}/inbox/{type}/{record}/interactions', [\App\Http\Controllers\BusinessInboxController::class, 'storeInteraction'])->whereNumber('record')->name('business.inbox.interactions.store');
    Route::patch('/businesses/{business}/inquiries/{inquiry}', [BusinessInquiryController::class, 'updateStatus'])->whereNumber('inquiry')->name('business.inquiries.status');
    Route::patch('/businesses/{business}/reviews/{review}', [BusinessReviewController::class, 'updateStatus'])->whereNumber('review')->name('business.reviews.status');
    Route::post('/businesses/{business}/catalog', [BusinessCatalogController::class, 'store'])->name('business.catalog.store');
    Route::put('/businesses/{business}/catalog/{item}', [BusinessCatalogController::class, 'update'])->whereNumber('item')->name('business.catalog.update');
    Route::delete('/businesses/{business}/catalog/{item}', [BusinessCatalogController::class, 'destroy'])->whereNumber('item')->name('business.catalog.destroy');
    Route::patch('/businesses/{business}/orders/{order}', [BusinessCatalogController::class, 'updateOrderStatus'])->whereNumber('order')->name('business.orders.status');
    Route::get('/businesses/{business}/smart-pages', [SmartPageController::class, 'index'])->name('business.smart-pages.index');
    Route::post('/businesses/{business}/smart-pages', [SmartPageController::class, 'store'])->name('business.smart-pages.store');
    Route::get('/businesses/{business}/smart-pages/{page}/edit', [SmartPageController::class, 'edit'])->name('business.smart-pages.edit');
    Route::put('/businesses/{business}/smart-pages/{page}', [SmartPageController::class, 'update'])->name('business.smart-pages.update');
    Route::delete('/businesses/{business}/smart-pages/{page}', [SmartPageController::class, 'destroy'])->name('business.smart-pages.destroy');
    Route::post('/businesses/{business}/smart-pages/{page}/schedules', [SmartPageController::class, 'storeSchedule'])->name('business.smart-pages.schedules.store');
    Route::delete('/businesses/{business}/smart-page-schedules/{schedule}', [SmartPageController::class, 'destroySchedule'])->whereNumber('schedule')->name('business.smart-pages.schedules.destroy');

    Route::get('/businesses/{business}/profile', [BusinessProfileController::class, 'edit'])->name('business.profile.edit');
    Route::put('/businesses/{business}/profile', [BusinessProfileController::class, 'update'])->name('business.profile.update');
    Route::post('/businesses/{business}/branches', [BusinessManagementController::class, 'storeBranch'])->name('business.branches.store');
    Route::delete('/businesses/{business}/branches/{branch}', [BusinessManagementController::class, 'destroyBranch'])->whereNumber('branch')->name('business.branches.destroy');
    Route::post('/businesses/{business}/members', [BusinessManagementController::class, 'inviteMember'])->name('business.members.invite');
    Route::delete('/businesses/{business}/members/{membership}', [BusinessManagementController::class, 'destroyMember'])->whereNumber('membership')->name('business.members.destroy');
    Route::get('/team/invitations', [BusinessManagementController::class, 'invitations'])->name('team.invitations');
    Route::post('/team/invitations/{membership}/accept', [BusinessManagementController::class, 'acceptInvitation'])->whereNumber('membership')->name('team.invitations.accept');

    Route::get('/businesses/{business}/qr', [BusinessQrController::class, 'index'])->name('business.qr.index');
    Route::get('/businesses/{business}/tables', [BusinessQrController::class, 'tables'])->name('business.tables.index');
    Route::post('/businesses/{business}/qr', [BusinessQrController::class, 'store'])->name('business.qr.store');
    Route::put('/businesses/{business}/qr/{qrCode}', [BusinessQrController::class, 'update'])->whereNumber('qrCode')->name('business.qr.update');
    Route::delete('/businesses/{business}/qr/{qrCode}', [BusinessQrController::class, 'destroy'])->whereNumber('qrCode')->name('business.qr.destroy');
    Route::get('/businesses/{business}/analytics', [QrAnalyticsController::class, 'index'])->name('business.analytics');
    Route::get('/businesses/{business}/analytics/export', [QrAnalyticsController::class, 'export'])->name('business.analytics.export');
    Route::get('/businesses/{business}/qr/{qrCode}/image.svg', [QrImageController::class, 'show'])->whereNumber('qrCode')->name('business.qr.image');
    Route::get('/businesses/{business}/qr/{qrCode}/print', [QrImageController::class, 'printCard'])->whereNumber('qrCode')->name('business.qr.print');

    Route::get('/support', [\App\Http\Controllers\SupportTicketController::class, 'index'])->name('support.index');
    Route::get('/support/create', [\App\Http\Controllers\SupportTicketController::class, 'create'])->name('support.create');
    Route::post('/support', [\App\Http\Controllers\SupportTicketController::class, 'store'])->middleware('throttle:10,1')->name('support.store');
    Route::get('/support/{ticket}', [\App\Http\Controllers\SupportTicketController::class, 'show'])->whereNumber('ticket')->name('support.show');
    Route::post('/support/{ticket}/replies', [\App\Http\Controllers\SupportTicketController::class, 'reply'])->whereNumber('ticket')->middleware('throttle:20,1')->name('support.reply');
    Route::get('/platform/support', [\App\Http\Controllers\SupportTicketController::class, 'platformIndex'])->name('platform.support.index');
    Route::patch('/platform/support/{ticket}', [\App\Http\Controllers\SupportTicketController::class, 'platformUpdate'])->whereNumber('ticket')->name('platform.support.update');

    Route::post('/logout', [SessionController::class, 'destroy'])->name('logout');
});

    Route::get('/payments/tbc/orders/return/{uuid}', [BusinessOrderPaymentController::class, 'returned'])->middleware('throttle:10,1')->name('business.orders.payment.return');

Route::get('/b/{slug}/cart', [BusinessCartController::class, 'show'])->name('business.cart.show');
Route::post('/b/{slug}/cart/{item}', [BusinessCartController::class, 'add'])->whereNumber('item')->middleware('throttle:30,1')->name('business.cart.add');
Route::patch('/b/{slug}/cart/{item}', [BusinessCartController::class, 'update'])->whereNumber('item')->name('business.cart.update');
Route::delete('/b/{slug}/cart/{item}', [BusinessCartController::class, 'remove'])->whereNumber('item')->name('business.cart.remove');
Route::post('/b/{slug}/checkout', [BusinessCartController::class, 'checkout'])->middleware('throttle:10,1')->name('business.cart.checkout');
Route::post('/b/{slug}/catalog/{item}/order', [BusinessCatalogController::class, 'submitOrder'])->whereNumber('item')->middleware('throttle:10,1')->name('business.catalog.order');
Route::post('/p/{slug}/interactions', [SmartPageInteractionController::class, 'store'])->where('slug', '[A-Za-z0-9-]+')->middleware('throttle:30,1')->name('business.public.interactions');
Route::post('/p/{slug}/inquiries', [BusinessInquiryController::class, 'store'])->where('slug', '[A-Za-z0-9-]+')->middleware('throttle:5,1')->name('business.public.inquiries.store');
Route::post('/p/{slug}/reviews', [BusinessReviewController::class, 'store'])->where('slug', '[A-Za-z0-9-]+')->middleware('throttle:3,10')->name('business.public.reviews.store');
Route::get('/p/{slug}', [BusinessProfileController::class, 'show'])->where('slug', '[A-Za-z0-9-]+')->name('business.public');
Route::get('/b/{slug}', [BusinessProfileController::class, 'show'])->where('slug', '[A-Za-z0-9-]+')->name('business.public.legacy');
Route::get('/q/{publicId}', QrRedirectController::class)->whereUuid('publicId')->name('qr.redirect');
