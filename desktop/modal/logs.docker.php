<?php
/* This file is part of Plugin zigbee for jeedom.
*
* Plugin zigbee for jeedom is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* Plugin zigbee for jeedom is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with Plugin zigbee for jeedom. If not, see <http://www.gnu.org/licenses/>.
*/
if (!isConnect('admin')) {
    throw new Exception('401 Unauthorized');
}
$eqLogic = docker2::byId(init('id'));
if (!is_object($eqLogic)) {
    throw new \Exception(__('Equipement introuvable', __FILE__) . ' : ' . init('id'));
}
echo '<a class="btn btn-default pull-right" id="bt_refreshLog"><i class="fas fa-sync"></i></a><br><br>';
echo '<pre id="pre_docker2Logs">';
echo $eqLogic->logs();
echo '</pre>';
?>


<script>
    $('#bt_refreshLog').off('click').on('click', function() {
        $.ajax({
            type: "POST",
            url: "plugins/docker2/core/ajax/docker2.ajax.php",
            data: {
                action: "logs",
                id: <?php echo init('id') ?>
            },
            dataType: 'json',
            error: function(error) {
                $.fn.showAlert({
                    message: error.message,
                    level: 'danger'
                })
            },
            success: function(data) {
                $('#pre_docker2Logs').empty().append(data.result)
            }
        })
    })
</script>
