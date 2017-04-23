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
    $redmine_key =  getenv('REDMINE_API_KEY');

    $start = $request->query->get('start');
    $end = $request->query->get('end');
    $user_id = $request->query->get('user') ?: 'me';

    $res = getDailySpentTime($user_id, $start, $end, $redmine_key);
    $results = createDailyAggregate($res);

    return $app->json($results);
});

$app->run();

function getDailySpentTime($user_id, $from, $to, $key) {
    $redmine_url = getenv('REDMINE_URL');

    $url = "$redmine_url/time_entries.json?key=$key&user_id=$user_id&from=$from&to=$to&limit=100";

    $times = json_decode(file_get_contents($url), true);

    $timeEntriesByDay = [];

    foreach ($times['time_entries'] as $time_entry) {
        if (!isset($time_entry['spent_on'])) {
            $timeEntriesByDay[$time_entry['spent_on']] = [];
        }

        $timeEntriesByDay[$time_entry['spent_on']][] = $time_entry;
    }

    return $timeEntriesByDay;
}

function createDailyAggregate($spent_time) {

    $results = [];

    foreach ($spent_time as $date => $day) {
        $billableHours = array_reduce($day, "sumBillableHours", 0);
        $unBillableHours = array_reduce($day, "sumUnbillableHours", 0);

        $entry = [];
        $entry['title'] = "". (float) $billableHours . " 💰\n" . (float) $unBillableHours . " 🔧";
        $entry['start'] = $date;
        $entry['details'] = array_reduce($day, "generateEntriesDescription", '');
        $entry['className'] = getClassNameByHour($billableHours, $unBillableHours);

        $results[] = $entry;
    }

    return $results;
}

function sumBillableHours($totalHours, $timeEntry) {

    if ($timeEntry['project']['id'] == 4 ||
        $timeEntry['project']['id'] == 67) {

        return $totalHours;
    }

    return $totalHours + $timeEntry['hours'];
}

function sumUnbillableHours($totalHours, $timeEntry) {

    if ($timeEntry['project']['id'] != 4 &&
        $timeEntry['project']['id'] != 67) {

        return $totalHours;
    }

    return $totalHours + $timeEntry['hours'];
}

function generateEntriesDescription($description, $timeEntry) {
    $redmine_url = getenv('REDMINE_URL');

    $msg = <<<EOT
<br/>
<a href="$redmine_url/issues/{$timeEntry['issue']['id']}/time_entries">{$timeEntry['hours']}h</a>
{$timeEntry['project']['name']}
<a href="$redmine_url/issues/{$timeEntry['issue']['id']}">{$timeEntry['issue']['id']}</a><br/>
EOT;

    if ($timeEntry['comments']) {
        $msg .= "<span class='small'>-{$timeEntry['comments']}</span><br/>";
    }

    return $description . $msg;
}

function getClassNameByHour($billableHours, $unBillableHours)
{
    $hours = $billableHours + $unBillableHours;

    $classes = ['event'];

    if ($hours < 4) {
        $classes[] = 'nogood';
    }

    if ($hours >= 4 && $hours < 6) {
        $classes[] = 'warning';
    }

    if ($hours >= 6) {
        $classes[] = 'good';
    }

    if ($billableHours < $unBillableHours) {
            $classes[] = 'caotic';
    }

    return $classes;
}