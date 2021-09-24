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
      return $output;
   }


   public static function pull($_docker_number = 1) {
      $dockers = self::execCmd(system::getCmdSudo() . ' docker ps -a', $_docker_number);
      if (isset($dockers['Command'])) {
         $dockers = array($dockers);
      }
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
         $eqLogic->checkAndUpdateCmd('status', $docker['Status']);
      }
   }



   /*     * *********************Méthodes d'instance************************* */

   public function postSave() {
      $cmd = $this->getCmd(null, 'start');
      if (!is_object($cmd)) {
         $cmd = new docker2Cmd();
         $cmd->setLogicalId('start');
         $cmd->setName(__('Démarrer', __FILE__));
      }
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

      if ($this->getConfiguration('create::mode') != 'manual') {
         $cmd = $this->getCmd(null, 'receate');
         if (!is_object($cmd)) {
            $cmd = new docker2Cmd();
            $cmd->setLogicalId('receate');
            $cmd->setName(__('Recréer', __FILE__));
         }
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
      }
      docker2::pull();
   }

   /*     * **********************Getteur Setteur*************************** */
}
