<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\HttpFoundation\Request;

const UNBILLABLE_PROJECT_IDS = array(4, 47);

$app          = new Silex\Application();
$app['debug'] = true;

$dotenv = new Dotenv\Dotenv(__DIR__ . "/..");
$dotenv->load();
$dotenv->required('REDMINE_API_KEY');
$dotenv->required('REDMINE_URL');


$app->get('/time', function (Request $request) use ($app) {
    $redmineKey = getenv('REDMINE_API_KEY');

    $start  = $request->query->get('start');
    $end    = $request->query->get('end');
    $userId = $request->query->get('user') ?: 'me';

    $res     = getDailySpentTime($userId, $start, $end, $redmineKey);
    $results = createDailyAggregate($res);

    return $app->json($results);
});

$app->get('/totals', function (Request $request) use ($app) {
    $redmineKey = getenv('REDMINE_API_KEY');

    $start  = $request->query->get('start');
    $end    = $request->query->get('end');
    $userId = $request->query->get('user') ?: 'me';

    $spentTime = getDailySpentTime($userId, $start, $end, $redmineKey);

    $totalBillable   = 0;
    $totalUnbillable = 0;

    foreach ($spentTime as $date => $day) {
        $billableHours   = array_reduce($day, "sumBillableHours", 0);
        $unBillableHours = array_reduce($day, "sumUnbillableHours", 0);

        $totalBillable   += (float)$billableHours;
        $totalUnbillable += (float)$unBillableHours;
    }

    if (0 == ($totalBillable + $totalUnbillable)) {
        return $app->json(['percBillable' => 0, 'percUnbillable' => 100]);
    }

    $percBillable   = round(($totalBillable * 100) / ($totalBillable + $totalUnbillable));
    $percUnbillable = 100 - $percBillable;

    return $app->json(['percBillable' => $percBillable, 'percUnbillable' => $percUnbillable]);
});

$app->run();

function getDailySpentTime($userId, $from, $to, $key)
{
    $redmineUrl = getenv('REDMINE_URL');

    $url = "$redmineUrl/time_entries.json?key=$key&user_id=$userId&from=$from&to=$to&limit=100";

    $times = json_decode(file_get_contents($url), true);

    $timeEntriesByDay = [];

    foreach ($times['time_entries'] as $timeEntry) {
        if ( ! isset($timeEntry['spent_on'])) {
            $timeEntriesByDay[$timeEntry['spent_on']] = [];
        }

        $timeEntriesByDay[$timeEntry['spent_on']][] = $timeEntry;
    }

    return $timeEntriesByDay;
}

function createDailyAggregate($spentTime)
{

    $results = [];

    foreach ($spentTime as $date => $day) {
        $billableHours   = array_reduce($day, "sumBillableHours", 0);
        $unBillableHours = array_reduce($day, "sumUnbillableHours", 0);

        $entry              = [];
        $entry['title']     = "" . (float)$billableHours . " billable \n" . (float)$unBillableHours . " unbillable";
        $event['billableHours'] = (float)$billableHours;
        $event['unbillableHours'] = (float)$unBillableHours;
        $entry['start']     = $date;
        $entry['details']   = array_reduce($day, "generateEntriesDescription", '');
        $entry['className'] = getClassNameByHour($billableHours, $unBillableHours);

        $results[] = $entry;
    }

    return $results;
}

function sumBillableHours($totalHours, $timeEntry)
{

    if (in_array($timeEntry['project']['id'], UNBILLABLE_PROJECT_IDS)) {
        return $totalHours;
    }

    return $totalHours + $timeEntry['hours'];
}

function sumUnbillableHours($totalHours, $timeEntry)
{

    if ( ! in_array($timeEntry['project']['id'], UNBILLABLE_PROJECT_IDS)) {
        return $totalHours;
    }

    return $totalHours + $timeEntry['hours'];
}

function generateEntriesDescription($description, $timeEntry)
{
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

    if ($hours < 8) {
        $classes[] = 'missing';
    }

    if ($billableHours >= 7) {
        $classes[] = 'good';
    }

    if ($billableHours >= 5 && $billableHours < 7) {
        $classes[] = 'warning';
    }

    if ($billableHours < 5) {
        $classes[] = 'nogood';
    }

    return $classes;
}
