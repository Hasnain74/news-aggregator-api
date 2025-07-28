<?php
namespace App\Console\Commands;

use App\Jobs\FetchArticleJob;
use Illuminate\Console\Command;

/**
 * Class FetchArticles
 * @package App\Console\Commands
 */
class FetchArticles extends Command
{
    protected $signature = 'fetch:articles';
    protected $description = 'Dispatch the job to fetch the articles';

    /**
     * @return void
     */
    public function handle(): void
    {
        FetchArticleJob::dispatch();
        $this->info('FetchArticlesJob dispatched successfully.');
    }
}
