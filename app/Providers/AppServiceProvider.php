<?php

namespace App\Providers;

use App\Repositories\BlogPostRepository;
use App\Repositories\ClaimRepository;
use App\Repositories\Contracts\IBlogPostRepository;
use App\Repositories\Contracts\IClaimRepository;
use App\Repositories\Contracts\IConversationRepository;
use App\Repositories\Contracts\IMessageRepository;
use App\Repositories\Contracts\IScheduleRepository;
use App\Repositories\Contracts\IUserRepository;
use App\Repositories\ConversationRepository;
use App\Repositories\MessageRepository;
use App\Repositories\ScheduleRepository;
use App\Repositories\UserRepository;
use App\Support\Seo;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(IUserRepository::class, UserRepository::class);
        $this->app->bind(IScheduleRepository::class, ScheduleRepository::class);
        $this->app->bind(IClaimRepository::class, ClaimRepository::class);
        $this->app->bind(IConversationRepository::class, ConversationRepository::class);
        $this->app->bind(IMessageRepository::class, MessageRepository::class);
        $this->app->bind(IBlogPostRepository::class, BlogPostRepository::class);
    }

    public function boot(): void
    {
        Seo::share();
    }
}
