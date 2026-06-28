<?php

namespace App\Providers;

use App\Repositories\BlogPostRepository;
use App\Repositories\ClaimRepository;
use App\Repositories\Contracts\IBlogPostRepository;
use App\Repositories\Contracts\IClaimRepository;
use App\Repositories\Contracts\IConversationRepository;
use App\Repositories\Contracts\IMessageRepository;
use App\Repositories\Contracts\IScheduleRepository;
use App\Repositories\Contracts\ITicketRepository;
use App\Repositories\Contracts\IUserRepository;
use App\Repositories\ConversationRepository;
use App\Repositories\MessageRepository;
use App\Repositories\ScheduleRepository;
use App\Repositories\TicketRepository;
use App\Repositories\UserRepository;
use App\Support\Seo;
use App\Services\Uploads\S3UploadService;
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Columns\ImageColumn;
use Illuminate\Support\ServiceProvider;
use League\Flysystem\UnableToCheckFileExistence;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

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
        $this->app->bind(ITicketRepository::class, TicketRepository::class);
    }

    public function boot(): void
    {
        Seo::share();

        FileUpload::configureUsing(function (FileUpload $component): void {
            $component
                ->disk('s3')
                ->visibility('public')
                ->saveUploadedFileUsing(function (FileUpload $component, TemporaryUploadedFile $file) {
                    try {
                        if (! $file->exists()) {
                            return null;
                        }
                    } catch (UnableToCheckFileExistence) {
                        return null;
                    }

                    /** @var S3UploadService $uploader */
                    $uploader = app(S3UploadService::class);

                    return $uploader->storeTemporaryFile(
                        $file,
                        $component->getDirectory(),
                        $component->getUploadedFileNameForStorage($file),
                    );
                });
        });

        ImageColumn::configureUsing(function (ImageColumn $column): void {
            $column
                ->disk('s3')
                ->visibility('public');
        });
    }
}
