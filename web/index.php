<?php

require_once __DIR__.'/../vendor/autoload.php';

use Symfony\Component\HttpFoundation\Request;

$app = new Silex\Application();
$app['debug'] = true;

$dotenv = new Dotenv\Dotenv(__DIR__ . "/..");
$dotenv->load();
$dotenv->required('REDMINE_API_KEY');
$dotenv->required('REDMINE_URL');

$app->get('/time', function(Request $request) use($app) {

    $key =  getenv('REDMINE_API_KEY');
    $redmine_url = getenv('REDMINE_URL');

    $start = $request->query->get('start');
    $end = $request->query->get('end');
    $user_id = $request->query->get('user') ?: 'me';

    $res = getDailySpentTime($redmine_url, $user_id, $start, $end, $key);
    $results = createDailyAggregate($redmine_url, $res);

    return $app->json($results);
});

$app->run();

function getDailySpentTime($redmine_url, $user, $from, $to, $key) {

    $url = "$redmine_url/time_entries.json?key=$key&user_id=$user&from=$from&to=$to&limit=100";

    $times = json_decode(file_get_contents($url), true);

    $res = [];
    foreach ($times['time_entries'] as $time_entry) {
        if (!isset($time_entry['spent_on'])) {
            $res[$time_entry['spent_on']] = [];
        }

        $res[$time_entry['spent_on']][] = $time_entry;
    }

    return $res;
}

function createDailyAggregate($redmine_url, $spent_time) {

    $results = [];
    foreach ($spent_time as $date => $day) {
        $val = array_reduce($day, function($acc, $item) use ($redmine_url){
            $acc['hours'] += $item['hours'];

            if ($item['project']['id'] == 4 || $item['project']['id'] == 67) {
                $acc['hours_non_billable'] += $item['hours'];
            } else {
                $acc['hours_billable'] += $item['hours'];
            }

            $msg = <<<EOT
<br/>
<a href="$redmine_url/issues/{$item['issue']['id']}/time_entries">{$item['hours']}h</a>
{$item['project']['name']}
<a href="$redmine_url/issues/{$item['issue']['id']}">{$item['issue']['id']}</a><br/>
EOT;

            if ($item['comments']) {
               $msg .= "<span class='small'>-{$item['comments']}</span><br/>";
            }

            $acc['details'] .= $msg;

            return $acc;
        }, ['hours' => 0, 'details' => '']);

        $entry = [];
        $entry['title'] = "". (int) $val['hours_billable'] . " 💰\n" . (int) $val['hours_non_billable'] . " 🔧";
        $entry['start'] = $date;
        $entry['className'] = ['event'];
        $entry['details'] = $val['details'];
        $entry['hours_billable'] = (int)$val['hours_billable'];
        $entry['hours_non_billable'] = (int)$val['hours_non_billable'];

        if ($val['hours'] >= 6 ) {
            $entry['className'][] = 'good';
        }

        if ($val['hours'] < 6 && $val['hours'] >= 4) {
            $entry['className'][] = 'warning';
        }

        if ($val['hours'] < 4) {
            $entry['className'][] = 'nogood';
        }

        if ($val['hours_billable'] < $val['hours_non_billable']) {
            $entry['className'][] = 'caotic';
        }

        $results[] = $entry;
    }

    return $results;
}