<?php


namespace App\ChatBot\Infrastructure;


use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Psr\Log\LoggerInterface;

class TaskTrackerNotificationService
{

    private $uri;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->uri = 'http://159.69.18.145:10083/issues.json';
        $this->logger = $logger;
    }

    public function createRepairRequest(
        string $forPhoneNumber,
        string $address,
        string $proposedObjectType,
        string $jobType
    ): string {
        $payload = [
            'issue' => [
                'project_id' => 1,
                'custom_fields' => [
                    [
                        'id' => 1,
                        'value' => $forPhoneNumber,
                    ],
                    [
                        'id' => 2,
                        'value' => $proposedObjectType,
                    ],
                    [
                        'id' => 3,
                        'value' => $jobType,
                    ],
                    [
                        'id' => 4,
                        'value' => $address,
                    ],
                ],
                'subject' => "Заявка от $address: $jobType",
            ],
        ];

        $client = new Client();
        try {
            $response = $client->post(
                $this->uri,
                [
                    'auth' => [
                        'admin',
                        'adminadmin',
                    ],
                    'json' => $payload,
                ]
            );
        } catch (ClientException $e) {
            $this->logger->warning('Failed to create task', [$e]);
            throw $e;
        }

        $contents = $response->getBody()->getContents();
        $deserialized = json_decode($contents, true);

        return $deserialized['issue']['id'];
    }
}