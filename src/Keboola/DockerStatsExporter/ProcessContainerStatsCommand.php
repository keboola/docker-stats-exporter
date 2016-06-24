<?php

namespace Keboola\DockerStatsExporter;

use Elasticsearch\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ProcessContainerStatsCommand extends Command
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
            ->setName('exporter:process-container-stats')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $params = [
            "index" => "cadvisor",
            "type" => "stats",
            "body" => <<<JSON
{
    "size": 0,
    "query": {
        "bool": {
            "should": [
                {
                    "range": {
                        "container_stats.network.tx_bytes": {
                            "gt": 0
                        }
                    }
                },
                {
                    "range": {
                        "container_stats.network.rx_bytes": {
                            "gt": 0
                        }
                    }
                }
            ],
            "minimum_should_match": 1
        }
    },
    "aggs": {
        "groupByContainerName": {
            "terms": {
                "field": "container_Name"
            },
            "aggs": {
                "maxNetworkTxBytes": {
                    "max": {
                        "field": "container_stats.network.tx_bytes"
                    }
                },
                "maxNetworkRxBytes": {
                    "max": {
                        "field": "container_stats.network.rx_bytes"
                    }
                },
                "maxMemoryUsage": {
                    "max": {
                        "field": "container_stats.memory.usage"
                    }
                }
            }
        }
    }
}
JSON
        ];

        $response = $this->elasticClient->search($params);

        if ($response['hits']['total'] > 0 && isset($response['aggregations']['groupByContainerName']['buckets'])) {
            foreach ($response['aggregations']['groupByContainerName']['buckets'] as $bucket) {
                var_dump($bucket);
            }
        }
    }
}
