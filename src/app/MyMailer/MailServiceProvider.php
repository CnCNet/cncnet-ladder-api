<?php namespace App\MyMailer;

class MailServiceProvider extends \Illuminate\Mail\MailServiceProvider
{
    public function registerSwiftTransport()
    {
        $this->app['swift.transport'] = $this->app->share(function ($app) {
            // Note: This is my own implementation of transport manager as shown below
            return new TransportManager($app);
        });
    }
}
