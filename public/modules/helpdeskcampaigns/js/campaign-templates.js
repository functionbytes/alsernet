/**
 * HelpdeskCampaigns — campaign-templates.js
 * Selector de plantillas: abre el modal "Nueva campaña" con la plantilla elegida.
 * Usado desde atributos onclick en managers/campaigns/templates.blade.php.
 */
function showCreateModal(templateId) {
    document.getElementById('template-id-input').value = templateId;
    new bootstrap.Modal(document.getElementById('createCampaignModal')).show();
}
