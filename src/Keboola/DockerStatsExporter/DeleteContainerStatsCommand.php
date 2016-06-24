<?php

namespace Keboola\DockerStatsExporter;

use Elasticsearch\Client;
use Elasticsearch\Common\Exceptions\Missing404Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DeleteContainerStatsCommand extends Command
{
    private $elasticClient;

    public function __construct(Client $elasticClient)
    {
        $this->elasticClient = $elasticClient;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('exporter:delete-container-stats')
            ->addOption(
                'olderThanSeconds',
                null,
                InputOption::VALUE_REQUIRED,
                'Delete documents older than specified seconds',
                600
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $olderThanSeconds = (int) $input->getOption('olderThanSeconds');

        $params = [
            "search_type" => "scan",
            "scroll" => "30s",
            "size" => 500,
            "index" => "cadvisor",
            "type" => "stats",
            "body" => [
                "query" => [
                    "bool" => [
                        "must" => [
                            [
                                "range" => [
                                    "timestamp" => [
                                        "lt" => (time() - $olderThanSeconds) * 1e6
                                    ]
                                ]
                            ]
                        ],
                    ]
                ]
            ]
        ];

        $docs = $this->elasticClient->search($params);
        $scrollId = $docs['_scroll_id'];
        $continue = true;

        while ($continue) {
            $response = $this->elasticClient->scroll([
                "scroll_id" => $scrollId,
                "scroll" => "30s"
            ]);

            if (count($response['hits']['hits']) > 0) {
                foreach ($response['hits']['hits'] as $hit) {
                    try {
                        $this->elasticClient->delete([
                            'index' => 'cadvisor',
                            'type' => 'stats',
                            'id' => $hit['_id']
                        ]);
                    } catch (Missing404Exception $e) {
                        echo 'Document not found' . "\n";
                    }

                }
                $scrollId = $response['_scroll_id'];
            } else {
                $continue = false;
            }
        }
    }
}
