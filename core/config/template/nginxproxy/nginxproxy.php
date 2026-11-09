<?php
function nginx_proxy_manager_post($_eqLogic, $_values) {

    if (empty($_values['USE_MYSQL']) || $_values['USE_MYSQL'] == '0') {
        // Mode SQLite : rien à faire, le compose par défaut suffit
        $_eqLogic->restartDocker();
        return;
    }

    // Mode MariaDB : vérification que le schéma/user existent bien
    $host    = $_values['NPM_DB_HOST'];
    $user    = $_values['NPM_DB_USER'];
    $pass    = $_values['NPM_DB_PASSWORD'];
    $dbname  = $_values['NPM_DB_NAME'];

    try {
        $pdo = new PDO(
            "mysql:host={$host};port=3306;dbname={$dbname};charset=utf8",
            $user,
            $pass,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    } catch (Exception $e) {
        throw new Exception(
            "Impossible de se connecter à MariaDB avec les paramètres fournis. " .
            "Vérifiez que le schéma '{$dbname}' et l'utilisateur '{$user}' ont bien été créés manuellement.\n" .
            "Commandes SQL à exécuter en tant que root :\n" .
            "  CREATE DATABASE IF NOT EXISTS \`{$dbname}\`;\n" .
            "  CREATE USER '{$user}'@'%' IDENTIFIED BY 'votre_mot_de_passe';\n" .
            "  GRANT ALL PRIVILEGES ON \`{$dbname}\`.* TO '{$user}'@'%';\n" .
            "  FLUSH PRIVILEGES;\n\n" .
            "Erreur : " . $e->getMessage()
        );
    }

    // Connexion OK : on patch le compose à la volée pour ajouter les variables DB
    $composeFile = dirname(__FILE__) . '/nginx-proxy-manager.compose';
    $compose = file_get_contents($composeFile);

    $dbEnv = "      DB_MYSQL_HOST: '{$host}'\n" .
             "      DB_MYSQL_PORT: '3306'\n" .
             "      DB_MYSQL_USER: '{$user}'\n" .
             "      DB_MYSQL_PASSWORD: '{$pass}'\n" .
             "      DB_MYSQL_NAME: '{$dbname}'\n";

    // Injection après la ligne DISABLE_IPV6
    $compose = str_replace(
        "      DISABLE_IPV6: 'true'\n",
        "      DISABLE_IPV6: 'true'\n" . $dbEnv,
        $compose
    );

    file_put_contents($composeFile, $compose);

    $_eqLogic->restartDocker();
}