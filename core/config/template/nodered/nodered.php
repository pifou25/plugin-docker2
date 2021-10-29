<?php
function nodered_post($_eqLogic, $_values) {
    $file = '/var/lib/docker/volumes/tmp_node-red-data/_data/settings.js';
    shell_exec('sudo sed -i \'/adminAuth: {type: "credentials"/d\' ' . $file);
    $password = trim(shell_exec('sudo docker exec nodered  node -e "console.log(require(\'bcryptjs\').hashSync(process.argv[1], 8));" ' . $_values['PASSWORD']));

    $cmd = 'sudo sed -i \'/\/\/adminAuth: {/i adminAuth: {type: "credentials",users: [{username: "admin",password: "' . $password . '",permissions: "*"}]},\' ' . $file;
    echo $cmd;
    shell_exec($cmd);
    $_eqLogic->restart();
}
