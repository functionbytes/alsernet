<?php

namespace Modules\Campaign\Models;

/**
 * Alias por compatibilidad con código heredado de Acelle que referencia
 * `Subscriber` sin el prefijo del módulo. Hereda toda la lógica de
 * CampaignSubscriber. En código nuevo usar CampaignSubscriber directamente.
 *
 * @deprecated Usa CampaignSubscriber.
 */
class Subscriber extends CampaignSubscriber
{
    protected $table = 'campaign_subscribers';
}
