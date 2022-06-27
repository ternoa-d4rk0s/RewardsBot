<?php

namespace D4rk0s\RewardsExplorer\Service;

use GuzzleHttp\Client;
use stdClass;

class RewardsService
{
    private const TERNOA_GRAPHQL_URL = 'https://dictionary-mainnet.ternoa.dev/';

    public static function getRewards(string $wallet) : array
    {
        setlocale(LC_TIME, 'fr_FR');
        date_default_timezone_set('Europe/Paris');

        $client = new Client();
        $body = '{"query":"\n  {\n  events(\n    orderBy: [BLOCK_HEIGHT_ASC]\n    filter: {\n      and: [\n        { call: { equalTo: \"Rewarded\" } }\n        {\n          argsValue: {\n            contains: \"' . $wallet . '\"\n          }\n        }\n      ]\n    }\n  ) {\n    totalCount\n    nodes {\n      argsValue\n      block {\n        timestamp\n      }\n    }\n  }\n}\n"}';
        $response = $client->request('POST', self::TERNOA_GRAPHQL_URL, [
            'body' => $body,
            'headers' => [
                'Content-Type' => 'application/json'
            ]
        ]);

        if ($response->getStatusCode() === 200) {
            $data = [];
            $responseDecoded = json_decode($response->getBody(), false, 512, JSON_THROW_ON_ERROR);
            foreach($responseDecoded->data->events->nodes as $nodeElement) {
                $rewardValue = $nodeElement->argsValue[1];
                $reward = new stdClass;
                $reward->date = date('D d/m/Y à H:i', strtotime($nodeElement->block->timestamp));
                //$reward->amount = (float)(substr($rewardValue, 0, -18) . "." . substr($rewardValue, -18));
                $reward->amount = $rewardValue;
                $data[] = $reward;
            }

            return $data;
        }

        throw new \Exception("Unable to have data");
    }
}