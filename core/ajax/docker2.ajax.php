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

try {
  require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
  include_file('core', 'authentification', 'php');

  if (!isConnect('admin')) {
    throw new Exception(__('401 - Accès non autorisé', __FILE__));
  }

  ajax::init(array('backupupload'));

  if (init('action') == 'sync') {
    docker2::pull();
    ajax::success();
  }

  if (init('action') == 'logs') {
    $eqLogic = docker2::byId(init('id'));
    if (!is_object($eqLogic)) {
      throw new \Exception(__('Equipement introuvable : ', __FILE__) . init('id'));
    }
    ajax::success($eqLogic->logs());
  }

  if (init('action') == 'backup') {
    $eqLogic = docker2::byId(init('id'));
    if (!is_object($eqLogic)) {
      throw new \Exception(__('Equipement introuvable : ', __FILE__) . init('id'));
    }
    ajax::success($eqLogic->backupDocker());
  }

  if (init('action') == 'restore') {
    $eqLogic = docker2::byId(init('id'));
    if (!is_object($eqLogic)) {
      throw new \Exception(__('Equipement introuvable : ', __FILE__) . init('id'));
    }
    ajax::success($eqLogic->restore());
  }

  if (init('action') == 'backupupload') {
    unautorizedInDemo();
    $uploaddir = __DIR__ . '/../../data/backup';
    if (!file_exists($uploaddir)) {
      mkdir($uploaddir);
    }
    if (!file_exists($uploaddir)) {
      throw new Exception(__('Répertoire de téléversement non trouvé : ', __FILE__) . $uploaddir);
    }
    if (!isset($_FILES['file'])) {
      throw new Exception(__('Aucun fichier trouvé. Vérifiez le paramètre PHP (post size limit)', __FILE__));
    }
    $extension = strtolower(strrchr($_FILES['file']['name'], '.'));
    if (!in_array($extension, array('.gz'))) {
      throw new Exception(__('Extension du fichier non valide (autorisé .tar.gz) : ', __FILE__) . $extension);
    }
    if (filesize($_FILES['file']['tmp_name']) > 100000000) {
      throw new Exception(__('Le fichier est trop gros (maximum 100Mo)', __FILE__));
    }
    if (!move_uploaded_file($_FILES['file']['tmp_name'], $uploaddir . '/' . $_FILES['file']['name'])) {
      throw new Exception(__('Impossible de déplacer le fichier temporaire', __FILE__));
    }
    if (!file_exists($uploaddir . '/' . $_FILES['file']['name'])) {
      throw new Exception(__('Impossible de téléverser le fichier (limite du serveur web ?)', __FILE__));
    }
    ajax::success();
  }

  throw new Exception(__('Aucune méthode correspondante à : ', __FILE__) . init('action'));
  /*     * *********Catch exeption*************** */
} catch (Exception $e) {
  ajax::error(displayException($e), $e->getCode());
}
