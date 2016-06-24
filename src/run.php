<?php

require __DIR__ . '/../vendor/autoload.php';

use Elasticsearch\ClientBuilder;
use Symfony\Component\Console\Application;
use Keboola\DockerStatsExporter\DeleteContainerStatsCommand;
use Keboola\DockerStatsExporter\ProcessContainerStatsCommand;

$elasticClient = ClientBuilder::create()
    ->setHosts([
        'http://elasticsearch:9200'
    ])
    ->build();

$application = new Application;
$application->addCommands([
    new ProcessContainerStatsCommand($elasticClient),
    new DeleteContainerStatsCommand($elasticClient)
]);
$application->run();
