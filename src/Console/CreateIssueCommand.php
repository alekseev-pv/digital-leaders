<?php


namespace App\Console;


use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CreateIssueCommand extends Command
{


    protected function configure()
    {
        $this->setName('tt:api:create_issue')
            ->setDescription('Pings task tracker api');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $client = new Client([]);
        $payload = [
            'issue' => [
                'project_id' => 1,
                'tracker_id' => 1,
                'status_id' => 1,
                'priority_id' => 2,
                'subject' => 'Test',
                'description' => 'testing',
            ],
        ];
        try {
            $response = $client->post(
                'http://159.69.18.145:10083/issues.json',
                [
                    'auth' => [
                        'admin',
                        'adminadmin',
                    ],
                    'json' => $payload,
                ]
            );
        } catch (ClientException $e) {
            $output->write('Error: '.$e->getResponse()->getBody()->getContents());
            $output->write('Error: '.$e->getMessage());

            return;
        } catch (\Throwable $e) {
            $output->write('Error: '.$e->getMessage());

            return;
        }

        $output->write($response->getBody()->getContents());

    }

}