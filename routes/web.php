// 도움말 시스템 라우트
Route::prefix('help')->name('help.')->group(function () {
    Route::get('/', 'HelpController@index')->name('index');
    Route::get('/search', 'HelpController@search')->name('search');
    Route::get('/category/{categorySlug}', 'HelpController@category')->name('category');
    Route::get('/article/{categorySlug}/{articleSlug}', 'HelpController@article')->name('article');
    
    // 관리자 전용 라우트
    Route::middleware(['auth', 'role:admin'])->group(function () {
        Route::get('/edit', 'HelpController@edit')->name('edit');
        Route::get('/edit/{categorySlug}', 'HelpController@edit')->name('edit.category');
        Route::get('/edit/{categorySlug}/{articleSlug}', 'HelpController@edit')->name('edit.article');
        Route::post('/save', 'HelpController@save')->name('save');
        Route::post('/delete', 'HelpController@delete')->name('delete');
        Route::post('/category/save', 'HelpController@saveCategory')->name('category.save');
        Route::post('/category/delete', 'HelpController@deleteCategory')->name('category.delete');
    });
});

// 온보딩 시스템 라우트
Route::prefix('onboarding')->name('onboarding.')->middleware('auth')->group(function () {
    Route::get('/', 'OnboardingController@index')->name('index');
    Route::get('/tour/{tourId}', 'OnboardingController@showTour')->name('tour');
}); 