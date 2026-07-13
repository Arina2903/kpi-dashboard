<?php

namespace App\Console\Commands;

use App\Services\TelegramReviewService;
use Illuminate\Console\Command;

class TelegramGenerateReview extends Command
{
    protected $signature = 'telegram:generate-review {period : weekly, monthly, or quarterly}';

    protected $description = 'Generates and sends AI performance reviews to all linked Telegram users for the given period';

    public function handle(TelegramReviewService $reviews): int
    {
        $period = $this->argument('period');

        if (!in_array($period, ['weekly', 'monthly', 'quarterly'])) {
            $this->error('Period must be one of: weekly, monthly, quarterly');
            return self::FAILURE;
        }

        $this->info("Generating {$period} reviews…");
        $count = $reviews->generate($period);
        $this->info("Generated {$count} reviews.");

        return self::SUCCESS;
    }
}
