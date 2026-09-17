/**
 * HelpdeskCampaigns — campaign-templates.js
 * Selector de plantillas: abre el modal "Nueva campaña" con la plantilla elegida.
 * Usado desde atributos onclick en managers/campaigns/templates.blade.php.
 */
function showCreateModal(templateId) {
    $('#template-id-input').val(templateId);
    new bootstrap.Modal($('#createCampaignModal')[0]).show();
}
