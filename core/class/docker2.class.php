<?php

/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

/* * ***************************Includes********************************* */
require_once __DIR__  . '/../../../../core/php/core.inc.php';

class docker2 extends eqLogic {
   /*     * *************************Attributs****************************** */


   /*     * ***********************Methode static*************************** */

   public static function execCmd($_cmd, $_docker_number = 1, $_format = "{{json . }}") {
      if ($_format != '') {
         $_cmd .= ' --format "{{json . }}" --no-trunc';
      }
      $config = config::byKey('docker_config_' . $_docker_number, 'docker2');
      if ($config['mode'] == 'local') {
         $output = null;
         $retval = null;
         exec($_cmd, $output, $retval);
         if ($retval != 0) {
            throw new Exception(__('Erreur d\'éxécution de la commande : ', __FILE__) . $_cmd . ' (' . $retval . ') => ' . json_encode($output));
         }
      } else if ($config['mode'] == 'ssh') {
         $connection = ssh2_connect($config['ip'], $config['port']);
         if ($connection === false) {
            throw new Exception(__('Impossible de se connecter sur :', __FILE__) . ' ' . $config['ip'] . ':' . $config['mode']);
         }
         $auth = @ssh2_auth_password($connection, $config['username'], $config['password']);
         if (false === $auth) {
            throw new Exception(__('Echec de l\'authentification SSH', __FILE__));
         }
         $stream = ssh2_exec($connection, $_cmd);
         stream_set_blocking($stream, true);
         $output =  stream_get_contents($stream);
      }
      if ($_format == "{{json . }}") {
         $return = array();
         foreach ($output as $line) {
            $return[] = json_decode($line, true);
         }
         return $return;
      }
      return implode("\n", $output);
   }

   public static function cron() {
      for ($i = 1; $i <= config::byKey('max_docker_number', "docker2"); $i++) {
         $config = config::byKey('docker_config_' . $i, 'docker2');
         if ($config['enable'] != 1 || $config['cron'] == '') {
            continue;
         }
         try {
            $c = new Cron\CronExpression(checkAndFixCron($config['cron']), new Cron\FieldFactory);
            if ($c->isDue()) {
               self::pull($i);
            }
         } catch (Exception $exc) {
            log::add('docker2', 'error', __('Expression cron non valide pour docker ', __FILE__) . $config['cron']);
         }
      }
   }


   public static function pull($_docker_number = 1) {
      $dockers = self::execCmd(system::getCmdSudo() . ' docker ps -a', $_docker_number);
      if (isset($dockers['Command'])) {
         $dockers = array($dockers);
      }
      $eqLogics = array();
      foreach ($dockers as $docker) {
         $eqLogic = self::byLogicalId($_docker_number . '::' . $docker['Names'], 'docker2');
         if (!is_object($eqLogic)) {
            $eqLogic = new eqLogic();
            $eqLogic->setLogicalId($_docker_number . '::' . $docker['Names']);
            $eqLogic->setName($docker['Names']);
            $eqLogic->setIsEnable(1);
            $eqLogic->setEqType_name('docker2');
            $eqLogic->setConfiguration('docker_number', $_docker_number);
            $eqLogic->setConfiguration('name', $docker['Names']);
         }
         $eqLogic->setConfiguration('id', $docker['ID']);
         $eqLogic->setConfiguration('command', $docker['Command']);
         $eqLogic->setConfiguration('image', $docker['Image']);
         $eqLogic->setConfiguration('createdAt', $docker['CreatedAt']);
         $eqLogic->setConfiguration('ports', $docker['Ports']);
         $eqLogic->setConfiguration('size', $docker['Size']);
         $eqLogic->setConfiguration('networks', $docker['Networks']);
         $eqLogic->setConfiguration('mounts', $docker['Mounts']);
         $eqLogic->save();

         $eqLogic->checkAndUpdateCmd('state', $docker['State']);

         $eqLogics[$docker['ID']] = $eqLogic;
      }
      $docker_stats = self::execCmd(system::getCmdSudo() . ' docker stats --no-stream -a', $_docker_number);
      foreach ($docker_stats as $docker_stat) {
         if (!isset($eqLogics[$docker_stat['ID']]) || !is_object($eqLogics[$docker_stat['ID']])) {
            continue;
         }
         $eqLogic = $eqLogics[$docker_stat['ID']];
         $eqLogic->checkAndUpdateCmd('cpu', (float) $docker_stat['CPUPerc']);
         $eqLogic->checkAndUpdateCmd('memory', (float) $docker_stat['MemPerc']);

         $net = explode('/', $docker_stat['NetIO']);
         $eqLogic->checkAndUpdateCmd('net_in', round((self::convertToUnit($net[0], 'M') - $eqLogic->getCache('stats::net_in', 0)) / (strtotime('now') - $eqLogic->getCache('stats::datetime', 0)), 2));
         $eqLogic->checkAndUpdateCmd('net_out', round((self::convertToUnit($net[1], 'M') - $eqLogic->getCache('stats::net_out', 0)) / (strtotime('now') - $eqLogic->getCache('stats::datetime', 0)), 2));

         $io = explode('/', $docker_stat['BlockIO']);
         $eqLogic->checkAndUpdateCmd('io_in', round((self::convertToUnit($io[1], 'M') - $eqLogic->getCache('stats::io_in', 0)) / (strtotime('now') - $eqLogic->getCache('stats::datetime', 0)), 2));
         $eqLogic->checkAndUpdateCmd('io_out', round((self::convertToUnit($io[1], 'M') - $eqLogic->getCache('stats::io_out', 0)) / (strtotime('now') - $eqLogic->getCache('stats::datetime', 0)), 2));

         $eqLogic->setCache(
            array(
               'stats::net_in' => self::convertToUnit($net[0], 'M'),
               'stats::net_out' => self::convertToUnit($net[1], 'M'),
               'stats::io_in' => self::convertToUnit($io[0], 'M'),
               'stats::io_out' => self::convertToUnit($io[1], 'M'),
               'stats::datetime' => strtotime('now')
            )
         );
      }

      foreach (eqLogic::byType('docker2', true) as $eqLogic) {
         if (isset($eqLogics[$eqLogic->getConfiguration('id')])) {
            continue;
         }
         $eqLogic->checkAndUpdateCmd('state', 'Not found');
         $eqLogic->checkAndUpdateCmd('cpu', 0);
         $eqLogic->checkAndUpdateCmd('memory', 0);
         $eqLogic->checkAndUpdateCmd('net_in', 0);
         $eqLogic->checkAndUpdateCmd('net_out', 0);
         $eqLogic->checkAndUpdateCmd('io_in', 0);
         $eqLogic->checkAndUpdateCmd('io_out', 0);
      }
   }

   public static function convertToUnit($_string, $_unit) {
      $_string = strtolower($_string);
      $return = (float) $_string;
      $coeff = 1;
      if (strpos($_string, 'gib') !== false || strpos($_string, 'gb') !== false) {
         $coeff = 1024 * 1024 * 1024;
      } elseif (strpos($_string, 'mib') !== false || strpos($_string, 'mb') !== false) {
         $coeff = 1024 * 1024;
      } elseif (strpos($_string, 'kib') !== false || strpos($_string, 'kb') !== false) {
         $coeff = 1024;
      }
      $return = $return * $coeff;
      if ($_unit == 'K') {
         return round($return / 1024, 2);
      }
      if ($_unit == 'M') {
         return round($return / 1024 / 1024, 2);
      }
      if ($_unit == 'G') {
         return round($return / 1024 / 1024 / 1024, 2);
      }
      return $return;
   }



   /*     * *********************Méthodes d'instance************************* */

   public function postSave() {
      $cmd = $this->getCmd(null, 'start');
      if (!is_object($cmd)) {
         $cmd = new docker2Cmd();
         $cmd->setLogicalId('start');
         $cmd->setName(__('Démarrer', __FILE__));
      }
      $cmd->setDisplay('icon', '<i class="fas fa-play"></i>');
      $cmd->setType('action');
      $cmd->setSubType('other');
      $cmd->setEqLogic_id($this->getId());
      $cmd->save();

      $cmd = $this->getCmd(null, 'stop');
      if (!is_object($cmd)) {
         $cmd = new docker2Cmd();
         $cmd->setLogicalId('stop');
         $cmd->setName(__('Arreter', __FILE__));
      }
      $cmd->setDisplay('icon', '<i class="fas fa-stop"></i>');
      $cmd->setType('action');
      $cmd->setSubType('other');
      $cmd->setEqLogic_id($this->getId());
      $cmd->save();


      $cmd = $this->getCmd(null, 'restart');
      if (!is_object($cmd)) {
         $cmd = new docker2Cmd();
         $cmd->setLogicalId('restart');
         $cmd->setName(__('Redémarrer', __FILE__));
      }
      $cmd->setDisplay('icon', '<i class="fas fa-sync"></i>');
      $cmd->setType('action');
      $cmd->setSubType('other');
      $cmd->setEqLogic_id($this->getId());
      $cmd->save();

      $cmd = $this->getCmd(null, 'remove');
      if (!is_object($cmd)) {
         $cmd = new docker2Cmd();
         $cmd->setLogicalId('remove');
         $cmd->setName(__('Supprimmer', __FILE__));
      }
      $cmd->setDisplay('icon', '<i class="fas fa-trash"></i>');
      $cmd->setType('action');
      $cmd->setSubType('other');
      $cmd->setEqLogic_id($this->getId());
      $cmd->save();

      $cmd = $this->getCmd(null, 'state');
      if (!is_object($cmd)) {
         $cmd = new docker2Cmd();
         $cmd->setLogicalId('state');
         $cmd->setName(__('Statut', __FILE__));
      }
      $cmd->setType('info');
      $cmd->setSubType('string');
      $cmd->setConfiguration('repeatEventManagement', 'never');
      $cmd->setEqLogic_id($this->getId());
      $cmd->save();

      $cmd = $this->getCmd(null, 'cpu');
      if (!is_object($cmd)) {
         $cmd = new docker2Cmd();
         $cmd->setLogicalId('cpu');
         $cmd->setName(__('CPU', __FILE__));
      }
      $cmd->setType('info');
      $cmd->setSubType('numeric');
      $cmd->setUnite('%');
      $cmd->setConfiguration('repeatEventManagement', 'never');
      $cmd->setEqLogic_id($this->getId());
      $cmd->save();

      $cmd = $this->getCmd(null, 'memory');
      if (!is_object($cmd)) {
         $cmd = new docker2Cmd();
         $cmd->setLogicalId('memory');
         $cmd->setName(__('Mémoire', __FILE__));
      }
      $cmd->setType('info');
      $cmd->setSubType('numeric');
      $cmd->setUnite('%');
      $cmd->setConfiguration('repeatEventManagement', 'never');
      $cmd->setEqLogic_id($this->getId());
      $cmd->save();

      $cmd = $this->getCmd(null, 'io_in');
      if (!is_object($cmd)) {
         $cmd = new docker2Cmd();
         $cmd->setLogicalId('io_in');
         $cmd->setName(__('IO in', __FILE__));
      }
      $cmd->setType('info');
      $cmd->setSubType('numeric');
      $cmd->setUnite('MB');
      $cmd->setConfiguration('repeatEventManagement', 'never');
      $cmd->setEqLogic_id($this->getId());
      $cmd->save();

      $cmd = $this->getCmd(null, 'io_out');
      if (!is_object($cmd)) {
         $cmd = new docker2Cmd();
         $cmd->setLogicalId('io_out');
         $cmd->setName(__('IO out', __FILE__));
      }
      $cmd->setType('info');
      $cmd->setSubType('numeric');
      $cmd->setUnite('MB');
      $cmd->setConfiguration('repeatEventManagement', 'never');
      $cmd->setEqLogic_id($this->getId());
      $cmd->save();

      $cmd = $this->getCmd(null, 'net_in');
      if (!is_object($cmd)) {
         $cmd = new docker2Cmd();
         $cmd->setLogicalId('net_in');
         $cmd->setName(__('Réseaux in', __FILE__));
      }
      $cmd->setType('info');
      $cmd->setSubType('numeric');
      $cmd->setUnite('MB');
      $cmd->setConfiguration('repeatEventManagement', 'never');
      $cmd->setEqLogic_id($this->getId());
      $cmd->save();

      $cmd = $this->getCmd(null, 'net_out');
      if (!is_object($cmd)) {
         $cmd = new docker2Cmd();
         $cmd->setLogicalId('net_out');
         $cmd->setName(__('Réseaux out', __FILE__));
      }
      $cmd->setUnite('MB');
      $cmd->setType('info');
      $cmd->setSubType('numeric');
      $cmd->setConfiguration('repeatEventManagement', 'never');
      $cmd->setEqLogic_id($this->getId());
      $cmd->save();

      $cmd->setConfiguration('repeatEventManagement', 'never');
      $cmd->setEqLogic_id($this->getId());
      $cmd->save();

      if ($this->getConfiguration('create::mode') != 'manual') {
         $cmd = $this->getCmd(null, 'receate');
         if (!is_object($cmd)) {
            $cmd = new docker2Cmd();
            $cmd->setLogicalId('receate');
            $cmd->setName(__('Recréer', __FILE__));
         }
         $cmd->setDisplay('icon', '<i class="fas fa-building"></i>');
         $cmd->setType('action');
         $cmd->setSubType('other');
         $cmd->setEqLogic_id($this->getId());
         $cmd->save();
      } else {
         $cmd = $this->getCmd(null, 'receate');
         if (is_object($cmd)) {
            $cmd->remove();
         }
      }

      if ($this->getLogicalId() == '') {
         $this->create();
      }
   }

   public function stop() {
      self::execCmd(system::getCmdSudo() . ' docker stop ' . $this->getConfiguration('id'), $this->getConfiguration('docker_number'), null);
   }

   public function start() {
      self::execCmd(system::getCmdSudo() . ' docker start ' . $this->getConfiguration('id'), $this->getConfiguration('docker_number'), null);
   }

   public function restart() {
      self::execCmd(system::getCmdSudo() . ' docker restart ' . $this->getConfiguration('id'), $this->getConfiguration('docker_number'), null);
   }

   public function create() {
      switch ($this->getConfiguration('create::mode')) {
         case 'jeedom_run':
            if ($this->getConfiguration('create::run') == '') {
               throw new Exception(__('Vous ne pouvez lancer la création d\'un docker depuis Jeedom sans commande de création', __FILE__));
            }
            $cmd = system::getCmdSudo() . ' docker run ';
            $cmd .= ' ' . str_replace('docker run', '', str_replace(system::getCmdSudo() . ' docker run', '', $this->getConfiguration('create::run')));
            if (strpos($cmd, ' -d') === false) {
               throw new Exception(__('Il n\'est pas possible de lancer un contenaire depuis Jeedom sans l\'option deamon', __FILE__));
            }
            self::execCmd($cmd, $this->getConfiguration('docker_number', 1), null);
            break;
         case 'jeedom_compose':
            if ($this->getConfiguration('create::compose') == '') {
               throw new Exception(__('Vous ne pouvez lancer la création d\'un docker depuis Jeedom sans docker compose', __FILE__));
            }
            $filename = '/tmp/' . bin2hex(random_bytes(10)) . '.yml';
            file_put_contents($filename, $this->getConfiguration('create::compose'));
            self::execCmd(system::getCmdSudo() . ' docker-compose -f ' . $filename . ' up -d --force-recreate', $this->getConfiguration('docker_number', 1), null);
            unlink($filename);
            break;
         default:
            throw new Exception(__('La création de ce docker n\'est pas gérée par Jeedom', __FILE__));
            break;
      }

      $this->setLogicalId($this->getConfiguration('docker_number', 1) . '::' . $this->getConfiguration('name'));
      $this->setConfiguration('docker_number', $this->getConfiguration('docker_number', 1));
      $this->save(true);
   }

   public function rm() {
      self::execCmd(system::getCmdSudo() . ' docker rm -f ' . $this->getConfiguration('id'), $this->getConfiguration('docker_number'), null);
   }

   public function logs() {
      return self::execCmd(system::getCmdSudo() . ' docker logs -t -n 100 ' . $this->getConfiguration('id'), $this->getConfiguration('docker_number'), null);
   }

   /*     * **********************Getteur Setteur*************************** */
}

class docker2Cmd extends cmd {
   /*     * *************************Attributs****************************** */


   /*     * ***********************Methode static*************************** */


   /*     * *********************Methode d'instance************************* */


   public function execute($_options = array()) {
      if ($this->getType() == 'info') {
         return;
      }
      $eqLogic = $this->getEqLogic();
      if ($this->getLogicalId() == 'start') {
         $eqLogic->start();
      } else if ($this->getLogicalId() == 'stop') {
         $eqLogic->stop();
      } else if ($this->getLogicalId() == 'restart') {
         $eqLogic->restart();
      } else if ($this->getLogicalId() == 'receate') {
         $eqLogic->rm();
         sleep(5);
         $eqLogic->create();
      } else if ($this->getLogicalId() == 'remove') {
         $eqLogic->rm();
      }
      docker2::pull();
   }

   /*     * **********************Getteur Setteur*************************** */
}
