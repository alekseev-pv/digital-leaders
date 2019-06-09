<?php


namespace App\Console;


use GuzzleHttp\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PingTaskTrackerCommand extends Command
{

    protected function configure()
    {
        $this->setName('tt:api:ping')
            ->setDescription('Pings task tracker api');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $client = new Client([]);
        try {
            $response = $client->get(
                'http://159.69.18.145:10083/issues.json',
                [
                    'auth' => [
                        'admin',
                        'adminadmin',
                    ],
                ]
            );
        } catch (\Throwable $e) {
            $output->write('Error: '.$e->getMessage());

            return;
        }

        $output->write($response->getBody()->getContents());

    }

}