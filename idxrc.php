<?php

$targets = array(
    'prod' => array(
        'hosts' => array('52.57.245.198'),
        'ssh_params' => ['user' => 'ideato'],
        'deploy' => array(
            'local_dir' => ".",
            'remote_dir' => "/var/www/time.ideato.it",
            'rsync_exclude_file' => 'deploy_exclude'
        ),
    ),
);

return \Idephix\Config::fromArray(
    array(
        'targets' => $targets,
        'sshClient' => new \Idephix\SSH\SshClient(new \Idephix\SSH\CLISshProxy()),
        'extension' => array(
            'project' => new \Idephix\Extension\Project\Project(),
        ),
    )
);