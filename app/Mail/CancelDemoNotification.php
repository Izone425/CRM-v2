<?php
namespace App\Mail;

use Illuminate\Mail\Mailable;

class CancelDemoNotification extends Mailable
{
    public $content;
    public $viewName;

    public function __construct($content, $viewName)
    {
        $this->content = $content;
        $this->viewName = $viewName;
    }

    public function build()
    {
        return $this->from($this->content['lead']['salespersonEmail'], $this->content['lead']['salespersonName'])
                    ->view($this->viewName)
                    ->subject("TIMETEC HRMS MEETING CANCELED")
                    ->with([
                        'lead' => $this->content['lead'],
                        'leadOwnerName' => $this->content['leadOwnerName'],
                    ]);
    }
}
