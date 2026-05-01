<?php

namespace App\Providers;

use App\Models\Advertisement;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\Department;
use App\Models\Booking;
use App\Policies\AdvertisementPolicy;
use App\Policies\DepartmentPolicy;
use App\Policies\BookingPolicy;
use App\Policies\WaitingListPolicy;
use App\Models\WaitingList;
use App\Policies\InstitutePolicy;
use App\Models\Institute;
use Illuminate\Database\Eloquent\Relations\Relation;
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // تعريف بوابة الوصول للإدارة (المدير العام فقط)
        // Gate::before(function ($user, $ability) {
        //     return $user->hasRole('super_admin') ? true : null;
        // });
        // تسجيل سياسة الوصول لقسم الإدارة
        Gate::policy(Department::class, DepartmentPolicy::class);
        Gate::policy(Advertisement::class,AdvertisementPolicy::class);
        Gate::policy(Booking::class, BookingPolicy::class);
        Gate::policy(WaitingList::class, WaitingListPolicy::class);
        Gate::policy(Institute::class, InstitutePolicy::class);
        // تعريف أسماء مستعارة للموديلات في علاقات الـ Morph

    }}







