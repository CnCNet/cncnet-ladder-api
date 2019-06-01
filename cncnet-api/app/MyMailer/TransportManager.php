<?php namespace App\MyMailer;

use App\MyMailer\Transport\SendGridTransport;
use GuzzleHttp\Client;

class TransportManager extends \Illuminate\Mail\TransportManager
{
    protected function createSendGridDriver()
    {
        $config = $this->app['config']->get('mail', []);

        $client = new Client();

        $sgt = new SendGridTransport(
            $client,
            $config['api_key']
        );

        return $sgt;
    }
}
