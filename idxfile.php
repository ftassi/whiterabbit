<?php

use Idephix\Context;

/**
 * Deploy application
 */
function deploy(Idephix\Context $context, $go = false)
{
    if (!$go) {
        $context->output()->writeln('<comment>Dry Run</comment>');
    }

    $dryRun = $go ? '' : '--dry-run';

    $user = $context->get('ssh_params.user');
    $host = $context->currentHost();

    $remoteDir = $context->get('deploy.remote_base_dir');
    $exclude = $context->get('deploy.rsync_exclude');

    $context->local("rsync -rlDcz --force --delete --progress --exclude-from '$exclude' $dryRun -e 'ssh' . $user@$host:$remoteDir");

}

/**
 * Run application for development
 */
function devServer(\Idephix\Context $context)
{
    $context->output()->writeln('<comment>Open http://localhost:8080/index.html</comment>');
    $context->local('php -S localhost:8080 -d display_errors=0 -t web');
}
