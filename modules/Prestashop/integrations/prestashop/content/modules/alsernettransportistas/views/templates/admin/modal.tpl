<div class="modal fade" id="helpModal" tabindex="-1" role="dialog" aria-labelledby="helpModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="helpModalLabel">{l s='¿Cómo funciona este formulario?' d='Admin.Catalog.Help'}</h5>
            </div>
            <div class="modal-body">
                <h6><strong>Objetivo del método</strong></h6>
                <p>Este método determina qué transportista debe asignarse a un pedido en función de la dirección de entrega y otros factores, como el país, el método de pago o el tipo de producto.</p>

                <h6><strong>Funcionamiento general</strong></h6>
                <ol>
                    <li>
                        <strong>Direcciones específicas:</strong>
                        <ul>
                            <li>Si el país de entrega corresponde a España no peninsular (Baleares, Canarias, Ceuta o Melilla), se asigna un transportista específico (<code>{Configuration::get('ALSERNET_TRANSPORTISTA_ESPANIA_NO_PENINSULAR')}</code>).</li>
                        </ul>
                    </li>
                    <li>
                        <strong>Método de pago:</strong>
                        <ul>
                            <li>Si el método de pago es contra reembolso, las reglas cambian ligeramente: Para España y Portugal, se asigna el transportista <code>{Configuration::get('ALSERNET_TRANSPORTISTA_ESPANIA_Y_PORTUGAL_CONTRA_REEMBOLSO')}</code>.</li>
                        </ul>
                    </li>
                    <li>
                        <strong>Envíos a España y Portugal:</strong>
                        <ul>
                            <li>Galicia (códigos postales que empiezan con 15, 36, 32 o 27) tiene un transportista específico: <code>{Configuration::get('ALSERNET_TRANSPORTISTA_ESPANIA_GALICIA')}</code>.</li>
                            <li>El resto de la península (España y Portugal) usa: <code>{Configuration::get('ALSERNET_TRANSPORTISTA_ESPANIA_Y_PORTUGAL')}</code>.</li>
                            <li>Para otros lugares de España, se usa: <code>{Configuration::get('ALSERNET_TRANSPORTISTA_RESTO_DE_ESPANIA')}</code>.</li>
                        </ul>
                    </li>
                    <li>
                        <strong>Envíos a Europa:</strong>
                        <ul>
                            <li>Europa con SEUR: <code>{Configuration::get('ALSERNET_TRANSPORTISTA_EUROPA_SI_SEUR_ID')}</code>.</li>
                            <li>Europa sin SEUR: <code>{Configuration::get('ALSERNET_TRANSPORTISTA_EUROPA_NO_SEUR_ID')}</code>.</li>
                        </ul>
                    </li>
                    <li>
                        <strong>Resto del mundo:</strong>
                        <ul>
                            <li>Para cualquier país que no encaje en las categorías anteriores, se asigna el transportista: <code>{Configuration::get('ALSERNET_TRANSPORTISTA_RESTO_DEL_MUNDO')}</code>.</li>
                        </ul>
                    </li>
                    <li>
                        <strong>Mondial Relay / InPost:</strong>
                        <ul>
                            <li>Si el pedido utiliza un punto de recogida Mondial Relay o InPost, se asigna: <code>{Configuration::get('ALSERNET_TRANSPORTISTA_ES_INPOST')}</code>.</li>
                        </ul>
                    </li>
                    <li>
                        <strong>Tipos de productos:</strong>
                        <ul>
                            <li>Armas: <code>{Configuration::get('ALSERNET_TRANSPORTISTA_ES_ARMA')}</code>.</li>
                            <li>Cartuchos: <code>{Configuration::get('ALSERNET_TRANSPORTISTA_ES_CARTUCHO')}</code>.</li>
                            <li>Tarjetas de regalo: <code>{Configuration::get('ALSERNET_TRANSPORTISTA_ES_TARJETAS_REGALO')}</code>.</li>
                        </ul>
                    </li>
                    <li>
                        <strong>Recogidas en tienda:</strong>
                        <ul>
                            <li>Coruña: <code>{Configuration::get('ALSERNET_TRANSPORTISTA_TIENDA_CORUNA')}</code>.</li>
                            <li>Capitan Haya: <code>{Configuration::get('ALSERNET_TRANSPORTISTA_TIENDA_CAPITAN_HAYA')}</code>.</li>
                            <li>Diego de Leon: <code>{Configuration::get('ALSERNET_TRANSPORTISTA_TIENDA_DIEGO_DE_LEON')}</code>.</li>
                        </ul>
                    </li>
                </ol>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">{l s='Cerrar' d='Admin.Actions'}</button>
            </div>
        </div>
    </div>
</div>
