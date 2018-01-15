<?php

$environments = array(
    'prod' => array(
        'hosts' => array('52.57.245.198'),
        'ssh_params' => ['user' => 'ideato'],
        'deploy' => array(
            'remote_base_dir' => "/var/www/time.ideato.it",
            'rsync_exclude' => 'deploy_exclude'
        ),
    ),
);

return [
    'envs' => $environments,
    'ssh_client' => new \Idephix\SSH\SshClient(),
    'extensions' => [
        'rsync' => new \Idephix\Extension\Project\Rsync(),
    ],
];
