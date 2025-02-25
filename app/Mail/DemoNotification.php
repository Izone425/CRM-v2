<?php
namespace App\Mail;

use Illuminate\Mail\Mailable;

class DemoNotification extends Mailable
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
                    ->subject(
                        $this->content['lead']['salespersonName'] . " | " .
                        strtoupper($this->content['lead']['demo_type']) . " | TIMETEC HR | " .
                        $this->content['lead']['company']
                    )
                    ->with([
                        'lead' => $this->content['lead'],
                        'leadOwnerName' => $this->content['leadOwnerName'],
                    ]);
    }
}
