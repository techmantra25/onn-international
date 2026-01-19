<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

Route::get('/cache-clear', function()
{
    \Artisan::call('optimize:clear');
    echo 'cache cleared';
});

Route::get('/home', 'HomeController@index')->name('home');

// website
Route::name('front.')->group(function () {
    // home
    Route::get('/', 'Front\FrontController@index')->name('home');
    Route::post('/subscribe', 'Front\FrontController@mailSubscribe')->name('subscription');

    // category detail
    Route::name('category.')->group(function () {
        Route::get('/category/{slug}', 'Front\CategoryController@detail')->name('detail');
        Route::post('/category/filter', 'Front\CategoryController@filter')->name('filter');
    });

    // sale
    Route::name('sale.')->group(function () {
        Route::get('/sale', 'Front\SaleController@index')->name('index');
    });

    // collection detail
    Route::name('collection.')->group(function () {
        Route::get('/collection/{slug}', 'Front\CollectionController@detail')->name('detail');
        Route::post('/collection/filter', 'Front\CollectionController@filter')->name('filter');
    });

    // product detail
    Route::name('product.')->group(function () {
        Route::get('/product/{slug}', 'Front\ProductController@detail')->name('detail');
        Route::post('/size', 'Front\ProductController@size')->name('size');
    });

    // wishlist
    Route::prefix('wishlist')->name('wishlist.')->group(function () {
        // Route::get('/', 'Front\WishlistController@viewByIp')->name('index');
        Route::post('/add', 'Front\WishlistController@add')->name('add');
        Route::post('/remove', 'Front\WishlistController@remove')->name('remove');
        Route::get('/delete/{id}', 'Front\WishlistController@delete')->name('delete');
    });

    // cart
    Route::prefix('cart')->name('cart.')->group(function () {
        // Route::get('/', 'Front\CartController@viewByIp')->name('index');
        Route::get('/details', 'Front\CartController@index')->name('index');
        Route::post('/coupon/check', 'Front\CartController@couponCheck')->name('coupon.check');
        Route::post('/coupon/remove', 'Front\CartController@couponRemove')->name('coupon.remove');
        Route::post('/add', 'Front\CartController@add')->name('add');
        Route::get('/delete/{id}', 'Front\CartController@delete')->name('delete');
        Route::get('/quantity/{id}/{type}', 'Front\CartController@qtyUpdate')->name('quantity');
    });

    // checkout
    Route::prefix('checkout')->name('checkout.')->group(function () {
        Route::get('/', 'Front\CheckoutController@index')->name('index');
        // Route::post('/coupon/check', 'Front\CheckoutController@coupon')->name('coupon.check');
        Route::post('/store', 'Front\CheckoutController@store')->name('store');
		Route::get('/payment/{order_id}', 'Front\CheckoutController@payment')->name('payment');
        Route::post('/payment/store', 'Front\CheckoutController@paymentStore')->name('payment.store');
        Route::view('/complete', 'front.checkout.complete')->name('complete');
    });

    // faq
    Route::prefix('faq')->name('faq.')->group(function () {
        Route::get('/', 'Front\FaqController@index')->name('index');
    });

    // offer
    Route::prefix('offer')->name('offer.')->group(function () {
        Route::get('/', 'Front\OfferController@index')->name('index');
    });

    // search
    Route::prefix('search')->name('search.')->group(function () {
        Route::get('/', 'Front\SearchController@index')->name('index');
        Route::post('/suggestion', 'Front\SearchController@suggestion')->name('suggestion');
    });

	// franchise
	Route::prefix('franchise')->name('franchise.')->group(function () {
        Route::get('/', 'Front\FranchiseController@index')->name('index');
        Route::post('/mail', 'Front\FranchiseController@mail')->name('mail');
        Route::post('/partner', 'Front\FranchiseController@partner')->name('partner');
        // Route::get('/thank-you', 'Front\FranchiseController@partner')->name('partner.success');
        Route::view('/thank-you', 'front.franchise.success')->name('partner.success');
    });

    // settings contents
    Route::name('content.')->group(function () {
        Route::get('/terms-and-conditions', 'Front\ContentController@termDetails')->name('terms');
        Route::get('/privacy-statement', 'Front\ContentController@privacyDetails')->name('privacy');
        Route::get('/security', 'Front\ContentController@securityDetails')->name('security');
        Route::get('/disclaimer', 'Front\ContentController@disclaimerDetails')->name('disclaimer');
        Route::get('/shipping-and-delivery', 'Front\ContentController@shippingDetails')->name('shipping');
        Route::get('/payment-voucher-promotion', 'Front\ContentController@paymentDetails')->name('payment');
        Route::get('/return-policy', 'Front\ContentController@returnDetails')->name('return');
        Route::get('/refund-policy', 'Front\ContentController@refundDetails')->name('refund');
        Route::get('/service-and-contact', 'Front\ContentController@serviceDetails')->name('service');

        Route::get('/blog', 'Front\ContentController@blog')->name('blog');
        Route::get('/blog/{slug}', 'Front\ContentController@blogDetail')->name('blog.detail');

		Route::get('/blog-demo', 'Front\ContentController@blog2')->name('blog.dummy');
        Route::get('/blog-demo/{slug}', 'Front\ContentController@blogDetail2')->name('blog.detail.dummy');

        Route::get('/about', 'Front\ContentController@about')->name('about');
        Route::get('/contact', 'Front\ContentController@contact')->name('contact');

        Route::get('/corporate', 'Front\ContentController@corporate')->name('corporate');
        Route::get('/news', 'Front\ContentController@news')->name('news');
        Route::get('/news/{slug}', 'Front\ContentController@newsDetail')->name('news.detail');

		Route::get('/news-demo', 'Front\ContentController@news2')->name('news.demo');
        Route::get('/news-demo/{slug}', 'Front\ContentController@newsDetail2')->name('news.detail.demo');

        Route::get('/career', 'Front\ContentController@career')->name('career');
        Route::get('/global', 'Front\ContentController@global')->name('global');
    });

    // user login & registration - guard
    Route::middleware(['guest:web'])->group(function () {
        Route::prefix('user/')->name('user.')->group(function () {
            Route::get('/register', 'Front\UserController@register')->name('register');
            Route::get('/register1', 'Front\UserController@register1')->name('register1');
           
            Route::post('/create', 'Front\UserController@create')->name('create');
            Route::get('/login', 'Front\UserController@login')->name('login');
            Route::post('/check', 'Front\UserController@check')->name('check');
            Route::get('/forgot-password', 'Front\UserController@forgotPassword')->name('forgot.password');
            Route::post('/forgot-password/check', 'Front\UserController@forgotPasswordCheck')->name('forgot.password.check');
        });
    });

    // profile login & registration - guard
    Route::middleware(['auth:web'])->group(function () {
        Route::prefix('user/')->name('user.')->group(function () {
            Route::view('profile', 'front.profile.index')->name('profile');
            Route::view('manage', 'front.profile.edit')->name('manage');
            Route::post('manage/update', 'Front\UserController@updateProfile')->name('manage.update');
            Route::post('password/update', 'Front\UserController@updatePassword')->name('password.update');
            Route::get('order', 'Front\UserController@order')->name('order');
            Route::post('order/cancel', 'Front\UserController@orderCancel')->name('order.cancel');
            Route::post('order/return', 'Front\UserController@orderReturn')->name('order.return');
            Route::get('order/{id}/invoice', 'Front\UserController@invoice')->name('invoice');
            Route::get('coupon', 'Front\UserController@coupon')->name('coupon');
            Route::get('address', 'Front\UserController@address')->name('address');
            Route::view('address/add', 'front.profile.address-add')->name('address.add');
            Route::post('address/add', 'Front\UserController@addressCreate')->name('address.create');
            Route::get('wishlist', 'Front\UserController@wishlist')->name('wishlist');
        });
    });

	// promotion
    Route::prefix('promotion')->name('promotion.')->group(function () {
        Route::get('/', 'Front\PromotionController@index')->name('index');
        Route::post('/store', 'Front\PromotionController@store')->name('store');
		Route::view('/thank-you', 'front.promotion.success')->name('success');
    });
	
	// gift
    Route::prefix('scanandwin')->name('scanandwin.')->group(function () {
        Route::get('/', 'Front\GiftController@index')->name('index');
        Route::post('/store', 'Front\GiftController@store')->name('store');
		Route::view('/thank-you', 'front.gift.detail')->name('success');
		Route::view('/fail', 'front.gift.detail')->name('failure');
		 Route::get('/tnc', 'Front\GiftController@tnc')->name('terms');
		Route::get('/winners','Front\GiftController@winner')->name('winner');
    });
	
	// festive offer
    Route::prefix('festiveoffer')->name('festiveoffer.')->group(function () {
        Route::get('/', 'Front\FestiveOfferController@index')->name('index');
        Route::post('/store', 'Front\FestiveOfferController@store')->name('store');
		Route::view('/thank-you', 'front.festiveoffer.detail')->name('detail');
		Route::view('/mail', 'front.festiveoffer.mail')->name('mail');
		Route::get('/tnc', 'Front\FestiveOfferController@tnc')->name('terms');
    });
	
	// sitemap
	Route::get('/products.xml', 'Front\SitemapController@product');

	// city from state
	Route::get('/state/{name}/detail', 'Front\StateController@detail')->name('state.detail');

    // mail template test
    Route::view('/mail/1', 'front.mail.register');
    Route::view('/mail/2', 'front.mail.order-confirm');
});

Auth::routes();
Route::post('/register1/create', 'Front\UserController@register1_create')->name('register1_create');
 Route::get('/buy-one-get-one/register', 'Front\UserController@buyonegetoneRegister')->name('buy-one-get-one.register');
Route::get('login', 'Front\UserController@login')->name('login');
Route::get('declaration', 'Front\FrontController@declare')->name('declare');
Route::get('/one', 'Front\FrontController@one')->name('one');
Route::get('/two', 'Front\FrontController@two')->name('two');
Route::get('/three', 'Front\FrontController@three')->name('three');
Route::get('/four', 'Front\FrontController@four')->name('four');
Route::get('/five', 'Front\FrontController@five')->name('five');
Route::get('/six', 'Front\FrontController@six')->name('six');
Route::get('/seven', 'Front\FrontController@seven')->name('seven');
Route::get('/eight', 'Front\FrontController@eight')->name('eight');
Route::get('/nine', 'Front\FrontController@nine')->name('nine');
Route::get('/ten', 'Front\FrontController@ten')->name('ten');
Route::get('/eleven', 'Front\FrontController@eleven')->name('eleven');
Route::get('/twelve', 'Front\FrontController@twelve')->name('twelve');
Route::get('/thirteen', 'Front\FrontController@thirteen')->name('thirteen');
Route::get('/fourteen', 'Front\FrontController@fourteen')->name('fourteen');
Route::get('/fifteen', 'Front\FrontController@fifteen')->name('fifteen');
Route::get('/sixteen', 'Front\FrontController@sixteen')->name('sixteen');
Route::get('/seventeen', 'Front\FrontController@seventeen')->name('seventeen');
Route::get('/eighteen', 'Front\FrontController@eighteen')->name('eighteen');
Route::get('/nineteen', 'Front\FrontController@nineteen')->name('nineteen');
Route::get('/twenty', 'Front\FrontController@twenty')->name('twenty');
Route::get('/twentyone', 'Front\FrontController@twentyone')->name('twentyone');
Route::get('/twentytwo', 'Front\FrontController@twentytwo')->name('twentytwo');
Route::get('/twentythree', 'Front\FrontController@twentythree')->name('twentythree');
Route::get('/twentyfour', 'Front\FrontController@twentyfour')->name('twentyfour');
Route::get('/twentyfive', 'Front\FrontController@twentyfive')->name('twentyfive');
Route::get('/catalogue', 'Front\FrontController@catalogue')->name('catalogue');

require 'admin.php';
