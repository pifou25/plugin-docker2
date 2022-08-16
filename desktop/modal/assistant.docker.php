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
    throw new \Exception(__('Equipement introuvable : ', __FILE__) . init('id'));
}
$template = docker2::getTemplate(init('template'));
if (count($template) == 0) {
    throw new \Exception(__('Template non trouvé : ', __FILE__) . init('template'));
}
?>
<legend>{{Description}}</legend>
<form class="form-horizontal">
    <fieldset>
        <?php echo $template['description']; ?>
    </fieldset>
</form>
<legend>{{Paramètres}}</legend>
<form class="form-horizontal" id="form_dockerTemplate">
    <fieldset>
        <?php
        foreach ($template['configuration'] as $id => $config) {
            echo '<div class="form-group">';
            echo '<label class="col-sm-3 control-label">' . $config['name'] . '</label>';
            echo '<div class="col-sm-7">';
            $default = '';
            if (isset($config['default'])) {
                $default = 'value="' . $config['default'] . '"';
            }
            switch ($config['type']) {
                case 'input':
                    if (isset($config['readonly']) && $config['readonly'] == 1) {
                        echo '<input class="form-control templateAttr" data-l1key="' . $id . '" readonly/>';
                    } else {
                        echo '<input class="form-control templateAttr" data-l1key="' . $id . '" ' . $default . ' />';
                    }
                    break;
                case 'number':
                    if (isset($config['readonly']) && $config['readonly'] == 1) {
                        echo '<input type="number" class="form-control templateAttr" data-l1key="' . $id . '" min="' . (isset($config['min']) ? $config['min'] : '') . '" max="' . (isset($config['max']) ? $config['max'] : '') . '" readonly/>';
                    } else {
                        echo '<input type="number" class="form-control templateAttr" data-l1key="' . $id . '" min="' . (isset($config['min']) ? $config['min'] : '') . '" max="' . (isset($config['max']) ? $config['max'] : '') . '" ' . $default . ' />';
                    }
                    break;
                case 'select':
                    if (isset($config['readonly']) && $config['readonly'] == 1) {
                        echo '<select class="form-control templateAttr" data-l1key="' . $id . '" disabled="true">';
                    } else {
                        echo '<select class="form-control templateAttr" data-l1key="' . $id . '">';
                    }
                    foreach ($config['values'] as $value) {
                        if (isset($config['default']) && $value['value'] == $config['default']) {
                            echo '<option value="' . $value['value'] . '" selected>' . $value['name'] . '</option>';
                        } else {
                            echo '<option value="' . $value['value'] . '">' . $value['name'] . '</option>';
                        }
                    }
                    echo '</select>';
                    break;
            }
            echo '</div>';
            echo '</div>';
        }
        ?>
    </fieldset>
</form>
<legend>{{Actions}}</legend>
<form class="form-horizontal">
    <fieldset>
        <div class="form-group">
            <label class="col-sm-3 control-label"></label>
            <div class="col-sm-7">
                <a class="btn btn-success" id="bt_dockerCreateTemplate"><i class="fas fa-check"></i> {{Valider}}</a>
            </div>
        </div>
    </fieldset>
</form>

<script>
    $('#bt_dockerCreateTemplate').off('click').on('click', function() {
        var values = $('#form_dockerTemplate').getValues('.templateAttr')[0];
        $.ajax({
            type: "POST",
            url: "plugins/docker2/core/ajax/docker2.ajax.php",
            data: {
                action: "applyTemplate",
                template: "<?php echo init('template') ?>",
                values: values,
                id: "<?php echo init('id') ?>"
            },
            dataType: 'json',
            error: function(request, status, error) {
                handleAjaxError(request, status, error);
            },
            success: function(data) {
                if (data.state != 'ok') {
                    $('#div_alert').showAlert({
                        message: data.result,
                        level: 'danger'
                    });
                    return;
                }
                window.location.reload();
            }
        });
    })
</script>