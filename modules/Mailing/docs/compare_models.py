#!/usr/bin/env python3
"""
Script para comparar modelos de Acelle con modelos migrados
"""

# Lista completa de modelos de Acelle (107 modelos)
ACELLE_MODELS = [
    "Admin", "AdminGroup", "Attachment", "Attribute", "AutoTrigger", "Automation2",
    "BillingAddress", "Blacklist", "BounceHandler", "BounceLog", "Campaign",
    "CampaignLink", "CampaignWebhook", "CampaignsListsSegment", "Category",
    "ClickLog", "Contact", "Country", "Currency", "Customer", "CustomerGroup",
    "CustomerGroupSendingServer", "DeliveryHandler", "Email", "EmailLink",
    "EmailVerificationServer", "EmailWebhook", "FailedJob", "FeedbackLog",
    "FeedbackLoopHandler", "Field", "FieldOption", "Form", "Funnel", "Invoice",
    "InvoiceChangePlan", "InvoiceItem", "InvoiceNewSubscription",
    "InvoiceRenewSubscription", "IpLocation", "Job", "JobMonitor", "Language",
    "Layout", "Lazada", "Log", "MailList", "MailListsSendingServer",
    "Notification", "OpenLog", "Order", "Page", "Plan", "PlanGeneral",
    "PlansEmailVerificationServer", "PlansSendingServer", "Plugin", "Product",
    "ProductAttribute", "Reply", "Segment", "SegmentCondition", "Sender",
    "SendingDomain", "SendingServer", "SendingServerAmazon",
    "SendingServerAmazonApi", "SendingServerAmazonSmtp",
    "SendingServerBlastengine", "SendingServerBlastengineApi",
    "SendingServerBlastengineSmtp", "SendingServerElasticEmail",
    "SendingServerElasticEmailApi", "SendingServerElasticEmailSmtp",
    "SendingServerMailgun", "SendingServerMailgunApi",
    "SendingServerMailgunSmtp", "SendingServerSendGrid",
    "SendingServerSendGridApi", "SendingServerSendGridSmtp",
    "SendingServerSendmail", "SendingServerSmtp", "SendingServerSparkPost",
    "SendingServerSparkPostApi", "SendingServerSparkPostSmtp", "Setting",
    "Source", "SubAccount", "SubAccountSendGrid", "Subscriber",
    "SubscriberField", "Subscription", "SubscriptionLog",
    "SubscriptionsEmailVerificationServer", "Template", "TemplateCategory",
    "TemplatesCategory", "Timeline", "TrackingDomain", "TrackingLog",
    "Transaction", "UnsubscribeLog", "User", "UserActivation", "Website",
    "WooCommerce", "WpPost"
]

# Modelos ya migrados (48 modelos)
MIGRATED_MODELS = [
    "ActivityLog", "ApiBatch", "Automation", "Bounce", "BounceHandler",
    "BounceLog", "BulkEmailSending", "Campaign", "CampaignAnalytics",
    "CampaignFolder", "CampaignLink", "CampaignStatus", "CampaignWebhook",
    "CronjobSetting", "CustomField", "EmailTemplate", "EmailValidation",
    "EmailVerificationResult", "EmailVerificationServer", "FeedbackLog",
    "FeedbackLoopHandler", "Field", "FieldOption", "GeneralSetting", "Group",
    "ImportJob", "Language", "Layout", "Lists", "MailerSetting", "MailingGroup",
    "MediaFile", "MediaFolder", "ResponseLog", "Segment", "SegmentCondition",
    "SendingServer", "Setting", "SmsSentMessage", "SmsTransactional",
    "SubAccount", "Subscriber", "SubscriberField", "Template", "TemplateForm",
    "UnsubscribeEvent", "UrlSetting", "User", "Webhook"
]

# Modelos que NO deben migrarse (usar sistema existente)
DO_NOT_MIGRATE = [
    "User",  # Usar App\Models\User
    "Admin",  # Usar sistema de roles de Alsernet
    "AdminGroup",  # Usar Spatie Permissions
    "Customer",  # Usar App\Models\User con roles
    "CustomerGroup",  # Usar grupos/roles existentes
]

# Modelos a EVALUAR (según necesidades)
EVALUATE_MODELS = [
    "Plan", "PlanGeneral", "Subscription", "SubscriptionLog",
    "SubscriptionsEmailVerificationServer",
    "Invoice", "InvoiceChangePlan", "InvoiceItem", "InvoiceNewSubscription",
    "InvoiceRenewSubscription", "Transaction", "BillingAddress",
    "Product", "ProductAttribute", "Order",
    "Funnel", "WooCommerce", "Lazada", "WpPost", "Website",
]

def normalize_name(name):
    """Normaliza nombres para comparación"""
    # Mapeo de nombres que pueden variar
    mappings = {
        "MailList": "Lists",
        "Automation2": "Automation",
        "Email": "EmailTemplate",  # Podría ser
        # Agregar más según sea necesario
    }
    return mappings.get(name, name)

def categorize_models():
    """Categoriza los modelos de Acelle"""

    # Normalizar nombres migrados para comparación
    normalized_migrated = {normalize_name(m): m for m in MIGRATED_MODELS}

    # Clasificar modelos
    migrated = []
    not_migrated_intentional = []
    to_evaluate = []
    missing = []

    for acelle_model in ACELLE_MODELS:
        normalized = normalize_name(acelle_model)

        if acelle_model in DO_NOT_MIGRATE:
            not_migrated_intentional.append(acelle_model)
        elif acelle_model in EVALUATE_MODELS:
            to_evaluate.append(acelle_model)
        elif normalized in normalized_migrated or acelle_model in MIGRATED_MODELS:
            # Encontrar el nombre migrado correspondiente
            migrated_name = normalized_migrated.get(normalized, acelle_model)
            migrated.append({
                'acelle': acelle_model,
                'mailing': migrated_name,
                'exact_match': acelle_model == migrated_name
            })
        else:
            missing.append(acelle_model)

    return {
        'migrated': migrated,
        'missing': missing,
        'do_not_migrate': not_migrated_intentional,
        'evaluate': to_evaluate
    }

def print_report():
    """Imprime el reporte completo"""
    categories = categorize_models()

    total_acelle = len(ACELLE_MODELS)
    total_migrated = len(categories['migrated'])
    total_missing = len(categories['missing'])
    total_do_not = len(categories['do_not_migrate'])
    total_evaluate = len(categories['evaluate'])

    print("=" * 80)
    print(" REPORTE DE VERIFICACIÓN DE MODELOS ACELLE → MAILING MODULE")
    print("=" * 80)
    print()

    print(f"📊 RESUMEN:")
    print(f"   Total de modelos en Acelle:           {total_acelle}")
    print(f"   ✅ Modelos migrados:                   {total_migrated}")
    print(f"   ❌ Modelos faltantes (para migrar):    {total_missing}")
    print(f"   ⚠️  Modelos NO migrar (usar Alsernet): {total_do_not}")
    print(f"   🤔 Modelos a evaluar:                  {total_evaluate}")
    print()

    percentage_migrated = (total_migrated / (total_acelle - total_do_not - total_evaluate)) * 100
    print(f"   Progreso de migración: {percentage_migrated:.1f}%")
    print()

    # Modelos migrados
    print("=" * 80)
    print("✅ MODELOS MIGRADOS ({})".format(total_migrated))
    print("=" * 80)
    for i, model in enumerate(sorted(categories['migrated'], key=lambda x: x['acelle']), 1):
        match_symbol = "=" if model['exact_match'] else "→"
        print(f"{i:3d}. {model['acelle']:<35} {match_symbol} {model['mailing']}")
    print()

    # Modelos faltantes
    print("=" * 80)
    print("❌ MODELOS FALTANTES - DEBEN MIGRARSE ({})".format(total_missing))
    print("=" * 80)
    if categories['missing']:
        for i, model in enumerate(sorted(categories['missing']), 1):
            print(f"{i:3d}. {model}")
    else:
        print("   ¡Todos los modelos críticos han sido migrados!")
    print()

    # Modelos que no deben migrarse
    print("=" * 80)
    print("⚠️  MODELOS NO MIGRAR - Usar Sistema Alsernet ({})".format(total_do_not))
    print("=" * 80)
    for i, model in enumerate(sorted(categories['do_not_migrate']), 1):
        print(f"{i:3d}. {model:<35} → Usar sistema existente")
    print()

    # Modelos a evaluar
    print("=" * 80)
    print("🤔 MODELOS A EVALUAR - Según Necesidad ({})".format(total_evaluate))
    print("=" * 80)
    for i, model in enumerate(sorted(categories['evaluate']), 1):
        category = ""
        if model in ["Plan", "PlanGeneral", "Subscription", "SubscriptionLog",
                     "SubscriptionsEmailVerificationServer"]:
            category = "(Billing/Planes)"
        elif model in ["Invoice", "InvoiceChangePlan", "InvoiceItem",
                       "InvoiceNewSubscription", "InvoiceRenewSubscription",
                       "Transaction", "BillingAddress"]:
            category = "(Facturación)"
        elif model in ["Product", "ProductAttribute", "Order"]:
            category = "(E-commerce)"
        elif model in ["Funnel", "WooCommerce", "Lazada", "WpPost", "Website"]:
            category = "(Integraciones)"

        print(f"{i:3d}. {model:<35} {category}")
    print()

    print("=" * 80)
    print(" RECOMENDACIONES")
    print("=" * 80)
    print()
    print("1. MIGRAR URGENTE:")
    if categories['missing']:
        print("   Los siguientes modelos faltan y son necesarios:")
        for model in sorted(categories['missing'])[:10]:
            print(f"      - {model}")
        if len(categories['missing']) > 10:
            print(f"      ... y {len(categories['missing']) - 10} más")
    else:
        print("   ✅ ¡Todos los modelos críticos están migrados!")
    print()

    print("2. MODELOS 'EVALUATE':")
    print("   Revisar si el proyecto necesita funcionalidad de:")
    print("      - Billing/Subscriptions (planes, facturas)")
    print("      - E-commerce (productos, órdenes)")
    print("      - Integraciones externas (WooCommerce, Lazada)")
    print()

    print("3. PRÓXIMOS PASOS:")
    print("   a) Migrar modelos faltantes prioritarios")
    print("   b) Actualizar namespaces en todos los modelos")
    print("   c) Verificar relaciones entre modelos")
    print("   d) Ejecutar tests de integración")
    print()

if __name__ == "__main__":
    print_report()
