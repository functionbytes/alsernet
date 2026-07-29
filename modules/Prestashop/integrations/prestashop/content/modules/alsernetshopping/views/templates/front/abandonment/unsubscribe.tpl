{*
* Template para página de unsubscribe de emails de carrito abandonado
* AlsernetShopping Module
*}

{extends file='page.tpl'}

{block name='page_header_container'}{/block}

{block name='page_content_container'}
<div class="unsubscribe-page">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8 col-xl-6">
                <div class="unsubscribe-card">
                    {if $success}
                        <div class="unsubscribe-success">
                            <div class="success-icon">
                                <i class="material-icons">check_circle</i>
                            </div>
                            <h2 class="success-title">¡Listo!</h2>
                            <p class="success-message">{$message}</p>
                            
                            <div class="success-details">
                                <h4>¿Qué significa esto?</h4>
                                <ul>
                                    <li>✅ No recibirás más emails sobre carritos abandonados</li>
                                    <li>✅ Tu configuración se ha guardado correctamente</li>
                                    <li>✅ Puedes seguir comprando normalmente en {$shop_name}</li>
                                </ul>
                            </div>
                            
                            <div class="action-buttons">
                                <a href="{$link->getPageLink('index')}" class="btn btn-primary">
                                    <i class="material-icons">home</i>
                                    Ir a la tienda
                                </a>
                                <a href="{$link->getPageLink('my-account')}" class="btn btn-outline-secondary">
                                    <i class="material-icons">account_circle</i>
                                    Mi cuenta
                                </a>
                            </div>
                        </div>
                    {else}
                        <div class="unsubscribe-error">
                            <div class="error-icon">
                                <i class="material-icons">error</i>
                            </div>
                            <h2 class="error-title">Oops, algo salió mal</h2>
                            <p class="error-message">{$message}</p>
                            
                            <div class="error-help">
                                <h4>¿Qué puedes hacer?</h4>
                                <ul>
                                    <li>🔄 Inténtalo de nuevo en unos minutos</li>
                                    <li>📧 Contacta con nuestro equipo de soporte</li>
                                    <li>💬 Utiliza el chat en vivo si está disponible</li>
                                </ul>
                            </div>
                            
                            <div class="action-buttons">
                                <a href="javascript:history.back()" class="btn btn-secondary">
                                    <i class="material-icons">arrow_back</i>
                                    Volver
                                </a>
                                <a href="mailto:{Configuration::get('PS_SHOP_EMAIL')}" class="btn btn-primary">
                                    <i class="material-icons">email</i>
                                    Contactar soporte
                                </a>
                            </div>
                        </div>
                    {/if}
                    
                    <div class="unsubscribe-footer">
                        <div class="footer-info">
                            <h5>Preferencias de email</h5>
                            <p>Si cambias de opinión, siempre puedes reactivar las notificaciones desde tu cuenta de usuario.</p>
                        </div>
                        
                        <div class="footer-links">
                            <a href="{$link->getPageLink('contact')}">Contacto</a>
                            <span>|</span>
                            <a href="{$link->getCMSLink(3)}">Política de privacidad</a>
                            <span>|</span>
                            <a href="{$link->getPageLink('index')}">Tienda</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
{/block}

{block name='page_footer_container'}{/block}

{block name='stylesheets'}
{$parent_stylesheets}
<style>
    .unsubscribe-page {
        min-height: 70vh;
        padding: 60px 0;
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    }
    
    .unsubscribe-card {
        background: white;
        border-radius: 20px;
        padding: 40px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        text-align: center;
    }
    
    .success-icon, .error-icon {
        margin-bottom: 20px;
    }
    
    .success-icon i {
        font-size: 64px;
        color: #28a745;
    }
    
    .error-icon i {
        font-size: 64px;
        color: #dc3545;
    }
    
    .success-title, .error-title {
        color: #333;
        margin-bottom: 20px;
        font-weight: 600;
    }
    
    .success-message, .error-message {
        font-size: 18px;
        color: #666;
        margin-bottom: 30px;
        line-height: 1.6;
    }
    
    .success-details, .error-help {
        background: #f8f9fa;
        border-radius: 10px;
        padding: 25px;
        margin: 30px 0;
        text-align: left;
    }
    
    .success-details h4, .error-help h4 {
        color: #333;
        margin-bottom: 15px;
        font-size: 16px;
        font-weight: 600;
    }
    
    .success-details ul, .error-help ul {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    
    .success-details li, .error-help li {
        padding: 8px 0;
        font-size: 14px;
        color: #555;
    }
    
    .action-buttons {
        display: flex;
        gap: 15px;
        justify-content: center;
        margin: 30px 0;
        flex-wrap: wrap;
    }
    
    .action-buttons .btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 12px 24px;
        border-radius: 25px;
        text-decoration: none;
        font-weight: 500;
        transition: all 0.3s ease;
    }
    
    .action-buttons .btn-primary {
        background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
        color: white;
        border: none;
    }
    
    .action-buttons .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0,123,255,0.4);
        color: white;
        text-decoration: none;
    }
    
    .action-buttons .btn-outline-secondary {
        background: transparent;
        color: #6c757d;
        border: 2px solid #6c757d;
    }
    
    .action-buttons .btn-outline-secondary:hover {
        background: #6c757d;
        color: white;
        text-decoration: none;
    }
    
    .action-buttons .btn-secondary {
        background: #6c757d;
        color: white;
        border: none;
    }
    
    .action-buttons .btn-secondary:hover {
        background: #545b62;
        color: white;
        text-decoration: none;
    }
    
    .unsubscribe-footer {
        border-top: 1px solid #eee;
        margin-top: 40px;
        padding-top: 30px;
    }
    
    .footer-info {
        margin-bottom: 20px;
    }
    
    .footer-info h5 {
        color: #333;
        font-size: 16px;
        margin-bottom: 10px;
        font-weight: 600;
    }
    
    .footer-info p {
        color: #666;
        font-size: 14px;
        margin: 0;
    }
    
    .footer-links {
        font-size: 14px;
    }
    
    .footer-links a {
        color: #007bff;
        text-decoration: none;
        margin: 0 10px;
    }
    
    .footer-links a:hover {
        text-decoration: underline;
    }
    
    .footer-links span {
        color: #ccc;
        margin: 0 5px;
    }
    
    @media (max-width: 768px) {
        .unsubscribe-page {
            padding: 30px 0;
        }
        
        .unsubscribe-card {
            padding: 25px;
            margin: 0 15px;
        }
        
        .action-buttons {
            flex-direction: column;
            align-items: center;
        }
        
        .action-buttons .btn {
            width: 100%;
            max-width: 280px;
            justify-content: center;
        }
        
        .success-icon i, .error-icon i {
            font-size: 48px;
        }
        
        .success-title, .error-title {
            font-size: 24px;
        }
        
        .success-message, .error-message {
            font-size: 16px;
        }
    }
</style>
{/block}