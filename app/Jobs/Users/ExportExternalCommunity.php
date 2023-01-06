<?php

namespace App\Jobs\Users;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Exports\ExternalCommunitiesExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\User\ExportExternalCommunityMail;

class ExportExternalCommunity implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    private $user;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->user = $data['user'];
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $externalCommunity = Excel::download(new ExternalCommunitiesExport($this->user))->queue('external-community.csv', \Maatwebsite\Excel\Excel::CSV);
        $message = "Your external community is attached to this mail";

        Mail::to($this->user)->send(new ExportExternalCommunityMail([
            'message' => $message,
            'user' => $this->user,
            'file' => $externalCommunity->getFile(),
        ]));
    }
}
