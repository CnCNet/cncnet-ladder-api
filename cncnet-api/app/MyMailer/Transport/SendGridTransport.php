<?php

namespace App\MyMailer\Transport;

use Swift_Transport;
use GuzzleHttp\ClientInterface;
use Swift_Mime_Message;
use GuzzleHttp\Middleware;

class SendGridTransport implements Swift_Transport
{
    /**
     * Guzzle HTTP client.
     *
     * @var ClientInterface
     */
    protected $client;

    /**
     *
     * @var string
     */
    protected $apiKey;

    /**
     * The SendGrid end point we're using to send the message.
     *
     * @var string
     */
    protected $endPoint = 'https://sendgrid.com/v3/mail/send';

	/**
	 * {@inheritdoc}
	 */
	public function isStarted()
	{
		return true;
	}

	/**
	 * {@inheritdoc}
	 */
	public function start()
	{
		return true;
	}

	/**
	 * {@inheritdoc}
	 */
	public function stop()
	{
		return true;
	}
	/**
	 * {@inheritdoc}
	 */

	public function registerPlugin(Swift_Events_EventListener $plugin)
	{
		//
	}

    /**
     * Create a new SendGrid transport instance.
     *
     * @param  \GuzzleHttp\ClientInterface $client
     * @param $apiKey
     */
    public function __construct(ClientInterface $client, $apiKey)
    {
        $this->client = $client;
        $this->apiKey = $apiKey;
    }

    /**
     * Send the given Message.
     *
     * Recipient/sender data will be retrieved from the Message API.
     * The return value is the number of recipients who were accepted for delivery.
     *
     * @param Swift_Mime_Message $message
     * @param string[] $failedRecipients An array of failures by-reference
     *
     * @return int
     */
    public function send(Swift_Mime_Message $message, &$failedRecipients = null)
    {
        //$this->beforeSendPerformed($message);

        $payload = [
            'headers' => ['Content-Type' => 'application/json',
                          'Authorization' => "Bearer ".$this->apiKey ],
            'json' => []
        ];

        $this->addFrom($message, $payload);
        $this->addSubject($message, $payload);
        $this->addContent($message, $payload);
        $this->addRecipients($message, $payload);

        /*$clientHandler = $this->client->getConfig('handler');

        $tapMiddleware = Middleware::tap(function ($request) {
            var_dump(['request' => $request, 'json' => $request->getBody()->read(1024)]);
        });

        $payload['handler'] = $tapMiddleware($clientHandler);*/

        $ret = $this->client->post($this->endPoint, $payload);
        return $ret;
    }

    /**
     * Add the from email and from name (If provided) to the payload.
     *
     * @param Swift_Mime_Message $message
     * @param array $payload
     */
    protected function addFrom(Swift_Mime_Message $message, &$payload)
    {
        $from = $message->getFrom();

        $fromAddress = key($from);
        if ($fromAddress) {
            $payload['json']["from"] = ["email" => $fromAddress];

            $fromName = $from[$fromAddress] ?: null;
            if ($fromName) {
                $payload['json']["from"]["name"] = $fromName;
            }
        }
    }

    /**
     * Add the subject of the email (If provided) to the payload.
     *
     * @param Swift_Mime_Message $message
     * @param array $payload
     */
    protected function addSubject(Swift_Mime_Message $message, &$payload)
    {
        $subject = $message->getSubject();
        if ($subject) {
            $payload['json']['personalizations'][0]['subject'] = $subject;
        }
    }

    /**
     * Add the content/json to the payload based upon the content type provided in the message object. In the unlikely
     * event that a content type isn't provided, we can guess it based on the existence of HTML tags in the json.
     *
     * @param Swift_Mime_Message $message
     * @param array $payload
     */
    protected function addContent(Swift_Mime_Message $message, &$payload)
    {
        $contentType = $message->getContentType();
        $json = $message->getBody();

        if (!in_array($contentType, ['text/html', 'text/plain'])) {
            $contentType = strip_tags($json) != $json ? 'text/html' : 'text/plain';
        }

        $payload['json']['content'][] = ['type'  => $contentType,
                                         'value' => $message->getBody()];
    }

    /**
     * Add to, cc and bcc recipients to the payload.
     *
     * @param Swift_Mime_Message $message
     * @param array $payload
     */
    protected function addRecipients(Swift_Mime_Message $message, &$payload)
    {
        foreach (['To', 'Cc', 'Bcc'] as $field) {
            $method = 'get' . $field;
            $contacts = (array) $message->$method();
            foreach ($contacts as $address => $display) {
                $payload['json']['personalizations'][0][strtolower($field)][] = [ 'email' => $address, 'name' => $display ];
            }
        }
    }
}