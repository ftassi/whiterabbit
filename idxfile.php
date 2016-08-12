<?php

use Idephix\IdephixInterface;

/**
 * Deploy application
 */
function deploy(IdephixInterface $idx, $go = false)
{
    if (!$go) {
        $idx->output->writeln('<comment>Dry Run</comment>');
    }

    $dryRun = $go ? '' : '--dry-run';
    $target = $idx->getCurrentTarget();

    $user = $target->get('ssh_params.user');
    $host = $idx->getCurrentTargetHost();
    $remoteDir = $target->get('deploy.remote_dir');
    $exclude = $target->get('deploy.rsync_exclude_file');

    $idx->local("rsync -rlDcz --force --delete --progress --exclude-from '$exclude' $dryRun -e 'ssh' . $user@$host:$remoteDir");

}

function server(IdephixInterface $idx) {
    $idx->local('php -S localhost:8080 -t web &');
}