<?php

require './vendor/autoload.php';

use FacebookAds\Api;
use FacebookAds\Object\AdAccount;
use FacebookAds\Object\Values\InsightsPresets;
use FacebookAds\Object\Values\InsightsLevels;

function requireEnv($key)
{
    $value = getenv($key);
    if (!$value) {
        throw new \InvalidArgumentException(sprintf('Environment variable %s must be set', $key));
    }
    return $value;
}

Api::init(requireEnv('FACEBOOK_APP_ID'), requireEnv('FACEBOOK_APPSECRET'), requireEnv('FACEBOOK_ACCESS_TOKEN'));

function waitForJobToComplete($job)
{
    sleep(2);
    $job->read();
    while (!$job->isComplete()) {
      sleep(5);
      $job->read();
    }
    
    $cursor = $job->getResult();
    $cursor->setUseImplicitFetch(true);
    return $cursor;
}

function queryTotal(array $dimensions, $column, \DateTime $date)
{
    $account = new AdAccount(requireEnv('FACEBOOK_ACCOUNT_ID'));
    $collapsedDimensions = implode(',', $dimensions);
    
    $params = [
        'breakdowns'  => $collapsedDimensions,
        'level'       => InsightsLevels::ADSET,
        'time_range'  => [
            'since'  => $date->format('Y-m-d'),
            'until'  => $date->format('Y-m-d')
        ]
    ];
    
    $asyncJob = $account->getInsightsAsync([], $params);
    
    echo sprintf("Created async job %s (%s)\n", $asyncJob->getData()['id'], $collapsedDimensions);
    
    $result = waitForJobToComplete($asyncJob);
    
    $total = 0;
    foreach ($result as $row) {
        $data = $row->getData();
        $total += $data['spend'];
    }
    
    return $total;
}

$date = new \DateTime('2015-11-01');

$totals = [
    'Placement + Impression Device' => queryTotal(['placement', 'impression_device'], 'spend', $date),
    'Age + Gender'                  => queryTotal(['age', 'gender'], 'spend', $date),
    'Product ID'                    => queryTotal(['product_id'], 'spend', $date),
];

echo sprintf("%-32s%s\n", "Breakdown", "Total Spend");
foreach ($totals as $title => $total) {
    echo sprintf("%-32s%f\n", $title, $total);
}

