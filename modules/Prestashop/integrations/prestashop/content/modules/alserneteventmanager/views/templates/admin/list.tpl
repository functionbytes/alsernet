<!-- Botón para agregar un nuevo evento -->
<div class="panel-heading">
    <a href="{$add_event_url}" class="btn btn-default">
        <i class="icon-plus-sign"></i> {$smarty.const.LANG_ADD_NEW_EVENT}
    </a>
</div>

<!-- Aquí va el resto del listado de eventos -->
<table class="table table-bordered">
    <thead>
    <tr>
        <th>{$smarty.const.LANG_EVENT_ID}</th>
        <th>{$smarty.const.LANG_EVENT_TITLE}</th>
        <th>{$smarty.const.LANG_START_DATE}</th>
        <th>{$smarty.const.LANG_END_DATE}</th>
        <th>{$smarty.const.LANG_AVAILABLE}</th>
        <th class="action">&nbsp;</th>
    </tr>
    </thead>
    <tbody>
    {foreach from=$events item=event}
        <tr>
            <td>{$event.id_event}</td>
            <td>{$event.title}</td>
            <td>{$event.start_date}</td>
            <td>{$event.end_date}</td>
            <td>{$event.available}</td>
            <td>
                <!-- Botones de acción, como editar y eliminar -->
                <a href="{$link->getAdminLink('AdminModules')}&configure={$module_name}&controller=editEvent&id_event={$event.id_event}" class="btn btn-default">
                    <i class="icon-pencil"></i> {$smarty.const.LANG_EDIT}
                </a>
                <a href="{$link->getAdminLink('AdminModules')}&configure={$module_name}&controller=deleteEvent&id_event={$event.id_event}" class="btn btn-danger">
                    <i class="icon-trash"></i> {$smarty.const.LANG_DELETE}
                </a>
            </td>
        </tr>
    {/foreach}
    </tbody>
</table>