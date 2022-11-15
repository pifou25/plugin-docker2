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

require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';
include_file('core', 'authentification', 'php');
if (!isConnect()) {
  include_file('desktop', '404', 'php');
  die();
}
?>
<form class="form-horizontal">
  <fieldset>
    <?php for ($i = 1; $i <= config::byKey('max_docker_number', "docker2"); $i++) { ?>
      <div class="col-lg-6">
        <legend><i class="fas fa-broadcast-tower"></i> {{Docker}} <?php echo $i ?></legend>
        <div class="form-group">
          <label class="col-md-3 control-label">{{Activer}}
            <sup><i class="fas fa-question-circle tooltips" title="{{Cocher la case pour activer le docker}} <?php echo $i ?>"></i></sup>
          </label>
          <div class="col-md-1">
            <input type="checkbox" class="configKey" data-l1key="docker_config_<?php echo $i ?>" data-l2key="enable">
          </div>
        </div>
        <br>
        <div id="docker_number_<?php echo $i ?>" style="display:none;">
          <div class="form-group">
            <label class="col-md-5 control-label">{{Nom}}</label>
            <div class="col-md-6">
              <input class="configKey form-control" data-l1key="docker_config_<?php echo $i ?>" data-l2key="name">
            </div>
          </div>
          <div class="form-group">
            <label class="col-md-5 control-label">{{Mode}}</label>
            <div class="col-md-6">
              <select class="configKey form-control" data-l1key="docker_config_<?php echo $i ?>" data-l2key="mode">
                <option value="local">{{Local}}</option>
              </select>
            </div>
          </div>
          <div class="form-group">
            <label class="col-md-5 control-label">{{Fréquence de mise à jour}}</label>
            <div class="col-md-6">
              <div class="input-group">
                <input class="configKey form-control" data-l1key="docker_config_<?php echo $i ?>" data-l2key="cron">
                <span class="input-group-btn">
                  <a class="btn btn-default jeeHelper" data-helper="cron"><i class="fas fa-question-circle"></i></a>
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    <?php } ?>
  </fieldset>
</form>
<script>
  <?php for ($i = 1; $i <= config::byKey('max_docker_number', "docker2"); $i++) { ?>
    $('.configKey[data-l1key="docker_config_<?php echo $i ?>"][data-l2key=enable]').off('change').on('change', function() {
      if ($(this).value() == 0) {
        $('#docker_number_<?php echo $i ?>').hide()
      } else {
        $('#docker_number_<?php echo $i ?>').show()
      }
    })
  <?php } ?>
</script>
