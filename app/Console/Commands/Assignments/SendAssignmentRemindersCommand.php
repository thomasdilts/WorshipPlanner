<?php

namespace App\Console\Commands\Assignments;

use App\Services\AssignmentReminderDispatcher;
use Illuminate\Console\Command;

class SendAssignmentRemindersCommand extends Command
{
    protected $signature = 'assignments:send-reminders';

    protected $description = 'Send church-local automatic assignment reminders for upcoming services';

    public function handle(AssignmentReminderDispatcher $dispatcher): int
    {
        $result = $dispatcher->run();

        $this->info(__('Reminders sent: :sent (skipped :skipped across :churches churches).', [
            'sent' => $result['sent'],
            'skipped' => $result['skipped'],
            'churches' => $result['churches'],
        ]));

        return self::SUCCESS;
    }
}
