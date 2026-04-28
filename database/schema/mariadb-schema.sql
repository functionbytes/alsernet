/*M!999999\- enable the sandbox mode */ 

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*M!100616 SET @OLD_NOTE_VERBOSITY=@@NOTE_VERBOSITY, NOTE_VERBOSITY=0 */;
DROP TABLE IF EXISTS `activity_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `activity_log` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `log_name` varchar(191) DEFAULT NULL,
  `event` varchar(191) DEFAULT NULL,
  `description` text NOT NULL,
  `subject_type` varchar(191) DEFAULT NULL,
  `subject_id` char(26) DEFAULT NULL,
  `causer_type` varchar(191) DEFAULT NULL,
  `causer_id` char(26) DEFAULT NULL,
  `properties` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`properties`)),
  `hmac_signature` varchar(64) DEFAULT NULL,
  `attribute_changes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`attribute_changes`)),
  `batch_uuid` varchar(191) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `subject` (`subject_type`,`subject_id`),
  KEY `causer` (`causer_type`,`causer_id`),
  KEY `activity_log_created_at_index` (`created_at`),
  KEY `activity_log_log_name_index` (`log_name`),
  KEY `activity_log_batch_uuid_index` (`batch_uuid`),
  KEY `activity_log_event_index` (`event`),
  KEY `activity_log_causer_id_event_index` (`causer_id`,`event`),
  KEY `activity_log_hmac_signature_index` (`hmac_signature`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ad_translations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ad_translations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ad_id` bigint(20) unsigned NOT NULL,
  `locale` varchar(10) NOT NULL,
  `name` varchar(191) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ad_translations_ad_id_locale_unique` (`ad_id`,`locale`),
  CONSTRAINT `ad_translations_ad_id_foreign` FOREIGN KEY (`ad_id`) REFERENCES `ads` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ads` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `key` varchar(120) NOT NULL,
  `status` varchar(60) NOT NULL DEFAULT 'published',
  `open_in_new_tab` tinyint(1) NOT NULL DEFAULT 0,
  `expired_at` date DEFAULT NULL,
  `location` varchar(120) DEFAULT NULL,
  `image` varchar(191) DEFAULT NULL,
  `tablet_image` varchar(191) DEFAULT NULL,
  `mobile_image` varchar(191) DEFAULT NULL,
  `url` varchar(191) DEFAULT NULL,
  `clicked` bigint(20) unsigned NOT NULL DEFAULT 0,
  `order` int(11) NOT NULL DEFAULT 0,
  `ads_type` varchar(60) NOT NULL DEFAULT 'image',
  `google_adsense_slot_id` varchar(191) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ads_key_unique` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ads_clicks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ads_clicks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ads_id` bigint(20) unsigned NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `clicked_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `ads_clicks_ads_id_foreign` (`ads_id`),
  CONSTRAINT `ads_clicks_ads_id_foreign` FOREIGN KEY (`ads_id`) REFERENCES `ads` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `alert_thresholds`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `alert_thresholds` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `type` varchar(191) NOT NULL,
  `conditions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`conditions`)),
  `channels` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`channels`)),
  `recipients` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`recipients`)),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_triggered_at` timestamp NULL DEFAULT NULL,
  `cooldown_minutes` int(11) NOT NULL DEFAULT 60,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `analytics_report_schedules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `analytics_report_schedules` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `frequency` enum('daily','weekly','monthly') NOT NULL,
  `email` varchar(191) NOT NULL,
  `format` enum('pdf','excel','csv') NOT NULL DEFAULT 'pdf',
  `metrics` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metrics`)),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_sent_at` timestamp NULL DEFAULT NULL,
  `next_run_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `analytics_report_schedules_user_id_foreign` (`user_id`),
  KEY `analytics_report_schedules_is_active_index` (`is_active`),
  KEY `analytics_report_schedules_next_run_at_index` (`next_run_at`),
  KEY `analytics_report_schedules_is_active_next_run_at_index` (`is_active`,`next_run_at`),
  CONSTRAINT `analytics_report_schedules_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `application_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `application_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `level` varchar(191) NOT NULL,
  `channel` varchar(191) DEFAULT NULL,
  `message` text NOT NULL,
  `context` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`context`)),
  `extra` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`extra`)),
  `stack_trace` longtext DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(191) DEFAULT NULL,
  `url` varchar(191) DEFAULT NULL,
  `method` varchar(191) DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `application_logs_level_created_at_index` (`level`,`created_at`),
  KEY `application_logs_channel_created_at_index` (`channel`,`created_at`),
  KEY `application_logs_level_index` (`level`),
  KEY `application_logs_channel_index` (`channel`),
  KEY `application_logs_user_id_index` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `attention_actions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `attention_actions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `attention_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `action` varchar(100) NOT NULL COMMENT 'Tipo de acción realizada',
  `description` text DEFAULT NULL COMMENT 'Descripción de la acción',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `attention_actions_attention_id_index` (`attention_id`),
  KEY `attention_actions_user_id_index` (`user_id`),
  KEY `attention_actions_action_index` (`action`),
  KEY `attention_actions_created_at_index` (`created_at`),
  KEY `idx_attention_action_created` (`attention_id`,`created_at`),
  KEY `idx_action_created` (`action`,`created_at`),
  CONSTRAINT `attention_actions_attention_id_foreign` FOREIGN KEY (`attention_id`) REFERENCES `attentions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `attention_actions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `attention_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `attention_categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `attention_categories_is_active_index` (`is_active`),
  KEY `attention_categories_order_index` (`order`),
  KEY `idx_active_order` (`is_active`,`order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `attention_department_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `attention_department_user` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `department_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `attention_department_user_department_id_user_id_unique` (`department_id`,`user_id`),
  KEY `attention_department_user_department_id_index` (`department_id`),
  KEY `attention_department_user_user_id_index` (`user_id`),
  CONSTRAINT `attention_department_user_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `attention_departments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `attention_department_user_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `attention_departments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `attention_departments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `responsible_name` varchar(150) DEFAULT NULL,
  `responsible_email` varchar(150) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `attention_departments_is_active_index` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `attention_mails`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `attention_mails` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uid` varchar(36) DEFAULT NULL,
  `attention_id` bigint(20) unsigned NOT NULL,
  `email_type` varchar(100) NOT NULL COMMENT 'Tipo de email',
  `recipient_email` varchar(150) NOT NULL COMMENT 'Destinatario',
  `subject` varchar(255) NOT NULL COMMENT 'Asunto del email',
  `body_html` longtext DEFAULT NULL COMMENT 'Cuerpo del email',
  `body_text` longtext DEFAULT NULL COMMENT 'Cuerpo del email en texto plano',
  `template_id` bigint(20) unsigned DEFAULT NULL,
  `sent_by` bigint(20) unsigned DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Metadatos adicionales del email' CHECK (json_valid(`metadata`)),
  `status` varchar(50) NOT NULL DEFAULT 'queued' COMMENT 'Estado del email',
  `sent_at` timestamp NULL DEFAULT NULL COMMENT 'Fecha de envío exitoso',
  `failed_at` timestamp NULL DEFAULT NULL COMMENT 'Fecha de fallo',
  `error_message` text DEFAULT NULL COMMENT 'Mensaje de error si falló',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `attention_mails_uid_unique` (`uid`),
  KEY `attention_mails_attention_id_index` (`attention_id`),
  KEY `attention_mails_email_type_index` (`email_type`),
  KEY `attention_mails_sent_at_index` (`sent_at`),
  KEY `attention_mails_failed_at_index` (`failed_at`),
  KEY `attention_mails_created_at_index` (`created_at`),
  KEY `attention_mails_template_id_foreign` (`template_id`),
  KEY `attention_mails_sent_by_foreign` (`sent_by`),
  KEY `idx_attention_mail_sent` (`attention_id`,`sent_at`),
  KEY `idx_recipient_email` (`recipient_email`),
  KEY `idx_email_type` (`email_type`),
  CONSTRAINT `attention_mails_attention_id_foreign` FOREIGN KEY (`attention_id`) REFERENCES `attentions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `attention_mails_sent_by_foreign` FOREIGN KEY (`sent_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `attention_mails_template_id_foreign` FOREIGN KEY (`template_id`) REFERENCES `mailer_templates` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `attention_notes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `attention_notes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `attention_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `content` longtext NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `attention_notes_attention_id_index` (`attention_id`),
  KEY `attention_notes_user_id_index` (`user_id`),
  KEY `attention_notes_created_at_index` (`created_at`),
  KEY `idx_attention_created` (`attention_id`,`created_at`),
  CONSTRAINT `attention_notes_attention_id_foreign` FOREIGN KEY (`attention_id`) REFERENCES `attentions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `attention_notes_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `attention_routing_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `attention_routing_rules` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `priority` tinyint(3) unsigned NOT NULL DEFAULT 10,
  `condition_type_id` bigint(20) unsigned DEFAULT NULL,
  `condition_category_id` bigint(20) unsigned DEFAULT NULL,
  `condition_sede_id` bigint(20) unsigned DEFAULT NULL,
  `assign_to_user_id` bigint(20) unsigned DEFAULT NULL,
  `assign_to_department_id` bigint(20) unsigned DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `attention_routing_rules_is_active_priority_index` (`is_active`,`priority`),
  KEY `attention_routing_rules_condition_type_id_foreign` (`condition_type_id`),
  KEY `attention_routing_rules_condition_category_id_foreign` (`condition_category_id`),
  KEY `attention_routing_rules_condition_sede_id_foreign` (`condition_sede_id`),
  KEY `attention_routing_rules_assign_to_user_id_foreign` (`assign_to_user_id`),
  KEY `attention_routing_rules_assign_to_department_id_foreign` (`assign_to_department_id`),
  KEY `attention_routing_rules_priority_index` (`priority`),
  KEY `attention_routing_rules_is_active_index` (`is_active`),
  CONSTRAINT `attention_routing_rules_assign_to_department_id_foreign` FOREIGN KEY (`assign_to_department_id`) REFERENCES `attention_departments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `attention_routing_rules_assign_to_user_id_foreign` FOREIGN KEY (`assign_to_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `attention_routing_rules_condition_category_id_foreign` FOREIGN KEY (`condition_category_id`) REFERENCES `attention_categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `attention_routing_rules_condition_sede_id_foreign` FOREIGN KEY (`condition_sede_id`) REFERENCES `attention_sedes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `attention_routing_rules_condition_type_id_foreign` FOREIGN KEY (`condition_type_id`) REFERENCES `attention_types` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `attention_satisfaction`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `attention_satisfaction` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `attention_id` bigint(20) unsigned NOT NULL,
  `rating` tinyint(3) unsigned NOT NULL COMMENT 'Rating 1-5 estrellas',
  `comment` text DEFAULT NULL COMMENT 'Comentario adicional',
  `submitted_at` timestamp NULL DEFAULT NULL COMMENT 'Fecha de envío de la encuesta',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `attention_satisfaction_attention_id_index` (`attention_id`),
  KEY `attention_satisfaction_rating_index` (`rating`),
  KEY `attention_satisfaction_submitted_at_index` (`submitted_at`),
  CONSTRAINT `attention_satisfaction_attention_id_foreign` FOREIGN KEY (`attention_id`) REFERENCES `attentions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `attention_sedes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `attention_sedes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `attention_sedes_is_active_index` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `attention_sla_breaches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `attention_sla_breaches` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `attention_id` bigint(20) unsigned NOT NULL,
  `sla_policy_id` bigint(20) unsigned NOT NULL,
  `breach_type` varchar(191) NOT NULL,
  `minutes_over` int(11) NOT NULL DEFAULT 0,
  `escalated` tinyint(1) NOT NULL DEFAULT 0,
  `escalated_at` timestamp NULL DEFAULT NULL,
  `resolved` tinyint(1) NOT NULL DEFAULT 0,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `attention_sla_breaches_attention_id_index` (`attention_id`),
  KEY `attention_sla_breaches_sla_policy_id_index` (`sla_policy_id`),
  KEY `attention_sla_breaches_breach_type_index` (`breach_type`),
  CONSTRAINT `attention_sla_breaches_attention_id_foreign` FOREIGN KEY (`attention_id`) REFERENCES `attentions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `attention_sla_breaches_sla_policy_id_foreign` FOREIGN KEY (`sla_policy_id`) REFERENCES `attention_sla_policies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `attention_sla_policies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `attention_sla_policies` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `description` text DEFAULT NULL,
  `response_time` int(11) NOT NULL COMMENT 'minutes',
  `resolution_time` int(11) NOT NULL COMMENT 'minutes',
  `closure_time` int(11) NOT NULL COMMENT 'minutes',
  `business_hours_only` tinyint(1) NOT NULL DEFAULT 0,
  `business_hours` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`business_hours`)),
  `timezone` varchar(191) NOT NULL DEFAULT 'UTC',
  `type_multipliers` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`type_multipliers`)),
  `enable_escalation` tinyint(1) NOT NULL DEFAULT 0,
  `escalation_threshold_percent` int(11) DEFAULT NULL,
  `escalation_recipients` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`escalation_recipients`)),
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `attention_sla_policies_is_default_index` (`is_default`),
  KEY `attention_sla_policies_active_index` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `attention_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `attention_types` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(10) NOT NULL COMMENT 'P, Q, R, S, F',
  `name` varchar(100) NOT NULL COMMENT 'Petición, Queja, Reclamo, etc',
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `attention_types_code_unique` (`code`),
  KEY `attention_types_is_active_index` (`is_active`),
  KEY `attention_types_order_index` (`order`),
  KEY `idx_type_active_order` (`is_active`,`order`),
  KEY `idx_type_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `attentions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `attentions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uid` uuid NOT NULL COMMENT 'Identificador único interno',
  `radicado` varchar(50) NOT NULL COMMENT 'peticiones-YYYY-NNNNNN',
  `type_id` bigint(20) unsigned NOT NULL,
  `category_id` bigint(20) unsigned DEFAULT NULL,
  `sede_id` bigint(20) unsigned DEFAULT NULL,
  `customer_firstname` varchar(150) DEFAULT NULL,
  `customer_lastname` varchar(150) DEFAULT NULL,
  `customer_email` varchar(150) DEFAULT NULL,
  `customer_cellphone` varchar(50) DEFAULT NULL,
  `customer_dni` varchar(50) DEFAULT NULL,
  `customer_address` varchar(255) DEFAULT NULL,
  `is_anonymous` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'peticiones anónimo',
  `subject` varchar(255) NOT NULL COMMENT 'Asunto',
  `description` longtext NOT NULL COMMENT 'Descripción detallada',
  `status` varchar(50) NOT NULL DEFAULT 'received' COMMENT 'received, in_process, resolved, closed',
  `department_id` bigint(20) unsigned DEFAULT NULL,
  `assigned_user_id` bigint(20) unsigned DEFAULT NULL,
  `sla_policy_id` bigint(20) unsigned DEFAULT NULL,
  `response_type` varchar(50) DEFAULT NULL COMMENT 'email, presencial, correo_fisico, telefono, no_requiere',
  `resolution` longtext DEFAULT NULL COMMENT 'Respuesta oficial',
  `resolved_at` timestamp NULL DEFAULT NULL COMMENT 'Fecha de resolución',
  `closed_at` timestamp NULL DEFAULT NULL COMMENT 'Fecha de cierre',
  `satisfaction_rating` tinyint(3) unsigned DEFAULT NULL COMMENT 'Rating 1-5 estrellas',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `archived_at` timestamp NULL DEFAULT NULL COMMENT 'Fecha de archivado según Ley 594/2000',
  PRIMARY KEY (`id`),
  UNIQUE KEY `attentions_uid_unique` (`uid`),
  UNIQUE KEY `attentions_radicado_unique` (`radicado`),
  KEY `attentions_uid_index` (`uid`),
  KEY `attentions_radicado_index` (`radicado`),
  KEY `attentions_type_id_index` (`type_id`),
  KEY `attentions_category_id_index` (`category_id`),
  KEY `attentions_sede_id_index` (`sede_id`),
  KEY `attentions_status_index` (`status`),
  KEY `attentions_department_id_index` (`department_id`),
  KEY `attentions_assigned_user_id_index` (`assigned_user_id`),
  KEY `attentions_is_anonymous_index` (`is_anonymous`),
  KEY `attentions_customer_email_index` (`customer_email`),
  KEY `attentions_customer_dni_index` (`customer_dni`),
  KEY `attentions_resolved_at_closed_at_index` (`resolved_at`,`closed_at`),
  KEY `attentions_created_at_index` (`created_at`),
  KEY `attentions_archived_at_index` (`archived_at`),
  KEY `attentions_sla_policy_id_index` (`sla_policy_id`),
  KEY `idx_status_created` (`status`,`created_at`),
  KEY `idx_department_status` (`department_id`,`status`),
  KEY `idx_assigned_status` (`assigned_user_id`,`status`),
  KEY `idx_type_status` (`type_id`,`status`),
  KEY `idx_sede_created` (`sede_id`,`created_at`),
  CONSTRAINT `attentions_assigned_user_id_foreign` FOREIGN KEY (`assigned_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `attentions_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `attention_categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `attentions_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `attention_departments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `attentions_sede_id_foreign` FOREIGN KEY (`sede_id`) REFERENCES `attention_sedes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `attentions_sla_policy_id_foreign` FOREIGN KEY (`sla_policy_id`) REFERENCES `attention_sla_policies` (`id`) ON DELETE SET NULL,
  CONSTRAINT `attentions_type_id_foreign` FOREIGN KEY (`type_id`) REFERENCES `attention_types` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `backup_schedules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `backup_schedules` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT 1,
  `frequency` enum('daily','weekly','monthly','custom') NOT NULL DEFAULT 'daily',
  `scheduled_time` time NOT NULL DEFAULT '02:00:00',
  `days_of_week` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`days_of_week`)),
  `days_of_month` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`days_of_month`)),
  `custom_interval_hours` int(11) DEFAULT NULL,
  `backup_types` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`backup_types`)),
  `last_run_at` timestamp NULL DEFAULT NULL,
  `next_run_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `backups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `backups` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(191) NOT NULL,
  `value` longtext DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `backups_key_unique` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `blog_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `blog_categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` varchar(400) DEFAULT NULL,
  `parent_id` bigint(20) unsigned NOT NULL DEFAULT 0,
  `status` enum('published','draft') NOT NULL DEFAULT 'published',
  `icon` varchar(60) DEFAULT NULL,
  `order` int(11) NOT NULL DEFAULT 0,
  `is_featured` tinyint(4) NOT NULL DEFAULT 0,
  `is_default` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `blog_categories_slug_unique` (`slug`),
  KEY `blog_categories_user_id_foreign` (`user_id`),
  KEY `blog_categories_slug_index` (`slug`),
  KEY `blog_categories_status_index` (`status`),
  KEY `blog_categories_parent_id_index` (`parent_id`),
  KEY `blog_categories_is_default_index` (`is_default`),
  KEY `blog_categories_order_index` (`order`),
  CONSTRAINT `blog_categories_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `blog_category_translations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `blog_category_translations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `blog_category_id` bigint(20) unsigned NOT NULL,
  `locale` varchar(10) NOT NULL,
  `name` varchar(120) NOT NULL,
  `slug` varchar(255) DEFAULT NULL,
  `description` varchar(400) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `blog_category_translations_blog_category_id_locale_unique` (`blog_category_id`,`locale`),
  UNIQUE KEY `blog_category_translations_locale_slug_unique` (`locale`,`slug`),
  CONSTRAINT `blog_category_translations_blog_category_id_foreign` FOREIGN KEY (`blog_category_id`) REFERENCES `blog_categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `blog_glossary_terms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `blog_glossary_terms` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `source_term` varchar(255) NOT NULL,
  `source_locale` varchar(5) NOT NULL DEFAULT 'es',
  `target_term` varchar(255) NOT NULL,
  `target_locale` varchar(5) NOT NULL,
  `do_not_translate` tinyint(1) NOT NULL DEFAULT 0,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `blog_glossary_terms_unique` (`source_term`,`source_locale`,`target_locale`),
  KEY `blog_glossary_terms_created_by_foreign` (`created_by`),
  CONSTRAINT `blog_glossary_terms_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `blog_post_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `blog_post_categories` (
  `post_id` bigint(20) unsigned NOT NULL,
  `category_id` bigint(20) unsigned NOT NULL,
  KEY `blog_post_categories_post_id_index` (`post_id`),
  KEY `blog_post_categories_category_id_index` (`category_id`),
  CONSTRAINT `blog_post_categories_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `blog_categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `blog_post_categories_post_id_foreign` FOREIGN KEY (`post_id`) REFERENCES `blog_posts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `blog_post_comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `blog_post_comments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `blog_post_id` bigint(20) unsigned NOT NULL,
  `parent_id` bigint(20) unsigned DEFAULT NULL,
  `author_name` varchar(100) NOT NULL,
  `author_email` varchar(150) NOT NULL,
  `content` text NOT NULL,
  `status` enum('pending','approved','spam') NOT NULL DEFAULT 'pending',
  `ip_address` varchar(45) DEFAULT NULL,
  `ip_expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `blog_post_comments_blog_post_id_index` (`blog_post_id`),
  KEY `blog_post_comments_parent_id_index` (`parent_id`),
  KEY `blog_post_comments_status_index` (`status`),
  KEY `blog_post_comments_blog_post_id_status_index` (`blog_post_id`,`status`),
  KEY `blog_post_comments_deleted_at_index` (`deleted_at`),
  CONSTRAINT `blog_post_comments_blog_post_id_foreign` FOREIGN KEY (`blog_post_id`) REFERENCES `blog_posts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `blog_post_comments_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `blog_post_comments` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `blog_post_tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `blog_post_tags` (
  `post_id` bigint(20) unsigned NOT NULL,
  `tag_id` bigint(20) unsigned NOT NULL,
  KEY `blog_post_tags_post_id_index` (`post_id`),
  KEY `blog_post_tags_tag_id_index` (`tag_id`),
  CONSTRAINT `blog_post_tags_post_id_foreign` FOREIGN KEY (`post_id`) REFERENCES `blog_posts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `blog_post_tags_tag_id_foreign` FOREIGN KEY (`tag_id`) REFERENCES `blog_tags` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `blog_post_translations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `blog_post_translations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `blog_post_id` bigint(20) unsigned NOT NULL,
  `locale` varchar(10) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `slug` varchar(255) DEFAULT NULL,
  `description` varchar(500) DEFAULT NULL,
  `content` longtext DEFAULT NULL,
  `seo_title` varchar(255) DEFAULT NULL,
  `seo_description` text DEFAULT NULL,
  `seo_keywords` text DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'manual',
  `translated_at` timestamp NULL DEFAULT NULL,
  `source_hash` varchar(64) DEFAULT NULL,
  `reviewed_by` bigint(20) unsigned DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `blog_post_translations_blog_post_id_locale_unique` (`blog_post_id`,`locale`),
  UNIQUE KEY `blog_post_translations_locale_slug_unique` (`locale`,`slug`),
  KEY `blog_post_translations_reviewed_by_foreign` (`reviewed_by`),
  CONSTRAINT `blog_post_translations_blog_post_id_foreign` FOREIGN KEY (`blog_post_id`) REFERENCES `blog_posts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `blog_post_translations_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `blog_post_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `blog_post_versions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `blog_post_id` bigint(20) unsigned NOT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `title` varchar(191) NOT NULL,
  `slug` varchar(191) NOT NULL,
  `content` longtext NOT NULL,
  `excerpt` text DEFAULT NULL,
  `version_number` int(11) NOT NULL DEFAULT 1,
  `change_summary` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `blog_post_versions_created_by_foreign` (`created_by`),
  KEY `blog_post_versions_blog_post_id_version_number_index` (`blog_post_id`,`version_number`),
  CONSTRAINT `blog_post_versions_blog_post_id_foreign` FOREIGN KEY (`blog_post_id`) REFERENCES `blog_posts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `blog_post_versions_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `blog_posts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `blog_posts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` varchar(500) DEFAULT NULL,
  `content` longtext DEFAULT NULL,
  `status` enum('draft','published','pending') NOT NULL DEFAULT 'draft',
  `send_newsletter` tinyint(1) NOT NULL DEFAULT 0,
  `newsletter_sent_at` timestamp NULL DEFAULT NULL,
  `is_featured` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `image` varchar(255) DEFAULT NULL,
  `format_type` varchar(30) DEFAULT NULL,
  `views` int(10) unsigned NOT NULL DEFAULT 0,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `seo_title` varchar(255) DEFAULT NULL,
  `seo_description` text DEFAULT NULL,
  `seo_keywords` text DEFAULT NULL,
  `published_at` timestamp NULL DEFAULT NULL,
  `publish_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `blog_posts_slug_unique` (`slug`),
  KEY `blog_posts_slug_index` (`slug`),
  KEY `blog_posts_status_published_at_index` (`status`,`published_at`),
  KEY `blog_posts_user_id_index` (`user_id`),
  KEY `blog_posts_is_featured_index` (`is_featured`),
  KEY `blog_posts_created_at_index` (`created_at`),
  KEY `blog_posts_status_publish_at_index` (`status`,`publish_at`),
  KEY `blog_posts_status_featured_published_index` (`status`,`is_featured`,`published_at`),
  KEY `blog_posts_user_id_status_index` (`user_id`,`status`),
  KEY `blog_posts_views_index` (`views`),
  FULLTEXT KEY `blog_posts_fulltext` (`title`,`description`,`content`),
  CONSTRAINT `blog_posts_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `blog_tag_translations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `blog_tag_translations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `blog_tag_id` bigint(20) unsigned NOT NULL,
  `locale` varchar(10) NOT NULL,
  `name` varchar(120) NOT NULL,
  `slug` varchar(255) DEFAULT NULL,
  `description` varchar(400) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `blog_tag_translations_blog_tag_id_locale_unique` (`blog_tag_id`,`locale`),
  KEY `blog_tag_translations_locale_slug_index` (`locale`,`slug`),
  CONSTRAINT `blog_tag_translations_blog_tag_id_foreign` FOREIGN KEY (`blog_tag_id`) REFERENCES `blog_tags` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `blog_tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `blog_tags` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` varchar(400) DEFAULT NULL,
  `status` enum('published','draft') NOT NULL DEFAULT 'published',
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `blog_tags_slug_unique` (`slug`),
  KEY `blog_tags_user_id_foreign` (`user_id`),
  KEY `blog_tags_slug_index` (`slug`),
  KEY `blog_tags_status_index` (`status`),
  CONSTRAINT `blog_tags_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `blog_translation_cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `blog_translation_cache` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `source_hash` varchar(64) NOT NULL,
  `source_locale` varchar(5) NOT NULL,
  `target_locale` varchar(5) NOT NULL,
  `field_name` varchar(30) NOT NULL,
  `translated_text` longtext NOT NULL,
  `provider` varchar(30) NOT NULL DEFAULT 'deepl',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `blog_translation_cache_unique` (`source_hash`,`source_locale`,`target_locale`,`field_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `blog_translation_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `blog_translation_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `blog_post_id` bigint(20) unsigned NOT NULL,
  `locale` varchar(5) NOT NULL,
  `action` varchar(30) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `fields_changed` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`fields_changed`)),
  `provider` varchar(30) DEFAULT 'deepl',
  `characters_used` int(10) unsigned DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `blog_translation_logs_user_id_foreign` (`user_id`),
  KEY `blog_translation_logs_blog_post_id_locale_index` (`blog_post_id`,`locale`),
  CONSTRAINT `blog_translation_logs_blog_post_id_foreign` FOREIGN KEY (`blog_post_id`) REFERENCES `blog_posts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `blog_translation_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `campaign_auto_triggers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `campaign_auto_triggers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uid` uuid NOT NULL,
  `automation_id` bigint(20) unsigned NOT NULL,
  `subscriber_id` bigint(20) unsigned DEFAULT NULL,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`data`)),
  `last_executed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `campaign_auto_triggers_uid_unique` (`uid`),
  KEY `campaign_auto_triggers_automation_id_foreign` (`automation_id`),
  KEY `campaign_auto_triggers_subscriber_id_foreign` (`subscriber_id`),
  KEY `campaign_auto_triggers_last_executed_at_index` (`last_executed_at`),
  CONSTRAINT `campaign_auto_triggers_automation_id_foreign` FOREIGN KEY (`automation_id`) REFERENCES `campaign_automations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `campaign_auto_triggers_subscriber_id_foreign` FOREIGN KEY (`subscriber_id`) REFERENCES `campaign_subscribers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `campaign_automation_elements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `campaign_automation_elements` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uid` uuid NOT NULL,
  `automation_id` bigint(20) unsigned NOT NULL,
  `element_id` varchar(191) NOT NULL,
  `type` varchar(64) NOT NULL,
  `options` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`options`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `campaign_automation_elements_uid_unique` (`uid`),
  KEY `campaign_automation_elements_automation_id_foreign` (`automation_id`),
  KEY `campaign_automation_elements_element_id_index` (`element_id`),
  CONSTRAINT `campaign_automation_elements_automation_id_foreign` FOREIGN KEY (`automation_id`) REFERENCES `campaign_automations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `campaign_automations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `campaign_automations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uid` uuid NOT NULL,
  `name` varchar(191) NOT NULL,
  `time_zone` varchar(64) NOT NULL DEFAULT 'UTC',
  `status` varchar(32) NOT NULL DEFAULT 'inactive',
  `data` longtext DEFAULT NULL,
  `mail_list_id` bigint(20) unsigned DEFAULT NULL,
  `segment_id` varchar(191) DEFAULT NULL,
  `last_error` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `description` text DEFAULT NULL,
  `last_executed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `campaign_automations_uid_unique` (`uid`),
  KEY `campaign_automations_mail_list_id_foreign` (`mail_list_id`),
  KEY `campaign_automations_status_index` (`status`),
  CONSTRAINT `campaign_automations_mail_list_id_foreign` FOREIGN KEY (`mail_list_id`) REFERENCES `campaign_maillists` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `campaign_click_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `campaign_click_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tracking_log_id` bigint(20) unsigned NOT NULL,
  `campaign_link_id` bigint(20) unsigned DEFAULT NULL,
  `ip` varchar(191) DEFAULT NULL,
  `user_agent` varchar(191) DEFAULT NULL,
  `country` varchar(191) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `campaign_click_logs_tracking_log_id_foreign` (`tracking_log_id`),
  KEY `campaign_click_logs_campaign_link_id_foreign` (`campaign_link_id`),
  CONSTRAINT `campaign_click_logs_campaign_link_id_foreign` FOREIGN KEY (`campaign_link_id`) REFERENCES `campaign_links` (`id`) ON DELETE SET NULL,
  CONSTRAINT `campaign_click_logs_tracking_log_id_foreign` FOREIGN KEY (`tracking_log_id`) REFERENCES `campaign_tracking_logs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `campaign_email_links`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `campaign_email_links` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uid` uuid NOT NULL,
  `email_id` bigint(20) unsigned NOT NULL,
  `url` text NOT NULL,
  `hash` varchar(64) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `campaign_email_links_uid_unique` (`uid`),
  KEY `campaign_email_links_email_id_foreign` (`email_id`),
  KEY `campaign_email_links_hash_index` (`hash`),
  CONSTRAINT `campaign_email_links_email_id_foreign` FOREIGN KEY (`email_id`) REFERENCES `campaign_emails` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `campaign_email_webhooks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `campaign_email_webhooks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uid` uuid NOT NULL,
  `email_id` bigint(20) unsigned NOT NULL,
  `event` varchar(64) NOT NULL,
  `url` varchar(191) NOT NULL,
  `method` varchar(8) NOT NULL DEFAULT 'POST',
  `headers` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`headers`)),
  `enabled` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `secret` varchar(128) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `campaign_email_webhooks_uid_unique` (`uid`),
  KEY `campaign_email_webhooks_email_id_foreign` (`email_id`),
  KEY `campaign_email_webhooks_event_index` (`event`),
  CONSTRAINT `campaign_email_webhooks_email_id_foreign` FOREIGN KEY (`email_id`) REFERENCES `campaign_emails` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `campaign_emails`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `campaign_emails` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uid` uuid NOT NULL,
  `subject` varchar(191) DEFAULT NULL,
  `from_email` varchar(191) DEFAULT NULL,
  `from_name` varchar(191) DEFAULT NULL,
  `reply_to` varchar(191) DEFAULT NULL,
  `html` longtext DEFAULT NULL,
  `plain` longtext DEFAULT NULL,
  `preheader` varchar(191) DEFAULT NULL,
  `track_open` tinyint(1) NOT NULL DEFAULT 1,
  `track_click` tinyint(1) NOT NULL DEFAULT 1,
  `sign_dkim` tinyint(1) NOT NULL DEFAULT 0,
  `automation_element_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `campaign_emails_uid_unique` (`uid`),
  KEY `campaign_emails_automation_element_id_index` (`automation_element_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `campaign_feedback_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `campaign_feedback_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tracking_log_id` bigint(20) unsigned DEFAULT NULL,
  `email` varchar(191) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `campaign_feedback_logs_tracking_log_id_foreign` (`tracking_log_id`),
  KEY `campaign_feedback_logs_email_index` (`email`),
  CONSTRAINT `campaign_feedback_logs_tracking_log_id_foreign` FOREIGN KEY (`tracking_log_id`) REFERENCES `campaign_tracking_logs` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `campaign_field_options`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `campaign_field_options` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `field_id` bigint(20) unsigned NOT NULL,
  `value` varchar(191) NOT NULL,
  `label` varchar(191) NOT NULL,
  `order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `campaign_field_options_field_id_foreign` (`field_id`),
  CONSTRAINT `campaign_field_options_field_id_foreign` FOREIGN KEY (`field_id`) REFERENCES `campaign_fields` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `campaign_fields`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `campaign_fields` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uid` uuid NOT NULL,
  `mail_list_id` bigint(20) unsigned DEFAULT NULL,
  `tag` varchar(191) NOT NULL,
  `label` varchar(191) NOT NULL,
  `type` varchar(32) NOT NULL DEFAULT 'text',
  `default_value` varchar(191) DEFAULT NULL,
  `required` tinyint(1) NOT NULL DEFAULT 0,
  `visible` tinyint(1) NOT NULL DEFAULT 1,
  `order` int(11) NOT NULL DEFAULT 0,
  `options` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`options`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `campaign_fields_uid_unique` (`uid`),
  KEY `campaign_fields_mail_list_id_foreign` (`mail_list_id`),
  KEY `campaign_fields_tag_index` (`tag`),
  CONSTRAINT `campaign_fields_mail_list_id_foreign` FOREIGN KEY (`mail_list_id`) REFERENCES `campaign_maillists` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `campaign_job_monitors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `campaign_job_monitors` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `subject_name` varchar(191) NOT NULL,
  `subject_id` bigint(20) unsigned NOT NULL,
  `job_type` varchar(191) NOT NULL,
  `job_id` varchar(191) DEFAULT NULL,
  `batch_id` varchar(191) DEFAULT NULL,
  `status` varchar(32) NOT NULL DEFAULT 'queued',
  `error` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `campaign_jm_subject_idx` (`subject_name`,`subject_id`),
  KEY `campaign_jm_subject_jobtype_idx` (`subject_name`,`subject_id`,`job_type`),
  KEY `campaign_job_monitors_job_id_index` (`job_id`),
  KEY `campaign_job_monitors_batch_id_index` (`batch_id`),
  KEY `campaign_job_monitors_status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `campaign_layouts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `campaign_layouts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uid` uuid NOT NULL,
  `name` varchar(191) NOT NULL,
  `content` longtext DEFAULT NULL,
  `default` tinyint(1) NOT NULL DEFAULT 0,
  `order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `campaign_layouts_uid_unique` (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `campaign_links`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `campaign_links` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uid` uuid NOT NULL,
  `campaign_id` bigint(20) unsigned NOT NULL,
  `url` text NOT NULL,
  `hash` varchar(64) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `campaign_links_uid_unique` (`uid`),
  KEY `campaign_links_campaign_id_foreign` (`campaign_id`),
  KEY `campaign_links_hash_index` (`hash`),
  CONSTRAINT `campaign_links_campaign_id_foreign` FOREIGN KEY (`campaign_id`) REFERENCES `campaigns` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `campaign_lists_segments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `campaign_lists_segments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `campaign_id` bigint(20) unsigned NOT NULL,
  `mail_list_id` bigint(20) unsigned NOT NULL,
  `segment_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `campaign_lists_segments_mail_list_id_foreign` (`mail_list_id`),
  KEY `campaign_lists_segments_segment_id_foreign` (`segment_id`),
  KEY `cls_idx` (`campaign_id`,`mail_list_id`,`segment_id`),
  CONSTRAINT `campaign_lists_segments_campaign_id_foreign` FOREIGN KEY (`campaign_id`) REFERENCES `campaigns` (`id`) ON DELETE CASCADE,
  CONSTRAINT `campaign_lists_segments_mail_list_id_foreign` FOREIGN KEY (`mail_list_id`) REFERENCES `campaign_maillists` (`id`) ON DELETE CASCADE,
  CONSTRAINT `campaign_lists_segments_segment_id_foreign` FOREIGN KEY (`segment_id`) REFERENCES `campaign_segments` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `campaign_maillists`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `campaign_maillists` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uid` uuid NOT NULL,
  `name` varchar(191) NOT NULL,
  `title` varchar(191) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `from_email` varchar(191) DEFAULT NULL,
  `from_name` varchar(191) DEFAULT NULL,
  `default_subject` varchar(191) DEFAULT NULL,
  `contact_company` varchar(191) DEFAULT NULL,
  `contact_state` varchar(191) DEFAULT NULL,
  `contact_city` varchar(191) DEFAULT NULL,
  `contact_zip` varchar(191) DEFAULT NULL,
  `contact_country_id` varchar(191) DEFAULT NULL,
  `contact_phone` varchar(191) DEFAULT NULL,
  `contact_email` varchar(191) DEFAULT NULL,
  `contact_url` varchar(191) DEFAULT NULL,
  `contact_address_1` varchar(191) DEFAULT NULL,
  `contact_address_2` varchar(191) DEFAULT NULL,
  `subscribe_confirmation` varchar(191) NOT NULL DEFAULT '1',
  `send_welcome_email` varchar(191) NOT NULL DEFAULT '0',
  `unsubscribe_notification` varchar(191) NOT NULL DEFAULT '0',
  `remind_message` text DEFAULT NULL,
  `subscribe_form_embed_code` text DEFAULT NULL,
  `options` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`options`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `mail_subscribe` varchar(191) DEFAULT NULL,
  `mail_unsubscribe` varchar(191) DEFAULT NULL,
  `mail_daily` varchar(191) DEFAULT NULL,
  `send_to` varchar(191) DEFAULT NULL,
  `custom_order` int(11) NOT NULL DEFAULT 0,
  `all_sending_servers` tinyint(1) NOT NULL DEFAULT 0,
  `available` tinyint(1) NOT NULL DEFAULT 1,
  `cached_subscriber_count` bigint(20) unsigned NOT NULL DEFAULT 0,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `campaign_maillists_uid_unique` (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `campaign_maillists_sending_servers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `campaign_maillists_sending_servers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `mail_list_id` bigint(20) unsigned NOT NULL,
  `sending_server_id` bigint(20) unsigned NOT NULL,
  `priority` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mls_unique` (`mail_list_id`,`sending_server_id`),
  KEY `campaign_maillists_sending_servers_sending_server_id_foreign` (`sending_server_id`),
  CONSTRAINT `campaign_maillists_sending_servers_mail_list_id_foreign` FOREIGN KEY (`mail_list_id`) REFERENCES `campaign_maillists` (`id`) ON DELETE CASCADE,
  CONSTRAINT `campaign_maillists_sending_servers_sending_server_id_foreign` FOREIGN KEY (`sending_server_id`) REFERENCES `campaign_sending_servers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `campaign_maillists_subscribers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `campaign_maillists_subscribers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uid` uuid NOT NULL,
  `mail_list_id` bigint(20) unsigned NOT NULL,
  `subscriber_id` bigint(20) unsigned NOT NULL,
  `status` varchar(32) NOT NULL DEFAULT 'subscribed',
  `tags` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`tags`)),
  `subscribed_at` timestamp NULL DEFAULT NULL,
  `unsubscribed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `campaign_maillists_subscribers_mail_list_id_subscriber_id_unique` (`mail_list_id`,`subscriber_id`),
  UNIQUE KEY `campaign_maillists_subscribers_uid_unique` (`uid`),
  KEY `campaign_maillists_subscribers_subscriber_id_foreign` (`subscriber_id`),
  KEY `campaign_maillists_subscribers_status_index` (`status`),
  CONSTRAINT `campaign_maillists_subscribers_mail_list_id_foreign` FOREIGN KEY (`mail_list_id`) REFERENCES `campaign_maillists` (`id`) ON DELETE CASCADE,
  CONSTRAINT `campaign_maillists_subscribers_subscriber_id_foreign` FOREIGN KEY (`subscriber_id`) REFERENCES `campaign_subscribers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `campaign_open_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `campaign_open_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tracking_log_id` bigint(20) unsigned NOT NULL,
  `ip` varchar(191) DEFAULT NULL,
  `user_agent` varchar(191) DEFAULT NULL,
  `country` varchar(191) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `campaign_open_logs_tracking_log_id_foreign` (`tracking_log_id`),
  CONSTRAINT `campaign_open_logs_tracking_log_id_foreign` FOREIGN KEY (`tracking_log_id`) REFERENCES `campaign_tracking_logs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `campaign_segment_conditions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `campaign_segment_conditions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `segment_id` bigint(20) unsigned NOT NULL,
  `field` varchar(191) NOT NULL,
  `operator` varchar(32) NOT NULL,
  `value` text DEFAULT NULL,
  `order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `campaign_segment_conditions_segment_id_foreign` (`segment_id`),
  KEY `campaign_segment_conditions_field_index` (`field`),
  CONSTRAINT `campaign_segment_conditions_segment_id_foreign` FOREIGN KEY (`segment_id`) REFERENCES `campaign_segments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `campaign_segments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `campaign_segments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uid` uuid NOT NULL,
  `mail_list_id` bigint(20) unsigned NOT NULL,
  `name` varchar(191) NOT NULL,
  `matching` varchar(16) NOT NULL DEFAULT 'and',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `campaign_segments_uid_unique` (`uid`),
  KEY `campaign_segments_mail_list_id_foreign` (`mail_list_id`),
  CONSTRAINT `campaign_segments_mail_list_id_foreign` FOREIGN KEY (`mail_list_id`) REFERENCES `campaign_maillists` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `campaign_sending_server_blacklists`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `campaign_sending_server_blacklists` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(191) NOT NULL,
  `reason` varchar(191) DEFAULT NULL,
  `source` varchar(32) NOT NULL DEFAULT 'manual',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `campaign_sending_server_blacklists_email_unique` (`email`),
  KEY `campaign_sending_server_blacklists_source_index` (`source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `campaign_sending_server_bounce_handlers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `campaign_sending_server_bounce_handlers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uid` uuid NOT NULL,
  `name` varchar(191) NOT NULL,
  `type` varchar(16) NOT NULL DEFAULT 'imap',
  `host` varchar(191) DEFAULT NULL,
  `port` int(11) DEFAULT NULL,
  `protocol` varchar(32) DEFAULT NULL,
  `username` varchar(191) DEFAULT NULL,
  `password` text DEFAULT NULL,
  `email` varchar(191) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `campaign_sending_server_bounce_handlers_uid_unique` (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `campaign_sending_server_bounce_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `campaign_sending_server_bounce_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(191) NOT NULL,
  `bounce_type` varchar(16) NOT NULL DEFAULT 'soft',
  `message_id` varchar(191) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `bounce_handler_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `campaign_sending_server_bounce_logs_bounce_handler_id_foreign` (`bounce_handler_id`),
  KEY `campaign_sending_server_bounce_logs_email_index` (`email`),
  KEY `campaign_sending_server_bounce_logs_message_id_index` (`message_id`),
  CONSTRAINT `campaign_sending_server_bounce_logs_bounce_handler_id_foreign` FOREIGN KEY (`bounce_handler_id`) REFERENCES `campaign_sending_server_bounce_handlers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `campaign_sending_server_domains`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `campaign_sending_server_domains` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uid` uuid NOT NULL,
  `name` varchar(191) NOT NULL,
  `sending_server_id` bigint(20) unsigned DEFAULT NULL,
  `status` varchar(32) NOT NULL DEFAULT 'pending',
  `signing_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `dkim_selector` varchar(191) DEFAULT NULL,
  `dkim_public_key` text DEFAULT NULL,
  `dkim_private_key` text DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `campaign_sending_server_domains_uid_unique` (`uid`),
  KEY `campaign_sending_server_domains_sending_server_id_foreign` (`sending_server_id`),
  KEY `campaign_sending_server_domains_name_index` (`name`),
  KEY `campaign_sending_server_domains_status_index` (`status`),
  CONSTRAINT `campaign_sending_server_domains_sending_server_id_foreign` FOREIGN KEY (`sending_server_id`) REFERENCES `campaign_sending_servers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `campaign_sending_server_feedback_handlers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `campaign_sending_server_feedback_handlers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uid` uuid NOT NULL,
  `name` varchar(191) NOT NULL,
  `type` varchar(16) NOT NULL DEFAULT 'imap',
  `host` varchar(191) DEFAULT NULL,
  `port` int(11) DEFAULT NULL,
  `protocol` varchar(32) DEFAULT NULL,
  `username` varchar(191) DEFAULT NULL,
  `password` text DEFAULT NULL,
  `email` varchar(191) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `campaign_sending_server_feedback_handlers_uid_unique` (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `campaign_sending_server_feedback_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `campaign_sending_server_feedback_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(191) NOT NULL,
  `message_id` varchar(191) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `feedback_loop_handler_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cssfl_handler_id_idx` (`feedback_loop_handler_id`),
  KEY `campaign_sending_server_feedback_logs_email_index` (`email`),
  KEY `campaign_sending_server_feedback_logs_message_id_index` (`message_id`),
  CONSTRAINT `cssfl_handler_id_fk` FOREIGN KEY (`feedback_loop_handler_id`) REFERENCES `campaign_sending_server_feedback_handlers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `campaign_sending_server_senders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `campaign_sending_server_senders` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uid` uuid NOT NULL,
  `email` varchar(191) NOT NULL,
  `name` varchar(191) DEFAULT NULL,
  `sending_server_id` bigint(20) unsigned NOT NULL,
  `status` varchar(32) NOT NULL DEFAULT 'pending',
  `verified_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `campaign_sending_server_senders_sending_server_id_email_unique` (`sending_server_id`,`email`),
  UNIQUE KEY `campaign_sending_server_senders_uid_unique` (`uid`),
  KEY `campaign_sending_server_senders_status_index` (`status`),
  CONSTRAINT `campaign_sending_server_senders_sending_server_id_foreign` FOREIGN KEY (`sending_server_id`) REFERENCES `campaign_sending_servers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `campaign_sending_server_tracking_domains`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `campaign_sending_server_tracking_domains` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uid` uuid NOT NULL,
  `name` varchar(191) NOT NULL,
  `status` varchar(32) NOT NULL DEFAULT 'pending',
  `verification_method` varchar(16) NOT NULL DEFAULT 'cname',
  `verified_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `campaign_sending_server_tracking_domains_uid_unique` (`uid`),
  UNIQUE KEY `campaign_sending_server_tracking_domains_name_unique` (`name`),
  KEY `campaign_sending_server_tracking_domains_status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `campaign_sending_servers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `campaign_sending_servers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uid` uuid NOT NULL,
  `name` varchar(191) NOT NULL,
  `type` varchar(64) NOT NULL,
  `status` varchar(32) NOT NULL DEFAULT 'active',
  `host` varchar(191) DEFAULT NULL,
  `smtp_username` text DEFAULT NULL,
  `smtp_password` text DEFAULT NULL,
  `smtp_port` int(11) DEFAULT NULL,
  `smtp_protocol` varchar(16) DEFAULT NULL,
  `sendmail_path` varchar(191) DEFAULT NULL,
  `aws_access_key_id` text DEFAULT NULL,
  `aws_secret_access_key` text DEFAULT NULL,
  `aws_region` varchar(32) DEFAULT NULL,
  `domain` varchar(191) DEFAULT NULL,
  `api_key` text DEFAULT NULL,
  `api_secret_key` text DEFAULT NULL,
  `quota_value` int(11) DEFAULT NULL,
  `quota_base` int(11) DEFAULT NULL,
  `quota_unit` varchar(32) DEFAULT NULL,
  `bounce_handler_id` bigint(20) unsigned DEFAULT NULL,
  `feedback_loop_handler_id` bigint(20) unsigned DEFAULT NULL,
  `default_from_email` varchar(191) DEFAULT NULL,
  `username` varchar(191) DEFAULT NULL,
  `options` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`options`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `campaign_sending_servers_uid_unique` (`uid`),
  KEY `campaign_sending_servers_type_index` (`type`),
  KEY `campaign_sending_servers_status_index` (`status`),
  KEY `campaign_sending_servers_bounce_handler_id_index` (`bounce_handler_id`),
  KEY `campaign_sending_servers_feedback_loop_handler_id_index` (`feedback_loop_handler_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `campaign_subscribers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `campaign_subscribers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uid` uuid NOT NULL,
  `email` varchar(191) NOT NULL,
  `attributes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`attributes`)),
  `ip` varchar(191) DEFAULT NULL,
  `source` varchar(32) NOT NULL DEFAULT 'web',
  `subscribed_at` timestamp NULL DEFAULT NULL,
  `unsubscribed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `first_name` varchar(191) DEFAULT NULL,
  `last_name` varchar(191) DEFAULT NULL,
  `verification_status` varchar(32) DEFAULT NULL,
  `verification_at` timestamp NULL DEFAULT NULL,
  `confirmation_code` varchar(64) DEFAULT NULL,
  `confirmed_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `campaign_subscribers_uid_unique` (`uid`),
  KEY `campaign_subscribers_email_index` (`email`),
  KEY `campaign_subscribers_verification_status_index` (`verification_status`),
  KEY `campaign_subscribers_confirmation_code_index` (`confirmation_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `campaign_template_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `campaign_template_categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uid` uuid NOT NULL,
  `name` varchar(191) NOT NULL,
  `order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `campaign_template_categories_uid_unique` (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `campaign_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `campaign_templates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uid` uuid NOT NULL,
  `name` varchar(191) NOT NULL,
  `title` varchar(191) DEFAULT NULL,
  `subject` varchar(191) DEFAULT NULL,
  `content` longtext DEFAULT NULL,
  `blocks` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`blocks`)),
  `global_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`global_settings`)),
  `html` longtext DEFAULT NULL,
  `plain` longtext DEFAULT NULL,
  `image` varchar(191) DEFAULT NULL,
  `shared` tinyint(1) NOT NULL DEFAULT 0,
  `layout_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `categories` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`categories`)),
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `campaign_templates_uid_unique` (`uid`),
  KEY `campaign_templates_layout_id_foreign` (`layout_id`),
  CONSTRAINT `campaign_templates_layout_id_foreign` FOREIGN KEY (`layout_id`) REFERENCES `campaign_layouts` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `campaign_templates_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `campaign_templates_categories` (
  `template_id` bigint(20) unsigned NOT NULL,
  `category_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`template_id`,`category_id`),
  KEY `campaign_templates_categories_category_id_foreign` (`category_id`),
  CONSTRAINT `campaign_templates_categories_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `campaign_template_categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `campaign_templates_categories_template_id_foreign` FOREIGN KEY (`template_id`) REFERENCES `campaign_templates` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `campaign_tracking_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `campaign_tracking_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uid` uuid NOT NULL,
  `campaign_id` bigint(20) unsigned DEFAULT NULL,
  `subscriber_id` bigint(20) unsigned DEFAULT NULL,
  `sending_server_id` bigint(20) unsigned DEFAULT NULL,
  `email` varchar(191) NOT NULL,
  `message_id` varchar(191) DEFAULT NULL,
  `runtime_message_id` varchar(191) DEFAULT NULL,
  `status` varchar(32) NOT NULL DEFAULT 'sent',
  `error` text DEFAULT NULL,
  `trigger_id` varchar(191) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `campaign_tracking_logs_uid_unique` (`uid`),
  KEY `campaign_tracking_logs_subscriber_id_foreign` (`subscriber_id`),
  KEY `campaign_tracking_logs_sending_server_id_foreign` (`sending_server_id`),
  KEY `campaign_tracking_logs_campaign_id_status_index` (`campaign_id`,`status`),
  KEY `campaign_tracking_logs_email_index` (`email`),
  KEY `campaign_tracking_logs_message_id_index` (`message_id`),
  KEY `campaign_tracking_logs_status_index` (`status`),
  KEY `campaign_tracking_logs_trigger_id_index` (`trigger_id`),
  CONSTRAINT `campaign_tracking_logs_campaign_id_foreign` FOREIGN KEY (`campaign_id`) REFERENCES `campaigns` (`id`) ON DELETE CASCADE,
  CONSTRAINT `campaign_tracking_logs_sending_server_id_foreign` FOREIGN KEY (`sending_server_id`) REFERENCES `campaign_sending_servers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `campaign_tracking_logs_subscriber_id_foreign` FOREIGN KEY (`subscriber_id`) REFERENCES `campaign_subscribers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `campaign_trigger_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `campaign_trigger_sessions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uid` uuid NOT NULL,
  `auto_trigger_id` bigint(20) unsigned NOT NULL,
  `status` varchar(32) NOT NULL DEFAULT 'queued',
  `last_error` text DEFAULT NULL,
  `finished_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `campaign_trigger_sessions_uid_unique` (`uid`),
  KEY `campaign_trigger_sessions_auto_trigger_id_foreign` (`auto_trigger_id`),
  KEY `campaign_trigger_sessions_status_index` (`status`),
  CONSTRAINT `campaign_trigger_sessions_auto_trigger_id_foreign` FOREIGN KEY (`auto_trigger_id`) REFERENCES `campaign_auto_triggers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `campaign_unsubscribe_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `campaign_unsubscribe_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tracking_log_id` bigint(20) unsigned DEFAULT NULL,
  `subscriber_id` bigint(20) unsigned NOT NULL,
  `ip` varchar(191) DEFAULT NULL,
  `reason` varchar(191) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `campaign_unsubscribe_logs_tracking_log_id_foreign` (`tracking_log_id`),
  KEY `campaign_unsubscribe_logs_subscriber_id_foreign` (`subscriber_id`),
  CONSTRAINT `campaign_unsubscribe_logs_subscriber_id_foreign` FOREIGN KEY (`subscriber_id`) REFERENCES `campaign_subscribers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `campaign_unsubscribe_logs_tracking_log_id_foreign` FOREIGN KEY (`tracking_log_id`) REFERENCES `campaign_tracking_logs` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `campaign_webhooks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `campaign_webhooks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uid` uuid NOT NULL,
  `campaign_id` bigint(20) unsigned NOT NULL,
  `name` varchar(191) DEFAULT NULL,
  `event` varchar(64) NOT NULL,
  `url` varchar(191) NOT NULL,
  `method` varchar(8) NOT NULL DEFAULT 'POST',
  `headers` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`headers`)),
  `enabled` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `secret` varchar(128) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `campaign_webhooks_uid_unique` (`uid`),
  KEY `campaign_webhooks_campaign_id_foreign` (`campaign_id`),
  KEY `campaign_webhooks_event_index` (`event`),
  CONSTRAINT `campaign_webhooks_campaign_id_foreign` FOREIGN KEY (`campaign_id`) REFERENCES `campaigns` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `campaigns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `campaigns` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uid` uuid NOT NULL,
  `customer_id` bigint(20) unsigned DEFAULT NULL,
  `type` varchar(32) NOT NULL DEFAULT 'regular',
  `name` varchar(191) DEFAULT NULL,
  `title` varchar(191) DEFAULT NULL,
  `subject` varchar(191) DEFAULT NULL,
  `plain` text DEFAULT NULL,
  `html` longtext DEFAULT NULL,
  `final_html` longtext DEFAULT NULL,
  `html_signature` varchar(191) DEFAULT NULL,
  `preheader` varchar(191) DEFAULT NULL,
  `from_email` varchar(191) DEFAULT NULL,
  `from_name` varchar(191) DEFAULT NULL,
  `reply_to` varchar(191) DEFAULT NULL,
  `status` varchar(32) NOT NULL DEFAULT 'new',
  `sign_dkim` tinyint(1) NOT NULL DEFAULT 0,
  `track_open` tinyint(1) NOT NULL DEFAULT 1,
  `track_click` tinyint(1) NOT NULL DEFAULT 1,
  `track_fbl` tinyint(1) NOT NULL DEFAULT 0,
  `resend` tinyint(1) NOT NULL DEFAULT 0,
  `skip_failed_message` tinyint(1) NOT NULL DEFAULT 1,
  `use_default_sending_server_from_email` tinyint(1) NOT NULL DEFAULT 0,
  `run_at` timestamp NULL DEFAULT NULL,
  `delivery_at` timestamp NULL DEFAULT NULL,
  `template_source` varchar(191) DEFAULT NULL,
  `last_error` text DEFAULT NULL,
  `image` varchar(191) DEFAULT NULL,
  `default_maillist_id` bigint(20) unsigned DEFAULT NULL,
  `tracking_domain_id` bigint(20) unsigned DEFAULT NULL,
  `running_pid` int(11) DEFAULT NULL,
  `template_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `score` double DEFAULT NULL,
  `step` varchar(32) DEFAULT NULL,
  `last_run_at` timestamp NULL DEFAULT NULL,
  `attachments_meta` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`attachments_meta`)),
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `campaigns_uid_unique` (`uid`),
  KEY `campaigns_customer_id_index` (`customer_id`),
  KEY `campaigns_name_index` (`name`),
  KEY `campaigns_status_index` (`status`),
  KEY `campaigns_run_at_index` (`run_at`),
  KEY `campaigns_default_maillist_id_index` (`default_maillist_id`),
  KEY `campaigns_tracking_domain_id_index` (`tracking_domain_id`),
  KEY `campaigns_template_id_index` (`template_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uid` varchar(191) NOT NULL,
  `title` varchar(191) NOT NULL,
  `parent_id` bigint(20) unsigned DEFAULT NULL,
  `position` int(11) NOT NULL DEFAULT 0,
  `depth` int(11) NOT NULL DEFAULT 0,
  `slug` varchar(191) DEFAULT NULL,
  `meta_title` varchar(191) DEFAULT NULL,
  `meta_description` text DEFAULT NULL,
  `meta_keywords` varchar(191) DEFAULT NULL,
  `icon` varchar(191) DEFAULT NULL,
  `color` varchar(191) DEFAULT NULL,
  `image` varchar(191) DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `visibility` enum('public','private','hidden') NOT NULL DEFAULT 'public',
  `prestashop_id` int(11) DEFAULT NULL,
  `last_synced_at` timestamp NULL DEFAULT NULL,
  `sync_direction` enum('laravel_to_ps','ps_to_laravel','bidirectional') NOT NULL DEFAULT 'bidirectional',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `categories_uid_unique` (`uid`),
  UNIQUE KEY `categories_slug_unique` (`slug`),
  UNIQUE KEY `categories_prestashop_id_unique` (`prestashop_id`),
  KEY `categories_active_index` (`active`),
  KEY `categories_parent_id_index` (`parent_id`),
  KEY `categories_prestashop_id_index` (`prestashop_id`),
  KEY `hierarchy_position_idx` (`parent_id`,`position`),
  KEY `state_visibility_idx` (`active`,`visibility`),
  CONSTRAINT `categories_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `colombian_holidays`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `colombian_holidays` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL COMMENT 'Fecha del festivo',
  `name` varchar(191) NOT NULL COMMENT 'Nombre del festivo',
  `type` enum('fixed','movable') NOT NULL DEFAULT 'fixed' COMMENT 'Tipo de festivo: fijo o móvil',
  `is_monday_law` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Se traslada al lunes siguiente según Ley Emiliani',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `colombian_holidays_date_unique` (`date`),
  KEY `colombian_holidays_date_index` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cookie_consent_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cookie_consent_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `session_id` varchar(64) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_hash` varchar(64) NOT NULL,
  `action` varchar(20) NOT NULL,
  `accepted_categories` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`accepted_categories`)),
  `user_agent` varchar(500) DEFAULT NULL,
  `version` varchar(20) NOT NULL DEFAULT '1.0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cookie_consent_logs_user_id_foreign` (`user_id`),
  KEY `cookie_consent_logs_session_id_index` (`session_id`),
  KEY `cookie_consent_logs_action_index` (`action`),
  KEY `cookie_consent_logs_ip_hash_created_at_index` (`ip_hash`,`created_at`),
  CONSTRAINT `cookie_consent_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cookie_inventory`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cookie_inventory` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `provider` varchar(100) NOT NULL,
  `category` varchar(30) NOT NULL,
  `purpose` varchar(255) NOT NULL,
  `duration` varchar(50) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cookie_inventory_category_index` (`category`),
  KEY `cookie_inventory_is_active_index` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `core_langs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `core_langs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uid` varchar(191) NOT NULL,
  `title` varchar(191) NOT NULL,
  `iso_code` varchar(191) NOT NULL,
  `lenguage_code` varchar(191) NOT NULL,
  `locate` varchar(191) DEFAULT NULL,
  `date_format_full` varchar(191) DEFAULT NULL,
  `date_format_lite` varchar(191) DEFAULT NULL,
  `available` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `core_langs_uid_unique` (`uid`),
  UNIQUE KEY `core_langs_iso_code_unique` (`iso_code`),
  UNIQUE KEY `core_langs_lenguage_code_unique` (`lenguage_code`),
  KEY `core_langs_available_index` (`available`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `countries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `countries` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uid` varchar(191) NOT NULL,
  `title` varchar(191) NOT NULL,
  `iso_code` varchar(191) NOT NULL,
  `call_prefix` varchar(191) DEFAULT NULL,
  `available` tinyint(1) NOT NULL DEFAULT 1,
  `currency_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `countries_uid_unique` (`uid`),
  UNIQUE KEY `countries_iso_code_unique` (`iso_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ecommerce_affiliate_referrals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ecommerce_affiliate_referrals` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `affiliate_id` bigint(20) unsigned NOT NULL,
  `order_id` bigint(20) unsigned NOT NULL,
  `customer_id` bigint(20) unsigned NOT NULL,
  `order_total` decimal(10,2) NOT NULL,
  `commission_amount` decimal(10,2) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ecommerce_affiliate_referrals_order_id_unique` (`order_id`),
  KEY `ecommerce_affiliate_referrals_affiliate_id_foreign` (`affiliate_id`),
  KEY `ecommerce_affiliate_referrals_customer_id_foreign` (`customer_id`),
  CONSTRAINT `ecommerce_affiliate_referrals_affiliate_id_foreign` FOREIGN KEY (`affiliate_id`) REFERENCES `ecommerce_affiliates` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ecommerce_affiliate_referrals_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `ecommerce_customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ecommerce_affiliate_referrals_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `ecommerce_orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ecommerce_affiliates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ecommerce_affiliates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint(20) unsigned NOT NULL,
  `code` varchar(30) NOT NULL,
  `commission_rate` decimal(5,2) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `total_earned` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_paid` decimal(12,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ecommerce_affiliates_code_unique` (`code`),
  KEY `ecommerce_affiliates_customer_id_foreign` (`customer_id`),
  KEY `ecommerce_affiliates_code_index` (`code`),
  CONSTRAINT `ecommerce_affiliates_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `ecommerce_customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ecommerce_brands`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ecommerce_brands` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `slug` varchar(191) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `website` varchar(191) DEFAULT NULL,
  `logo` varchar(191) DEFAULT NULL,
  `status` varchar(60) NOT NULL DEFAULT 'published',
  `order` int(10) unsigned NOT NULL DEFAULT 0,
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ecommerce_brands_slug_unique` (`slug`),
  KEY `ecommerce_brands_status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ecommerce_bundle_products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ecommerce_bundle_products` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `bundle_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `qty` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ecommerce_bundle_products_bundle_id_foreign` (`bundle_id`),
  KEY `ecommerce_bundle_products_product_id_foreign` (`product_id`),
  CONSTRAINT `ecommerce_bundle_products_bundle_id_foreign` FOREIGN KEY (`bundle_id`) REFERENCES `ecommerce_bundles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ecommerce_bundle_products_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `ecommerce_products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ecommerce_bundles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ecommerce_bundles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `discount_type` varchar(20) NOT NULL,
  `discount_value` decimal(10,2) NOT NULL,
  `image` varchar(191) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'published',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ecommerce_bundles_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ecommerce_carts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ecommerce_carts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint(20) unsigned DEFAULT NULL,
  `session_id` varchar(191) DEFAULT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `qty` int(10) unsigned NOT NULL DEFAULT 1,
  `options` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`options`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ecommerce_carts_product_id_foreign` (`product_id`),
  KEY `ecommerce_carts_customer_id_index` (`customer_id`),
  KEY `ecommerce_carts_session_id_index` (`session_id`),
  CONSTRAINT `ecommerce_carts_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `ecommerce_customers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ecommerce_carts_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `ecommerce_products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ecommerce_currencies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ecommerce_currencies` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(191) NOT NULL,
  `symbol` varchar(10) NOT NULL,
  `is_prefix_symbol` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `decimals` tinyint(3) unsigned DEFAULT 0,
  `order` int(10) unsigned DEFAULT 0,
  `is_default` tinyint(4) NOT NULL DEFAULT 0,
  `exchange_rate` double NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ecommerce_customer_addresses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ecommerce_customer_addresses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint(20) unsigned NOT NULL,
  `name` varchar(191) NOT NULL,
  `phone` varchar(191) DEFAULT NULL,
  `email` varchar(191) DEFAULT NULL,
  `country` varchar(191) DEFAULT NULL,
  `state` varchar(191) DEFAULT NULL,
  `city` varchar(191) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `zip_code` varchar(191) DEFAULT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `type` varchar(60) NOT NULL DEFAULT 'shipping',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ecommerce_customer_addresses_customer_id_index` (`customer_id`),
  CONSTRAINT `ecommerce_customer_addresses_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `ecommerce_customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ecommerce_customer_deletion_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ecommerce_customer_deletion_requests` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint(20) unsigned NOT NULL,
  `token` varchar(191) DEFAULT NULL,
  `status` varchar(60) NOT NULL DEFAULT 'pending',
  `confirmed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ecommerce_customer_password_resets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ecommerce_customer_password_resets` (
  `email` varchar(191) NOT NULL,
  `token` varchar(191) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  KEY `ecommerce_customer_password_resets_email_index` (`email`),
  KEY `ecommerce_customer_password_resets_token_index` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ecommerce_customer_push_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ecommerce_customer_push_tokens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint(20) unsigned NOT NULL,
  `token` varchar(512) NOT NULL,
  `platform` enum('ios','android') NOT NULL,
  `device_id` varchar(191) DEFAULT NULL,
  `app_version` varchar(32) DEFAULT NULL,
  `locale` varchar(8) DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ecommerce_customer_push_tokens_token_unique` (`token`),
  KEY `ecommerce_customer_push_tokens_customer_id_is_active_index` (`customer_id`,`is_active`),
  CONSTRAINT `ecommerce_customer_push_tokens_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `ecommerce_customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ecommerce_customer_recently_viewed_products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ecommerce_customer_recently_viewed_products` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint(20) unsigned DEFAULT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `session_id` varchar(191) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ecommerce_customer_used_coupons`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ecommerce_customer_used_coupons` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint(20) unsigned NOT NULL,
  `discount_id` bigint(20) unsigned NOT NULL,
  `order_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ecommerce_customers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ecommerce_customers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `email` varchar(191) NOT NULL,
  `password` varchar(191) NOT NULL,
  `avatar` varchar(191) DEFAULT NULL,
  `phone` varchar(191) DEFAULT NULL,
  `status` varchar(60) NOT NULL DEFAULT 'active',
  `private_notes` text DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `email_verification_token` varchar(191) DEFAULT NULL,
  `provider` varchar(50) DEFAULT NULL,
  `provider_id` varchar(191) DEFAULT NULL,
  `wishlist_share_token` varchar(60) DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ecommerce_customers_email_unique` (`email`),
  UNIQUE KEY `ecommerce_customers_wishlist_share_token_unique` (`wishlist_share_token`),
  KEY `ecommerce_customers_status_index` (`status`),
  KEY `ecommerce_customers_email_index` (`email`),
  KEY `ecommerce_customers_provider_provider_id_index` (`provider`,`provider_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ecommerce_discount_customers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ecommerce_discount_customers` (
  `discount_id` bigint(20) unsigned NOT NULL,
  `customer_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`discount_id`,`customer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ecommerce_discount_product_collections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ecommerce_discount_product_collections` (
  `discount_id` bigint(20) unsigned NOT NULL,
  `product_collection_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`discount_id`,`product_collection_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ecommerce_discount_products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ecommerce_discount_products` (
  `discount_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`discount_id`,`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ecommerce_discounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ecommerce_discounts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(191) NOT NULL,
  `code` varchar(191) DEFAULT NULL,
  `start_date` timestamp NULL DEFAULT NULL,
  `end_date` timestamp NULL DEFAULT NULL,
  `quantity` int(10) unsigned DEFAULT NULL,
  `total_used` int(10) unsigned NOT NULL DEFAULT 0,
  `value` decimal(15,2) NOT NULL DEFAULT 0.00,
  `type` varchar(60) NOT NULL DEFAULT 'fixed',
  `target` varchar(60) NOT NULL DEFAULT 'all_products',
  `min_order_price` decimal(15,2) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ecommerce_discounts_code_unique` (`code`),
  KEY `ecommerce_discounts_code_index` (`code`),
  KEY `ecommerce_discounts_is_active_index` (`is_active`),
  KEY `ecommerce_discounts_type_index` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ecommerce_email_campaign_recipients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ecommerce_email_campaign_recipients` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `campaign_id` bigint(20) unsigned NOT NULL,
  `customer_id` bigint(20) unsigned NOT NULL,
  `email` varchar(255) NOT NULL,
  `token` varchar(60) NOT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `opened_at` timestamp NULL DEFAULT NULL,
  `clicked_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ecommerce_email_campaign_recipients_token_unique` (`token`),
  KEY `ecommerce_email_campaign_recipients_customer_id_foreign` (`customer_id`),
  KEY `ecommerce_email_campaign_recipients_campaign_id_opened_at_index` (`campaign_id`,`opened_at`),
  KEY `ecommerce_email_campaign_recipients_token_index` (`token`),
  CONSTRAINT `ecommerce_email_campaign_recipients_campaign_id_foreign` FOREIGN KEY (`campaign_id`) REFERENCES `ecommerce_email_campaigns` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ecommerce_email_campaign_recipients_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `ecommerce_customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ecommerce_email_campaigns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ecommerce_email_campaigns` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `subject` varchar(500) NOT NULL,
  `content` longtext NOT NULL,
  `segment` varchar(50) NOT NULL DEFAULT 'all',
  `status` varchar(20) NOT NULL DEFAULT 'draft',
  `scheduled_at` timestamp NULL DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `recipients_count` int(11) NOT NULL DEFAULT 0,
  `opens_count` int(11) NOT NULL DEFAULT 0,
  `clicks_count` int(11) NOT NULL DEFAULT 0,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ecommerce_email_campaigns_created_by_foreign` (`created_by`),
  KEY `ecommerce_email_campaigns_status_scheduled_at_index` (`status`,`scheduled_at`),
  CONSTRAINT `ecommerce_email_campaigns_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ecommerce_flash_sale_products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ecommerce_flash_sale_products` (
  `flash_sale_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `price` decimal(15,2) NOT NULL DEFAULT 0.00,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `sold` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`flash_sale_id`,`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ecommerce_flash_sales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ecommerce_flash_sales` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `start_date` timestamp NULL DEFAULT NULL,
  `end_date` timestamp NULL DEFAULT NULL,
  `status` varchar(60) NOT NULL DEFAULT 'published',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ecommerce_gift_cards`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ecommerce_gift_cards` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(30) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `balance` decimal(10,2) NOT NULL,
  `buyer_customer_id` bigint(20) unsigned DEFAULT NULL,
  `recipient_email` varchar(255) DEFAULT NULL,
  `recipient_name` varchar(255) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `expires_at` timestamp NULL DEFAULT NULL,
  `order_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ecommerce_gift_cards_code_unique` (`code`),
  KEY `ecommerce_gift_cards_buyer_customer_id_foreign` (`buyer_customer_id`),
  KEY `ecommerce_gift_cards_order_id_foreign` (`order_id`),
  KEY `ecommerce_gift_cards_code_index` (`code`),
  KEY `ecommerce_gift_cards_status_index` (`status`),
  CONSTRAINT `ecommerce_gift_cards_buyer_customer_id_foreign` FOREIGN KEY (`buyer_customer_id`) REFERENCES `ecommerce_customers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ecommerce_gift_cards_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `ecommerce_orders` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ecommerce_global_option_values`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ecommerce_global_option_values` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `option_id` bigint(20) unsigned NOT NULL,
  `option_value` varchar(191) NOT NULL,
  `affect_price` decimal(15,2) DEFAULT 0.00,
  `affect_type` varchar(20) DEFAULT 'fixed',
  `order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ecommerce_global_options`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ecommerce_global_options` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `option_type` varchar(60) NOT NULL DEFAULT 'text',
  `required` tinyint(1) NOT NULL DEFAULT 0,
  `order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ecommerce_grouped_products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ecommerce_grouped_products` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `parent_product_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `fixed_qty` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ecommerce_invoice_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ecommerce_invoice_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `invoice_id` bigint(20) unsigned NOT NULL,
  `reference_type` varchar(191) NOT NULL,
  `reference_id` bigint(20) unsigned NOT NULL,
  `name` varchar(191) NOT NULL,
  `description` varchar(400) DEFAULT NULL,
  `image` varchar(191) DEFAULT NULL,
  `qty` int(10) unsigned NOT NULL,
  `sub_total` decimal(15,2) unsigned NOT NULL,
  `tax_amount` decimal(15,2) unsigned NOT NULL DEFAULT 0.00,
  `discount_amount` decimal(15,2) unsigned NOT NULL DEFAULT 0.00,
  `amount` decimal(15,2) unsigned NOT NULL,
  `options` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ecommerce_invoice_items_reference_type_reference_id_index` (`reference_type`,`reference_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ecommerce_invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ecommerce_invoices` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `reference_type` varchar(191) NOT NULL,
  `reference_id` bigint(20) unsigned NOT NULL,
  `code` varchar(191) NOT NULL,
  `customer_name` varchar(191) DEFAULT NULL,
  `company_name` varchar(191) DEFAULT NULL,
  `company_logo` varchar(191) DEFAULT NULL,
  `customer_email` varchar(191) DEFAULT NULL,
  `customer_phone` varchar(191) DEFAULT NULL,
  `customer_address` varchar(191) DEFAULT NULL,
  `customer_tax_id` varchar(191) DEFAULT NULL,
  `sub_total` decimal(15,2) unsigned NOT NULL,
  `tax_amount` decimal(15,2) unsigned NOT NULL DEFAULT 0.00,
  `shipping_amount` decimal(15,2) unsigned NOT NULL DEFAULT 0.00,
  `discount_amount` decimal(15,2) unsigned NOT NULL DEFAULT 0.00,
  `shipping_option` varchar(60) DEFAULT NULL,
  `shipping_method` varchar(60) NOT NULL DEFAULT 'default',
  `coupon_code` varchar(120) DEFAULT NULL,
  `discount_description` varchar(191) DEFAULT NULL,
  `amount` decimal(15,2) unsigned NOT NULL,
  `description` text DEFAULT NULL,
  `payment_id` bigint(20) unsigned DEFAULT NULL,
  `status` varchar(191) NOT NULL DEFAULT 'pending',
  `paid_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ecommerce_invoices_code_unique` (`code`),
  KEY `ecommerce_invoices_reference_type_reference_id_index` (`reference_type`,`reference_id`),
  KEY `ecommerce_invoices_payment_id_index` (`payment_id`),
  KEY `ecommerce_invoices_status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ecommerce_legal_pages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ecommerce_legal_pages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `slug` varchar(100) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` longtext NOT NULL,
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` varchar(500) DEFAULT NULL,
  `is_published` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ecommerce_legal_pages_slug_unique` (`slug`),
  KEY `ecommerce_legal_pages_slug_is_published_index` (`slug`,`is_published`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ecommerce_newsletter_subscribers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ecommerce_newsletter_subscribers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'subscribed',
  `source` varchar(50) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `subscribed_at` timestamp NULL DEFAULT NULL,
  `unsubscribed_at` timestamp NULL DEFAULT NULL,
  `unsubscribe_token` varchar(80) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ecommerce_newsletter_subscribers_email_unique` (`email`),
  UNIQUE KEY `ecommerce_newsletter_subscribers_unsubscribe_token_unique` (`unsubscribe_token`),
  KEY `ecommerce_newsletter_subscribers_email_index` (`email`),
  KEY `ecommerce_newsletter_subscribers_status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ecommerce_option_product`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ecommerce_option_product` (
  `option_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`option_id`,`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ecommerce_option_values`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ecommerce_option_values` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `option_id` bigint(20) unsigned NOT NULL,
  `option_value` varchar(191) NOT NULL,
  `affect_price` decimal(15,2) DEFAULT 0.00,
  `affect_type` varchar(20) DEFAULT 'fixed',
  `order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ecommerce_options`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ecommerce_options` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `option_type` varchar(60) NOT NULL DEFAULT 'text',
  `required` tinyint(1) NOT NULL DEFAULT 0,
  `order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ecommerce_order_addresses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ecommerce_order_addresses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(191) DEFAULT NULL,
  `country` varchar(120) DEFAULT NULL,
  `state` varchar(120) DEFAULT NULL,
  `city` varchar(120) DEFAULT NULL,
  `country_id` bigint(20) unsigned DEFAULT NULL,
  `state_id` bigint(20) unsigned DEFAULT NULL,
  `city_id` bigint(20) unsigned DEFAULT NULL,
  `zip_code` varchar(20) DEFAULT NULL,
  `address` varchar(191) DEFAULT NULL,
  `order_id` bigint(20) unsigned NOT NULL,
  `type` varchar(20) NOT NULL DEFAULT 'shipping',
  PRIMARY KEY (`id`),
  KEY `ecommerce_order_addresses_country_id_index` (`country_id`),
  KEY `ecommerce_order_addresses_state_id_index` (`state_id`),
  KEY `ecommerce_order_addresses_city_id_index` (`city_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ecommerce_order_histories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ecommerce_order_histories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint(20) unsigned NOT NULL,
  `action` varchar(191) NOT NULL,
  `description` text DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ecommerce_order_histories_user_id_foreign` (`user_id`),
  KEY `ecommerce_order_histories_order_id_index` (`order_id`),
  CONSTRAINT `ecommerce_order_histories_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `ecommerce_orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ecommerce_order_histories_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ecommerce_order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ecommerce_order_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned DEFAULT NULL,
  `product_type` varchar(60) NOT NULL DEFAULT 'physical',
  `times_downloaded` int(11) NOT NULL DEFAULT 0,
  `product_name` varchar(191) NOT NULL,
  `product_image` varchar(191) DEFAULT NULL,
  `qty` int(10) unsigned NOT NULL,
  `price` decimal(15,2) NOT NULL,
  `total` decimal(15,2) NOT NULL,
  `options` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`options`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ecommerce_order_items_product_id_foreign` (`product_id`),
  KEY `ecommerce_order_items_order_id_index` (`order_id`),
  CONSTRAINT `ecommerce_order_items_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `ecommerce_orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ecommerce_order_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `ecommerce_products` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ecommerce_order_referrals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ecommerce_order_referrals` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint(20) unsigned NOT NULL,
  `ip` varchar(191) DEFAULT NULL,
  `landing_domain` varchar(191) DEFAULT NULL,
  `landing_page` varchar(191) DEFAULT NULL,
  `landing_params` text DEFAULT NULL,
  `referral_domain` varchar(191) DEFAULT NULL,
  `referral_url` text DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ecommerce_order_return_histories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ecommerce_order_return_histories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_return_id` bigint(20) unsigned NOT NULL,
  `action` varchar(120) NOT NULL,
  `description` varchar(400) DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ecommerce_order_return_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ecommerce_order_return_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_return_id` bigint(20) unsigned NOT NULL,
  `order_product_id` bigint(20) unsigned DEFAULT NULL,
  `product_id` bigint(20) unsigned DEFAULT NULL,
  `product_name` varchar(191) DEFAULT NULL,
  `qty` int(11) NOT NULL DEFAULT 1,
  `reason` varchar(191) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ecommerce_order_returns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ecommerce_order_returns` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint(20) unsigned NOT NULL,
  `store_id` bigint(20) unsigned DEFAULT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `reason` text DEFAULT NULL,
  `order_status` varchar(191) DEFAULT NULL,
  `return_status` varchar(191) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ecommerce_order_tax_information`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ecommerce_order_tax_information` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint(20) unsigned NOT NULL,
  `company_name` varchar(191) DEFAULT NULL,
  `company_address` varchar(191) DEFAULT NULL,
  `company_email` varchar(191) DEFAULT NULL,
  `company_phone` varchar(191) DEFAULT NULL,
  `company_tax_code` varchar(191) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ecommerce_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ecommerce_orders` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(191) NOT NULL,
  `token` varchar(191) DEFAULT NULL,
  `customer_id` bigint(20) unsigned DEFAULT NULL,
  `status` varchar(60) NOT NULL DEFAULT 'pending',
  `sub_total` decimal(15,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `shipping_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `discount_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total` decimal(15,2) NOT NULL DEFAULT 0.00,
  `coupon_code` varchar(191) DEFAULT NULL,
  `discount_description` varchar(191) DEFAULT NULL,
  `shipping_method` varchar(191) DEFAULT NULL,
  `shipping_option` varchar(191) DEFAULT NULL,
  `payment_method` varchar(191) DEFAULT NULL,
  `transaction_id` varchar(191) DEFAULT NULL,
  `payment_status` varchar(60) NOT NULL DEFAULT 'pending',
  `customer_note` text DEFAULT NULL,
  `admin_note` text DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `delivery_date` date DEFAULT NULL,
  `delivery_time` time DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ecommerce_orders_code_unique` (`code`),
  UNIQUE KEY `ecommerce_orders_token_unique` (`token`),
  KEY `ecommerce_orders_status_index` (`status`),
  KEY `ecommerce_orders_customer_id_index` (`customer_id`),
  KEY `ecommerce_orders_payment_status_index` (`payment_status`),
  KEY `ecommerce_orders_code_index` (`code`),
  CONSTRAINT `ecommerce_orders_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `ecommerce_customers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ecommerce_page_views`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ecommerce_page_views` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `session_id` varchar(80) NOT NULL,
  `customer_id` bigint(20) unsigned DEFAULT NULL,
  `url` varchar(500) NOT NULL,
  `referrer` varchar(500) DEFAULT NULL,
  `product_id` bigint(20) unsigned DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `viewed_at` timestamp NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ecommerce_page_views_product_id_foreign` (`product_id`),
  KEY `ecommerce_page_views_session_id_viewed_at_index` (`session_id`,`viewed_at`),
  KEY `ecommerce_page_views_customer_id_index` (`customer_id`),
  CONSTRAINT `ecommerce_page_views_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `ecommerce_customers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ecommerce_page_views_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `ecommerce_products` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ecommerce_payment_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ecommerce_payment_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `payment_id` bigint(20) unsigned DEFAULT NULL,
  `payment_method` varchar(60) DEFAULT NULL,
  `request` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`request`)),
  `response` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`response`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ecommerce_payment_logs_payment_id_index` (`payment_id`),
  CONSTRAINT `ecommerce_payment_logs_payment_id_foreign` FOREIGN KEY (`payment_id`) REFERENCES `ecommerce_payments` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ecommerce_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ecommerce_payments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `charge_id` varchar(191) NOT NULL,
  `order_id` bigint(20) unsigned DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `payment_fee` decimal(15,2) DEFAULT NULL,
  `currency` varchar(10) NOT NULL DEFAULT 'COP',
  `payment_channel` varchar(60) NOT NULL DEFAULT 'wompi',
  `description` text DEFAULT NULL,
  `status` varchar(60) NOT NULL DEFAULT 'pending',
  `payment_type` varchar(60) DEFAULT NULL,
  `customer_id` bigint(20) unsigned DEFAULT NULL,
  `customer_type` varchar(191) DEFAULT NULL,
  `refunded_amount` decimal(15,2) DEFAULT NULL,
  `refund_note` text DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ecommerce_payments_charge_id_unique` (`charge_id`),
  KEY `ecommerce_payments_order_id_index` (`order_id`),
  KEY `ecommerce_payments_charge_id_index` (`charge_id`),
  KEY `ecommerce_payments_status_index` (`status`),
  KEY `ecommerce_payments_payment_channel_index` (`payment_channel`),
  CONSTRAINT `ecommerce_payments_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `ecommerce_orders` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ecommerce_product_attribute_sets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ecommerce_product_attribute_sets` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(120) NOT NULL,
  `slug` varchar(120) DEFAULT NULL,
  `display_layout` varchar(191) NOT NULL DEFAULT 'swatch_dropdown',
  `is_searchable` tinyint(3) unsigned NOT NULL DEFAULT 1,
  `is_comparable` tinyint(3) unsigned NOT NULL DEFAULT 1,
  `is_use_in_product_listing` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `status` varchar(60) NOT NULL DEFAULT 'published',
  `order` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ecommerce_product_attributes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ecommerce_product_attributes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `attribute_set_id` bigint(20) unsigned NOT NULL,
  `title` varchar(120) NOT NULL,
  `slug` varchar(120) DEFAULT NULL,
  `color` varchar(120) DEFAULT NULL,
  `image` varchar(191) DEFAULT NULL,
  `is_default` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `order` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `status` varchar(60) NOT NULL DEFAULT 'published',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ecommerce_product_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ecommerce_product_categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `parent_id` bigint(20) unsigned DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` varchar(60) NOT NULL DEFAULT 'published',
  `order` int(10) unsigned NOT NULL DEFAULT 0,
  `image` varchar(191) DEFAULT NULL,
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `slug` varchar(191) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ecommerce_product_categories_slug_unique` (`slug`),
  KEY `ecommerce_product_categories_status_index` (`status`),
  KEY `ecommerce_product_categories_parent_id_index` (`parent_id`),
  CONSTRAINT `ecommerce_product_categories_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `ecommerce_product_categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ecommerce_product_category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ecommerce_product_category` (
  `product_id` bigint(20) unsigned NOT NULL,
  `category_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`product_id`,`category_id`),
  KEY `ecommerce_product_category_category_id_foreign` (`category_id`),
  CONSTRAINT `ecommerce_product_category_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `ecommerce_product_categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ecommerce_product_category_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `ecommerce_products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ecommerce_product_collection_products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ecommerce_product_collection_products` (
  `product_collection_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`product_id`,`product_collection_id`),
  KEY `ec_coll_prod_coll_id_idx` (`product_collection_id`),
  KEY `ec_coll_prod_prod_id_idx` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ecommerce_product_collections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ecommerce_product_collections` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `slug` varchar(191) NOT NULL,
  `description` varchar(400) DEFAULT NULL,
  `image` varchar(191) DEFAULT NULL,
  `status` varchar(60) NOT NULL DEFAULT 'published',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ecommerce_product_cross_sale_relations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ecommerce_product_cross_sale_relations` (
  `from_product_id` bigint(20) unsigned NOT NULL,
  `to_product_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`from_product_id`,`to_product_id`),
  KEY `ecommerce_product_cross_sale_relations_from_product_id_index` (`from_product_id`),
  KEY `ecommerce_product_cross_sale_relations_to_product_id_index` (`to_product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ecommerce_product_files`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ecommerce_product_files` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) unsigned NOT NULL,
  `url` varchar(400) DEFAULT NULL,
  `name` varchar(191) DEFAULT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `size` bigint(20) unsigned DEFAULT NULL,
  `description` text DEFAULT NULL,
  `file` varchar(191) NOT NULL,
  `file_size` int(11) NOT NULL DEFAULT 0,
  `file_ext` varchar(191) DEFAULT NULL,
  `is_free` tinyint(1) NOT NULL DEFAULT 0,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ecommerce_product_labels`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ecommerce_product_labels` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `color` varchar(20) DEFAULT NULL,
  `text_color` varchar(20) DEFAULT NULL,
  `status` varchar(60) NOT NULL DEFAULT 'published',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ecommerce_product_option_values`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ecommerce_product_option_values` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `option_id` bigint(20) unsigned NOT NULL,
  `option_value` varchar(191) NOT NULL,
  `affect_price` decimal(15,2) NOT NULL DEFAULT 0.00,
  `affect_type` varchar(20) NOT NULL DEFAULT 'plus',
  `order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ecommerce_product_option_values_option_id_index` (`option_id`),
  CONSTRAINT `ecommerce_product_option_values_option_id_foreign` FOREIGN KEY (`option_id`) REFERENCES `ecommerce_product_options` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ecommerce_product_options`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ecommerce_product_options` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) unsigned NOT NULL,
  `name` varchar(191) NOT NULL,
  `option_type` varchar(60) NOT NULL DEFAULT 'text',
  `required` tinyint(1) NOT NULL DEFAULT 0,
  `order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ecommerce_product_options_product_id_index` (`product_id`),
  CONSTRAINT `ecommerce_product_options_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `ecommerce_products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ecommerce_product_questions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ecommerce_product_questions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) unsigned NOT NULL,
  `customer_id` bigint(20) unsigned DEFAULT NULL,
  `author_name` varchar(255) NOT NULL,
  `author_email` varchar(255) DEFAULT NULL,
  `question` text NOT NULL,
  `answer` text DEFAULT NULL,
  `answered_by` varchar(255) DEFAULT NULL,
  `answered_at` timestamp NULL DEFAULT NULL,
  `is_published` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ecommerce_product_questions_customer_id_foreign` (`customer_id`),
  KEY `ecommerce_product_questions_product_id_is_published_index` (`product_id`,`is_published`),
  CONSTRAINT `ecommerce_product_questions_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `ecommerce_customers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ecommerce_product_questions_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `ecommerce_products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ecommerce_product_related_relations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ecommerce_product_related_relations` (
  `from_product_id` bigint(20) unsigned NOT NULL,
  `to_product_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`from_product_id`,`to_product_id`),
  KEY `ecommerce_product_related_relations_from_product_id_index` (`from_product_id`),
  KEY `ecommerce_product_related_relations_to_product_id_index` (`to_product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ecommerce_product_restock_alerts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ecommerce_product_restock_alerts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) unsigned NOT NULL,
  `email` varchar(255) NOT NULL,
  `customer_id` bigint(20) unsigned DEFAULT NULL,
  `notified_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ecommerce_product_restock_alerts_product_id_email_unique` (`product_id`,`email`),
  KEY `ecommerce_product_restock_alerts_customer_id_foreign` (`customer_id`),
  KEY `ecommerce_product_restock_alerts_product_id_notified_at_index` (`product_id`,`notified_at`),
  CONSTRAINT `ecommerce_product_restock_alerts_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `ecommerce_customers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ecommerce_product_restock_alerts_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `ecommerce_products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ecommerce_product_specification_attribute`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ecommerce_product_specification_attribute` (
  `product_id` bigint(20) unsigned NOT NULL,
  `attribute_id` bigint(20) unsigned NOT NULL,
  `value` text DEFAULT NULL,
  `hidden` tinyint(1) NOT NULL DEFAULT 0,
  `order` tinyint(4) NOT NULL DEFAULT 0,
  PRIMARY KEY (`product_id`,`attribute_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ecommerce_product_specification_tables`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ecommerce_product_specification_tables` (
  `product_id` bigint(20) unsigned NOT NULL,
  `table_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`product_id`,`table_id`),
  KEY `ecommerce_product_specification_tables_table_id_foreign` (`table_id`),
  CONSTRAINT `ecommerce_product_specification_tables_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `ecommerce_products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ecommerce_product_specification_tables_table_id_foreign` FOREIGN KEY (`table_id`) REFERENCES `ecommerce_specification_tables` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ecommerce_product_tag_product`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ecommerce_product_tag_product` (
  `product_id` bigint(20) unsigned NOT NULL,
  `tag_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`product_id`,`tag_id`),
  KEY `ecommerce_product_tag_product_product_id_index` (`product_id`),
  KEY `ecommerce_product_tag_product_tag_id_index` (`tag_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ecommerce_product_tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ecommerce_product_tags` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL,
  `slug` varchar(191) DEFAULT NULL,
  `description` varchar(400) DEFAULT NULL,
  `status` varchar(60) NOT NULL DEFAULT 'published',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ecommerce_product_tags_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ecommerce_product_up_sale_relations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ecommerce_product_up_sale_relations` (
  `from_product_id` bigint(20) unsigned NOT NULL,
  `to_product_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`from_product_id`,`to_product_id`),
  KEY `ecommerce_product_up_sale_relations_from_product_id_index` (`from_product_id`),
  KEY `ecommerce_product_up_sale_relations_to_product_id_index` (`to_product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ecommerce_product_variation_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ecommerce_product_variation_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `attribute_id` bigint(20) unsigned NOT NULL,
  `variation_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ec_var_items_attr_var_unique` (`attribute_id`,`variation_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ecommerce_product_variations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ecommerce_product_variations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) unsigned DEFAULT NULL,
  `configurable_product_id` bigint(20) unsigned NOT NULL,
  `is_default` tinyint(4) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ecommerce_product_views`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ecommerce_product_views` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) unsigned NOT NULL,
  `views` bigint(20) NOT NULL DEFAULT 0,
  `date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ecommerce_product_with_attribute_set`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ecommerce_product_with_attribute_set` (
  `attribute_set_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `order` tinyint(3) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`product_id`,`attribute_set_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ecommerce_products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ecommerce_products` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `description` text DEFAULT NULL,
  `content` longtext DEFAULT NULL,
  `meta_title` varchar(191) DEFAULT NULL,
  `meta_description` text DEFAULT NULL,
  `status` varchar(60) NOT NULL DEFAULT 'published',
  `product_type` varchar(60) DEFAULT 'physical',
  `slug` varchar(191) NOT NULL,
  `images` text DEFAULT NULL,
  `featured_image` varchar(191) DEFAULT NULL,
  `sku` varchar(191) DEFAULT NULL,
  `order` int(10) unsigned NOT NULL DEFAULT 0,
  `quantity` int(10) unsigned DEFAULT NULL,
  `allow_checkout_when_out_of_stock` tinyint(1) NOT NULL DEFAULT 0,
  `with_storehouse_management` tinyint(1) NOT NULL DEFAULT 0,
  `is_subscription` tinyint(1) NOT NULL DEFAULT 0,
  `subscription_interval` varchar(20) DEFAULT NULL,
  `subscription_discount_percent` decimal(5,2) NOT NULL DEFAULT 0.00,
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `brand_id` bigint(20) unsigned DEFAULT NULL,
  `label_id` bigint(20) unsigned DEFAULT NULL,
  `is_variation` tinyint(1) NOT NULL DEFAULT 0,
  `sale_type` varchar(60) NOT NULL DEFAULT 'default',
  `price` decimal(15,2) NOT NULL DEFAULT 0.00,
  `sale_price` decimal(15,2) DEFAULT NULL,
  `start_date` timestamp NULL DEFAULT NULL,
  `end_date` timestamp NULL DEFAULT NULL,
  `length` decimal(15,2) DEFAULT NULL,
  `wide` decimal(15,2) DEFAULT NULL,
  `height` decimal(15,2) DEFAULT NULL,
  `weight` decimal(15,2) DEFAULT NULL,
  `views` int(10) unsigned NOT NULL DEFAULT 0,
  `stock_status` varchar(60) NOT NULL DEFAULT 'in_stock',
  `barcode` varchar(191) DEFAULT NULL,
  `cost_per_item` decimal(15,2) DEFAULT NULL,
  `minimum_order_quantity` int(10) unsigned NOT NULL DEFAULT 1,
  `maximum_order_quantity` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ecommerce_products_slug_unique` (`slug`),
  UNIQUE KEY `ecommerce_products_sku_unique` (`sku`),
  KEY `ecommerce_products_status_index` (`status`),
  KEY `ecommerce_products_sku_index` (`sku`),
  KEY `ecommerce_products_stock_status_index` (`stock_status`),
  KEY `ecommerce_products_is_featured_index` (`is_featured`),
  KEY `ecommerce_products_brand_id_index` (`brand_id`),
  KEY `ecommerce_products_label_id_foreign` (`label_id`),
  CONSTRAINT `ecommerce_products_brand_id_foreign` FOREIGN KEY (`brand_id`) REFERENCES `ecommerce_brands` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ecommerce_products_label_id_foreign` FOREIGN KEY (`label_id`) REFERENCES `ecommerce_product_labels` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ecommerce_review_replies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ecommerce_review_replies` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `review_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `comment` text NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ecommerce_reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ecommerce_reviews` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) unsigned NOT NULL,
  `customer_id` bigint(20) unsigned DEFAULT NULL,
  `customer_name` varchar(191) DEFAULT NULL,
  `customer_email` varchar(191) DEFAULT NULL,
  `star` tinyint(3) unsigned NOT NULL,
  `is_verified_buyer` tinyint(1) NOT NULL DEFAULT 0,
  `comment` text DEFAULT NULL,
  `status` varchar(60) NOT NULL DEFAULT 'pending',
  `images` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`images`)),
  `reply` text DEFAULT NULL,
  `replied_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ecommerce_reviews_customer_id_foreign` (`customer_id`),
  KEY `ecommerce_reviews_product_id_index` (`product_id`),
  KEY `ecommerce_reviews_status_index` (`status`),
  CONSTRAINT `ecommerce_reviews_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `ecommerce_customers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ecommerce_reviews_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `ecommerce_products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ecommerce_saved_searches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ecommerce_saved_searches` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `query` varchar(255) DEFAULT NULL,
  `filters` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`filters`)),
  `last_notified_at` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ecommerce_saved_searches_customer_id_index` (`customer_id`),
  CONSTRAINT `ecommerce_saved_searches_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `ecommerce_customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ecommerce_search_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ecommerce_search_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `query` varchar(255) NOT NULL,
  `results_count` int(10) unsigned NOT NULL DEFAULT 0,
  `customer_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `ecommerce_search_logs_customer_id_foreign` (`customer_id`),
  KEY `ecommerce_search_logs_query_index` (`query`),
  KEY `ecommerce_search_logs_created_at_index` (`created_at`),
  CONSTRAINT `ecommerce_search_logs_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `ecommerce_customers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ecommerce_shared_wishlists`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ecommerce_shared_wishlists` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint(20) unsigned NOT NULL,
  `token` varchar(191) NOT NULL,
  `title` varchar(191) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ecommerce_shared_wishlists_token_unique` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ecommerce_shipment_histories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ecommerce_shipment_histories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `action` varchar(120) NOT NULL,
  `description` varchar(400) DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `shipment_id` bigint(20) unsigned NOT NULL,
  `order_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ecommerce_shipments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ecommerce_shipments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `weight` double DEFAULT 0,
  `shipment_id` varchar(120) DEFAULT NULL,
  `rate_id` varchar(120) DEFAULT NULL,
  `note` varchar(120) DEFAULT NULL,
  `tracking_id` varchar(191) DEFAULT NULL,
  `tracking_link` varchar(500) DEFAULT NULL,
  `shipping_company_name` varchar(191) DEFAULT NULL,
  `estimate_date_shipped` date DEFAULT NULL,
  `date_shipped` date DEFAULT NULL,
  `delivery_token` varchar(100) DEFAULT NULL,
  `delivered_at` timestamp NULL DEFAULT NULL,
  `delivered_by` varchar(45) DEFAULT NULL,
  `status` varchar(120) NOT NULL DEFAULT 'pending',
  `cod_amount` decimal(15,2) DEFAULT 0.00,
  `cod_status` varchar(60) NOT NULL DEFAULT 'pending',
  `cross_checking_status` varchar(60) NOT NULL DEFAULT 'pending',
  `price` decimal(15,2) DEFAULT 0.00,
  `store_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ecommerce_shipments_delivery_token_unique` (`delivery_token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ecommerce_shipping`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ecommerce_shipping` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(191) DEFAULT NULL,
  `country` varchar(120) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ecommerce_shipping_rule_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ecommerce_shipping_rule_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `shipping_rule_id` bigint(20) unsigned NOT NULL,
  `country` varchar(120) DEFAULT NULL,
  `state` varchar(120) DEFAULT NULL,
  `city` varchar(120) DEFAULT NULL,
  `adjustment_price` decimal(15,2) DEFAULT 0.00,
  `is_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ecommerce_shipping_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ecommerce_shipping_rules` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL,
  `shipping_id` bigint(20) unsigned NOT NULL,
  `type` varchar(60) DEFAULT 'based_on_price',
  `currency_id` bigint(20) unsigned DEFAULT NULL,
  `from` decimal(15,2) DEFAULT 0.00,
  `to` decimal(15,2) DEFAULT 0.00,
  `price` decimal(15,2) DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ecommerce_specification_attributes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ecommerce_specification_attributes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `group_id` bigint(20) unsigned NOT NULL,
  `name` varchar(191) NOT NULL,
  `type` varchar(20) NOT NULL DEFAULT 'text',
  `options` text DEFAULT NULL,
  `default_value` varchar(191) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ecommerce_specification_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ecommerce_specification_groups` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `description` varchar(400) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ecommerce_specification_table_group`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ecommerce_specification_table_group` (
  `table_id` bigint(20) unsigned NOT NULL,
  `group_id` bigint(20) unsigned NOT NULL,
  `order` tinyint(4) NOT NULL DEFAULT 0,
  PRIMARY KEY (`table_id`,`group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ecommerce_specification_tables`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ecommerce_specification_tables` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `description` varchar(400) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ecommerce_store_locators`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ecommerce_store_locators` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(60) NOT NULL,
  `email` varchar(60) DEFAULT NULL,
  `phone` varchar(20) NOT NULL,
  `address` varchar(191) NOT NULL,
  `country` varchar(120) DEFAULT NULL,
  `state` varchar(120) DEFAULT NULL,
  `city` varchar(120) DEFAULT NULL,
  `is_primary` tinyint(1) DEFAULT 0,
  `is_shipping_location` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ecommerce_subscriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ecommerce_subscriptions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `qty` int(11) NOT NULL DEFAULT 1,
  `interval` varchar(20) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `next_billing_at` timestamp NOT NULL,
  `last_billed_at` timestamp NULL DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ecommerce_subscriptions_product_id_foreign` (`product_id`),
  KEY `ecommerce_subscriptions_customer_id_status_index` (`customer_id`,`status`),
  KEY `ecommerce_subscriptions_next_billing_at_index` (`next_billing_at`),
  CONSTRAINT `ecommerce_subscriptions_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `ecommerce_customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ecommerce_subscriptions_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `ecommerce_products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ecommerce_tax_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ecommerce_tax_rules` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tax_id` bigint(20) unsigned NOT NULL,
  `name` varchar(191) NOT NULL DEFAULT '',
  `basis` varchar(20) NOT NULL DEFAULT 'price',
  `price_from` decimal(15,2) NOT NULL DEFAULT 0.00,
  `price_to` decimal(15,2) DEFAULT NULL,
  `percentage` decimal(5,2) NOT NULL DEFAULT 0.00,
  `order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ecommerce_tax_rules_tax_id_index` (`tax_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ecommerce_taxes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ecommerce_taxes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(191) DEFAULT NULL,
  `percentage` float DEFAULT NULL,
  `priority` int(11) DEFAULT NULL,
  `status` varchar(60) NOT NULL DEFAULT 'published',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ecommerce_translations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ecommerce_translations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `translatable_type` varchar(191) NOT NULL,
  `translatable_id` bigint(20) unsigned NOT NULL,
  `locale` varchar(5) NOT NULL,
  `field` varchar(50) NOT NULL,
  `value` text NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ecommerce_translations_unique` (`translatable_type`,`translatable_id`,`locale`,`field`),
  KEY `ecommerce_translations_morph_index` (`translatable_type`,`translatable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ecommerce_webhook_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ecommerce_webhook_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `event` varchar(80) NOT NULL,
  `url` varchar(500) NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`payload`)),
  `response_status` int(11) DEFAULT NULL,
  `response_body` text DEFAULT NULL,
  `attempts` int(11) NOT NULL DEFAULT 0,
  `success` tinyint(1) NOT NULL DEFAULT 0,
  `error` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ecommerce_webhook_logs_event_success_index` (`event`,`success`),
  KEY `ecommerce_webhook_logs_created_at_index` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ecommerce_wish_lists`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ecommerce_wish_lists` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `email_change_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `email_change_tokens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `new_email` varchar(191) NOT NULL,
  `token_hash` varchar(64) NOT NULL,
  `expires_at` timestamp NOT NULL,
  `confirmed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email_change_tokens_token_hash_unique` (`token_hash`),
  KEY `email_change_tokens_user_id_confirmed_at_index` (`user_id`,`confirmed_at`),
  CONSTRAINT `email_change_tokens_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `faq_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `faq_categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `description` text DEFAULT NULL,
  `order` int(11) NOT NULL DEFAULT 0,
  `status` varchar(60) NOT NULL DEFAULT 'published',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `faq_category_translations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `faq_category_translations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `faq_category_id` bigint(20) unsigned NOT NULL,
  `locale` varchar(10) NOT NULL,
  `name` varchar(120) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `faq_category_translations_faq_category_id_locale_unique` (`faq_category_id`,`locale`),
  CONSTRAINT `faq_category_translations_faq_category_id_foreign` FOREIGN KEY (`faq_category_id`) REFERENCES `faq_categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `faq_translations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `faq_translations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `faq_id` bigint(20) unsigned NOT NULL,
  `locale` varchar(10) NOT NULL,
  `question` text NOT NULL,
  `answer` longtext NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `faq_translations_faq_id_locale_unique` (`faq_id`,`locale`),
  CONSTRAINT `faq_translations_faq_id_foreign` FOREIGN KEY (`faq_id`) REFERENCES `faqs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `faqs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `faqs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `question` varchar(191) NOT NULL,
  `answer` text DEFAULT NULL,
  `status` varchar(60) NOT NULL DEFAULT 'published',
  `category_id` bigint(20) unsigned NOT NULL,
  `order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `faqs_available_index` (`status`),
  KEY `faqs_category_id_index` (`category_id`),
  CONSTRAINT `faqs_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `faq_categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `features`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `features` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `scope` varchar(191) NOT NULL,
  `value` text NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `features_name_scope_unique` (`name`,`scope`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `form_abandon_tracking`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `form_abandon_tracking` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `form_id` bigint(20) unsigned NOT NULL,
  `session_token` varchar(64) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `partial_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`partial_data`)),
  `current_step` tinyint(3) unsigned NOT NULL DEFAULT 1,
  `last_field_key` varchar(100) DEFAULT NULL,
  `started_at` timestamp NOT NULL,
  `last_activity_at` timestamp NOT NULL,
  `reminder_sent_at` timestamp NULL DEFAULT NULL,
  `is_completed` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `form_abandon_tracking_form_id_index` (`form_id`),
  KEY `form_abandon_tracking_is_completed_index` (`is_completed`),
  KEY `form_abandon_tracking_reminder_sent_at_index` (`reminder_sent_at`),
  KEY `form_abandon_tracking_form_id_is_completed_index` (`form_id`,`is_completed`),
  KEY `form_abandon_tracking_form_session_index` (`form_id`,`session_token`),
  CONSTRAINT `form_abandon_tracking_form_id_foreign` FOREIGN KEY (`form_id`) REFERENCES `forms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `form_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `form_access_tokens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `form_id` bigint(20) unsigned NOT NULL,
  `token` varchar(64) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `max_uses` int(10) unsigned NOT NULL DEFAULT 1,
  `times_used` int(10) unsigned NOT NULL DEFAULT 0,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `form_access_tokens_token_unique` (`token`),
  KEY `form_access_tokens_form_id_index` (`form_id`),
  KEY `form_access_tokens_expires_at_index` (`expires_at`),
  KEY `form_access_tokens_created_by_foreign` (`created_by`),
  CONSTRAINT `form_access_tokens_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `form_access_tokens_form_id_foreign` FOREIGN KEY (`form_id`) REFERENCES `forms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `form_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `form_categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `color` varchar(7) DEFAULT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `form_categories_slug_unique` (`slug`),
  KEY `form_categories_is_active_index` (`is_active`),
  KEY `form_categories_sort_order_index` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `form_conditional_emails`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `form_conditional_emails` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `form_id` bigint(20) unsigned NOT NULL,
  `condition_field_key` varchar(100) NOT NULL,
  `condition_operator` enum('equals','contains','not_equals','starts_with','ends_with') NOT NULL DEFAULT 'equals',
  `condition_value` varchar(255) NOT NULL,
  `admin_template_id` bigint(20) unsigned DEFAULT NULL,
  `client_template_id` bigint(20) unsigned DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `form_conditional_emails_form_id_index` (`form_id`),
  CONSTRAINT `form_conditional_emails_form_id_foreign` FOREIGN KEY (`form_id`) REFERENCES `forms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `form_field_type_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `form_field_type_settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(50) NOT NULL,
  `group_name` varchar(100) NOT NULL,
  `group_order` smallint(6) NOT NULL DEFAULT 0,
  `label` varchar(100) NOT NULL,
  `icon` varchar(100) NOT NULL,
  `sort_order` smallint(6) NOT NULL DEFAULT 0,
  `is_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `default_css_class` varchar(255) DEFAULT NULL,
  `default_placeholder` varchar(255) DEFAULT NULL,
  `default_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`default_settings`)),
  `custom_css` text DEFAULT NULL,
  `custom_html` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `form_field_type_settings_type_unique` (`type`),
  KEY `form_field_type_settings_group_order_sort_order_index` (`group_order`,`sort_order`),
  KEY `form_field_type_settings_is_enabled_index` (`is_enabled`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `form_fields`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `form_fields` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `form_id` bigint(20) unsigned NOT NULL,
  `key` varchar(100) NOT NULL,
  `label` varchar(255) NOT NULL,
  `type` enum('text','email','tel','number','url','textarea','select','checkbox','radio','file','date','time','datetime','hidden','rating','calculation','signature','nps','likert','slider','image_choice','rich_text','address','section_header','html_block','divider','spacer','consent','newsletter_consent','color_picker') NOT NULL DEFAULT 'text',
  `placeholder` varchar(255) DEFAULT NULL,
  `default_value` text DEFAULT NULL,
  `options` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`options`)),
  `validation_rules` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`validation_rules`)),
  `conditions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`conditions`)),
  `logic_jumps` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`logic_jumps`)),
  `is_required` tinyint(1) NOT NULL DEFAULT 0,
  `is_visible` tinyint(1) NOT NULL DEFAULT 1,
  `width` enum('full','half','third','quarter') NOT NULL DEFAULT 'full',
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `step_number` tinyint(3) unsigned NOT NULL DEFAULT 1,
  `help_text` varchar(500) DEFAULT NULL,
  `show_char_counter` tinyint(1) NOT NULL DEFAULT 0,
  `label_position` enum('top','floating','hidden') NOT NULL DEFAULT 'top',
  `formula` varchar(500) DEFAULT NULL,
  `min_value` decimal(10,2) DEFAULT NULL,
  `max_value` decimal(10,2) DEFAULT NULL,
  `step_value` decimal(10,2) DEFAULT NULL,
  `mailrelay_group_id` int(10) unsigned DEFAULT NULL,
  `auto_populate_param` varchar(100) DEFAULT NULL,
  `html_content` text DEFAULT NULL,
  `likert_rows` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`likert_rows`)),
  `consent_text` text DEFAULT NULL,
  `translations` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`translations`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `form_fields_form_id_key_unique` (`form_id`,`key`),
  KEY `form_fields_form_id_index` (`form_id`),
  KEY `form_fields_sort_order_index` (`sort_order`),
  KEY `form_fields_step_number_index` (`step_number`),
  CONSTRAINT `form_fields_form_id_foreign` FOREIGN KEY (`form_id`) REFERENCES `forms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `form_follow_ups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `form_follow_ups` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `form_id` bigint(20) unsigned NOT NULL,
  `name` varchar(150) NOT NULL,
  `send_after_days` int(10) unsigned NOT NULL DEFAULT 1,
  `email_template_id` bigint(20) unsigned DEFAULT NULL,
  `recipient_type` enum('admin','submitter','both') NOT NULL DEFAULT 'admin',
  `condition_field_key` varchar(100) DEFAULT NULL,
  `condition_value` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `form_follow_ups_form_id_index` (`form_id`),
  KEY `form_follow_ups_is_active_index` (`is_active`),
  CONSTRAINT `form_follow_ups_form_id_foreign` FOREIGN KEY (`form_id`) REFERENCES `forms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `form_submission_actions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `form_submission_actions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `submission_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `form_submission_actions_user_id_foreign` (`user_id`),
  KEY `form_submission_actions_submission_id_created_at_index` (`submission_id`,`created_at`),
  CONSTRAINT `form_submission_actions_submission_id_foreign` FOREIGN KEY (`submission_id`) REFERENCES `form_submissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `form_submission_actions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `form_submission_emails`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `form_submission_emails` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `submission_id` bigint(20) unsigned NOT NULL,
  `email_type` enum('confirmation','admin','custom','resend') NOT NULL DEFAULT 'admin',
  `recipient_email` varchar(191) NOT NULL,
  `subject` varchar(191) NOT NULL,
  `body_html` text DEFAULT NULL,
  `status` enum('queued','sent','failed') NOT NULL DEFAULT 'queued',
  `sent_by` bigint(20) unsigned DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `form_submission_emails_submission_id_index` (`submission_id`),
  KEY `form_submission_emails_status_index` (`status`),
  KEY `form_submission_emails_sent_by_foreign` (`sent_by`),
  CONSTRAINT `form_submission_emails_sent_by_foreign` FOREIGN KEY (`sent_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `form_submission_emails_submission_id_foreign` FOREIGN KEY (`submission_id`) REFERENCES `form_submissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `form_submission_files`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `form_submission_files` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `submission_id` bigint(20) unsigned NOT NULL,
  `uploaded_by` bigint(20) unsigned DEFAULT NULL,
  `filename` varchar(191) NOT NULL,
  `path` varchar(191) NOT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `size` bigint(20) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `form_submission_files_uploaded_by_foreign` (`uploaded_by`),
  KEY `form_submission_files_submission_id_index` (`submission_id`),
  CONSTRAINT `form_submission_files_submission_id_foreign` FOREIGN KEY (`submission_id`) REFERENCES `form_submissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `form_submission_files_uploaded_by_foreign` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `form_submission_notes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `form_submission_notes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `submission_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `note` text NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `form_submission_notes_submission_id_index` (`submission_id`),
  KEY `form_submission_notes_user_id_foreign` (`user_id`),
  CONSTRAINT `form_submission_notes_submission_id_foreign` FOREIGN KEY (`submission_id`) REFERENCES `form_submissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `form_submission_notes_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `form_submission_values`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `form_submission_values` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `submission_id` bigint(20) unsigned NOT NULL,
  `form_field_id` bigint(20) unsigned DEFAULT NULL,
  `field_key` varchar(100) NOT NULL,
  `field_label` varchar(255) NOT NULL,
  `field_type` varchar(50) NOT NULL,
  `value` longtext DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `form_submission_values_submission_id_index` (`submission_id`),
  KEY `form_submission_values_form_field_id_index` (`form_field_id`),
  KEY `fsv_field_key_index` (`field_key`),
  KEY `fsv_field_type_index` (`field_type`),
  KEY `form_submission_values_field_key_index` (`field_key`),
  KEY `form_submission_values_field_type_index` (`field_type`),
  KEY `form_submission_values_sub_key_index` (`submission_id`,`field_key`),
  FULLTEXT KEY `form_submission_values_value_fulltext` (`value`),
  CONSTRAINT `form_submission_values_form_field_id_foreign` FOREIGN KEY (`form_field_id`) REFERENCES `form_fields` (`id`) ON DELETE SET NULL,
  CONSTRAINT `form_submission_values_submission_id_foreign` FOREIGN KEY (`submission_id`) REFERENCES `form_submissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `form_submissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `form_submissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `form_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `assigned_to` bigint(20) unsigned DEFAULT NULL,
  `status` enum('new','in_review','resolved','rejected') NOT NULL DEFAULT 'new',
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `referrer_url` varchar(500) DEFAULT NULL,
  `source_page_id` bigint(20) unsigned DEFAULT NULL,
  `locale` varchar(10) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `utm_source` varchar(255) DEFAULT NULL,
  `utm_medium` varchar(255) DEFAULT NULL,
  `utm_campaign` varchar(255) DEFAULT NULL,
  `utm_term` varchar(255) DEFAULT NULL,
  `utm_content` varchar(255) DEFAULT NULL,
  `time_to_complete` int(10) unsigned DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `is_spam` tinyint(1) NOT NULL DEFAULT 0,
  `is_starred` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `form_submissions_form_id_index` (`form_id`),
  KEY `form_submissions_status_index` (`status`),
  KEY `form_submissions_is_read_index` (`is_read`),
  KEY `form_submissions_is_spam_index` (`is_spam`),
  KEY `form_submissions_created_at_index` (`created_at`),
  KEY `form_submissions_user_id_foreign` (`user_id`),
  KEY `form_submissions_is_starred_index` (`is_starred`),
  KEY `form_submissions_form_id_is_starred_index` (`form_id`,`is_starred`),
  KEY `form_submissions_form_status_created_index` (`form_id`,`status`,`created_at`),
  KEY `form_submissions_form_is_read_created_index` (`form_id`,`is_read`,`created_at`),
  KEY `form_submissions_assigned_to_index` (`assigned_to`),
  KEY `form_submissions_form_is_spam_index` (`form_id`,`is_spam`),
  KEY `form_submissions_form_assigned_index` (`form_id`,`assigned_to`),
  KEY `form_submissions_form_created_index` (`form_id`,`created_at`),
  KEY `form_submissions_form_source_page_index` (`form_id`,`source_page_id`),
  CONSTRAINT `form_submissions_assigned_to_foreign` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `form_submissions_form_id_foreign` FOREIGN KEY (`form_id`) REFERENCES `forms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `form_submissions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `form_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `form_versions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `form_id` bigint(20) unsigned NOT NULL,
  `version_number` int(10) unsigned NOT NULL,
  `form_snapshot` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`form_snapshot`)),
  `fields_snapshot` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`fields_snapshot`)),
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `form_versions_form_id_index` (`form_id`),
  KEY `form_versions_version_number_index` (`version_number`),
  KEY `form_versions_created_by_foreign` (`created_by`),
  CONSTRAINT `form_versions_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `form_versions_form_id_foreign` FOREIGN KEY (`form_id`) REFERENCES `forms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `forms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `forms` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `category_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(150) NOT NULL,
  `slug` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `success_message` text DEFAULT NULL,
  `redirect_url` varchar(500) DEFAULT NULL,
  `admin_notification_email` varchar(500) DEFAULT NULL,
  `send_confirmation` tinyint(1) NOT NULL DEFAULT 0,
  `confirmation_subject` varchar(255) DEFAULT NULL,
  `confirmation_message` text DEFAULT NULL,
  `email_field_key` varchar(100) DEFAULT NULL,
  `admin_template_id` bigint(20) unsigned DEFAULT NULL,
  `confirmation_template_id` bigint(20) unsigned DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `allow_multiple` tinyint(1) NOT NULL DEFAULT 1,
  `honeypot_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `captcha_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `is_password_protected` tinyint(1) NOT NULL DEFAULT 0,
  `password` varchar(255) DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `max_submissions` int(10) unsigned DEFAULT NULL,
  `prevent_duplicate_email` tinyint(1) NOT NULL DEFAULT 0,
  `access_control` enum('public','authenticated','roles') NOT NULL DEFAULT 'public',
  `allowed_roles` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`allowed_roles`)),
  `retention_days` int(10) unsigned DEFAULT NULL,
  `limit_per_user` tinyint(1) NOT NULL DEFAULT 0,
  `webhook_url` varchar(500) DEFAULT NULL,
  `webhook_secret` varchar(255) DEFAULT NULL,
  `is_multi_step` tinyint(1) NOT NULL DEFAULT 0,
  `steps_config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`steps_config`)),
  `theme` varchar(50) NOT NULL DEFAULT 'default',
  `custom_css` text DEFAULT NULL,
  `custom_js` text DEFAULT NULL,
  `style_config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`style_config`)),
  `submit_button_text` varchar(100) NOT NULL DEFAULT 'Enviar',
  `button_position` enum('left','center','right','full') NOT NULL DEFAULT 'left',
  `button_size` enum('sm','md','lg') NOT NULL DEFAULT 'md',
  `button_variant` varchar(50) NOT NULL DEFAULT 'primary',
  `button_icon` varchar(50) DEFAULT NULL,
  `success_animation` enum('none','fade','checkmark','confetti') NOT NULL DEFAULT 'fade',
  `progress_bar_style` enum('bar','dots','steps','percentage') NOT NULL DEFAULT 'bar',
  `floating_label` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `forms_slug_unique` (`slug`),
  KEY `forms_slug_index` (`slug`),
  KEY `forms_is_active_index` (`is_active`),
  KEY `forms_category_id_index` (`category_id`),
  CONSTRAINT `forms_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `form_categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `generated_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `generated_reports` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `report_type` varchar(50) NOT NULL,
  `format` varchar(10) NOT NULL,
  `filename` varchar(191) NOT NULL,
  `filepath` varchar(191) NOT NULL,
  `filters` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`filters`)),
  `file_size` int(10) unsigned DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'processing',
  `error_message` text DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `generated_reports_user_id_created_at_index` (`user_id`,`created_at`),
  KEY `generated_reports_status_index` (`status`),
  KEY `generated_reports_expires_at_index` (`expires_at`),
  CONSTRAINT `generated_reports_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `health_check_result_history_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `health_check_result_history_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `check_name` varchar(191) NOT NULL,
  `check_label` varchar(191) NOT NULL,
  `status` varchar(191) NOT NULL,
  `notification_message` text DEFAULT NULL,
  `short_summary` varchar(191) DEFAULT NULL,
  `meta` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`meta`)),
  `ended_at` timestamp NOT NULL,
  `batch` uuid NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `health_check_result_history_items_created_at_index` (`created_at`),
  KEY `health_check_result_history_items_batch_index` (`batch`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `helpdesk_agent_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `helpdesk_agent_settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `accepts_conversations` tinyint(1) NOT NULL DEFAULT 1,
  `max_concurrent_conversations` int(11) NOT NULL DEFAULT 5,
  `auto_assign` tinyint(1) NOT NULL DEFAULT 1,
  `preferences` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`preferences`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `helpdesk_agent_settings_user_id_unique` (`user_id`),
  KEY `helpdesk_agent_settings_user_id_index` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `helpdesk_agent_shifts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `helpdesk_agent_shifts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `day_of_week` tinyint(4) NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `timezone` varchar(64) NOT NULL DEFAULT 'UTC',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `helpdesk_agent_shifts_user_id_day_of_week_index` (`user_id`,`day_of_week`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `helpdesk_agent_vacations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `helpdesk_agent_vacations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `starts_at` date NOT NULL,
  `ends_at` date NOT NULL,
  `reason` varchar(191) DEFAULT NULL,
  `status` varchar(16) NOT NULL DEFAULT 'approved',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `helpdesk_agent_vacations_user_id_starts_at_index` (`user_id`,`starts_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `helpdesk_ai_agent_flow_nodes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `helpdesk_ai_agent_flow_nodes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `flow_id` bigint(20) unsigned NOT NULL,
  `node_id` varchar(191) NOT NULL,
  `type` enum('input','prompt','condition','action','output') NOT NULL,
  `label` varchar(191) DEFAULT NULL,
  `position` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`position`)),
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`data`)),
  PRIMARY KEY (`id`),
  UNIQUE KEY `helpdesk_ai_agent_flow_nodes_flow_id_node_id_unique` (`flow_id`,`node_id`),
  KEY `helpdesk_ai_agent_flow_nodes_flow_id_index` (`flow_id`),
  CONSTRAINT `helpdesk_ai_agent_flow_nodes_flow_id_foreign` FOREIGN KEY (`flow_id`) REFERENCES `helpdesk_ai_agent_flows` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `helpdesk_ai_agent_flows`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `helpdesk_ai_agent_flows` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ai_agent_id` bigint(20) unsigned NOT NULL,
  `name` varchar(191) NOT NULL,
  `description` text DEFAULT NULL,
  `trigger_type` enum('message','intent','keyword','conversation_start') NOT NULL,
  `trigger_conditions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`trigger_conditions`)),
  `nodes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`nodes`)),
  `edges` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`edges`)),
  `status` enum('draft','published','archived') NOT NULL DEFAULT 'draft',
  `version` int(10) unsigned NOT NULL DEFAULT 1,
  `published_at` timestamp NULL DEFAULT NULL,
  `archived_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `helpdesk_ai_agent_flows_ai_agent_id_index` (`ai_agent_id`),
  KEY `helpdesk_ai_agent_flows_status_index` (`status`),
  KEY `helpdesk_ai_agent_flows_ai_agent_id_status_index` (`ai_agent_id`,`status`),
  CONSTRAINT `helpdesk_ai_agent_flows_ai_agent_id_foreign` FOREIGN KEY (`ai_agent_id`) REFERENCES `helpdesk_ai_agents` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `helpdesk_ai_agent_knowledge_base`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `helpdesk_ai_agent_knowledge_base` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ai_agent_id` bigint(20) unsigned NOT NULL,
  `title` varchar(191) NOT NULL,
  `content` longtext NOT NULL,
  `type` varchar(32) NOT NULL,
  `source_url` varchar(191) DEFAULT NULL,
  `source_type` varchar(64) DEFAULT NULL,
  `source_id` bigint(20) unsigned DEFAULT NULL,
  `embedding` longtext DEFAULT NULL,
  `embedding_model` varchar(64) DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `tags` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`tags`)),
  `summary` text DEFAULT NULL,
  `usage_count` int(10) unsigned NOT NULL DEFAULT 0,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `helpdesk_ai_agent_knowledge_base_ai_agent_id_index` (`ai_agent_id`),
  KEY `helpdesk_ai_agent_knowledge_base_is_active_index` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `helpdesk_ai_agent_session_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `helpdesk_ai_agent_session_messages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `session_id` bigint(20) unsigned NOT NULL,
  `role` enum('user','assistant','system') NOT NULL,
  `content` text NOT NULL,
  `node_id` varchar(191) DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `helpdesk_ai_agent_session_messages_session_id_index` (`session_id`),
  KEY `helpdesk_ai_agent_session_messages_role_index` (`role`),
  CONSTRAINT `helpdesk_ai_agent_session_messages_session_id_foreign` FOREIGN KEY (`session_id`) REFERENCES `helpdesk_ai_agent_sessions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `helpdesk_ai_agent_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `helpdesk_ai_agent_sessions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ai_agent_id` bigint(20) unsigned NOT NULL,
  `conversation_id` bigint(20) unsigned DEFAULT NULL,
  `customer_id` bigint(20) unsigned DEFAULT NULL,
  `flow_id` bigint(20) unsigned DEFAULT NULL,
  `current_node_id` varchar(191) DEFAULT NULL,
  `status` enum('active','completed','failed','paused') NOT NULL DEFAULT 'active',
  `context` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`context`)),
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `started_at` timestamp NULL DEFAULT NULL,
  `ended_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `helpdesk_ai_agent_sessions_customer_id_foreign` (`customer_id`),
  KEY `helpdesk_ai_agent_sessions_flow_id_foreign` (`flow_id`),
  KEY `helpdesk_ai_agent_sessions_ai_agent_id_index` (`ai_agent_id`),
  KEY `helpdesk_ai_agent_sessions_conversation_id_index` (`conversation_id`),
  CONSTRAINT `helpdesk_ai_agent_sessions_ai_agent_id_foreign` FOREIGN KEY (`ai_agent_id`) REFERENCES `helpdesk_ai_agents` (`id`) ON DELETE CASCADE,
  CONSTRAINT `helpdesk_ai_agent_sessions_conversation_id_foreign` FOREIGN KEY (`conversation_id`) REFERENCES `helpdesk_conversations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `helpdesk_ai_agent_sessions_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `helpdesk_customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `helpdesk_ai_agent_sessions_flow_id_foreign` FOREIGN KEY (`flow_id`) REFERENCES `helpdesk_ai_agent_flows` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `helpdesk_ai_agent_tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `helpdesk_ai_agent_tags` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `color` varchar(32) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `priority` int(11) NOT NULL DEFAULT 0,
  `icon` varchar(100) DEFAULT NULL,
  `system_prompt_addition` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `helpdesk_ai_agent_tags_name_index` (`name`),
  KEY `helpdesk_ai_agent_tags_is_active_index` (`is_active`),
  KEY `helpdesk_ai_agent_tags_priority_index` (`priority`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `helpdesk_ai_agent_tools`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `helpdesk_ai_agent_tools` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ai_agent_id` bigint(20) unsigned NOT NULL,
  `name` varchar(191) NOT NULL,
  `description` text DEFAULT NULL,
  `type` varchar(32) NOT NULL,
  `parameters` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`parameters`)),
  `implementation` longtext DEFAULT NULL,
  `auth_config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`auth_config`)),
  `requires_approval` tinyint(1) NOT NULL DEFAULT 0,
  `usage_count` int(10) unsigned NOT NULL DEFAULT 0,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `helpdesk_ai_agent_tools_ai_agent_id_index` (`ai_agent_id`),
  KEY `helpdesk_ai_agent_tools_is_active_index` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `helpdesk_ai_agents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `helpdesk_ai_agents` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `description` text DEFAULT NULL,
  `provider` varchar(191) DEFAULT NULL,
  `model` varchar(191) DEFAULT NULL,
  `personality` text DEFAULT NULL,
  `status` varchar(32) NOT NULL DEFAULT 'inactive',
  `type` varchar(191) NOT NULL,
  `configuration` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`configuration`)),
  `backups` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`backups`)),
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `enabled_at` timestamp NULL DEFAULT NULL,
  `api_key_encrypted` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `helpdesk_ai_agents_type_index` (`type`),
  KEY `helpdesk_ai_agents_active_index` (`active`),
  KEY `helpdesk_ai_agents_provider_index` (`provider`),
  KEY `helpdesk_ai_agents_status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `helpdesk_attributes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `helpdesk_attributes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `key` varchar(191) NOT NULL,
  `type` varchar(191) NOT NULL,
  `format` varchar(191) NOT NULL,
  `required` tinyint(1) NOT NULL DEFAULT 0,
  `permission` varchar(191) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `customer_name` varchar(191) DEFAULT NULL,
  `customer_description` text DEFAULT NULL,
  `config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`config`)),
  `internal` tinyint(1) NOT NULL DEFAULT 0,
  `materialized` tinyint(1) NOT NULL DEFAULT 0,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `helpdesk_attributes_key_unique` (`key`),
  KEY `helpdesk_attributes_key_index` (`key`),
  KEY `helpdesk_attributes_active_index` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `helpdesk_automations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `helpdesk_automations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `description` text DEFAULT NULL,
  `trigger_event` varchar(32) NOT NULL,
  `conditions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`conditions`)),
  `actions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`actions`)),
  `order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `run_count` int(11) NOT NULL DEFAULT 0,
  `last_run_at` timestamp NULL DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `helpdesk_automations_trigger_event_index` (`trigger_event`),
  KEY `helpdesk_automations_order_index` (`order`),
  KEY `helpdesk_automations_is_active_index` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `helpdesk_campaign_impressions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `helpdesk_campaign_impressions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `campaign_id` bigint(20) unsigned NOT NULL,
  `customer_id` bigint(20) unsigned DEFAULT NULL,
  `status` varchar(191) NOT NULL DEFAULT 'shown',
  `impression_id` varchar(191) NOT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `helpdesk_campaign_impressions_impression_id_unique` (`impression_id`),
  KEY `helpdesk_campaign_impressions_campaign_id_index` (`campaign_id`),
  KEY `helpdesk_campaign_impressions_customer_id_index` (`customer_id`),
  KEY `helpdesk_campaign_impressions_status_index` (`status`),
  CONSTRAINT `helpdesk_campaign_impressions_campaign_id_foreign` FOREIGN KEY (`campaign_id`) REFERENCES `helpdesk_campaigns` (`id`) ON DELETE CASCADE,
  CONSTRAINT `helpdesk_campaign_impressions_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `helpdesk_customers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `helpdesk_campaign_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `helpdesk_campaign_templates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `description` text DEFAULT NULL,
  `template_type` varchar(191) NOT NULL,
  `template_content` longtext NOT NULL,
  `variables` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`variables`)),
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `helpdesk_campaign_templates_template_type_index` (`template_type`),
  KEY `helpdesk_campaign_templates_active_index` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `helpdesk_campaigns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `helpdesk_campaigns` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `type` varchar(32) NOT NULL DEFAULT 'announcement',
  `description` text DEFAULT NULL,
  `template_id` bigint(20) unsigned DEFAULT NULL,
  `status` varchar(191) NOT NULL DEFAULT 'draft',
  `started_at` timestamp NULL DEFAULT NULL,
  `ended_at` timestamp NULL DEFAULT NULL,
  `targeting_rules` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`targeting_rules`)),
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `helpdesk_campaigns_status_index` (`status`),
  KEY `helpdesk_campaigns_started_at_index` (`started_at`),
  KEY `helpdesk_campaigns_type_index` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `helpdesk_canned_replies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `helpdesk_canned_replies` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `conversation_id` bigint(20) unsigned DEFAULT NULL,
  `title` varchar(191) NOT NULL,
  `content` text NOT NULL,
  `short_code` text DEFAULT NULL,
  `usage_count` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `helpdesk_canned_replies_conversation_id_index` (`conversation_id`),
  CONSTRAINT `helpdesk_canned_replies_conversation_id_foreign` FOREIGN KEY (`conversation_id`) REFERENCES `helpdesk_conversations` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `helpdesk_canned_reply_ticket_category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `helpdesk_canned_reply_ticket_category` (
  `ticket_canned_reply_id` bigint(20) unsigned NOT NULL,
  `ticket_category_id` bigint(20) unsigned NOT NULL,
  `order` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`ticket_canned_reply_id`,`ticket_category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `helpdesk_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `helpdesk_categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uid` varchar(36) NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `color` varchar(7) DEFAULT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `parent_id` bigint(20) unsigned DEFAULT NULL,
  `order` int(10) unsigned NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `helpdesk_categories_uid_unique` (`uid`),
  UNIQUE KEY `helpdesk_categories_slug_unique` (`slug`),
  KEY `helpdesk_categories_parent_id_is_active_index` (`parent_id`,`is_active`),
  KEY `helpdesk_categories_is_active_index` (`is_active`),
  CONSTRAINT `helpdesk_categories_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `helpdesk_categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `helpdesk_conversation_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `helpdesk_conversation_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `conversation_id` bigint(20) unsigned NOT NULL,
  `external_id` varchar(191) DEFAULT NULL,
  `author_id` bigint(20) unsigned DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `type` varchar(191) NOT NULL DEFAULT 'message',
  `body` longtext DEFAULT NULL,
  `html_body` longtext DEFAULT NULL,
  `attachment_urls` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`attachment_urls`)),
  `is_internal` tinyint(1) NOT NULL DEFAULT 0,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `helpdesk_conversation_items_conversation_id_index` (`conversation_id`),
  KEY `helpdesk_conversation_items_type_index` (`type`),
  KEY `helpdesk_conversation_items_created_at_index` (`created_at`),
  KEY `helpdesk_conversation_items_user_id_index` (`user_id`),
  KEY `helpdesk_conversation_items_author_id_index` (`author_id`),
  KEY `helpdesk_conv_items_conversation_created_index` (`conversation_id`,`created_at`),
  KEY `helpdesk_conv_items_external_id_index` (`external_id`),
  CONSTRAINT `helpdesk_conversation_items_author_id_foreign` FOREIGN KEY (`author_id`) REFERENCES `helpdesk_customers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `helpdesk_conversation_items_conversation_id_foreign` FOREIGN KEY (`conversation_id`) REFERENCES `helpdesk_conversations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `helpdesk_conversation_reads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `helpdesk_conversation_reads` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `conversation_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_74128` (`conversation_id`,`user_id`),
  KEY `helpdesk_conversation_reads_conversation_id_index` (`conversation_id`),
  CONSTRAINT `helpdesk_conversation_reads_conversation_id_foreign` FOREIGN KEY (`conversation_id`) REFERENCES `helpdesk_conversations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `helpdesk_conversation_statuses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `helpdesk_conversation_statuses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uid` varchar(191) DEFAULT NULL,
  `name` varchar(191) NOT NULL,
  `key` varchar(191) DEFAULT NULL,
  `slug` varchar(191) DEFAULT NULL,
  `color` varchar(191) DEFAULT NULL,
  `icon` varchar(191) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `order` int(11) NOT NULL DEFAULT 0,
  `position` int(11) NOT NULL DEFAULT 0,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `is_system` tinyint(1) NOT NULL DEFAULT 0,
  `is_open` tinyint(1) NOT NULL DEFAULT 1,
  `is_closed` tinyint(1) NOT NULL DEFAULT 0,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `helpdesk_conversation_statuses_slug_unique` (`slug`),
  UNIQUE KEY `helpdesk_conversation_statuses_key_unique` (`key`),
  UNIQUE KEY `helpdesk_conversation_statuses_uid_unique` (`uid`),
  KEY `helpdesk_conversation_statuses_is_default_index` (`is_default`),
  KEY `helpdesk_conversation_statuses_is_open_index` (`is_open`),
  KEY `helpdesk_conversation_statuses_order_index` (`order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `helpdesk_conversation_tag_pivot`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `helpdesk_conversation_tag_pivot` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `conversation_id` bigint(20) unsigned NOT NULL,
  `tag_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_54231` (`conversation_id`,`tag_id`),
  KEY `helpdesk_conversation_tag_pivot_tag_id_foreign` (`tag_id`),
  KEY `helpdesk_conversation_tag_pivot_conversation_id_index` (`conversation_id`),
  CONSTRAINT `helpdesk_conversation_tag_pivot_conversation_id_foreign` FOREIGN KEY (`conversation_id`) REFERENCES `helpdesk_conversations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `helpdesk_conversation_tag_pivot_tag_id_foreign` FOREIGN KEY (`tag_id`) REFERENCES `helpdesk_conversation_tags` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `helpdesk_conversation_tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `helpdesk_conversation_tags` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `slug` varchar(191) NOT NULL,
  `color` varchar(191) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `helpdesk_conversation_tags_slug_unique` (`slug`),
  KEY `helpdesk_conversation_tags_slug_index` (`slug`),
  KEY `helpdesk_conversation_tags_is_active_index` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `helpdesk_conversation_views`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `helpdesk_conversation_views` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `conversation_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `viewed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `name` varchar(191) DEFAULT NULL,
  `slug` varchar(191) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `filters` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`filters`)),
  `is_public` tinyint(1) NOT NULL DEFAULT 0,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `order` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_74129` (`conversation_id`,`user_id`),
  KEY `helpdesk_conversation_views_conversation_id_index` (`conversation_id`),
  KEY `helpdesk_conversation_views_slug_index` (`slug`),
  KEY `helpdesk_conversation_views_is_public_index` (`is_public`),
  KEY `helpdesk_conversation_views_is_default_index` (`is_default`),
  KEY `helpdesk_conversation_views_order_index` (`order`),
  CONSTRAINT `helpdesk_conversation_views_conversation_id_foreign` FOREIGN KEY (`conversation_id`) REFERENCES `helpdesk_conversations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `helpdesk_conversations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `helpdesk_conversations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint(20) unsigned NOT NULL,
  `channel` varchar(191) DEFAULT 'widget',
  `external_id` varchar(191) DEFAULT NULL,
  `external_sender_id` varchar(191) DEFAULT NULL,
  `subject` varchar(191) NOT NULL,
  `status_id` bigint(20) unsigned DEFAULT NULL,
  `assignee_id` bigint(20) unsigned DEFAULT NULL,
  `priority` varchar(191) NOT NULL DEFAULT 'normal',
  `is_archived` tinyint(1) NOT NULL DEFAULT 0,
  `is_public` tinyint(1) NOT NULL DEFAULT 0,
  `assigned_at` timestamp NULL DEFAULT NULL,
  `closed_at` timestamp NULL DEFAULT NULL,
  `first_response_at` timestamp NULL DEFAULT NULL,
  `last_message_at` timestamp NULL DEFAULT NULL,
  `tags` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`tags`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `helpdesk_conversations_channel_external_id_unique` (`channel`,`external_id`),
  KEY `helpdesk_conversations_customer_id_index` (`customer_id`),
  KEY `helpdesk_conversations_status_id_index` (`status_id`),
  KEY `helpdesk_conversations_assignee_id_index` (`assignee_id`),
  KEY `helpdesk_conversations_is_archived_index` (`is_archived`),
  KEY `helpdesk_conversations_created_at_index` (`created_at`),
  KEY `helpdesk_conversations_channel_index` (`channel`),
  KEY `helpdesk_conversations_sender_channel_index` (`external_sender_id`,`channel`),
  KEY `helpdesk_conversations_last_message_at_index` (`last_message_at`),
  KEY `helpdesk_conversations_updated_at_index` (`updated_at`),
  KEY `helpdesk_conversations_priority_index` (`priority`),
  KEY `helpdesk_conversations_closed_at_index` (`closed_at`),
  KEY `helpdesk_conversations_assignee_closed_index` (`assignee_id`,`closed_at`),
  KEY `helpdesk_conversations_archived_status_index` (`is_archived`,`status_id`),
  CONSTRAINT `helpdesk_conversations_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `helpdesk_customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `helpdesk_conversations_status_id_foreign` FOREIGN KEY (`status_id`) REFERENCES `helpdesk_conversation_statuses` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `helpdesk_customer_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `helpdesk_customer_sessions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint(20) unsigned NOT NULL,
  `session_id` varchar(191) NOT NULL,
  `ip_address` varchar(191) DEFAULT NULL,
  `user_agent` varchar(191) DEFAULT NULL,
  `last_activity_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `helpdesk_customer_sessions_session_id_unique` (`session_id`),
  KEY `helpdesk_customer_sessions_customer_id_index` (`customer_id`),
  KEY `helpdesk_customer_sessions_session_id_index` (`session_id`),
  CONSTRAINT `helpdesk_customer_sessions_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `helpdesk_customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `helpdesk_customers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `helpdesk_customers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `email` varchar(191) NOT NULL,
  `phone` varchar(191) DEFAULT NULL,
  `whatsapp_phone` varchar(191) DEFAULT NULL,
  `facebook_psid` varchar(191) DEFAULT NULL,
  `instagram_id` varchar(191) DEFAULT NULL,
  `avatar_url` varchar(191) DEFAULT NULL,
  `country` varchar(191) DEFAULT NULL,
  `state` varchar(191) DEFAULT NULL,
  `city` varchar(191) DEFAULT NULL,
  `postal_code` varchar(191) DEFAULT NULL,
  `language` varchar(191) NOT NULL DEFAULT 'es',
  `timezone` varchar(191) DEFAULT NULL,
  `custom_attributes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`custom_attributes`)),
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `banned_at` timestamp NULL DEFAULT NULL,
  `ban_reason` text DEFAULT NULL,
  `last_seen_at` timestamp NULL DEFAULT NULL,
  `total_conversations` int(11) NOT NULL DEFAULT 0,
  `total_page_visits` int(11) NOT NULL DEFAULT 0,
  `internal_notes` text DEFAULT NULL,
  `portal_token` varchar(64) DEFAULT NULL,
  `portal_token_expires_at` timestamp NULL DEFAULT NULL,
  `portal_password` varchar(191) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `helpdesk_customers_email_unique` (`email`),
  UNIQUE KEY `helpdesk_customers_portal_token_unique` (`portal_token`),
  KEY `helpdesk_customers_email_index` (`email`),
  KEY `helpdesk_customers_banned_at_index` (`banned_at`),
  KEY `helpdesk_customers_created_at_index` (`created_at`),
  KEY `helpdesk_customers_email_verified_at_index` (`email_verified_at`),
  KEY `helpdesk_customers_phone_index` (`phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `helpdesk_group_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `helpdesk_group_user` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `group_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `conversation_priority` varchar(191) NOT NULL DEFAULT 'primary',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_87211` (`group_id`,`user_id`),
  KEY `helpdesk_group_user_group_id_index` (`group_id`),
  CONSTRAINT `helpdesk_group_user_group_id_foreign` FOREIGN KEY (`group_id`) REFERENCES `helpdesk_groups` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `helpdesk_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `helpdesk_groups` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uid` varchar(191) DEFAULT NULL,
  `name` varchar(191) NOT NULL,
  `key` varchar(191) DEFAULT NULL,
  `description` varchar(191) DEFAULT NULL,
  `email` varchar(191) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `position` int(11) NOT NULL DEFAULT 0,
  `assignment_mode` varchar(191) NOT NULL DEFAULT 'round_robin',
  `default` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `helpdesk_groups_key_unique` (`key`),
  UNIQUE KEY `helpdesk_groups_uid_unique` (`uid`),
  KEY `helpdesk_groups_default_index` (`default`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `helpdesk_helpcenter_articles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `helpdesk_helpcenter_articles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `category_id` bigint(20) unsigned NOT NULL,
  `title` varchar(191) NOT NULL,
  `slug` varchar(191) NOT NULL,
  `content` longtext NOT NULL,
  `excerpt` text DEFAULT NULL,
  `order` int(11) NOT NULL DEFAULT 0,
  `views_count` int(11) NOT NULL DEFAULT 0,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `is_published` tinyint(1) NOT NULL DEFAULT 0,
  `author_id` bigint(20) unsigned DEFAULT NULL,
  `published_at` timestamp NULL DEFAULT NULL,
  `meta_description` varchar(500) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `helpdesk_helpcenter_articles_slug_unique` (`slug`),
  KEY `helpdesk_helpcenter_articles_category_id_index` (`category_id`),
  KEY `helpdesk_helpcenter_articles_order_index` (`order`),
  KEY `helpdesk_helpcenter_articles_active_index` (`active`),
  CONSTRAINT `helpdesk_helpcenter_articles_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `helpdesk_helpcenter_categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `helpdesk_helpcenter_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `helpdesk_helpcenter_categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `slug` varchar(191) NOT NULL,
  `description` text DEFAULT NULL,
  `order` int(11) NOT NULL DEFAULT 0,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `parent_id` bigint(20) unsigned DEFAULT NULL,
  `is_section` tinyint(1) NOT NULL DEFAULT 0,
  `position` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `helpdesk_helpcenter_categories_slug_unique` (`slug`),
  KEY `helpdesk_helpcenter_categories_order_index` (`order`),
  KEY `helpdesk_helpcenter_categories_active_index` (`active`),
  KEY `helpdesk_helpcenter_categories_parent_id_index` (`parent_id`),
  KEY `helpdesk_helpcenter_categories_is_section_index` (`is_section`),
  KEY `helpdesk_helpcenter_categories_position_index` (`position`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `helpdesk_helpcenter_tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `helpdesk_helpcenter_tags` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `slug` varchar(191) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `helpdesk_helpcenter_tags_slug_unique` (`slug`),
  KEY `helpdesk_helpcenter_tags_slug_index` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `helpdesk_macros`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `helpdesk_macros` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `description` text DEFAULT NULL,
  `actions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`actions`)),
  `is_shared` tinyint(1) NOT NULL DEFAULT 1,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `usage_count` int(11) NOT NULL DEFAULT 0,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `helpdesk_macros_is_shared_index` (`is_shared`),
  KEY `helpdesk_macros_is_active_index` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `helpdesk_oncall_rotations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `helpdesk_oncall_rotations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `user_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`user_ids`)),
  `shift_duration_hours` int(11) NOT NULL DEFAULT 24,
  `started_at` timestamp NOT NULL,
  `current_user_id` bigint(20) unsigned DEFAULT NULL,
  `next_handoff_at` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `helpdesk_page_visits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `helpdesk_page_visits` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint(20) unsigned DEFAULT NULL,
  `page_url` varchar(191) NOT NULL,
  `referrer` varchar(191) DEFAULT NULL,
  `duration_seconds` int(11) NOT NULL DEFAULT 0,
  `device_type` varchar(191) DEFAULT NULL,
  `browser` varchar(191) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `helpdesk_page_visits_customer_id_index` (`customer_id`),
  KEY `helpdesk_page_visits_created_at_index` (`created_at`),
  CONSTRAINT `helpdesk_page_visits_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `helpdesk_customers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `helpdesk_priorities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `helpdesk_priorities` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uid` varchar(36) NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `level` tinyint(3) unsigned NOT NULL,
  `color` varchar(7) NOT NULL,
  `response_time_hours` smallint(5) unsigned DEFAULT NULL,
  `resolution_time_hours` smallint(5) unsigned DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `helpdesk_priorities_uid_unique` (`uid`),
  UNIQUE KEY `helpdesk_priorities_slug_unique` (`slug`),
  UNIQUE KEY `helpdesk_priorities_level_unique` (`level`),
  KEY `helpdesk_priorities_is_active_index` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `helpdesk_recurring_tickets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `helpdesk_recurring_tickets` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `subject` varchar(191) NOT NULL,
  `description` text DEFAULT NULL,
  `category_id` bigint(20) unsigned DEFAULT NULL,
  `priority_id` bigint(20) unsigned DEFAULT NULL,
  `assignee_id` bigint(20) unsigned DEFAULT NULL,
  `frequency` enum('daily','weekly','monthly','custom') NOT NULL,
  `cron_expression` varchar(191) DEFAULT NULL,
  `next_run_at` timestamp NULL DEFAULT NULL,
  `last_run_at` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `tickets_created` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `helpdesk_recurring_tickets_category_id_foreign` (`category_id`),
  KEY `helpdesk_recurring_tickets_priority_id_foreign` (`priority_id`),
  KEY `helpdesk_recurring_tickets_is_active_next_run_at_index` (`is_active`,`next_run_at`),
  CONSTRAINT `helpdesk_recurring_tickets_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `helpdesk_ticket_categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `helpdesk_recurring_tickets_priority_id_foreign` FOREIGN KEY (`priority_id`) REFERENCES `helpdesk_priorities` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `helpdesk_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `helpdesk_settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(255) NOT NULL,
  `value` text DEFAULT NULL,
  `group` varchar(100) NOT NULL DEFAULT 'general',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `helpdesk_settings_key_unique` (`key`),
  KEY `helpdesk_settings_group_index` (`group`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `helpdesk_sla_policies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `helpdesk_sla_policies` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uid` varchar(36) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `priority_id` bigint(20) unsigned DEFAULT NULL,
  `category_id` bigint(20) unsigned DEFAULT NULL,
  `first_response_time_hours` smallint(5) unsigned NOT NULL,
  `resolution_time_hours` smallint(5) unsigned NOT NULL,
  `business_hours_only` tinyint(1) NOT NULL DEFAULT 1,
  `warning_threshold_percent` tinyint(3) unsigned NOT NULL DEFAULT 80,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `helpdesk_sla_policies_uid_unique` (`uid`),
  KEY `helpdesk_sla_policies_priority_id_index` (`priority_id`),
  KEY `helpdesk_sla_policies_category_id_index` (`category_id`),
  KEY `helpdesk_sla_policies_is_active_index` (`is_active`),
  CONSTRAINT `helpdesk_sla_policies_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `helpdesk_categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `helpdesk_sla_policies_priority_id_foreign` FOREIGN KEY (`priority_id`) REFERENCES `helpdesk_priorities` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `helpdesk_statuses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `helpdesk_statuses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uid` varchar(36) NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `type` enum('open','pending','resolved','closed') NOT NULL,
  `color` varchar(7) NOT NULL,
  `order` smallint(5) unsigned NOT NULL DEFAULT 0,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `helpdesk_statuses_uid_unique` (`uid`),
  UNIQUE KEY `helpdesk_statuses_slug_unique` (`slug`),
  KEY `helpdesk_statuses_type_is_active_index` (`type`,`is_active`),
  KEY `helpdesk_statuses_type_index` (`type`),
  KEY `helpdesk_statuses_is_active_index` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `helpdesk_tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `helpdesk_tags` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `slug` varchar(191) NOT NULL,
  `color` varchar(32) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `helpdesk_tags_slug_unique` (`slug`),
  KEY `helpdesk_tags_is_active_index` (`is_active`),
  KEY `helpdesk_tags_order_index` (`order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `helpdesk_ticket_assignments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `helpdesk_ticket_assignments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uid` varchar(36) NOT NULL,
  `ticket_id` bigint(20) unsigned NOT NULL,
  `assigned_to` bigint(20) unsigned NOT NULL,
  `assigned_by` bigint(20) unsigned NOT NULL,
  `assigned_at` timestamp NOT NULL,
  `unassigned_at` timestamp NULL DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `helpdesk_ticket_assignments_uid_unique` (`uid`),
  KEY `helpdesk_ticket_assignments_assigned_by_foreign` (`assigned_by`),
  KEY `helpdesk_ticket_assignments_ticket_id_index` (`ticket_id`),
  KEY `helpdesk_ticket_assignments_assigned_to_index` (`assigned_to`),
  CONSTRAINT `helpdesk_ticket_assignments_assigned_by_foreign` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `helpdesk_ticket_assignments_assigned_to_foreign` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `helpdesk_ticket_assignments_ticket_id_foreign` FOREIGN KEY (`ticket_id`) REFERENCES `helpdesk_tickets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `helpdesk_ticket_attachments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `helpdesk_ticket_attachments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uid` varchar(36) NOT NULL,
  `ticket_message_id` bigint(20) unsigned NOT NULL,
  `filename` varchar(255) NOT NULL,
  `original_filename` varchar(255) NOT NULL,
  `mime_type` varchar(100) NOT NULL,
  `size` bigint(20) unsigned NOT NULL,
  `path` varchar(500) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `helpdesk_ticket_attachments_uid_unique` (`uid`),
  KEY `helpdesk_ticket_attachments_ticket_message_id_index` (`ticket_message_id`),
  CONSTRAINT `helpdesk_ticket_attachments_ticket_message_id_foreign` FOREIGN KEY (`ticket_message_id`) REFERENCES `helpdesk_ticket_messages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `helpdesk_ticket_canned_replies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `helpdesk_ticket_canned_replies` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(191) NOT NULL,
  `content` longtext NOT NULL,
  `short_code` varchar(191) DEFAULT NULL,
  `usage_count` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `is_global` tinyint(1) NOT NULL DEFAULT 0,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `html_body` longtext DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `tags` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`tags`)),
  PRIMARY KEY (`id`),
  UNIQUE KEY `helpdesk_ticket_canned_replies_short_code_unique` (`short_code`),
  KEY `helpdesk_ticket_canned_replies_is_global_index` (`is_global`),
  KEY `helpdesk_ticket_canned_replies_user_id_index` (`user_id`),
  KEY `helpdesk_ticket_canned_replies_is_active_index` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `helpdesk_ticket_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `helpdesk_ticket_categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uid` varchar(191) DEFAULT NULL,
  `name` varchar(191) NOT NULL,
  `key` varchar(191) DEFAULT NULL,
  `slug` varchar(191) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(191) DEFAULT NULL,
  `color` varchar(191) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `position` int(11) NOT NULL DEFAULT 0,
  `order` int(11) NOT NULL DEFAULT 0,
  `default_sla_policy_id` bigint(20) unsigned DEFAULT NULL,
  `custom_form_fields` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`custom_form_fields`)),
  `required_fields` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`required_fields`)),
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `helpdesk_ticket_categories_slug_unique` (`slug`),
  UNIQUE KEY `helpdesk_ticket_categories_key_unique` (`key`),
  UNIQUE KEY `helpdesk_ticket_categories_uid_unique` (`uid`),
  KEY `helpdesk_ticket_categories_order_index` (`order`),
  KEY `helpdesk_ticket_categories_active_index` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `helpdesk_ticket_category_ticket_canned_reply`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `helpdesk_ticket_category_ticket_canned_reply` (
  `ticket_category_id` bigint(20) unsigned NOT NULL,
  `ticket_canned_reply_id` bigint(20) unsigned NOT NULL,
  `order` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`ticket_category_id`,`ticket_canned_reply_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `helpdesk_ticket_category_ticket_group`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `helpdesk_ticket_category_ticket_group` (
  `ticket_category_id` bigint(20) unsigned NOT NULL,
  `ticket_group_id` bigint(20) unsigned NOT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `priority` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`ticket_category_id`,`ticket_group_id`),
  KEY `hdtcg_cat_idx` (`ticket_category_id`),
  KEY `hdtcg_grp_idx` (`ticket_group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `helpdesk_ticket_comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `helpdesk_ticket_comments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ticket_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `author_id` bigint(20) unsigned DEFAULT NULL,
  `body` longtext DEFAULT NULL,
  `html_body` longtext DEFAULT NULL,
  `is_internal` tinyint(1) NOT NULL DEFAULT 0,
  `attachment_urls` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`attachment_urls`)),
  `edited_by` bigint(20) unsigned DEFAULT NULL,
  `edited_at` timestamp NULL DEFAULT NULL,
  `edit_reason` text DEFAULT NULL,
  `mentioned_user_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`mentioned_user_ids`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `helpdesk_ticket_comments_author_id_foreign` (`author_id`),
  KEY `helpdesk_ticket_comments_ticket_id_index` (`ticket_id`),
  KEY `helpdesk_ticket_comments_is_internal_index` (`is_internal`),
  KEY `helpdesk_ticket_comments_created_at_index` (`created_at`),
  KEY `helpdesk_ticket_comments_user_id_index` (`user_id`),
  CONSTRAINT `helpdesk_ticket_comments_author_id_foreign` FOREIGN KEY (`author_id`) REFERENCES `helpdesk_customers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `helpdesk_ticket_comments_ticket_id_foreign` FOREIGN KEY (`ticket_id`) REFERENCES `helpdesk_tickets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `helpdesk_ticket_daily_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `helpdesk_ticket_daily_reports` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `report_date` date NOT NULL,
  `total_created` int(10) unsigned NOT NULL DEFAULT 0,
  `total_closed` int(10) unsigned NOT NULL DEFAULT 0,
  `total_resolved` int(10) unsigned NOT NULL DEFAULT 0,
  `avg_response_time_minutes` double DEFAULT NULL,
  `avg_resolution_time_minutes` double DEFAULT NULL,
  `sla_breached_count` int(10) unsigned NOT NULL DEFAULT 0,
  `by_category` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`by_category`)),
  `by_priority` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`by_priority`)),
  `by_status` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`by_status`)),
  `agent_performance` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`agent_performance`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `helpdesk_ticket_daily_reports_report_date_unique` (`report_date`),
  KEY `helpdesk_ticket_daily_reports_report_date_index` (`report_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `helpdesk_ticket_followups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `helpdesk_ticket_followups` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ticket_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `scheduled_at` timestamp NOT NULL,
  `note` text DEFAULT NULL,
  `is_sent` tinyint(1) NOT NULL DEFAULT 0,
  `sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `helpdesk_ticket_followups_ticket_id_foreign` (`ticket_id`),
  KEY `helpdesk_ticket_followups_user_id_index` (`user_id`),
  KEY `helpdesk_ticket_followups_scheduled_at_index` (`scheduled_at`),
  KEY `helpdesk_ticket_followups_is_sent_index` (`is_sent`),
  CONSTRAINT `helpdesk_ticket_followups_ticket_id_foreign` FOREIGN KEY (`ticket_id`) REFERENCES `helpdesk_tickets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `helpdesk_ticket_group_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `helpdesk_ticket_group_user` (
  `ticket_group_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `priority` varchar(20) NOT NULL DEFAULT 'primary',
  `conversation_priority` varchar(16) NOT NULL DEFAULT 'primary',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`ticket_group_id`,`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `helpdesk_ticket_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `helpdesk_ticket_groups` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `description` varchar(191) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `email` varchar(191) DEFAULT NULL,
  `position` int(11) NOT NULL DEFAULT 0,
  `assignment_mode` varchar(32) NOT NULL DEFAULT 'round_robin',
  `deleted_at` timestamp NULL DEFAULT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `order` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `helpdesk_ticket_groups_is_active_index` (`is_active`),
  KEY `helpdesk_ticket_groups_position_index` (`position`),
  KEY `helpdesk_ticket_groups_is_default_index` (`is_default`),
  KEY `helpdesk_ticket_groups_order_index` (`order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `helpdesk_ticket_histories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `helpdesk_ticket_histories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ticket_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `field_name` varchar(191) DEFAULT NULL,
  `old_value` longtext DEFAULT NULL,
  `new_value` longtext DEFAULT NULL,
  `action_type` varchar(191) NOT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `helpdesk_ticket_histories_ticket_id_index` (`ticket_id`),
  KEY `helpdesk_ticket_histories_action_type_index` (`action_type`),
  KEY `helpdesk_ticket_histories_created_at_index` (`created_at`),
  CONSTRAINT `helpdesk_ticket_histories_ticket_id_foreign` FOREIGN KEY (`ticket_id`) REFERENCES `helpdesk_tickets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `helpdesk_ticket_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `helpdesk_ticket_history` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uid` varchar(36) NOT NULL,
  `ticket_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `field` varchar(100) DEFAULT NULL,
  `old_value` text DEFAULT NULL,
  `new_value` text DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `helpdesk_ticket_history_uid_unique` (`uid`),
  KEY `helpdesk_ticket_history_user_id_foreign` (`user_id`),
  KEY `helpdesk_ticket_history_ticket_id_index` (`ticket_id`),
  KEY `helpdesk_ticket_history_ticket_id_created_at_index` (`ticket_id`,`created_at`),
  KEY `helpdesk_ticket_history_action_index` (`action`),
  CONSTRAINT `helpdesk_ticket_history_ticket_id_foreign` FOREIGN KEY (`ticket_id`) REFERENCES `helpdesk_tickets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `helpdesk_ticket_history_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `helpdesk_ticket_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `helpdesk_ticket_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ticket_id` bigint(20) unsigned NOT NULL,
  `author_id` bigint(20) unsigned DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `type` varchar(191) NOT NULL DEFAULT 'message',
  `body` longtext DEFAULT NULL,
  `html_body` longtext DEFAULT NULL,
  `attachment_urls` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`attachment_urls`)),
  `is_internal` tinyint(1) NOT NULL DEFAULT 0,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `sentiment` varchar(16) DEFAULT NULL,
  `sentiment_score` decimal(3,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `helpdesk_ticket_items_author_id_foreign` (`author_id`),
  KEY `helpdesk_ticket_items_ticket_id_index` (`ticket_id`),
  KEY `helpdesk_ticket_items_type_index` (`type`),
  KEY `helpdesk_ticket_items_created_at_index` (`created_at`),
  KEY `helpdesk_ticket_items_sentiment_index` (`sentiment`),
  CONSTRAINT `helpdesk_ticket_items_author_id_foreign` FOREIGN KEY (`author_id`) REFERENCES `helpdesk_customers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `helpdesk_ticket_items_ticket_id_foreign` FOREIGN KEY (`ticket_id`) REFERENCES `helpdesk_tickets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `helpdesk_ticket_links`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `helpdesk_ticket_links` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ticket_id` bigint(20) unsigned NOT NULL,
  `linked_ticket_id` bigint(20) unsigned NOT NULL,
  `link_type` varchar(32) NOT NULL DEFAULT 'related',
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `helpdesk_ticket_links_ticket_id_linked_ticket_id_unique` (`ticket_id`,`linked_ticket_id`),
  KEY `helpdesk_ticket_links_linked_ticket_id_foreign` (`linked_ticket_id`),
  KEY `helpdesk_ticket_links_ticket_id_link_type_index` (`ticket_id`,`link_type`),
  CONSTRAINT `helpdesk_ticket_links_linked_ticket_id_foreign` FOREIGN KEY (`linked_ticket_id`) REFERENCES `helpdesk_tickets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `helpdesk_ticket_links_ticket_id_foreign` FOREIGN KEY (`ticket_id`) REFERENCES `helpdesk_tickets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `helpdesk_ticket_mails`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `helpdesk_ticket_mails` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ticket_id` bigint(20) unsigned NOT NULL,
  `ticket_comment_id` bigint(20) unsigned DEFAULT NULL,
  `direction` varchar(191) NOT NULL,
  `message_id` varchar(191) DEFAULT NULL,
  `in_reply_to` varchar(191) DEFAULT NULL,
  `references` text DEFAULT NULL,
  `from` varchar(191) NOT NULL,
  `to` varchar(191) NOT NULL,
  `cc` varchar(191) DEFAULT NULL,
  `bcc` varchar(191) DEFAULT NULL,
  `subject` varchar(191) NOT NULL,
  `body_html` longtext DEFAULT NULL,
  `body_text` longtext DEFAULT NULL,
  `attachments` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`attachments`)),
  `headers` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`headers`)),
  `status` varchar(191) NOT NULL DEFAULT 'pending',
  `delivery_error` text DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `delivered_at` timestamp NULL DEFAULT NULL,
  `raw_email` longtext DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `helpdesk_ticket_mails_ticket_comment_id_foreign` (`ticket_comment_id`),
  KEY `helpdesk_ticket_mails_ticket_id_index` (`ticket_id`),
  KEY `helpdesk_ticket_mails_direction_index` (`direction`),
  KEY `helpdesk_ticket_mails_status_index` (`status`),
  KEY `helpdesk_ticket_mails_message_id_index` (`message_id`),
  CONSTRAINT `helpdesk_ticket_mails_ticket_comment_id_foreign` FOREIGN KEY (`ticket_comment_id`) REFERENCES `helpdesk_ticket_comments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `helpdesk_ticket_mails_ticket_id_foreign` FOREIGN KEY (`ticket_id`) REFERENCES `helpdesk_tickets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `helpdesk_ticket_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `helpdesk_ticket_messages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uid` varchar(36) NOT NULL,
  `ticket_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `is_internal` tinyint(1) NOT NULL DEFAULT 0,
  `message` text NOT NULL,
  `message_html` longtext DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `helpdesk_ticket_messages_uid_unique` (`uid`),
  KEY `helpdesk_ticket_messages_user_id_foreign` (`user_id`),
  KEY `helpdesk_ticket_messages_ticket_id_index` (`ticket_id`),
  KEY `helpdesk_ticket_messages_is_internal_index` (`is_internal`),
  CONSTRAINT `helpdesk_ticket_messages_ticket_id_foreign` FOREIGN KEY (`ticket_id`) REFERENCES `helpdesk_tickets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `helpdesk_ticket_messages_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `helpdesk_ticket_notes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `helpdesk_ticket_notes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ticket_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `title` varchar(191) DEFAULT NULL,
  `body` longtext NOT NULL,
  `is_pinned` tinyint(1) NOT NULL DEFAULT 0,
  `color` varchar(191) NOT NULL DEFAULT 'yellow',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `helpdesk_ticket_notes_ticket_id_index` (`ticket_id`),
  KEY `helpdesk_ticket_notes_is_pinned_index` (`is_pinned`),
  KEY `helpdesk_ticket_notes_user_id_index` (`user_id`),
  CONSTRAINT `helpdesk_ticket_notes_ticket_id_foreign` FOREIGN KEY (`ticket_id`) REFERENCES `helpdesk_tickets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `helpdesk_ticket_reads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `helpdesk_ticket_reads` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ticket_item_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_71607` (`ticket_item_id`,`user_id`),
  KEY `helpdesk_ticket_reads_ticket_item_id_index` (`ticket_item_id`),
  CONSTRAINT `helpdesk_ticket_reads_ticket_item_id_foreign` FOREIGN KEY (`ticket_item_id`) REFERENCES `helpdesk_ticket_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `helpdesk_ticket_sla_breaches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `helpdesk_ticket_sla_breaches` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ticket_id` bigint(20) unsigned NOT NULL,
  `sla_type` varchar(191) NOT NULL,
  `breached_at` timestamp NOT NULL,
  `minutes_over` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `helpdesk_ticket_sla_breaches_ticket_id_index` (`ticket_id`),
  KEY `helpdesk_ticket_sla_breaches_breached_at_index` (`breached_at`),
  CONSTRAINT `helpdesk_ticket_sla_breaches_ticket_id_foreign` FOREIGN KEY (`ticket_id`) REFERENCES `helpdesk_tickets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `helpdesk_ticket_sla_policies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `helpdesk_ticket_sla_policies` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uid` varchar(191) DEFAULT NULL,
  `key` varchar(191) DEFAULT NULL,
  `name` varchar(191) NOT NULL,
  `description` text DEFAULT NULL,
  `priority` varchar(191) DEFAULT NULL,
  `first_response_time_hours` int(11) DEFAULT NULL,
  `next_response_time_hours` int(11) DEFAULT NULL,
  `resolution_time_hours` int(11) DEFAULT NULL,
  `first_response_escalation_hours` int(11) DEFAULT NULL,
  `next_response_escalation_hours` int(11) DEFAULT NULL,
  `resolution_escalation_hours` int(11) DEFAULT NULL,
  `escalate_to_manager` tinyint(1) NOT NULL DEFAULT 0,
  `send_escalation_email` tinyint(1) NOT NULL DEFAULT 1,
  `applicable_status_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`applicable_status_ids`)),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `position` int(11) NOT NULL DEFAULT 0,
  `first_response_time` int(11) DEFAULT NULL,
  `next_response_time` int(11) DEFAULT NULL,
  `resolution_time` int(11) DEFAULT NULL,
  `business_hours_only` tinyint(1) NOT NULL DEFAULT 0,
  `business_hours` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`business_hours`)),
  `timezone` varchar(191) NOT NULL DEFAULT 'UTC',
  `priority_multipliers` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`priority_multipliers`)),
  `enable_escalation` tinyint(1) NOT NULL DEFAULT 0,
  `escalation_threshold_percent` int(11) DEFAULT NULL,
  `escalation_recipients` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`escalation_recipients`)),
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `helpdesk_ticket_sla_policies_uid_unique` (`uid`),
  UNIQUE KEY `helpdesk_ticket_sla_policies_key_unique` (`key`),
  KEY `helpdesk_ticket_sla_policies_is_default_index` (`is_default`),
  KEY `helpdesk_ticket_sla_policies_active_index` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `helpdesk_ticket_statuses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `helpdesk_ticket_statuses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `key` varchar(191) DEFAULT NULL,
  `slug` varchar(191) NOT NULL,
  `color` varchar(191) DEFAULT NULL,
  `icon` varchar(191) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `order` int(11) NOT NULL DEFAULT 0,
  `position` int(11) NOT NULL DEFAULT 0,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `is_system` tinyint(1) NOT NULL DEFAULT 0,
  `is_open` tinyint(1) NOT NULL DEFAULT 1,
  `is_closed` tinyint(1) NOT NULL DEFAULT 0,
  `stops_sla_timer` tinyint(1) NOT NULL DEFAULT 0,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `helpdesk_ticket_statuses_slug_unique` (`slug`),
  UNIQUE KEY `helpdesk_ticket_statuses_key_unique` (`key`),
  KEY `helpdesk_ticket_statuses_is_default_index` (`is_default`),
  KEY `helpdesk_ticket_statuses_is_open_index` (`is_open`),
  KEY `helpdesk_ticket_statuses_order_index` (`order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `helpdesk_ticket_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `helpdesk_ticket_templates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `description` varchar(191) DEFAULT NULL,
  `subject` varchar(191) NOT NULL,
  `body` text NOT NULL,
  `category_id` bigint(20) unsigned DEFAULT NULL,
  `priority_id` bigint(20) unsigned DEFAULT NULL,
  `fields` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`fields`)),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `helpdesk_ticket_templates_priority_id_foreign` (`priority_id`),
  KEY `ticket_templates_category_active_index` (`category_id`,`is_active`),
  KEY `ticket_templates_active_index` (`is_active`),
  CONSTRAINT `helpdesk_ticket_templates_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `helpdesk_ticket_categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `helpdesk_ticket_templates_priority_id_foreign` FOREIGN KEY (`priority_id`) REFERENCES `helpdesk_priorities` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `helpdesk_ticket_time_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `helpdesk_ticket_time_entries` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ticket_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `description` varchar(191) DEFAULT NULL,
  `minutes` int(10) unsigned NOT NULL,
  `logged_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `helpdesk_ticket_time_entries_ticket_id_index` (`ticket_id`),
  KEY `helpdesk_ticket_time_entries_user_id_index` (`user_id`),
  KEY `helpdesk_ticket_time_entries_ticket_id_user_id_index` (`ticket_id`,`user_id`),
  CONSTRAINT `helpdesk_ticket_time_entries_ticket_id_foreign` FOREIGN KEY (`ticket_id`) REFERENCES `helpdesk_tickets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `helpdesk_ticket_views`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `helpdesk_ticket_views` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ticket_id` bigint(20) unsigned DEFAULT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `viewed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `name` varchar(191) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `filters` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`filters`)),
  `sort_by` varchar(191) DEFAULT NULL,
  `sort_direction` varchar(8) NOT NULL DEFAULT 'desc',
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `is_shared` tinyint(1) NOT NULL DEFAULT 0,
  `order` int(10) unsigned NOT NULL DEFAULT 0,
  `is_system` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_6530` (`ticket_id`,`user_id`),
  KEY `helpdesk_ticket_views_ticket_id_index` (`ticket_id`),
  KEY `helpdesk_ticket_views_order_index` (`order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `helpdesk_ticket_watchers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `helpdesk_ticket_watchers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ticket_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_6529` (`ticket_id`,`user_id`),
  KEY `helpdesk_ticket_watchers_ticket_id_index` (`ticket_id`),
  CONSTRAINT `helpdesk_ticket_watchers_ticket_id_foreign` FOREIGN KEY (`ticket_id`) REFERENCES `helpdesk_tickets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `helpdesk_tickets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `helpdesk_tickets` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ticket_number` varchar(191) NOT NULL,
  `customer_id` bigint(20) unsigned NOT NULL,
  `category_id` bigint(20) unsigned DEFAULT NULL,
  `status_id` bigint(20) unsigned DEFAULT NULL,
  `sla_policy_id` bigint(20) unsigned DEFAULT NULL,
  `group_id` bigint(20) unsigned DEFAULT NULL,
  `assignee_id` bigint(20) unsigned DEFAULT NULL,
  `subject` varchar(191) NOT NULL,
  `description` longtext DEFAULT NULL,
  `priority` varchar(191) NOT NULL DEFAULT 'normal',
  `source` varchar(191) DEFAULT NULL,
  `custom_fields` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`custom_fields`)),
  `tags` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`tags`)),
  `assigned_at` timestamp NULL DEFAULT NULL,
  `closed_at` timestamp NULL DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `first_response_at` timestamp NULL DEFAULT NULL,
  `last_message_at` timestamp NULL DEFAULT NULL,
  `sla_first_response_due_at` timestamp NULL DEFAULT NULL,
  `sla_next_response_due_at` timestamp NULL DEFAULT NULL,
  `sla_resolution_due_at` timestamp NULL DEFAULT NULL,
  `sla_first_response_breached` tinyint(1) NOT NULL DEFAULT 0,
  `sla_next_response_breached` tinyint(1) NOT NULL DEFAULT 0,
  `sla_resolution_breached` tinyint(1) NOT NULL DEFAULT 0,
  `sla_paused_at` timestamp NULL DEFAULT NULL,
  `sla_paused_duration_minutes` int(11) NOT NULL DEFAULT 0,
  `is_archived` tinyint(1) NOT NULL DEFAULT 0,
  `rating` tinyint(4) DEFAULT NULL,
  `rating_comment` varchar(500) DEFAULT NULL,
  `rated_at` timestamp NULL DEFAULT NULL,
  `escalated_at` timestamp NULL DEFAULT NULL,
  `escalation_count` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `last_activity_at` timestamp NULL DEFAULT NULL,
  `ai_suggested_category_id` bigint(20) unsigned DEFAULT NULL,
  `ai_suggested_priority` varchar(16) DEFAULT NULL,
  `customer_sentiment_avg` decimal(3,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `helpdesk_tickets_ticket_number_unique` (`ticket_number`),
  KEY `helpdesk_tickets_sla_policy_id_foreign` (`sla_policy_id`),
  KEY `helpdesk_tickets_group_id_foreign` (`group_id`),
  KEY `helpdesk_tickets_ticket_number_index` (`ticket_number`),
  KEY `helpdesk_tickets_customer_id_index` (`customer_id`),
  KEY `helpdesk_tickets_status_id_index` (`status_id`),
  KEY `helpdesk_tickets_assignee_id_index` (`assignee_id`),
  KEY `helpdesk_tickets_is_archived_index` (`is_archived`),
  KEY `helpdesk_tickets_created_at_index` (`created_at`),
  KEY `helpdesk_tickets_assignee_status_index` (`assignee_id`,`status_id`),
  KEY `helpdesk_tickets_sla_resolution_due_at_index` (`sla_resolution_due_at`),
  KEY `helpdesk_tickets_customer_created_index` (`customer_id`,`created_at`),
  KEY `helpdesk_tickets_category_id_index` (`category_id`),
  KEY `helpdesk_tickets_first_response_at_index` (`first_response_at`),
  KEY `helpdesk_tickets_resolved_at_index` (`resolved_at`),
  KEY `helpdesk_tickets_sla_breach_status_index` (`sla_first_response_breached`,`status_id`),
  KEY `helpdesk_tickets_sla_first_response_due_at_index` (`sla_first_response_due_at`),
  FULLTEXT KEY `helpdesk_tickets_search_fulltext` (`description`),
  CONSTRAINT `helpdesk_tickets_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `helpdesk_ticket_categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `helpdesk_tickets_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `helpdesk_customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `helpdesk_tickets_group_id_foreign` FOREIGN KEY (`group_id`) REFERENCES `helpdesk_groups` (`id`) ON DELETE SET NULL,
  CONSTRAINT `helpdesk_tickets_sla_policy_id_foreign` FOREIGN KEY (`sla_policy_id`) REFERENCES `helpdesk_ticket_sla_policies` (`id`) ON DELETE SET NULL,
  CONSTRAINT `helpdesk_tickets_status_id_foreign` FOREIGN KEY (`status_id`) REFERENCES `helpdesk_ticket_statuses` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `helpdesk_webhook_deliveries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `helpdesk_webhook_deliveries` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `webhook_id` bigint(20) unsigned NOT NULL,
  `event` varchar(64) NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`payload`)),
  `response_status` int(11) DEFAULT NULL,
  `response_body` text DEFAULT NULL,
  `duration_ms` int(11) DEFAULT NULL,
  `delivered_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `helpdesk_webhook_deliveries_webhook_id_created_at_index` (`webhook_id`,`created_at`),
  KEY `helpdesk_webhook_deliveries_event_index` (`event`),
  CONSTRAINT `helpdesk_webhook_deliveries_webhook_id_foreign` FOREIGN KEY (`webhook_id`) REFERENCES `helpdesk_webhooks` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `helpdesk_webhooks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `helpdesk_webhooks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `url` varchar(500) NOT NULL,
  `integration_type` varchar(16) NOT NULL DEFAULT 'generic',
  `events` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`events`)),
  `secret` varchar(64) DEFAULT NULL,
  `headers` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`headers`)),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `success_count` int(11) NOT NULL DEFAULT 0,
  `failure_count` int(11) NOT NULL DEFAULT 0,
  `last_triggered_at` timestamp NULL DEFAULT NULL,
  `last_error` text DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `helpdesk_webhooks_is_active_index` (`is_active`),
  KEY `helpdesk_webhooks_integration_type_index` (`integration_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `idempotency_keys`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `idempotency_keys` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(80) NOT NULL,
  `endpoint` varchar(200) NOT NULL,
  `response` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`response`)),
  `status_code` int(11) DEFAULT NULL,
  `expires_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idempotency_keys_key_unique` (`key`),
  KEY `idempotency_keys_expires_at_index` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `impersonation_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `impersonation_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `impersonator_id` bigint(20) unsigned NOT NULL,
  `impersonated_id` bigint(20) unsigned NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `reason` varchar(191) DEFAULT NULL,
  `started_at` timestamp NULL DEFAULT NULL,
  `ended_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `impersonation_logs_impersonator_id_started_at_index` (`impersonator_id`,`started_at`),
  KEY `impersonation_logs_impersonated_id_started_at_index` (`impersonated_id`,`started_at`),
  CONSTRAINT `impersonation_logs_impersonated_id_foreign` FOREIGN KEY (`impersonated_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `impersonation_logs_impersonator_id_foreign` FOREIGN KEY (`impersonator_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ip_locations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ip_locations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(191) NOT NULL,
  `country_code` varchar(191) DEFAULT NULL,
  `country_name` varchar(191) DEFAULT NULL,
  `region_code` varchar(191) DEFAULT NULL,
  `region_name` varchar(191) DEFAULT NULL,
  `city` varchar(191) DEFAULT NULL,
  `zipcode` varchar(191) DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(10,8) DEFAULT NULL,
  `metro_code` varchar(191) DEFAULT NULL,
  `areacode` varchar(191) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ip_locations_ip_address_unique` (`ip_address`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(191) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `langs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `langs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uid` varchar(191) NOT NULL,
  `title` varchar(191) NOT NULL,
  `iso_code` varchar(191) NOT NULL,
  `lenguage_code` varchar(191) NOT NULL,
  `locate` varchar(191) DEFAULT NULL,
  `date_format_full` varchar(191) DEFAULT NULL,
  `date_format_lite` varchar(191) DEFAULT NULL,
  `available` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `langs_uid_unique` (`uid`),
  UNIQUE KEY `langs_iso_code_unique` (`iso_code`),
  UNIQUE KEY `langs_lenguage_code_unique` (`lenguage_code`),
  KEY `langs_available_index` (`available`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `locales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `locales` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(10) NOT NULL COMMENT 'ISO locale code (es, en, pt...)',
  `language_code` varchar(10) DEFAULT NULL COMMENT 'Full ISO code, e.g. es_CO, en_US',
  `name` varchar(100) NOT NULL COMMENT 'English name (Spanish, English...)',
  `native_name` varchar(100) NOT NULL COMMENT 'Native name (Español, English...)',
  `rtl` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Right-to-left text direction',
  `flag` varchar(10) DEFAULT NULL COMMENT 'Emoji flag',
  `is_default` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Only one can be default',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `order` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `locales_code_unique` (`code`),
  KEY `locales_is_active_index` (`is_active`),
  KEY `locales_is_active_order_index` (`is_active`,`order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `locations_cities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `locations_cities` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `country_id` bigint(20) unsigned NOT NULL,
  `state_id` bigint(20) unsigned NOT NULL,
  `name` varchar(120) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `locations_cities_country_id_index` (`country_id`),
  KEY `locations_cities_state_id_index` (`state_id`),
  KEY `locations_cities_is_active_index` (`is_active`),
  CONSTRAINT `locations_cities_country_id_foreign` FOREIGN KEY (`country_id`) REFERENCES `locations_countries` (`id`) ON DELETE CASCADE,
  CONSTRAINT `locations_cities_state_id_foreign` FOREIGN KEY (`state_id`) REFERENCES `locations_states` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `locations_countries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `locations_countries` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL,
  `code` varchar(10) NOT NULL,
  `phone_code` varchar(10) DEFAULT NULL,
  `currency_code` varchar(10) DEFAULT NULL,
  `currency_symbol` varchar(10) DEFAULT NULL,
  `flag_emoji` varchar(10) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `locations_countries_code_unique` (`code`),
  KEY `locations_countries_is_active_index` (`is_active`),
  KEY `locations_countries_order_index` (`order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `locations_states`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `locations_states` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `country_id` bigint(20) unsigned NOT NULL,
  `name` varchar(120) NOT NULL,
  `code` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `locations_states_country_id_index` (`country_id`),
  KEY `locations_states_is_active_index` (`is_active`),
  CONSTRAINT `locations_states_country_id_foreign` FOREIGN KEY (`country_id`) REFERENCES `locations_countries` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `login_attempts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `login_attempts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `email` varchar(191) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `status` varchar(20) NOT NULL,
  `reason` varchar(191) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `attempted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `login_attempts_user_id_attempted_at_index` (`user_id`,`attempted_at`),
  KEY `login_attempts_email_attempted_at_index` (`email`,`attempted_at`),
  KEY `login_attempts_ip_address_attempted_at_index` (`ip_address`,`attempted_at`),
  KEY `login_attempts_status_index` (`status`),
  CONSTRAINT `login_attempts_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `magic_login_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `magic_login_tokens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `token_hash` varchar(64) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `expires_at` timestamp NOT NULL,
  `used_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `magic_login_tokens_token_hash_unique` (`token_hash`),
  KEY `magic_login_tokens_user_id_used_at_index` (`user_id`,`used_at`),
  CONSTRAINT `magic_login_tokens_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `mailer_endpoint_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `mailer_endpoint_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `mailer_endpoint_id` bigint(20) unsigned NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`payload`)),
  `status` varchar(191) NOT NULL,
  `error_message` text DEFAULT NULL,
  `recipient_email` varchar(191) DEFAULT NULL,
  `mailer_subject` varchar(191) DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `job_id` varchar(191) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `mailer_endpoint_logs_status_index` (`status`),
  KEY `mailer_endpoint_logs_mailer_endpoint_id_index` (`mailer_endpoint_id`),
  KEY `mailer_endpoint_logs_created_at_index` (`created_at`),
  KEY `mailer_endpoint_logs_recipient_email_index` (`recipient_email`),
  KEY `idx_logs_status_created` (`status`,`created_at`),
  KEY `idx_logs_job_id` (`job_id`),
  CONSTRAINT `mailer_endpoint_logs_mailer_endpoint_id_foreign` FOREIGN KEY (`mailer_endpoint_id`) REFERENCES `mailer_endpoints` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `mailer_endpoints`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `mailer_endpoints` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `slug` varchar(191) NOT NULL,
  `source` varchar(191) NOT NULL,
  `type` varchar(191) NOT NULL,
  `description` text DEFAULT NULL,
  `mailer_template_id` bigint(20) unsigned DEFAULT NULL,
  `lang_id` bigint(20) unsigned NOT NULL,
  `expected_variables` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`expected_variables`)),
  `required_variables` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`required_variables`)),
  `variable_mappings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`variable_mappings`)),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `api_token` varchar(191) NOT NULL,
  `requests_count` int(11) NOT NULL DEFAULT 0,
  `last_request_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mailer_endpoints_slug_unique` (`slug`),
  UNIQUE KEY `mailer_endpoints_api_token_unique` (`api_token`),
  KEY `mailer_endpoints_lang_id_foreign` (`lang_id`),
  KEY `mailer_endpoints_slug_index` (`slug`),
  KEY `mailer_endpoints_is_active_index` (`is_active`),
  KEY `idx_endpoints_template_active` (`mailer_template_id`,`is_active`),
  CONSTRAINT `mailer_endpoints_lang_id_foreign` FOREIGN KEY (`lang_id`) REFERENCES `langs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `mailer_endpoints_mailer_template_id_foreign` FOREIGN KEY (`mailer_template_id`) REFERENCES `mailer_templates` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `mailer_layout_langs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `mailer_layout_langs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uid` uuid NOT NULL,
  `layout_id` bigint(20) unsigned NOT NULL,
  `lang_id` bigint(20) unsigned NOT NULL,
  `subject` varchar(191) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_6502` (`layout_id`,`lang_id`),
  UNIQUE KEY `mailer_layout_langs_uid_unique` (`uid`),
  KEY `mailer_layout_langs_lang_id_index` (`lang_id`),
  CONSTRAINT `mailer_layout_langs_lang_id_foreign` FOREIGN KEY (`lang_id`) REFERENCES `langs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `mailer_layout_langs_layout_id_foreign` FOREIGN KEY (`layout_id`) REFERENCES `mailer_layouts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `mailer_layouts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `mailer_layouts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uid` uuid NOT NULL,
  `alias` varchar(191) NOT NULL,
  `group_name` varchar(191) NOT NULL,
  `code` varchar(191) NOT NULL,
  `type` varchar(191) NOT NULL,
  `is_protected` tinyint(1) NOT NULL DEFAULT 0,
  `is_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mailer_layouts_uid_unique` (`uid`),
  UNIQUE KEY `mailer_layouts_alias_unique` (`alias`),
  KEY `mailer_layouts_alias_index` (`alias`),
  KEY `mailer_layouts_code_index` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `mailer_template_langs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `mailer_template_langs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uid` uuid NOT NULL,
  `mailer_template_id` bigint(20) unsigned NOT NULL,
  `lang_id` bigint(20) unsigned NOT NULL,
  `subject` varchar(191) DEFAULT NULL,
  `preheader` varchar(191) DEFAULT NULL,
  `content` longtext DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_89470` (`mailer_template_id`,`lang_id`),
  UNIQUE KEY `mailer_template_langs_uid_unique` (`uid`),
  KEY `mailer_template_langs_lang_id_index` (`lang_id`),
  CONSTRAINT `mailer_template_langs_lang_id_foreign` FOREIGN KEY (`lang_id`) REFERENCES `langs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `mailer_template_langs_mailer_template_id_foreign` FOREIGN KEY (`mailer_template_id`) REFERENCES `mailer_templates` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `mailer_template_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `mailer_template_versions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `mailer_template_id` bigint(20) unsigned NOT NULL,
  `lang_id` bigint(20) unsigned NOT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `subject` varchar(191) DEFAULT NULL,
  `content` longtext DEFAULT NULL,
  `change_note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `mailer_template_versions_lang_id_foreign` (`lang_id`),
  KEY `mailer_template_versions_created_by_foreign` (`created_by`),
  KEY `mailer_template_versions_mailer_template_id_lang_id_index` (`mailer_template_id`,`lang_id`),
  KEY `idx_versions_template_created` (`mailer_template_id`,`created_at`),
  CONSTRAINT `mailer_template_versions_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `mailer_template_versions_lang_id_foreign` FOREIGN KEY (`lang_id`) REFERENCES `langs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `mailer_template_versions_mailer_template_id_foreign` FOREIGN KEY (`mailer_template_id`) REFERENCES `mailer_templates` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `mailer_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `mailer_templates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uid` uuid NOT NULL,
  `key` varchar(191) NOT NULL,
  `name` varchar(191) NOT NULL,
  `layout_id` bigint(20) unsigned DEFAULT NULL,
  `is_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `is_protected` tinyint(1) NOT NULL DEFAULT 0,
  `variables` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`variables`)),
  `module` varchar(191) NOT NULL DEFAULT 'core',
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mailer_templates_uid_unique` (`uid`),
  UNIQUE KEY `mailer_templates_key_unique` (`key`),
  KEY `mailer_templates_layout_id_foreign` (`layout_id`),
  KEY `mailer_templates_module_index` (`module`),
  KEY `mailer_templates_is_enabled_index` (`is_enabled`),
  CONSTRAINT `mailer_templates_layout_id_foreign` FOREIGN KEY (`layout_id`) REFERENCES `mailer_layouts` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `mailer_variable_langs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `mailer_variable_langs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uid` uuid NOT NULL,
  `mailer_variable_id` bigint(20) unsigned NOT NULL,
  `lang_id` bigint(20) unsigned NOT NULL,
  `name` varchar(191) NOT NULL,
  `description` text DEFAULT NULL,
  `value` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_34187` (`mailer_variable_id`,`lang_id`),
  UNIQUE KEY `mailer_variable_langs_uid_unique` (`uid`),
  KEY `mailer_variable_langs_lang_id_index` (`lang_id`),
  CONSTRAINT `mailer_variable_langs_lang_id_foreign` FOREIGN KEY (`lang_id`) REFERENCES `langs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `mailer_variable_langs_mailer_variable_id_foreign` FOREIGN KEY (`mailer_variable_id`) REFERENCES `mailer_variables` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `mailer_variables`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `mailer_variables` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uid` uuid NOT NULL,
  `key` varchar(191) NOT NULL,
  `name` varchar(191) NOT NULL,
  `description` text DEFAULT NULL,
  `example_value` varchar(191) DEFAULT NULL,
  `category` varchar(191) DEFAULT NULL,
  `module` varchar(191) NOT NULL DEFAULT 'core',
  `is_system` tinyint(1) NOT NULL DEFAULT 0,
  `is_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_90505` (`key`,`module`),
  UNIQUE KEY `mailer_variables_uid_unique` (`uid`),
  KEY `mailer_variables_module_index` (`module`),
  KEY `mailer_variables_category_index` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `media`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `media` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `model_type` varchar(191) NOT NULL,
  `model_id` bigint(20) unsigned NOT NULL,
  `uuid` uuid DEFAULT NULL,
  `collection_name` varchar(191) NOT NULL,
  `name` varchar(191) NOT NULL,
  `file_name` varchar(191) NOT NULL,
  `mime_type` varchar(191) DEFAULT NULL,
  `disk` varchar(191) NOT NULL,
  `conversions_disk` varchar(191) DEFAULT NULL,
  `size` bigint(20) unsigned NOT NULL,
  `manipulations` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`manipulations`)),
  `custom_properties` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`custom_properties`)),
  `generated_conversions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`generated_conversions`)),
  `responsive_images` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`responsive_images`)),
  `order_column` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `media_uuid_unique` (`uuid`),
  KEY `media_model_type_model_id_index` (`model_type`,`model_id`),
  KEY `media_order_column_index` (`order_column`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `media_access_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `media_access_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `media_file_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `action` varchar(32) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(512) DEFAULT NULL,
  `referer` varchar(512) DEFAULT NULL,
  `share_token` varchar(128) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `media_access_logs_media_file_id_action_index` (`media_file_id`,`action`),
  KEY `media_access_logs_user_id_index` (`user_id`),
  KEY `media_access_logs_created_at_index` (`created_at`),
  CONSTRAINT `media_access_logs_media_file_id_foreign` FOREIGN KEY (`media_file_id`) REFERENCES `media_files` (`id`) ON DELETE CASCADE,
  CONSTRAINT `media_access_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `media_comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `media_comments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `commentable_id` bigint(20) unsigned NOT NULL,
  `commentable_type` varchar(191) NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `parent_id` bigint(20) unsigned DEFAULT NULL,
  `content` text NOT NULL,
  `mentions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`mentions`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `media_comments_user_id_foreign` (`user_id`),
  KEY `media_comments_commentable_id_commentable_type_index` (`commentable_id`,`commentable_type`),
  KEY `media_comments_parent_id_index` (`parent_id`),
  CONSTRAINT `media_comments_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `media_comments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `media_comments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `media_favorites`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `media_favorites` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `favoritable_id` bigint(20) unsigned NOT NULL,
  `favoritable_type` varchar(64) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `media_favorites_unique` (`user_id`,`favoritable_type`,`favoritable_id`),
  CONSTRAINT `media_favorites_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `media_file_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `media_file_versions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `media_file_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `name` varchar(191) NOT NULL,
  `mime_type` varchar(191) NOT NULL,
  `size` bigint(20) unsigned NOT NULL,
  `url` varchar(191) NOT NULL,
  `disk` varchar(191) NOT NULL,
  `file_hash` varchar(64) DEFAULT NULL,
  `version_number` int(11) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `media_file_versions_user_id_foreign` (`user_id`),
  KEY `media_file_versions_media_file_id_version_number_index` (`media_file_id`,`version_number`),
  CONSTRAINT `media_file_versions_media_file_id_foreign` FOREIGN KEY (`media_file_id`) REFERENCES `media_files` (`id`) ON DELETE CASCADE,
  CONSTRAINT `media_file_versions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `media_files`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `media_files` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uid` varchar(191) NOT NULL,
  `name` varchar(191) NOT NULL,
  `mime_type` varchar(191) NOT NULL,
  `type` varchar(191) NOT NULL,
  `size` bigint(20) NOT NULL,
  `url` varchar(191) NOT NULL,
  `alt` text DEFAULT NULL,
  `folder_id` bigint(20) unsigned DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `disk` varchar(50) NOT NULL DEFAULT 'media',
  `file_hash` varchar(64) DEFAULT NULL,
  `phash` varchar(16) DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `visibility` varchar(191) NOT NULL DEFAULT 'private',
  `workflow_status` varchar(16) NOT NULL DEFAULT 'draft',
  `approved_by_user_id` bigint(20) unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `media_files_uid_unique` (`uid`),
  KEY `media_files_folder_id_index` (`folder_id`),
  KEY `media_files_user_id_index` (`user_id`),
  KEY `media_files_mime_type_index` (`mime_type`),
  KEY `media_files_type_index` (`type`),
  KEY `media_files_disk_index` (`disk`),
  KEY `media_files_file_hash_index` (`file_hash`),
  KEY `media_files_created_at_index` (`created_at`),
  KEY `media_files_phash_index` (`phash`),
  KEY `media_files_expires_at_index` (`expires_at`),
  KEY `media_files_approved_by_user_id_foreign` (`approved_by_user_id`),
  KEY `media_files_workflow_status_index` (`workflow_status`),
  FULLTEXT KEY `media_files_name_fulltext` (`name`),
  CONSTRAINT `media_files_approved_by_user_id_foreign` FOREIGN KEY (`approved_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `media_files_folder_id_foreign` FOREIGN KEY (`folder_id`) REFERENCES `media_folders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `media_files_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `media_folder_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `media_folder_templates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `slug` varchar(191) NOT NULL,
  `description` text DEFAULT NULL,
  `structure` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`structure`)),
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `is_shared` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `media_folder_templates_slug_unique` (`slug`),
  KEY `media_folder_templates_user_id_foreign` (`user_id`),
  CONSTRAINT `media_folder_templates_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `media_folders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `media_folders` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uid` varchar(191) NOT NULL,
  `name` varchar(191) NOT NULL,
  `slug` varchar(191) NOT NULL,
  `parent_id` bigint(20) unsigned DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `color` varchar(191) NOT NULL DEFAULT '#5a67d8',
  `disk` varchar(50) NOT NULL DEFAULT 'media',
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `media_folders_uid_unique` (`uid`),
  UNIQUE KEY `media_folders_slug_unique` (`slug`),
  KEY `media_folders_parent_id_index` (`parent_id`),
  KEY `media_folders_user_id_index` (`user_id`),
  KEY `media_folders_slug_index` (`slug`),
  CONSTRAINT `media_folders_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `media_folders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `media_folders_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `media_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `media_settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(191) NOT NULL,
  `value` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`value`)),
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `media_settings_key_user_id_index` (`key`,`user_id`),
  KEY `media_settings_user_id_foreign` (`user_id`),
  CONSTRAINT `media_settings_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `media_share_revocations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `media_share_revocations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `token_hash` varchar(64) NOT NULL,
  `revoked_by_user_id` bigint(20) unsigned NOT NULL,
  `reason` text DEFAULT NULL,
  `revoked_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `media_share_revocations_token_hash_unique` (`token_hash`),
  KEY `media_share_revocations_revoked_by_user_id_foreign` (`revoked_by_user_id`),
  CONSTRAINT `media_share_revocations_revoked_by_user_id_foreign` FOREIGN KEY (`revoked_by_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `media_shares`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `media_shares` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `shareable_id` bigint(20) unsigned NOT NULL,
  `shareable_type` varchar(191) NOT NULL,
  `shared_by_user_id` bigint(20) unsigned NOT NULL,
  `shared_with_user_id` bigint(20) unsigned NOT NULL,
  `role` varchar(16) NOT NULL DEFAULT 'view',
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `media_shares_unique` (`shareable_id`,`shareable_type`,`shared_with_user_id`),
  KEY `media_shares_shared_by_user_id_foreign` (`shared_by_user_id`),
  KEY `media_shares_shared_with_user_id_foreign` (`shared_with_user_id`),
  KEY `media_shares_shareable_id_shareable_type_index` (`shareable_id`,`shareable_type`),
  CONSTRAINT `media_shares_shared_by_user_id_foreign` FOREIGN KEY (`shared_by_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `media_shares_shared_with_user_id_foreign` FOREIGN KEY (`shared_with_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `media_taggables`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `media_taggables` (
  `media_tag_id` bigint(20) unsigned NOT NULL,
  `taggable_id` bigint(20) unsigned NOT NULL,
  `taggable_type` varchar(191) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`media_tag_id`,`taggable_id`,`taggable_type`),
  KEY `media_taggables_taggable_id_taggable_type_index` (`taggable_id`,`taggable_type`),
  CONSTRAINT `media_taggables_media_tag_id_foreign` FOREIGN KEY (`media_tag_id`) REFERENCES `media_tags` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `media_tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `media_tags` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(64) NOT NULL,
  `slug` varchar(64) NOT NULL,
  `color` varchar(7) DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `media_tags_slug_unique` (`slug`),
  KEY `media_tags_user_id_foreign` (`user_id`),
  CONSTRAINT `media_tags_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `mediables`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `mediables` (
  `media_file_id` bigint(20) unsigned NOT NULL,
  `mediable_id` bigint(20) unsigned NOT NULL,
  `mediable_type` varchar(191) NOT NULL,
  `collection` varchar(64) NOT NULL DEFAULT 'default',
  `order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`media_file_id`,`mediable_id`,`mediable_type`),
  KEY `mediables_mediable_id_mediable_type_index` (`mediable_id`,`mediable_type`),
  CONSTRAINT `mediables_media_file_id_foreign` FOREIGN KEY (`media_file_id`) REFERENCES `media_files` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `menu_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `menu_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `menu_id` bigint(20) unsigned NOT NULL,
  `parent_id` bigint(20) unsigned DEFAULT NULL,
  `title` varchar(191) NOT NULL,
  `url` varchar(191) DEFAULT NULL,
  `target` varchar(191) NOT NULL DEFAULT '_self',
  `icon` varchar(191) DEFAULT NULL,
  `css_class` varchar(191) DEFAULT NULL,
  `order` int(11) NOT NULL DEFAULT 0,
  `has_child` tinyint(1) NOT NULL DEFAULT 0,
  `type` enum('custom','page','post','category','route') NOT NULL DEFAULT 'custom',
  `reference_id` bigint(20) unsigned DEFAULT NULL,
  `reference_type` varchar(191) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `menu_items_menu_id_foreign` (`menu_id`),
  KEY `menu_items_parent_id_foreign` (`parent_id`),
  KEY `menu_items_reference_id_reference_type_index` (`reference_id`,`reference_type`),
  CONSTRAINT `menu_items_menu_id_foreign` FOREIGN KEY (`menu_id`) REFERENCES `menus` (`id`) ON DELETE CASCADE,
  CONSTRAINT `menu_items_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `menu_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `menus`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `menus` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `slug` varchar(191) NOT NULL,
  `location` varchar(191) DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `menus_slug_unique` (`slug`),
  KEY `menus_location_index` (`location`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(191) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=496 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `model_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `model_has_permissions` (
  `permission_id` bigint(20) unsigned NOT NULL,
  `model_type` varchar(191) NOT NULL,
  `model_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `model_has_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `model_has_roles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `role_id` bigint(20) unsigned NOT NULL,
  `model_type` varchar(191) NOT NULL,
  `model_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_role_model` (`role_id`,`model_id`,`model_type`),
  KEY `model_has_roles_model_type_model_id_index` (`model_type`,`model_id`),
  KEY `model_has_roles_model_id_index` (`model_id`),
  KEY `model_has_roles_model_type_index` (`model_type`),
  CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `newsletter_subscribers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `newsletter_subscribers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(191) NOT NULL,
  `name` varchar(191) DEFAULT NULL,
  `status` tinyint(3) unsigned NOT NULL DEFAULT 1,
  `ip_address` varchar(45) DEFAULT NULL,
  `mailjet_id` varchar(100) DEFAULT NULL,
  `subscribed_at` timestamp NULL DEFAULT NULL,
  `unsubscribed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `newsletter_subscribers_email_unique` (`email`),
  KEY `newsletter_subscribers_status_index` (`status`),
  KEY `newsletter_subscribers_status_created_at_index` (`status`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `notification_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `notification_settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `channel` varchar(191) NOT NULL,
  `notification_type` varchar(191) NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_47991` (`user_id`,`channel`,`notification_type`),
  CONSTRAINT `notification_settings_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `id` uuid NOT NULL,
  `type` varchar(191) NOT NULL,
  `notifiable_type` varchar(191) NOT NULL,
  `notifiable_id` bigint(20) unsigned NOT NULL,
  `data` longtext NOT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notifications_notifiable_type_notifiable_id_index` (`notifiable_type`,`notifiable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `page_approvals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `page_approvals` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `page_id` bigint(20) unsigned NOT NULL,
  `requested_by` bigint(20) unsigned NOT NULL,
  `reviewed_by` bigint(20) unsigned DEFAULT NULL,
  `status` enum('pending','approved','rejected','cancelled') NOT NULL DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `requested_at` timestamp NOT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `page_approvals_requested_by_foreign` (`requested_by`),
  KEY `page_approvals_reviewed_by_foreign` (`reviewed_by`),
  KEY `page_approvals_page_id_status_index` (`page_id`,`status`),
  KEY `page_approvals_status_index` (`status`),
  CONSTRAINT `page_approvals_page_id_foreign` FOREIGN KEY (`page_id`) REFERENCES `pages` (`id`) ON DELETE CASCADE,
  CONSTRAINT `page_approvals_requested_by_foreign` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`),
  CONSTRAINT `page_approvals_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `page_auto_saves`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `page_auto_saves` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `page_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT 'Contenido, título, slug, etc. guardados automáticamente' CHECK (json_valid(`data`)),
  `status` varchar(191) NOT NULL DEFAULT 'draft' COMMENT 'draft, published, pending',
  `content` longtext DEFAULT NULL COMMENT 'Contenido en borrador',
  `saved_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NULL DEFAULT NULL COMMENT 'Cuándo expira este borrador',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `page_auto_saves_page_id_user_id_unique` (`page_id`,`user_id`),
  KEY `page_auto_saves_page_id_index` (`page_id`),
  KEY `page_auto_saves_user_id_index` (`user_id`),
  KEY `page_auto_saves_saved_at_index` (`saved_at`),
  KEY `page_auto_saves_expires_at_index` (`expires_at`),
  CONSTRAINT `page_auto_saves_page_id_foreign` FOREIGN KEY (`page_id`) REFERENCES `pages` (`id`) ON DELETE CASCADE,
  CONSTRAINT `page_auto_saves_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `page_cache_audits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `page_cache_audits` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `action` varchar(191) NOT NULL,
  `page_id` bigint(20) unsigned DEFAULT NULL,
  `slug` varchar(191) DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `page_cache_audits_page_id_foreign` (`page_id`),
  KEY `page_cache_audits_user_id_foreign` (`user_id`),
  KEY `page_cache_audits_action_index` (`action`),
  KEY `page_cache_audits_created_at_index` (`created_at`),
  CONSTRAINT `page_cache_audits_page_id_foreign` FOREIGN KEY (`page_id`) REFERENCES `pages` (`id`) ON DELETE SET NULL,
  CONSTRAINT `page_cache_audits_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `page_cache_configs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `page_cache_configs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `page_id` bigint(20) unsigned NOT NULL,
  `cache_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `warm_on_save` tinyint(1) NOT NULL DEFAULT 0,
  `excluded_roles` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`excluded_roles`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `page_cache_configs_page_id_unique` (`page_id`),
  CONSTRAINT `page_cache_configs_page_id_foreign` FOREIGN KEY (`page_id`) REFERENCES `pages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `page_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `page_categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `slug` varchar(191) NOT NULL,
  `description` varchar(191) DEFAULT NULL,
  `color` varchar(7) NOT NULL DEFAULT '#90bb13',
  `icon` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `page_categories_slug_unique` (`slug`),
  KEY `page_categories_is_active_index` (`is_active`),
  KEY `page_categories_sort_order_index` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `page_category_page`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `page_category_page` (
  `page_id` bigint(20) unsigned NOT NULL,
  `page_category_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`page_id`,`page_category_id`),
  KEY `page_category_page_page_category_id_foreign` (`page_category_id`),
  CONSTRAINT `page_category_page_page_category_id_foreign` FOREIGN KEY (`page_category_id`) REFERENCES `page_categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `page_category_page_page_id_foreign` FOREIGN KEY (`page_id`) REFERENCES `pages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `page_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `page_locks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `page_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `session_id` varchar(191) DEFAULT NULL COMMENT 'Session ID para detectar si sesión está activa',
  `locked_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NOT NULL COMMENT 'Cuándo expira el lock (5 minutos)',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `page_locks_page_id_unique` (`page_id`),
  KEY `page_locks_page_id_index` (`page_id`),
  KEY `page_locks_user_id_index` (`user_id`),
  KEY `page_locks_expires_at_index` (`expires_at`),
  CONSTRAINT `page_locks_page_id_foreign` FOREIGN KEY (`page_id`) REFERENCES `pages` (`id`) ON DELETE CASCADE,
  CONSTRAINT `page_locks_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `page_performance_metrics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `page_performance_metrics` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `page_id` bigint(20) unsigned NOT NULL,
  `strategy` enum('mobile','desktop') NOT NULL,
  `performance_score` double DEFAULT NULL,
  `accessibility_score` double DEFAULT NULL,
  `seo_score` double DEFAULT NULL,
  `best_practices_score` double DEFAULT NULL,
  `lcp` int(11) DEFAULT NULL,
  `fid` int(11) DEFAULT NULL,
  `cls_score` int(11) DEFAULT NULL,
  `fcp` int(11) DEFAULT NULL,
  `ttfb` int(11) DEFAULT NULL,
  `tbt` int(11) DEFAULT NULL,
  `speed_index` int(11) DEFAULT NULL,
  `opportunities` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`opportunities`)),
  `diagnostics` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`diagnostics`)),
  `page_url` varchar(191) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `page_performance_metrics_page_id_strategy_created_at_index` (`page_id`,`strategy`,`created_at`),
  KEY `page_performance_score_index` (`performance_score`),
  CONSTRAINT `page_performance_metrics_page_id_foreign` FOREIGN KEY (`page_id`) REFERENCES `pages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `page_preview_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `page_preview_tokens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `page_id` bigint(20) unsigned NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` timestamp NOT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `viewed_count` int(10) unsigned NOT NULL DEFAULT 0,
  `last_viewed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `page_preview_tokens_token_unique` (`token`),
  KEY `page_preview_tokens_page_id_token_index` (`page_id`,`token`),
  KEY `page_preview_tokens_expires_at_index` (`expires_at`),
  KEY `page_preview_tokens_created_by_index` (`created_by`),
  KEY `page_preview_tokens_token_index` (`token`),
  CONSTRAINT `page_preview_tokens_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `page_preview_tokens_page_id_foreign` FOREIGN KEY (`page_id`) REFERENCES `pages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `page_tag_page`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `page_tag_page` (
  `page_id` bigint(20) unsigned NOT NULL,
  `page_tag_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`page_id`,`page_tag_id`),
  KEY `page_tag_page_page_tag_id_foreign` (`page_tag_id`),
  CONSTRAINT `page_tag_page_page_id_foreign` FOREIGN KEY (`page_id`) REFERENCES `pages` (`id`) ON DELETE CASCADE,
  CONSTRAINT `page_tag_page_page_tag_id_foreign` FOREIGN KEY (`page_tag_id`) REFERENCES `page_tags` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `page_tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `page_tags` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `slug` varchar(191) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `page_tags_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `page_translations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `page_translations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `page_id` bigint(20) unsigned NOT NULL,
  `locale_id` bigint(20) unsigned DEFAULT NULL,
  `title` varchar(191) DEFAULT NULL,
  `slug` varchar(191) DEFAULT NULL,
  `content` longtext DEFAULT NULL,
  `description` text DEFAULT NULL,
  `seo_title` varchar(191) DEFAULT NULL,
  `seo_description` text DEFAULT NULL,
  `seo_keywords` text DEFAULT NULL,
  `seo_image_url` varchar(500) DEFAULT NULL,
  `seo_noindex` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('draft','published','pending') NOT NULL DEFAULT 'draft',
  `published_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `page_translations_page_id_locale_id_unique` (`page_id`,`locale_id`),
  KEY `page_translations_locale_id_index` (`locale_id`),
  KEY `page_translations_page_id_index` (`page_id`),
  KEY `page_translations_locale_id_slug_index` (`locale_id`,`slug`),
  KEY `page_translations_locale_id_status_published_at_index` (`locale_id`,`status`,`published_at`),
  KEY `page_translations_slug_index` (`slug`),
  KEY `page_translations_status_index` (`status`),
  KEY `page_translations_published_at_index` (`published_at`),
  CONSTRAINT `page_translations_locale_id_foreign` FOREIGN KEY (`locale_id`) REFERENCES `locales` (`id`) ON DELETE SET NULL,
  CONSTRAINT `page_translations_page_id_foreign` FOREIGN KEY (`page_id`) REFERENCES `pages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `page_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `page_versions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `page_id` bigint(20) unsigned NOT NULL,
  `version_number` int(10) unsigned NOT NULL,
  `title` varchar(191) NOT NULL,
  `content` longtext DEFAULT NULL,
  `description` text DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `template` varchar(60) DEFAULT NULL,
  `status` enum('draft','published','pending') DEFAULT NULL,
  `slug` varchar(191) DEFAULT NULL,
  `seo_title` varchar(191) DEFAULT NULL,
  `seo_description` text DEFAULT NULL,
  `seo_keywords` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `page_versions_page_id_version_number_unique` (`page_id`,`version_number`),
  KEY `page_versions_user_id_foreign` (`user_id`),
  KEY `page_versions_page_id_version_number_index` (`page_id`,`version_number`),
  KEY `page_versions_page_id_index` (`page_id`),
  KEY `page_versions_created_at_index` (`created_at`),
  CONSTRAINT `page_versions_page_id_foreign` FOREIGN KEY (`page_id`) REFERENCES `pages` (`id`) ON DELETE CASCADE,
  CONSTRAINT `page_versions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `page_views`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `page_views` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `page_id` bigint(20) unsigned NOT NULL,
  `session_id` varchar(40) DEFAULT NULL,
  `ip_hash` varchar(64) NOT NULL,
  `locale` varchar(10) DEFAULT NULL,
  `referrer` varchar(191) DEFAULT NULL,
  `referrer_domain` varchar(100) DEFAULT NULL,
  `device_type` enum('desktop','mobile','tablet') NOT NULL DEFAULT 'desktop',
  `browser` varchar(50) DEFAULT NULL,
  `country_code` varchar(2) DEFAULT NULL,
  `viewed_date` date NOT NULL,
  `viewed_at` timestamp NOT NULL,
  `time_on_page` smallint(5) unsigned DEFAULT NULL,
  `bounced` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `page_views_page_id_viewed_date_index` (`page_id`,`viewed_date`),
  KEY `page_views_page_id_device_type_index` (`page_id`,`device_type`),
  KEY `page_views_viewed_date_index` (`viewed_date`),
  KEY `page_views_referrer_domain_index` (`referrer_domain`),
  KEY `page_views_ip_hash_index` (`ip_hash`),
  KEY `page_views_time_on_page_index` (`time_on_page`),
  KEY `page_views_bounced_index` (`bounced`),
  CONSTRAINT `page_views_page_id_foreign` FOREIGN KEY (`page_id`) REFERENCES `pages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `page_webhooks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `page_webhooks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `url` varchar(191) NOT NULL,
  `secret` varchar(64) DEFAULT NULL,
  `events` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`events`)),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `timeout` int(11) NOT NULL DEFAULT 10,
  `success_count` int(10) unsigned NOT NULL DEFAULT 0,
  `fail_count` int(10) unsigned NOT NULL DEFAULT 0,
  `last_triggered_at` timestamp NULL DEFAULT NULL,
  `last_success_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `page_webhooks_is_active_index` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `pages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` bigint(20) unsigned DEFAULT NULL,
  `title` varchar(191) NOT NULL,
  `slug` varchar(191) NOT NULL,
  `content` longtext DEFAULT NULL,
  `template` varchar(60) DEFAULT 'default',
  `page_type` varchar(191) DEFAULT NULL,
  `header_style` varchar(50) NOT NULL DEFAULT 'header-style-1',
  `description` text DEFAULT NULL,
  `status` enum('draft','published','pending') NOT NULL DEFAULT 'draft',
  `pending_approval` tinyint(1) NOT NULL DEFAULT 0,
  `notify_subscribers` tinyint(1) NOT NULL DEFAULT 0,
  `views_count` bigint(20) unsigned NOT NULL DEFAULT 0,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `seo_title` varchar(191) DEFAULT NULL,
  `seo_description` text DEFAULT NULL,
  `seo_keywords` text DEFAULT NULL,
  `seo_image_url` varchar(500) DEFAULT NULL,
  `featured_image_url` varchar(191) DEFAULT NULL,
  `seo_noindex` tinyint(1) NOT NULL DEFAULT 0,
  `published_at` timestamp NULL DEFAULT NULL,
  `publish_at` timestamp NULL DEFAULT NULL,
  `unpublish_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pages_slug_unique` (`slug`),
  KEY `pages_status_published_at_index` (`status`,`published_at`),
  KEY `pages_slug_index` (`slug`),
  KEY `pages_user_id_index` (`user_id`),
  KEY `pages_created_at_index` (`created_at`),
  KEY `pages_status_publish_at_index` (`status`,`publish_at`),
  KEY `pages_status_unpublish_at_index` (`status`,`unpublish_at`),
  KEY `pages_parent_id_index` (`parent_id`),
  KEY `pages_views_count_index` (`views_count`),
  KEY `pages_status_pending_approval_index` (`status`,`pending_approval`),
  KEY `pages_template_index` (`template`),
  KEY `pages_updated_at_index` (`updated_at`),
  KEY `pages_deleted_at_index` (`deleted_at`),
  KEY `pages_published_at_index` (`published_at`),
  KEY `pages_page_type_index` (`page_type`),
  KEY `pages_pending_approval_index` (`pending_approval`),
  KEY `pages_seo_noindex_index` (`seo_noindex`),
  FULLTEXT KEY `fulltext_search` (`title`,`content`,`description`),
  CONSTRAINT `pages_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `password_histories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_histories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `password_hash` varchar(191) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `password_histories_user_id_created_at_index` (`user_id`,`created_at`),
  CONSTRAINT `password_histories_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(191) NOT NULL,
  `token` varchar(191) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `permissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `guard_name` varchar(191) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(191) NOT NULL,
  `tokenable_id` bigint(20) unsigned NOT NULL,
  `name` text NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
  KEY `personal_access_tokens_expires_at_index` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `products` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uid` uuid DEFAULT NULL,
  `sku` varchar(191) DEFAULT NULL,
  `title` varchar(191) DEFAULT NULL,
  `slug` varchar(191) DEFAULT NULL,
  `reference` varchar(191) DEFAULT NULL,
  `barcode` varchar(191) DEFAULT NULL,
  `stock` int(11) DEFAULT 0,
  `available` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `products_uid_unique` (`uid`),
  UNIQUE KEY `products_sku_unique` (`sku`),
  UNIQUE KEY `products_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `push_notification_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `push_notification_tokens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `token` varchar(191) NOT NULL,
  `device_type` varchar(191) DEFAULT NULL,
  `device_id` varchar(191) DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `last_used_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_10310` (`user_id`,`token`),
  CONSTRAINT `push_notification_tokens_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `push_subscriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `push_subscriptions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `endpoint` varchar(500) NOT NULL,
  `p256dh` varchar(191) DEFAULT NULL,
  `auth` varchar(191) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `push_subscriptions_endpoint_unique` (`endpoint`),
  KEY `push_subscriptions_user_id_foreign` (`user_id`),
  CONSTRAINT `push_subscriptions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `review_ai_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `review_ai_settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `provider` varchar(191) NOT NULL DEFAULT 'anthropic',
  `api_key` text NOT NULL,
  `model` varchar(191) NOT NULL DEFAULT 'claude-sonnet-4-6',
  `is_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `tone` varchar(191) NOT NULL DEFAULT 'professional',
  `language` varchar(191) NOT NULL DEFAULT 'es',
  `max_tokens` smallint(5) unsigned NOT NULL DEFAULT 500,
  `custom_instructions` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `review_auto_reply_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `review_auto_reply_rules` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `location_id` bigint(20) unsigned NOT NULL,
  `name` varchar(150) NOT NULL COMMENT 'Human-readable rule name',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Whether the rule is enabled',
  `conditions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT 'Matching conditions: min_rating, max_rating, has_comment' CHECK (json_valid(`conditions`)),
  `template_id` bigint(20) unsigned DEFAULT NULL,
  `custom_text` text DEFAULT NULL COMMENT 'Reply text when no template is set',
  `delay_minutes` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Minutes to wait before dispatching the reply',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `review_auto_reply_rules_template_id_foreign` (`template_id`),
  KEY `review_auto_reply_rules_location_id_index` (`location_id`),
  KEY `review_auto_reply_rules_is_active_index` (`is_active`),
  KEY `review_auto_reply_rules_location_id_is_active_index` (`location_id`,`is_active`),
  CONSTRAINT `review_auto_reply_rules_location_id_foreign` FOREIGN KEY (`location_id`) REFERENCES `review_google_locations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `review_auto_reply_rules_template_id_foreign` FOREIGN KEY (`template_id`) REFERENCES `review_reply_templates` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `review_auto_suggestions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `review_auto_suggestions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `trigger_keywords` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT 'Array of keywords to match in review comments' CHECK (json_valid(`trigger_keywords`)),
  `rating_range` varchar(10) NOT NULL COMMENT 'Rating range like "1-2", "3", "4-5"',
  `suggested_template_id` bigint(20) unsigned DEFAULT NULL,
  `priority` int(11) NOT NULL DEFAULT 0 COMMENT 'Higher priority suggestions shown first',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Enable/disable this suggestion rule',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `review_auto_suggestions_is_active_index` (`is_active`),
  KEY `review_auto_suggestions_priority_index` (`priority`),
  KEY `review_auto_suggestions_is_active_priority_index` (`is_active`,`priority`),
  KEY `review_auto_suggestions_suggested_template_id_index` (`suggested_template_id`),
  CONSTRAINT `review_auto_suggestions_suggested_template_id_foreign` FOREIGN KEY (`suggested_template_id`) REFERENCES `review_reply_templates` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `review_competitor_snapshots`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `review_competitor_snapshots` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `competitor_id` bigint(20) unsigned NOT NULL,
  `avg_rating` decimal(3,2) NOT NULL,
  `review_count` int(10) unsigned NOT NULL,
  `captured_at` timestamp NOT NULL,
  PRIMARY KEY (`id`),
  KEY `review_competitor_snapshots_competitor_id_captured_at_index` (`competitor_id`,`captured_at`),
  CONSTRAINT `review_competitor_snapshots_competitor_id_foreign` FOREIGN KEY (`competitor_id`) REFERENCES `review_competitors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `review_competitors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `review_competitors` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `location_id` bigint(20) unsigned NOT NULL,
  `name` varchar(150) NOT NULL,
  `google_maps_url` varchar(500) NOT NULL,
  `place_id` varchar(200) DEFAULT NULL,
  `last_checked_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `review_competitors_location_id_index` (`location_id`),
  CONSTRAINT `review_competitors_location_id_foreign` FOREIGN KEY (`location_id`) REFERENCES `review_google_locations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `review_export_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `review_export_history` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `filters` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`filters`)),
  `format` varchar(10) NOT NULL,
  `status` enum('pending','processing','completed','failed') NOT NULL DEFAULT 'pending',
  `file_path` varchar(191) DEFAULT NULL,
  `file_name` varchar(191) DEFAULT NULL,
  `row_count` int(10) unsigned DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `review_export_history_user_id_created_at_index` (`user_id`,`created_at`),
  KEY `review_export_history_status_index` (`status`),
  KEY `review_export_history_expires_at_index` (`expires_at`),
  CONSTRAINT `review_export_history_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `review_google_connections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `review_google_connections` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `platform` varchar(50) NOT NULL DEFAULT 'google',
  `name` varchar(100) NOT NULL COMMENT 'Friendly name for this connection',
  `google_account_id` varchar(100) DEFAULT NULL,
  `google_email` varchar(255) DEFAULT NULL,
  `access_token` text DEFAULT NULL,
  `refresh_token` text DEFAULT NULL,
  `token_expires_at` timestamp NULL DEFAULT NULL COMMENT 'When access token expires',
  `expires_at` timestamp NULL DEFAULT NULL COMMENT 'When this connection authorization expires',
  `scopes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'OAuth scopes granted' CHECK (json_valid(`scopes`)),
  `status` enum('pending','active','expired','revoked','error') NOT NULL DEFAULT 'pending' COMMENT 'Connection status',
  `last_error` text DEFAULT NULL COMMENT 'Last error message if any',
  `connected_at` timestamp NULL DEFAULT NULL COMMENT 'When successfully connected',
  `revoked_at` timestamp NULL DEFAULT NULL COMMENT 'When user revoked access',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `review_google_connections_google_account_id_unique` (`google_account_id`),
  KEY `review_google_connections_user_id_index` (`user_id`),
  KEY `review_google_connections_google_account_id_index` (`google_account_id`),
  KEY `review_google_connections_status_index` (`status`),
  KEY `review_google_connections_connected_at_index` (`connected_at`),
  KEY `review_google_connections_platform_index` (`platform`),
  CONSTRAINT `review_google_connections_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `review_google_locations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `review_google_locations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `connection_id` bigint(20) unsigned DEFAULT NULL,
  `google_location_id` varchar(255) NOT NULL COMMENT 'Google Business Profile location ID',
  `google_account_id` varchar(100) NOT NULL COMMENT 'Google account ID for this location',
  `place_id` varchar(191) DEFAULT NULL,
  `sync_strategy` enum('oauth','places_api','serpapi','manual') NOT NULL DEFAULT 'oauth',
  `available_tags` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`available_tags`)),
  `name` varchar(255) NOT NULL COMMENT 'Business name',
  `address` varchar(500) DEFAULT NULL COMMENT 'Full address',
  `phone` varchar(50) DEFAULT NULL COMMENT 'Contact phone',
  `website_url` varchar(500) DEFAULT NULL COMMENT 'Website URL',
  `average_rating` decimal(3,2) DEFAULT NULL COMMENT 'Average star rating (0.00 - 5.00)',
  `total_reviews` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Total review count',
  `is_verified` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Location is verified on Google',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Location is active for sync',
  `metadata_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Additional Google metadata' CHECK (json_valid(`metadata_json`)),
  `synced_at` timestamp NULL DEFAULT NULL COMMENT 'Last sync timestamp',
  `sla_hours` smallint(5) unsigned DEFAULT NULL COMMENT 'Hours allowed to reply before SLA breach (null = no SLA)',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `review_google_locations_google_location_id_unique` (`google_location_id`),
  KEY `review_google_locations_connection_id_index` (`connection_id`),
  KEY `review_google_locations_google_location_id_index` (`google_location_id`),
  KEY `review_google_locations_google_account_id_index` (`google_account_id`),
  KEY `review_google_locations_is_active_index` (`is_active`),
  KEY `review_google_locations_synced_at_index` (`synced_at`),
  KEY `review_google_locations_connection_id_is_active_index` (`connection_id`,`is_active`),
  KEY `review_google_locations_place_id_index` (`place_id`),
  KEY `review_google_locations_sync_strategy_index` (`sync_strategy`),
  CONSTRAINT `review_google_locations_connection_id_foreign` FOREIGN KEY (`connection_id`) REFERENCES `review_google_connections` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `review_moderations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `review_moderations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `review_id` bigint(20) unsigned NOT NULL,
  `is_visible` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Show on website/widgets',
  `is_featured` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Highlight as featured review',
  `tags` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Custom tags for filtering' CHECK (json_valid(`tags`)),
  `internal_notes` text DEFAULT NULL COMMENT 'Private internal notes',
  `moderated_by` bigint(20) unsigned DEFAULT NULL,
  `moderated_at` timestamp NULL DEFAULT NULL COMMENT 'When moderation was applied',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `review_moderations_review_id_unique` (`review_id`),
  KEY `review_moderations_review_id_index` (`review_id`),
  KEY `review_moderations_is_visible_index` (`is_visible`),
  KEY `review_moderations_is_featured_index` (`is_featured`),
  KEY `review_moderations_moderated_by_index` (`moderated_by`),
  KEY `review_moderations_is_visible_is_featured_index` (`is_visible`,`is_featured`),
  KEY `review_moderations_moderated_at_index` (`moderated_at`),
  KEY `review_moderations_review_id_is_visible_index` (`review_id`,`is_visible`),
  KEY `review_moderations_review_id_is_featured_index` (`review_id`,`is_featured`),
  KEY `review_moderations_created_at_index` (`created_at`),
  CONSTRAINT `review_moderations_moderated_by_foreign` FOREIGN KEY (`moderated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `review_moderations_review_id_foreign` FOREIGN KEY (`review_id`) REFERENCES `reviews` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `review_replies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `review_replies` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `review_id` bigint(20) unsigned NOT NULL,
  `reply_text` text NOT NULL COMMENT 'Reply message text',
  `status` enum('draft','pending_approval','approved','scheduled','published','failed') NOT NULL DEFAULT 'draft' COMMENT 'Reply workflow status',
  `error_message` text DEFAULT NULL COMMENT 'Error message if publish failed',
  `error_count` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Number of failed publish attempts',
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL COMMENT 'When reply was approved',
  `approval_requested_at` timestamp NULL DEFAULT NULL COMMENT 'When approval was requested',
  `approval_requested_by` bigint(20) unsigned DEFAULT NULL,
  `approval_note` text DEFAULT NULL COMMENT 'Reason or notes for the approval request',
  `rejection_reason` text DEFAULT NULL COMMENT 'Reason given by supervisor for rejecting the reply',
  `published_at` timestamp NULL DEFAULT NULL COMMENT 'When reply was published to Google',
  `scheduled_at` timestamp NULL DEFAULT NULL COMMENT 'When reply is scheduled to be published',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `review_replies_review_id_index` (`review_id`),
  KEY `review_replies_status_index` (`status`),
  KEY `review_replies_created_by_index` (`created_by`),
  KEY `review_replies_approved_by_index` (`approved_by`),
  KEY `review_replies_review_id_status_index` (`review_id`,`status`),
  KEY `review_replies_published_at_index` (`published_at`),
  KEY `review_replies_status_review_id_index` (`status`,`review_id`),
  KEY `review_replies_scheduled_at_index` (`scheduled_at`),
  KEY `review_replies_approval_requested_at_index` (`approval_requested_at`),
  KEY `review_replies_approval_requested_by_index` (`approval_requested_by`),
  CONSTRAINT `review_replies_approval_requested_by_foreign` FOREIGN KEY (`approval_requested_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `review_replies_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `review_replies_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `review_replies_review_id_foreign` FOREIGN KEY (`review_id`) REFERENCES `reviews` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `review_reply_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `review_reply_templates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL COMMENT 'Template name/title',
  `body` text NOT NULL COMMENT 'Template text with {variable} placeholders',
  `category` enum('positive','negative','neutral','general') DEFAULT NULL COMMENT 'Template category for quick filtering',
  `language` varchar(10) NOT NULL DEFAULT 'es' COMMENT 'ISO 639-1 language code for this template',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Template is available for use',
  `usage_count` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Times this template was used',
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `review_google_location_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `review_reply_templates_category_index` (`category`),
  KEY `review_reply_templates_is_active_index` (`is_active`),
  KEY `review_reply_templates_category_is_active_index` (`category`,`is_active`),
  KEY `review_reply_templates_usage_count_index` (`usage_count`),
  KEY `review_reply_templates_created_by_index` (`created_by`),
  KEY `review_reply_templates_language_index` (`language`),
  KEY `review_reply_templates_review_google_location_id_foreign` (`review_google_location_id`),
  CONSTRAINT `review_reply_templates_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `review_reply_templates_review_google_location_id_foreign` FOREIGN KEY (`review_google_location_id`) REFERENCES `review_google_locations` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `review_request_campaigns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `review_request_campaigns` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `location_id` bigint(20) unsigned NOT NULL,
  `name` varchar(150) NOT NULL,
  `subject` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `review_url` varchar(500) NOT NULL,
  `status` enum('draft','active','paused') NOT NULL DEFAULT 'draft',
  `scheduled_at` timestamp NULL DEFAULT NULL,
  `sent_count` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `review_request_campaigns_location_id_foreign` (`location_id`),
  CONSTRAINT `review_request_campaigns_location_id_foreign` FOREIGN KEY (`location_id`) REFERENCES `review_google_locations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `review_request_sends`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `review_request_sends` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `campaign_id` bigint(20) unsigned NOT NULL,
  `customer_name` varchar(150) NOT NULL,
  `customer_email` varchar(200) NOT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `opened_at` timestamp NULL DEFAULT NULL,
  `status` enum('pending','sent','failed') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `review_request_sends_campaign_id_index` (`campaign_id`),
  KEY `review_request_sends_status_index` (`status`),
  KEY `review_request_sends_opened_at_index` (`opened_at`),
  KEY `review_request_sends_campaign_status_index` (`campaign_id`,`status`),
  CONSTRAINT `review_request_sends_campaign_id_foreign` FOREIGN KEY (`campaign_id`) REFERENCES `review_request_campaigns` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `review_saved_filters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `review_saved_filters` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `name` varchar(100) NOT NULL,
  `filters_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`filters_json`)),
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `is_shared` tinyint(1) NOT NULL DEFAULT 0,
  `shared_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `review_saved_filters_user_id_index` (`user_id`),
  KEY `review_saved_filters_user_id_is_default_index` (`user_id`,`is_default`),
  KEY `review_saved_filters_shared_by_foreign` (`shared_by`),
  CONSTRAINT `review_saved_filters_shared_by_foreign` FOREIGN KEY (`shared_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `review_saved_filters_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `review_template_translations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `review_template_translations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `template_id` bigint(20) unsigned NOT NULL,
  `language` varchar(10) NOT NULL COMMENT 'ISO 639-1 language code',
  `template_text` text NOT NULL COMMENT 'Translated version of the template body',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `review_template_translations_template_id_language_unique` (`template_id`,`language`),
  CONSTRAINT `review_template_translations_template_id_foreign` FOREIGN KEY (`template_id`) REFERENCES `review_reply_templates` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `review_template_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `review_template_versions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `review_reply_template_id` bigint(20) unsigned NOT NULL,
  `content` text NOT NULL,
  `language` varchar(10) NOT NULL DEFAULT 'es',
  `version_number` int(10) unsigned NOT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `review_template_versions_review_reply_template_id_foreign` (`review_reply_template_id`),
  KEY `review_template_versions_created_by_foreign` (`created_by`),
  CONSTRAINT `review_template_versions_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `review_template_versions_review_reply_template_id_foreign` FOREIGN KEY (`review_reply_template_id`) REFERENCES `review_reply_templates` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `review_translations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `review_translations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `review_id` bigint(20) unsigned NOT NULL,
  `locale_code` varchar(10) NOT NULL,
  `translated_text` text NOT NULL,
  `detected_language` varchar(10) DEFAULT NULL,
  `translated_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `review_translations_review_id_locale_code_unique` (`review_id`,`locale_code`),
  KEY `review_translations_locale_code_index` (`locale_code`),
  CONSTRAINT `review_translations_review_id_foreign` FOREIGN KEY (`review_id`) REFERENCES `reviews` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `review_webhook_subscriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `review_webhook_subscriptions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `url` varchar(500) NOT NULL,
  `events` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`events`)),
  `secret` varchar(64) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_triggered_at` timestamp NULL DEFAULT NULL,
  `failure_count` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `review_webhook_subscriptions_user_id_foreign` (`user_id`),
  CONSTRAINT `review_webhook_subscriptions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `review_widget_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `review_widget_tokens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `location_id` bigint(20) unsigned NOT NULL,
  `token` varchar(64) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `allowed_origins` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`allowed_origins`)),
  `max_reviews` tinyint(3) unsigned NOT NULL DEFAULT 5,
  `min_rating` tinyint(3) unsigned NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `review_widget_tokens_token_unique` (`token`),
  KEY `review_widget_tokens_location_id_foreign` (`location_id`),
  CONSTRAINT `review_widget_tokens_location_id_foreign` FOREIGN KEY (`location_id`) REFERENCES `review_google_locations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `reviews` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `location_id` bigint(20) unsigned NOT NULL,
  `google_review_id` varchar(255) NOT NULL COMMENT 'Unique Google review identifier',
  `reviewer_name` varchar(255) NOT NULL COMMENT 'Reviewer display name',
  `reviewer_photo_url` varchar(1000) DEFAULT NULL COMMENT 'Reviewer profile photo URL',
  `star_rating` enum('ONE','TWO','THREE','FOUR','FIVE') NOT NULL COMMENT 'Star rating from Google',
  `comment` text DEFAULT NULL COMMENT 'Review text comment',
  `translated_comment` text DEFAULT NULL,
  `detected_language` varchar(10) DEFAULT NULL,
  `translation_cached_at` timestamp NULL DEFAULT NULL,
  `review_time` timestamp NOT NULL COMMENT 'When review was posted on Google',
  `update_time` timestamp NULL DEFAULT NULL COMMENT 'When review was last updated on Google',
  `google_reply_text` text DEFAULT NULL COMMENT 'Business reply from Google',
  `google_reply_time` timestamp NULL DEFAULT NULL COMMENT 'When reply was posted on Google',
  `raw_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Full raw Google API response' CHECK (json_valid(`raw_json`)),
  `synced_at` timestamp NULL DEFAULT NULL COMMENT 'Last sync timestamp',
  `source` enum('business_profile','places_api','serpapi','csv','manual') DEFAULT 'business_profile',
  `sla_alerted_at` timestamp NULL DEFAULT NULL COMMENT 'When the SLA breach alert was last sent for this review',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `reviews_google_review_id_unique` (`google_review_id`),
  KEY `reviews_location_id_index` (`location_id`),
  KEY `reviews_google_review_id_index` (`google_review_id`),
  KEY `reviews_star_rating_index` (`star_rating`),
  KEY `reviews_review_time_index` (`review_time`),
  KEY `reviews_location_id_star_rating_index` (`location_id`,`star_rating`),
  KEY `reviews_location_id_review_time_index` (`location_id`,`review_time`),
  KEY `reviews_synced_at_index` (`synced_at`),
  KEY `reviews_created_at_location_id_index` (`created_at`,`location_id`),
  KEY `reviews_sla_alerted_at_index` (`sla_alerted_at`),
  KEY `reviews_source_index` (`source`),
  CONSTRAINT `reviews_location_id_foreign` FOREIGN KEY (`location_id`) REFERENCES `review_google_locations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `role_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `role_has_permissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `permission_id` bigint(20) unsigned NOT NULL,
  `role_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_permission_role` (`permission_id`,`role_id`),
  KEY `role_has_permissions_permission_id_index` (`permission_id`),
  KEY `role_has_permissions_role_id_index` (`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `guard_name` varchar(191) NOT NULL DEFAULT 'web',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `description` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `seo_404_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `seo_404_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `path` varchar(500) NOT NULL,
  `referer` varchar(1000) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `hit_count` int(10) unsigned NOT NULL DEFAULT 1,
  `first_seen_at` timestamp NOT NULL,
  `last_seen_at` timestamp NOT NULL,
  `has_redirect` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `seo_404_logs_path_unique` (`path`),
  KEY `seo_404_logs_hit_count_index` (`hit_count`),
  KEY `seo_404_logs_last_seen_at_index` (`last_seen_at`),
  KEY `seo_404_logs_has_redirect_index` (`has_redirect`),
  KEY `seo_404_logs_has_redirect_last_seen_index` (`has_redirect`,`last_seen_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `seo_alerts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `seo_alerts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(50) NOT NULL,
  `severity` enum('info','warning','critical') NOT NULL DEFAULT 'warning',
  `title` varchar(200) NOT NULL,
  `message` text DEFAULT NULL,
  `context` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`context`)),
  `url` varchar(500) DEFAULT NULL,
  `acknowledged_at` timestamp NULL DEFAULT NULL,
  `acknowledged_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `seo_alerts_severity_acknowledged_at_index` (`severity`,`acknowledged_at`),
  KEY `seo_alerts_type_created_at_index` (`type`,`created_at`),
  KEY `seo_alerts_type_index` (`type`),
  KEY `seo_alerts_acknowledged_at_index` (`acknowledged_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `seo_audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `seo_audit_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `seo_meta_id` bigint(20) unsigned DEFAULT NULL,
  `url` varchar(191) DEFAULT NULL COMMENT 'Audited URL if this was a URL audit',
  `score` tinyint(3) unsigned NOT NULL,
  `grade` char(1) NOT NULL,
  `issues_count` smallint(5) unsigned NOT NULL DEFAULT 0,
  `issues` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Array of audit issues' CHECK (json_valid(`issues`)),
  `passed_count` smallint(5) unsigned NOT NULL DEFAULT 0,
  `audited_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `seo_audit_logs_seo_meta_id_index` (`seo_meta_id`),
  KEY `seo_audit_logs_audited_at_index` (`audited_at`),
  CONSTRAINT `seo_audit_logs_seo_meta_id_foreign` FOREIGN KEY (`seo_meta_id`) REFERENCES `seo_metas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `seo_metas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `seo_metas` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `seoable_type` varchar(191) NOT NULL,
  `seoable_id` bigint(20) unsigned NOT NULL,
  `title` varchar(255) DEFAULT NULL COMMENT 'Page title for SEO',
  `title_b` varchar(191) DEFAULT NULL,
  `description` text DEFAULT NULL COMMENT 'Meta description for search results',
  `description_b` text DEFAULT NULL,
  `ab_winner` enum('a','b') DEFAULT NULL,
  `ab_impressions_a` int(10) unsigned NOT NULL DEFAULT 0,
  `ab_impressions_b` int(10) unsigned NOT NULL DEFAULT 0,
  `keywords` text DEFAULT NULL COMMENT 'Meta keywords (comma separated)',
  `target_keyword` varchar(100) DEFAULT NULL,
  `og_title` varchar(255) DEFAULT NULL COMMENT 'Open Graph title (Facebook, LinkedIn)',
  `og_description` text DEFAULT NULL COMMENT 'Open Graph description',
  `og_image` varchar(500) DEFAULT NULL COMMENT 'Open Graph image URL',
  `locale` varchar(10) DEFAULT NULL,
  `og_type` varchar(50) NOT NULL DEFAULT 'website' COMMENT 'Open Graph type (website, article, etc.)',
  `twitter_card` varchar(50) NOT NULL DEFAULT 'summary' COMMENT 'Twitter card type (summary, summary_large_image)',
  `twitter_title` varchar(255) DEFAULT NULL COMMENT 'Twitter card title',
  `twitter_description` text DEFAULT NULL COMMENT 'Twitter card description',
  `twitter_image` varchar(500) DEFAULT NULL COMMENT 'Twitter card image URL',
  `canonical_url` varchar(500) DEFAULT NULL COMMENT 'Canonical URL for duplicate content',
  `robots` varchar(100) NOT NULL DEFAULT 'index,follow' COMMENT 'Robots meta tag directive',
  `schema_custom` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`schema_custom`)),
  `schema_type` varchar(50) DEFAULT NULL,
  `seo_score` tinyint(3) unsigned DEFAULT NULL,
  `seo_grade` char(1) DEFAULT NULL,
  `seo_audited_at` timestamp NULL DEFAULT NULL,
  `gsc_clicks` int(10) unsigned DEFAULT NULL,
  `gsc_impressions` int(10) unsigned DEFAULT NULL,
  `gsc_position` decimal(5,1) DEFAULT NULL,
  `gsc_updated_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `seo_metas_seoable_locale_unique` (`seoable_id`,`seoable_type`,`locale`),
  KEY `seo_metas_seoable_type_seoable_id_index` (`seoable_type`,`seoable_id`),
  KEY `seo_metas_robots_index` (`robots`),
  KEY `seo_metas_created_at_index` (`created_at`),
  KEY `seo_metas_seo_score_index` (`seo_score`),
  KEY `seo_metas_seo_grade_index` (`seo_grade`),
  KEY `seo_metas_seo_audited_at_index` (`seo_audited_at`),
  KEY `seo_metas_target_keyword_index` (`target_keyword`),
  KEY `seo_metas_locale_index` (`locale`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `seo_pagespeed_snapshots`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `seo_pagespeed_snapshots` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `url` varchar(500) NOT NULL,
  `url_path` varchar(500) NOT NULL,
  `strategy` enum('mobile','desktop') NOT NULL DEFAULT 'mobile',
  `performance` tinyint(4) DEFAULT NULL,
  `accessibility` tinyint(4) DEFAULT NULL,
  `best_practices` tinyint(4) DEFAULT NULL,
  `seo` tinyint(4) DEFAULT NULL,
  `lcp_ms` decimal(10,2) DEFAULT NULL,
  `inp_ms` decimal(10,2) DEFAULT NULL,
  `cls` decimal(6,3) DEFAULT NULL,
  `fcp_ms` decimal(10,2) DEFAULT NULL,
  `ttfb_ms` decimal(10,2) DEFAULT NULL,
  `captured_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `seo_pagespeed_snapshots_url_path_strategy_captured_at_index` (`url_path`,`strategy`,`captured_at`),
  KEY `seo_pagespeed_snapshots_url_path_index` (`url_path`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `seo_redirect_hits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `seo_redirect_hits` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `seo_redirect_id` bigint(20) unsigned NOT NULL,
  `hit_date` date NOT NULL,
  `hit_count` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `seo_redirect_hits_seo_redirect_id_hit_date_unique` (`seo_redirect_id`,`hit_date`),
  KEY `seo_redirect_hits_hit_date_index` (`hit_date`),
  CONSTRAINT `seo_redirect_hits_seo_redirect_id_foreign` FOREIGN KEY (`seo_redirect_id`) REFERENCES `seo_redirects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `seo_redirects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `seo_redirects` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `source_path` varchar(191) NOT NULL,
  `target_path` varchar(191) NOT NULL,
  `status_code` int(11) NOT NULL DEFAULT 301,
  `hits_count` bigint(20) unsigned NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_regex` tinyint(1) NOT NULL DEFAULT 0,
  `is_wildcard` tinyint(1) NOT NULL DEFAULT 0,
  `active_from` timestamp NULL DEFAULT NULL,
  `active_until` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `seo_redirects_source_path_index` (`source_path`),
  KEY `seo_redirects_is_active_index` (`is_active`),
  KEY `seo_redirects_source_path_is_active_index` (`source_path`,`is_active`),
  KEY `seo_redirects_is_wildcard_index` (`is_wildcard`),
  KEY `seo_redirects_is_regex_index` (`is_regex`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `seo_static_urls`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `seo_static_urls` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `url` varchar(191) NOT NULL,
  `priority` decimal(2,1) NOT NULL DEFAULT 0.5,
  `changefreq` enum('always','hourly','daily','weekly','monthly','yearly','never') NOT NULL DEFAULT 'weekly',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `notes` varchar(191) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `seo_static_urls_url_unique` (`url`),
  KEY `seo_static_urls_is_active_index` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `seo_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `seo_templates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `model_type` varchar(191) DEFAULT NULL,
  `title_pattern` varchar(200) DEFAULT NULL,
  `description_pattern` varchar(500) DEFAULT NULL,
  `og_type` varchar(50) NOT NULL DEFAULT 'website',
  `twitter_card` varchar(50) NOT NULL DEFAULT 'summary_large_image',
  `robots` varchar(100) NOT NULL DEFAULT 'index,follow',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `priority` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `seo_templates_model_type_is_active_index` (`model_type`,`is_active`),
  KEY `seo_templates_priority_index` (`priority`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `seo_web_vitals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `seo_web_vitals` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `url` varchar(500) NOT NULL,
  `url_path` varchar(500) DEFAULT NULL,
  `metric` varchar(16) NOT NULL COMMENT 'LCP, INP, CLS, FCP, TTFB',
  `value` decimal(12,4) NOT NULL COMMENT 'ms (LCP/INP/FCP/TTFB) o score (CLS)',
  `rating` varchar(32) DEFAULT NULL COMMENT 'good | needs-improvement | poor',
  `device` varchar(16) DEFAULT NULL,
  `connection` varchar(16) DEFAULT NULL,
  `navigation_type` varchar(32) DEFAULT NULL,
  `captured_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_wv_path_metric_captured` (`url_path`,`metric`,`captured_at`),
  KEY `idx_wv_metric_captured` (`metric`,`captured_at`),
  KEY `seo_web_vitals_url_path_index` (`url_path`),
  KEY `seo_web_vitals_captured_at_index` (`captured_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(191) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(191) NOT NULL,
  `value` longtext DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `settings_key_unique` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `shortcode_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `shortcode_categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `slug` varchar(50) NOT NULL,
  `label` varchar(100) NOT NULL,
  `sort_order` smallint(5) unsigned NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `shortcode_categories_slug_unique` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `shortcodes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `shortcodes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(191) NOT NULL,
  `name` varchar(191) NOT NULL,
  `description` varchar(191) DEFAULT NULL,
  `icon` varchar(191) NOT NULL DEFAULT 'fas fa-puzzle-piece',
  `category` varchar(50) DEFAULT 'otros',
  `config_fields` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`config_fields`)),
  `shortcode_template` varchar(500) DEFAULT NULL,
  `render_template` mediumtext DEFAULT '',
  `css_code` text DEFAULT NULL,
  `js_code` text DEFAULT NULL,
  `sort_order` int(10) unsigned NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `shortcodes_key_unique` (`key`),
  KEY `shortcodes_is_active_sort_order_index` (`is_active`,`sort_order`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sitemap_generations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sitemap_generations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `status` varchar(20) NOT NULL DEFAULT 'success',
  `url_count` int(10) unsigned NOT NULL DEFAULT 0,
  `duration_ms` int(10) unsigned NOT NULL DEFAULT 0,
  `error_message` text DEFAULT NULL,
  `source` varchar(20) NOT NULL DEFAULT 'command',
  `generated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sitemap_generations_status_index` (`status`),
  KEY `sitemap_generations_created_at_index` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `supervisor_backups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `supervisor_backups` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `description` text DEFAULT NULL,
  `environment` varchar(191) NOT NULL DEFAULT 'dev',
  `config_files` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`config_files`)),
  `supervisor_status` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`supervisor_status`)),
  `backup_size` bigint(20) unsigned NOT NULL DEFAULT 0,
  `is_auto` tinyint(1) NOT NULL DEFAULT 0,
  `backed_up_at` timestamp NULL DEFAULT NULL,
  `restored_at` timestamp NULL DEFAULT NULL,
  `restored_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `supervisor_backups_environment_backed_up_at_index` (`environment`,`backed_up_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `telescope_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `telescope_entries` (
  `sequence` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` uuid NOT NULL,
  `batch_id` uuid NOT NULL,
  `family_hash` varchar(191) DEFAULT NULL,
  `should_display_on_index` tinyint(1) NOT NULL DEFAULT 1,
  `type` varchar(20) NOT NULL,
  `content` longtext NOT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`sequence`),
  UNIQUE KEY `telescope_entries_uuid_unique` (`uuid`),
  KEY `telescope_entries_batch_id_index` (`batch_id`),
  KEY `telescope_entries_family_hash_index` (`family_hash`),
  KEY `telescope_entries_created_at_index` (`created_at`),
  KEY `telescope_entries_type_should_display_on_index_index` (`type`,`should_display_on_index`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `telescope_entries_tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `telescope_entries_tags` (
  `entry_uuid` uuid NOT NULL,
  `tag` varchar(191) NOT NULL,
  PRIMARY KEY (`entry_uuid`,`tag`),
  KEY `telescope_entries_tags_tag_index` (`tag`),
  CONSTRAINT `telescope_entries_tags_entry_uuid_foreign` FOREIGN KEY (`entry_uuid`) REFERENCES `telescope_entries` (`uuid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `telescope_monitoring`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `telescope_monitoring` (
  `tag` varchar(191) NOT NULL,
  PRIMARY KEY (`tag`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `template_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `template_versions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `template_id` bigint(20) unsigned NOT NULL,
  `version` int(10) unsigned NOT NULL,
  `content` longtext DEFAULT NULL,
  `description` text DEFAULT NULL,
  `changed_fields` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`changed_fields`)),
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(191) DEFAULT NULL,
  `slug` varchar(191) DEFAULT NULL,
  `author` varchar(191) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `template_versions_template_id_version_unique` (`template_id`,`version`),
  KEY `template_versions_created_by_foreign` (`created_by`),
  KEY `template_versions_template_id_version_index` (`template_id`,`version`),
  KEY `template_versions_template_id_index` (`template_id`),
  KEY `template_versions_created_at_index` (`created_at`),
  CONSTRAINT `template_versions_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `template_versions_template_id_foreign` FOREIGN KEY (`template_id`) REFERENCES `templates` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `templates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `slug` varchar(191) NOT NULL,
  `description` text DEFAULT NULL,
  `content` longtext DEFAULT NULL,
  `template_path` varchar(191) DEFAULT NULL COMMENT 'Ruta en filesystem: public/templates/{slug}',
  `inherit` varchar(191) DEFAULT NULL COMMENT 'Slug del template padre (herencia)',
  `status` enum('active','inactive') NOT NULL DEFAULT 'inactive',
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `author` varchar(191) DEFAULT NULL,
  `version` varchar(191) NOT NULL DEFAULT '1.0.0',
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `templates_slug_unique` (`slug`),
  KEY `templates_status_created_at_index` (`status`,`created_at`),
  KEY `templates_slug_index` (`slug`),
  KEY `templates_user_id_index` (`user_id`),
  KEY `templates_inherit_index` (`inherit`),
  KEY `templates_created_at_index` (`created_at`),
  CONSTRAINT `templates_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `trusted_devices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `trusted_devices` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `fingerprint` varchar(64) NOT NULL,
  `label` varchar(191) DEFAULT NULL,
  `platform` varchar(50) DEFAULT NULL,
  `browser` varchar(50) DEFAULT NULL,
  `device_type` varchar(30) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `trusted` tinyint(1) NOT NULL DEFAULT 0,
  `first_seen_at` timestamp NULL DEFAULT NULL,
  `last_seen_at` timestamp NULL DEFAULT NULL,
  `trusted_until` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `trusted_devices_user_id_fingerprint_unique` (`user_id`,`fingerprint`),
  KEY `trusted_devices_last_seen_at_index` (`last_seen_at`),
  CONSTRAINT `trusted_devices_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_dashboards`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_dashboards` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `name` varchar(191) NOT NULL DEFAULT 'My Dashboard',
  `widgets` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`widgets`)),
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_dashboards_user_id_is_default_index` (`user_id`,`is_default`),
  CONSTRAINT `user_dashboards_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_notification_preferences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_notification_preferences` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `notification_type` varchar(191) NOT NULL,
  `channels` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`channels`)),
  `is_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `filters` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`filters`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_notification_preferences_user_id_notification_type_unique` (`user_id`,`notification_type`),
  KEY `user_notification_preferences_is_enabled_index` (`is_enabled`),
  CONSTRAINT `user_notification_preferences_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uid` varchar(191) DEFAULT NULL,
  `firstname` varchar(191) DEFAULT NULL,
  `lastname` varchar(191) DEFAULT NULL,
  `identification` varchar(191) DEFAULT NULL,
  `cellphone` varchar(191) DEFAULT NULL,
  `email` varchar(191) NOT NULL,
  `mail_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(191) NOT NULL,
  `password_changed_at` timestamp NULL DEFAULT NULL,
  `must_change_password` tinyint(1) NOT NULL DEFAULT 0,
  `two_factor_secret` text DEFAULT NULL,
  `two_factor_recovery_codes` text DEFAULT NULL,
  `two_factor_confirmed_at` timestamp NULL DEFAULT NULL,
  `address` varchar(191) DEFAULT NULL,
  `available` tinyint(1) NOT NULL DEFAULT 1,
  `verified` tinyint(1) NOT NULL DEFAULT 0,
  `terms` tinyint(1) NOT NULL DEFAULT 0,
  `validation` varchar(191) DEFAULT NULL,
  `page` varchar(191) DEFAULT NULL,
  `setting` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`setting`)),
  `role` varchar(191) DEFAULT NULL,
  `company` varchar(191) DEFAULT NULL,
  `detail` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`detail`)),
  `user_img` varchar(191) DEFAULT NULL,
  `citie_id` bigint(20) unsigned DEFAULT NULL,
  `enterprise_id` bigint(20) unsigned DEFAULT NULL,
  `timezone` varchar(191) DEFAULT NULL,
  `locale` varchar(10) DEFAULT NULL,
  `voilated` tinyint(1) NOT NULL DEFAULT 0,
  `last_login_at` timestamp NULL DEFAULT NULL,
  `last_login_ip` varchar(191) DEFAULT NULL,
  `failed_login_count` smallint(5) unsigned NOT NULL DEFAULT 0,
  `locked_until` timestamp NULL DEFAULT NULL,
  `last_logins_at` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `helpdesk_status` varchar(16) NOT NULL DEFAULT 'offline',
  `helpdesk_status_updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  UNIQUE KEY `users_uid_unique` (`uid`),
  KEY `users_email_index` (`email`),
  KEY `users_available_index` (`available`),
  KEY `users_uid_index` (`uid`),
  KEY `users_available_verified_index` (`available`,`verified`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ve_user_preferences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ve_user_preferences` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `key` varchar(80) NOT NULL,
  `value` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`value`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ve_user_preferences_user_id_key_unique` (`user_id`,`key`),
  CONSTRAINT `ve_user_preferences_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*M!100616 SET NOTE_VERBOSITY=@OLD_NOTE_VERBOSITY */;

/*M!999999\- enable the sandbox mode */ 

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*M!100616 SET @OLD_NOTE_VERBOSITY=@@NOTE_VERBOSITY, NOTE_VERBOSITY=0 */;

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT IGNORE INTO `migrations` VALUES
(1,'2024_01_01_000000_create_password_reset_tokens_table',1),
(2,'2024_01_01_000001_create_ecommerce_core_tables',1),
(3,'2024_01_01_000001_create_menus_table',1),
(4,'2024_01_01_000002_create_ecommerce_order_shipment_tables',1),
(5,'2024_01_01_000002_create_menu_items_table',1),
(6,'2024_01_01_000003_create_ecommerce_discount_return_tables',1),
(7,'2024_01_01_000004_create_ecommerce_option_spec_tables',1),
(8,'2024_01_01_000005_create_ecommerce_misc_tables',1),
(9,'2025_11_28_230312_create_backup_schedules_table',1),
(10,'2025_12_29_000001_create_products_table',1),
(11,'2025_12_29_000003_create_media_table',1),
(12,'2025_12_29_000100_create_activity_log_table',1),
(13,'2025_12_29_010725_create_sessions_table',1),
(14,'2025_12_29_014762_create_categories_table',1),
(15,'2025_12_29_014764_create_users_table',1),
(16,'2025_12_29_014765_create_core_langs_table',1),
(17,'2025_12_29_014765_create_langs_table',1),
(18,'2025_12_29_014766_create_settings_table',1),
(19,'2025_12_29_014768_create_notifications_table',1),
(20,'2025_12_29_014770_create_application_logs_table',1),
(21,'2025_12_29_014771_create_media_folders_table',1),
(22,'2025_12_29_014772_create_media_files_table',1),
(23,'2025_12_29_020405_create_notification_tables',1),
(24,'2025_12_29_020501_create_mailer_layouts_table',1),
(25,'2025_12_29_020502_create_mailer_templates_table',1),
(26,'2025_12_29_020503_create_mailer_variables_table',1),
(27,'2025_12_29_020504_create_mailer_endpoints_table',1),
(28,'2025_12_29_020505_create_mailer_template_langs_table',1),
(29,'2025_12_29_020506_create_mailer_layout_langs_table',1),
(30,'2025_12_29_020507_create_mailer_variable_langs_table',1),
(31,'2025_12_29_020508_create_mailer_endpoint_logs_table',1),
(32,'2025_12_29_020509_create_faq_tables',1),
(33,'2025_12_29_020626_create_ip_locations_table',1),
(34,'2025_12_29_020627_create_countries_table',1),
(35,'2025_12_29_020901_create_helpdesk_customers_table',1),
(36,'2025_12_29_020902_create_helpdesk_ticket_statuses_table',1),
(37,'2025_12_29_020903_create_helpdesk_ticket_categories_table',1),
(38,'2025_12_29_020904_create_helpdesk_groups_table',1),
(39,'2025_12_29_020905_create_helpdesk_ticket_sla_policies_table',1),
(40,'2025_12_29_020906_create_helpdesk_tickets_table',1),
(41,'2025_12_29_020907_create_helpdesk_ticket_items_table',1),
(42,'2025_12_29_020908_create_helpdesk_ticket_comments_table',1),
(43,'2025_12_29_020909_create_helpdesk_ticket_notes_table',1),
(44,'2025_12_29_020910_create_helpdesk_ticket_mails_table',1),
(45,'2025_12_29_020911_create_helpdesk_ticket_histories_table',1),
(46,'2025_12_29_020912_create_helpdesk_ticket_watchers_table',1),
(47,'2025_12_29_020913_create_helpdesk_ticket_reads_table',1),
(48,'2025_12_29_020914_create_helpdesk_conversation_statuses_table',1),
(49,'2025_12_29_020915_create_helpdesk_conversations_table',1),
(50,'2025_12_29_020916_create_helpdesk_conversation_items_table',1),
(51,'2025_12_29_020917_create_helpdesk_conversation_reads_table',1),
(52,'2025_12_29_020918_create_helpdesk_conversation_tags_table',1),
(53,'2025_12_29_020919_create_helpdesk_conversation_tag_pivot_table',1),
(54,'2025_12_29_020920_create_helpdesk_conversation_views_table',1),
(55,'2025_12_29_020921_create_helpdesk_canned_replies_table',1),
(56,'2025_12_29_020922_create_helpdesk_ticket_canned_replies_table',1),
(57,'2025_12_29_020923_create_helpdesk_attributes_table',1),
(58,'2025_12_29_020924_create_helpdesk_customer_sessions_table',1),
(59,'2025_12_29_020925_create_helpdesk_page_visits_table',1),
(60,'2025_12_29_020926_create_helpdesk_ticket_groups_table',1),
(61,'2025_12_29_020927_create_helpdesk_ticket_group_user_table',1),
(62,'2025_12_29_020928_create_helpdesk_ticket_sla_breaches_table',1),
(63,'2025_12_29_020929_create_helpdesk_ticket_views_table',1),
(64,'2025_12_29_020930_create_helpdesk_helpcenter_categories_table',1),
(65,'2025_12_29_020931_create_helpdesk_helpcenter_articles_table',1),
(66,'2025_12_29_020932_create_helpdesk_helpcenter_tags_table',1),
(67,'2025_12_29_020933_create_helpdesk_campaigns_table',1),
(68,'2025_12_29_020934_create_helpdesk_campaign_templates_table',1),
(69,'2025_12_29_020935_create_helpdesk_campaign_impressions_table',1),
(70,'2025_12_29_020936_create_helpdesk_agent_settings_table',1),
(71,'2025_12_29_020937_create_helpdesk_ai_agents_table',1),
(72,'2025_12_29_052122_create_health_tables',1),
(73,'2025_12_29_054242_create_notification_settings_table',1),
(74,'2025_12_29_054249_create_push_notification_tokens_table',1),
(75,'2025_12_29_054257_create_helpdesk_group_user_table',1),
(76,'2025_12_29_054258_create_roles_table',1),
(77,'2025_12_29_054303_create_model_has_roles_table',1),
(78,'2025_12_29_054303_create_role_has_permissions_table',1),
(79,'2025_12_29_100001_create_helpdesk_categories_table',1),
(80,'2025_12_29_100002_create_helpdesk_priorities_table',1),
(81,'2025_12_29_100003_create_helpdesk_statuses_table',1),
(82,'2025_12_29_100004_create_helpdesk_sla_policies_table',1),
(83,'2025_12_29_100006_create_helpdesk_ticket_messages_table',1),
(84,'2025_12_29_100007_create_helpdesk_ticket_attachments_table',1),
(85,'2025_12_29_100009_create_helpdesk_ticket_assignments_table',1),
(86,'2025_12_29_100010_create_helpdesk_ticket_history_table',1),
(87,'2026_01_01_203319_add_missing_permission_tables',1),
(88,'2026_01_02_054123_add_event_column_to_activity_log_table',1),
(89,'2026_02_08_000001_add_analytics_dashboard_widgets_setting',1),
(90,'2026_02_08_000001_create_attention_types_table',1),
(91,'2026_02_08_000001_create_pages_table',1),
(92,'2026_02_08_000001_create_seo_metas_table',1),
(93,'2026_02_08_000002_create_attention_categories_table',1),
(94,'2026_02_08_000002_create_page_versions_table',1),
(95,'2026_02_08_000002_create_seo_redirects_table',1),
(96,'2026_02_08_000003_add_fulltext_index_to_pages_table',1),
(97,'2026_02_08_000003_create_attention_departments_table',1),
(98,'2026_02_08_000003_create_page_preview_tokens_table',1),
(99,'2026_02_08_000004_create_attention_sedes_table',1),
(100,'2026_02_08_000005_create_attentions_table',1),
(101,'2026_02_08_000006_create_attention_notes_table',1),
(102,'2026_02_08_000007_create_attention_actions_table',1),
(103,'2026_02_08_000008_create_attention_mails_table',1),
(104,'2026_02_08_000009_create_attention_satisfaction_table',1),
(105,'2026_02_08_000010_add_fields_to_attention_departments_table',1),
(106,'2026_02_08_000011_add_soft_deletes_to_attentions_table',1),
(107,'2026_02_08_000012_add_archived_at_to_attentions_table',1),
(108,'2026_02_08_000013_create_attention_sla_policies_table',1),
(109,'2026_02_08_000014_add_sla_policy_id_to_attentions_table',1),
(110,'2026_02_08_000015_create_attention_sla_breaches_table',1),
(111,'2026_02_08_000016_update_attention_mails_table_for_mailer_integration',1),
(112,'2026_02_08_000017_create_colombian_holidays_table',1),
(113,'2026_02_08_000018_add_additional_indexes_to_attention_tables',1),
(114,'2026_02_08_230657_add_scheduled_publishing_to_pages_table',1),
(115,'2026_02_09_205643_create_sessions_table',1),
(116,'2026_02_17_000001_add_header_style_seo_noindex_to_pages',1),
(117,'2026_02_17_000001_create_templates_table',1),
(118,'2026_02_17_000002_add_seo_image_url_to_pages_table',1),
(119,'2026_02_17_000002_create_template_versions_table',1),
(120,'2026_02_17_000003_create_shortcodes_table',1),
(121,'2026_02_17_051921_create_permission_tables',1),
(122,'2026_02_17_143834_add_has_child_to_menu_items_table',1),
(123,'2026_02_17_162548_add_js_code_to_shortcodes_table',1),
(124,'2026_02_17_create_cookie_settings_table',1),
(125,'2026_02_20_000001_create_review_google_connections_table',1),
(126,'2026_02_20_000002_create_review_google_locations_table',1),
(127,'2026_02_20_000003_create_reviews_table',1),
(128,'2026_02_20_000004_create_review_moderations_table',1),
(129,'2026_02_20_000005_create_review_replies_table',1),
(130,'2026_02_20_000006_create_review_reply_templates_table',1),
(131,'2026_02_22_215422_create_review_saved_filters_table',1),
(132,'2026_02_22_221752_create_activity_log_table',1),
(133,'2026_02_22_221753_add_event_column_to_activity_log_table',1),
(134,'2026_02_22_221754_add_batch_uuid_column_to_activity_log_table',1),
(135,'2026_02_22_232500_create_review_auto_suggestions_table',1),
(136,'2026_02_23_003245_add_nullable_to_google_account_id_in_review_google_connections',1),
(137,'2026_02_23_003400_add_nullable_to_google_email_in_review_google_connections',1),
(138,'2026_02_23_003416_add_nullable_to_oauth_fields_in_review_google_connections',1),
(139,'2026_02_24_000001_create_page_cache_audits_table',1),
(140,'2026_02_24_000002_create_page_cache_configs_table',1),
(141,'2026_02_27_152044_add_missing_indexes_to_review_moderations_table',1),
(142,'2026_02_27_160156_create_user_notification_preferences_table',1),
(143,'2026_02_27_160235_create_generated_reports_table',1),
(144,'2026_02_28_170641_create_page_auto_saves_table',1),
(145,'2026_02_28_171340_create_page_locks_table',1),
(146,'2026_03_21_000001_add_review_id_is_featured_index_to_review_moderations_table',1),
(147,'2026_03_21_000001_create_page_translations_table',1),
(148,'2026_03_21_000001_create_settings_table',1),
(149,'2026_03_21_000001_drop_cookie_settings_table',1),
(150,'2026_03_21_000002_add_analytics_report_settings',1),
(151,'2026_03_21_000002_create_review_ai_settings_table',1),
(152,'2026_03_21_000002_create_seo_static_urls_table',1),
(153,'2026_03_23_000001_add_views_count_to_pages_table',1),
(154,'2026_03_23_000001_create_attention_routing_rules_table',1),
(155,'2026_03_23_000002_add_parent_id_to_pages_table',1),
(156,'2026_03_23_000003_create_analytics_report_schedules_table',1),
(157,'2026_03_23_100000_create_media_settings_table',1),
(158,'2026_03_24_000001_create_form_categories_table',1),
(159,'2026_03_24_000002_create_forms_table',1),
(160,'2026_03_24_000003_create_form_fields_table',1),
(161,'2026_03_24_000004_create_form_submissions_table',1),
(162,'2026_03_24_000005_create_form_submission_values_table',1),
(163,'2026_03_24_000006_create_form_submission_notes_table',1),
(164,'2026_03_24_000007_create_form_versions_table',1),
(165,'2026_03_24_000008_create_form_follow_ups_table',1),
(166,'2026_03_24_000009_create_form_conditional_emails_table',1),
(167,'2026_03_24_000010_create_form_access_tokens_table',1),
(168,'2026_03_24_000011_create_form_abandon_tracking_table',1),
(169,'2026_03_24_000012_fix_forms_table_column_names',1),
(170,'2026_03_24_000013_add_color_picker_to_form_fields_type_enum',1),
(171,'2026_03_24_000014_create_form_submission_emails_table',1),
(172,'2026_03_24_000015_create_form_submission_actions_table',1),
(173,'2026_03_24_000016_create_form_submission_files_table',1),
(174,'2026_03_25_000001_create_blog_categories_table',1),
(175,'2026_03_25_000002_create_blog_tags_table',1),
(176,'2026_03_25_000003_create_blog_posts_table',1),
(177,'2026_03_25_000004_create_blog_pivot_tables',1),
(178,'2026_03_25_000005_create_blog_translations_tables',1),
(179,'2026_03_26_000001_add_index_views_count_to_pages_table',1),
(180,'2026_03_26_000001_add_performance_indexes_to_form_submissions_table',1),
(181,'2026_03_26_000001_create_blog_post_comments_table',1),
(182,'2026_03_26_000002_add_composite_indexes_to_form_submissions_table',1),
(183,'2026_03_26_000002_add_indexes_to_attention_routing_rules_table',1),
(184,'2026_03_26_000002_add_publish_at_to_blog_posts_table',1),
(185,'2026_03_26_000002_create_page_performance_metrics_table',1),
(186,'2026_03_26_000003_add_performance_indexes_to_blog_posts_table',1),
(187,'2026_03_26_000003_create_page_views_table',1),
(188,'2026_03_26_000004_add_indexes_to_page_analytics_tables',1),
(189,'2026_03_26_000004_add_newsletter_fields_to_blog_posts_table',1),
(190,'2026_03_26_000010_create_page_categories_table',1),
(191,'2026_03_26_000011_create_page_tags_table',1),
(192,'2026_03_26_000012_create_page_category_page_table',1),
(193,'2026_03_26_000013_create_page_tag_page_table',1),
(194,'2026_03_26_000014_create_page_approvals_table',1),
(195,'2026_03_26_000015_add_pending_approval_to_pages_table',1),
(196,'2026_03_26_000016_add_time_on_page_to_page_views_table',1),
(197,'2026_03_26_000017_add_notify_subscribers_to_pages_table',1),
(198,'2026_03_26_000018_create_page_webhooks_table',1),
(199,'2026_03_26_000020_add_composite_index_to_pages_table',1),
(200,'2026_03_26_100000_add_two_factor_to_users_table',1),
(201,'2026_03_26_100001_create_cookie_consent_logs_table',1),
(202,'2026_03_26_200001_add_file_hash_to_media_files_table',1),
(203,'2026_03_26_200001_add_locale_to_users_table',1),
(204,'2026_03_26_200001_create_alert_thresholds_table',1),
(205,'2026_03_26_300001_add_softdeletes_to_template_versions_table',1),
(206,'2026_03_26_400001_create_blog_post_versions_table',1),
(207,'2026_03_26_400001_create_mailer_template_versions_table',1),
(208,'2026_03_27_000001_add_soft_deletes_to_attention_categories_table',1),
(209,'2026_03_27_000001_add_soft_deletes_to_backup_schedules_table',1),
(210,'2026_03_27_000001_add_soft_deletes_to_blog_categories_table',1),
(211,'2026_03_27_000002_add_soft_deletes_to_attention_types_table',1),
(212,'2026_03_27_000003_add_soft_deletes_to_attention_departments_table',1),
(213,'2026_03_27_000004_add_soft_deletes_to_attention_sedes_table',1),
(214,'2026_04_01_000001_add_missing_indexes_to_helpdesk_tables',1),
(215,'2026_04_01_000001_add_recipient_email_index_to_mailer_endpoint_logs_table',1),
(216,'2026_04_01_000002_add_social_channel_fields_to_helpdesk_tables',1),
(217,'2026_04_01_000003_add_performance_indexes_to_helpdesk_tables',1),
(218,'2026_04_01_000004_create_helpdesk_ticket_daily_reports_table',1),
(219,'2026_04_01_000005_create_helpdesk_ai_agent_flow_tables',1),
(220,'2026_04_01_000006_create_helpdesk_ticket_time_entries_table',1),
(221,'2026_04_01_000007_add_portal_fields_to_helpdesk_customers',1),
(222,'2026_04_01_000008_add_query_indexes_to_helpdesk_tables',1),
(223,'2026_04_01_000009_add_rating_to_helpdesk_tickets',1),
(224,'2026_04_01_000010_add_escalation_to_helpdesk_tickets',1),
(225,'2026_04_01_000011_create_helpdesk_ticket_templates_table',1),
(226,'2026_04_01_000013_create_helpdesk_recurring_tickets_table',1),
(227,'2026_04_01_000014_add_indexes_to_helpdesk_ticket_templates_table',1),
(228,'2026_04_01_000015_create_helpdesk_settings_table',1),
(229,'2026_04_01_100000_add_performance_indexes_to_mailer_tables',1),
(230,'2026_04_02_000001_add_attribute_changes_to_activity_log_table',1),
(231,'2026_04_02_000001_add_indexes_and_user_id_to_analytics_report_schedules',1),
(232,'2026_04_02_000001_add_missing_indexes_to_reviews_tables',1),
(233,'2026_04_02_000001_add_seo_score_to_seo_metas_table',1),
(234,'2026_04_02_000001_add_soft_deletes_to_blog_post_comments_table',1),
(235,'2026_04_02_000002_add_sync_status_to_review_google_connections',1),
(236,'2026_04_02_000002_add_unique_slug_locale_to_blog_translations_tables',1),
(237,'2026_04_02_000002_create_seo_audit_logs_table',1),
(238,'2026_04_02_000003_add_fulltext_indexes_to_blog_posts_table',1),
(239,'2026_04_02_000003_create_review_export_history_table',1),
(240,'2026_04_02_000003_create_seo_404_logs_table',1),
(241,'2026_04_02_000004_add_ip_expires_at_to_blog_post_comments_table',1),
(242,'2026_04_02_000004_add_scheduled_at_to_review_replies',1),
(243,'2026_04_02_000004_create_seo_templates_table',1),
(244,'2026_04_02_000005_add_advanced_fields_to_seo_redirects_table',1),
(245,'2026_04_02_000005_add_approval_fields_to_review_replies',1),
(246,'2026_04_02_000005_make_created_by_nullable_in_blog_post_versions_table',1),
(247,'2026_04_02_000006_add_locale_to_seo_metas_table',1),
(248,'2026_04_02_000006_add_platform_to_review_connections',1),
(249,'2026_04_02_000010_add_indexes_to_seo_tables',1),
(250,'2026_04_02_000011_add_target_keyword_to_seo_metas_table',1),
(251,'2026_04_02_000012_add_gsc_fields_to_seo_metas_table',1),
(252,'2026_04_02_000013_create_seo_redirect_hits_table',1),
(253,'2026_04_02_000014_add_is_wildcard_to_seo_redirects_table',1),
(254,'2026_04_02_000015_add_ab_fields_to_seo_metas_table',1),
(255,'2026_04_02_100001_create_review_auto_reply_rules_table',1),
(256,'2026_04_02_100002_add_sla_hours_to_review_google_locations',1),
(257,'2026_04_02_100003_add_sla_alerted_at_to_reviews',1),
(258,'2026_04_02_100004_create_review_request_campaigns_table',1),
(259,'2026_04_02_100005_create_review_widget_tokens_table',1),
(260,'2026_04_02_100006_create_review_webhook_subscriptions_table',1),
(261,'2026_04_02_100007_add_language_to_review_reply_templates',1),
(262,'2026_04_02_100008_create_review_template_translations_table',1),
(263,'2026_04_02_200001_add_is_shared_to_review_saved_filters_table',1),
(264,'2026_04_02_200001_add_translation_to_reviews',1),
(265,'2026_04_02_200002_add_location_id_to_review_reply_templates_table',1),
(266,'2026_04_02_200002_create_review_competitors_table',1),
(267,'2026_04_02_200010_add_global_performance_indexes',1),
(268,'2026_04_02_300001_add_indexes_to_form_submissions_and_values_tables',1),
(269,'2026_04_02_300001_add_indexes_to_pages_table',1),
(270,'2026_04_02_300001_add_indexes_to_review_request_sends_table',1),
(271,'2026_04_02_400001_add_status_scheduled_at_to_review_request_campaigns_table',1),
(272,'2026_04_02_400002_create_review_template_versions_table',1),
(273,'2026_04_03_000001_create_supervisor_backups_table',1),
(274,'2026_04_04_000001_add_status_fields_to_blog_post_translations_table',1),
(275,'2026_04_04_000002_add_reviewer_to_blog_post_translations_table',1),
(276,'2026_04_04_000002_add_unique_index_to_page_auto_saves_table',1),
(277,'2026_04_04_000003_create_blog_translation_logs_table',1),
(278,'2026_04_04_000004_create_blog_glossary_terms_table',1),
(279,'2026_04_04_000005_create_blog_translation_cache_table',1),
(280,'2026_04_04_182835_add_is_active_and_icon_to_page_categories_table',1),
(281,'2026_04_05_000001_create_blog_tag_translations_table',1),
(282,'2026_04_06_000001_add_page_type_to_pages_table',1),
(283,'2026_04_07_000001_create_locales_table',1),
(284,'2026_04_07_231757_add_css_code_to_shortcodes_table',1),
(285,'2026_04_08_000001_add_category_to_shortcodes_table',1),
(286,'2026_04_08_000001_add_locale_id_to_page_translations_table',1),
(287,'2026_04_08_000001_add_unique_index_to_seo_metas_table',1),
(288,'2026_04_08_000001_create_newsletter_subscribers_table',1),
(289,'2026_04_08_000002_create_shortcode_categories_table',1),
(290,'2026_04_08_000002_drop_locale_from_page_translations',1),
(291,'2026_04_08_121100_add_featured_image_url_to_pages_table',1),
(292,'2026_04_12_000001_create_form_field_type_settings_table',1),
(293,'2026_04_12_000002_add_custom_css_to_form_field_type_settings_table',1),
(294,'2026_04_12_220520_add_custom_html_to_form_field_type_settings_table',1),
(295,'2026_04_16_000001_add_deleted_at_to_helpdesk_tables',1),
(296,'2026_04_16_000001_add_sync_fields_to_review_google_locations',1),
(297,'2026_04_16_000002_add_source_to_reviews',1),
(298,'2026_04_16_100001_create_review_translations_table',1),
(299,'2026_04_17_000001_add_available_tags_to_review_google_locations_table',1),
(300,'2026_04_17_012534_add_translations_to_form_fields_table',1),
(301,'2026_04_19_000001_add_created_at_index_to_mailer_template_versions',1),
(302,'2026_04_19_000001_add_created_at_index_to_media_files',1),
(303,'2026_04_19_000001_add_description_to_permissions_table',1),
(304,'2026_04_19_000001_add_extra_indexes_to_form_submissions_table',1),
(305,'2026_04_19_000001_add_missing_columns_to_helpdesk_ticket_statuses_table',1),
(306,'2026_04_19_000001_add_missing_indexes_to_seo_tables',1),
(307,'2026_04_19_000001_alter_notification_settings_refactor_channels',1),
(308,'2026_04_19_000001_create_sitemap_generations_table',1),
(309,'2026_04_19_000002_add_description_to_roles_table',1),
(310,'2026_04_19_000002_align_helpdesk_ticket_sla_policies_schema',1),
(311,'2026_04_19_000002_create_media_favorites_table',1),
(312,'2026_04_19_000002_create_seo_web_vitals_table',1),
(313,'2026_04_19_000002_register_form_shortcodes_in_template_table',1),
(314,'2026_04_19_000003_add_key_to_helpdesk_categories_and_statuses',1),
(315,'2026_04_19_000003_add_schema_custom_to_seo_metas_table',1),
(316,'2026_04_19_000003_drop_is_favorite_from_media_files_table',1),
(317,'2026_04_19_000004_add_phash_to_media_files_table',1),
(318,'2026_04_19_000004_create_seo_pagespeed_snapshots_table',1),
(319,'2026_04_19_000004_fix_helpdesk_slugs_and_add_missing_keys',1),
(320,'2026_04_19_000005_add_uid_and_position_to_helpdesk_tables',1),
(321,'2026_04_19_000005_create_media_file_versions_table',1),
(322,'2026_04_19_000005_create_seo_alerts_table',1),
(323,'2026_04_19_000006_add_icon_to_helpdesk_conversation_statuses',1),
(324,'2026_04_19_000006_create_media_access_logs_table',1),
(325,'2026_04_19_000007_add_email_position_to_helpdesk_groups',1),
(326,'2026_04_19_000007_create_media_tags_tables',1),
(327,'2026_04_19_000008_add_api_key_encrypted_to_helpdesk_ai_agents',1),
(328,'2026_04_19_000008_add_expires_at_to_media_files',1),
(329,'2026_04_19_000009_create_mediables_table',1),
(330,'2026_04_19_000009_migrate_api_key_to_encrypted_column_on_helpdesk_ai_agents',1),
(331,'2026_04_19_000010_add_missing_columns_to_helpdesk_ai_agents',1),
(332,'2026_04_19_000010_create_media_shares_table',1),
(333,'2026_04_19_000011_add_fulltext_index_to_helpdesk_tickets',1),
(334,'2026_04_19_000011_create_media_comments_table',1),
(335,'2026_04_19_000012_add_workflow_to_media_files',1),
(336,'2026_04_19_000012_rename_ticket_history_table',1),
(337,'2026_04_19_000013_add_hmac_to_activity_log',1),
(338,'2026_04_19_000013_add_soft_deletes_to_helpdesk_misc_tables',1),
(339,'2026_04_19_000014_create_media_share_revocations_table',1),
(340,'2026_04_19_000015_create_folder_templates_table',1),
(341,'2026_04_19_051817_create_jobs_table',1),
(342,'2026_04_19_100000_create_password_histories_table',1),
(343,'2026_04_19_100000_create_ve_user_preferences_table',1),
(344,'2026_04_19_100001_create_login_attempts_table',1),
(345,'2026_04_19_100002_create_trusted_devices_table',1),
(346,'2026_04_19_100003_create_impersonation_logs_table',1),
(347,'2026_04_19_100004_add_password_metadata_to_users_table',1),
(348,'2026_04_19_110000_add_lockout_fields_to_users_table',1),
(349,'2026_04_19_110001_create_magic_login_tokens_table',1),
(350,'2026_04_19_110002_create_email_change_tokens_table',1),
(351,'2026_04_19_110003_add_soft_deletes_to_users_table',1),
(352,'2026_04_19_150049_add_indexes_to_cookie_consent_logs_table',1),
(353,'2026_04_19_200000_add_source_page_id_to_form_submissions',1),
(354,'2026_04_19_200000_create_cookie_inventory_table',1),
(355,'2026_04_20_000001_align_helpdesk_tickets_schema',1),
(356,'2026_04_20_000002_align_helpdesk_ticket_views_schema',1),
(357,'2026_04_20_000010_add_soft_deletes_to_helpdesk_campaigns',1),
(358,'2026_04_20_000020_align_helpdesk_misc_schemas',1),
(359,'2026_04_20_000021_align_additional_columns',1),
(360,'2026_04_20_000022_more_columns',1),
(361,'2026_04_20_000023_more_alignments',1),
(362,'2026_04_20_000024_misc_pivots',1),
(363,'2026_04_20_000025_final_pivots',1),
(364,'2026_04_20_000026_final_column_alignments',1),
(365,'2026_04_20_000030_align_conversation_views',1),
(366,'2026_04_20_000031_align_more_tables',1),
(367,'2026_04_20_000032_add_priority_to_ticket_group_user',1),
(368,'2026_04_20_000033_add_canned_reply_fields',1),
(369,'2026_04_20_000034_make_ticket_id_nullable_in_views',1),
(370,'2026_04_20_000040_add_ai_tag_columns',1),
(371,'2026_04_20_000041_create_ai_tool_knowledge_tables',1),
(372,'2026_04_20_000042_add_type_to_campaigns',1),
(373,'2026_04_20_000050_add_updated_at_to_ticket_histories',1),
(374,'2026_04_20_000060_add_performance_indexes',1),
(375,'2026_04_20_000070_add_last_activity_at_to_tickets',1),
(376,'2026_04_20_000080_create_ticket_links',1),
(377,'2026_04_20_000090_create_webhooks_table',1),
(378,'2026_04_20_000100_create_automations',1),
(379,'2026_04_20_000110_create_macros',1),
(380,'2026_04_20_000120_align_helpcenter_articles',1),
(381,'2026_04_20_000130_create_ticket_followups',1),
(382,'2026_04_20_000140_add_helpdesk_status_to_users',1),
(383,'2026_04_20_000150_add_integration_type_to_webhooks',1),
(384,'2026_04_20_000160_add_deleted_at_to_helpcenter_articles',1),
(385,'2026_04_20_000170_fix_helpcenter_categories',1),
(386,'2026_04_20_000180_add_ai_fields',1),
(387,'2026_04_20_000190_create_push_subscriptions_table',1),
(388,'2026_04_20_000200_create_agent_shifts',1),
(389,'2026_04_20_000210_create_user_dashboards',1),
(390,'2026_04_22_000001_change_render_template_to_medium_text_on_shortcodes_table',1),
(391,'2026_04_22_140041_add_locale_to_form_submissions',1),
(392,'2026_04_23_000001_add_missing_indexes_to_page_module',1),
(393,'2026_04_23_000001_add_missing_performance_indexes_to_helpdesk',1),
(394,'2026_04_23_011627_add_soft_deletes_to_forms_and_submissions',1),
(395,'2026_04_23_020000_add_fulltext_index_to_form_submission_values',1),
(396,'2026_04_24_000001_create_ecommerce_brands_table',1),
(397,'2026_04_24_000001_create_ecommerce_payments_table',1),
(398,'2026_04_24_000002_create_ecommerce_payment_logs_table',1),
(399,'2026_04_24_000002_create_ecommerce_product_categories_table',1),
(400,'2026_04_24_000003_add_token_to_ecommerce_orders_table',1),
(401,'2026_04_24_000003_create_ecommerce_products_table',1),
(402,'2026_04_24_000004_add_soft_deletes_to_ecommerce_payments_table',1),
(403,'2026_04_24_000004_create_ecommerce_product_category_table',1),
(404,'2026_04_24_000005_create_ecommerce_customers_table',1),
(405,'2026_04_24_000006_create_ecommerce_customer_addresses_table',1),
(406,'2026_04_24_000007_create_ecommerce_orders_table',1),
(407,'2026_04_24_000008_create_ecommerce_order_items_table',1),
(408,'2026_04_24_000009_create_ecommerce_order_histories_table',1),
(409,'2026_04_24_000010_create_ecommerce_discounts_table',1),
(410,'2026_04_24_000011_create_ecommerce_reviews_table',1),
(411,'2026_04_24_000012_create_ecommerce_carts_table',1),
(412,'2026_04_24_150421_add_slug_to_ecommerce_brands_table',1),
(413,'2026_04_25_000001_add_label_id_to_ecommerce_products_table',1),
(414,'2026_04_25_000001_create_ads_table',1),
(415,'2026_04_25_000001_create_faq_categories_table',1),
(416,'2026_04_25_000002_create_ads_clicks_table',1),
(417,'2026_04_25_000002_create_ecommerce_product_specification_tables_table',1),
(418,'2026_04_25_000002_create_faqs_table',1),
(419,'2026_04_25_000003_create_ad_translations_table',1),
(420,'2026_04_25_000003_create_ecommerce_product_options_table',1),
(421,'2026_04_25_000003_create_faq_translations_table',1),
(422,'2026_04_25_000004_create_ecommerce_product_option_values_table',1),
(423,'2026_04_25_000004_create_faq_category_translations_table',1),
(424,'2026_04_25_000005_create_ecommerce_tax_rules_table',1),
(425,'2026_04_25_000006_add_reply_to_ecommerce_reviews_table',1),
(426,'2026_04_25_100001_add_tracking_fields_to_ecommerce_shipments_table',1),
(427,'2026_04_25_152557_adapt_old_faq_tables_to_new_structure',1),
(428,'2026_04_25_152613_add_is_active_to_ecommerce_shipping',1),
(429,'2026_04_25_200001_add_digital_products_support',1),
(430,'2026_04_25_200002_create_ecommerce_review_replies_table',1),
(431,'2026_04_25_200003_create_ecommerce_customer_recently_viewed_products_table',1),
(432,'2026_04_25_215653_add_transaction_id_to_orders_table',1),
(433,'2026_04_25_300001_add_delivery_fields_to_ecommerce_orders_table',1),
(434,'2026_04_25_400001_add_seo_fields_to_ecommerce_products_table',1),
(435,'2026_04_25_400002_add_slug_to_ecommerce_product_tags_table',1),
(436,'2026_04_26_000001_add_fields_to_locales_table',1),
(437,'2026_04_26_100001_add_location_ids_to_ecommerce_order_addresses',1),
(438,'2026_04_26_100001_create_locations_countries_table',1),
(439,'2026_04_26_100002_create_locations_states_table',1),
(440,'2026_04_26_100003_create_locations_cities_table',1),
(441,'2026_04_26_212808_add_zip_code_to_ecommerce_order_addresses_table',1),
(442,'2026_04_26_232831_add_email_verification_token_to_ecommerce_customers',1),
(443,'2026_04_27_000001_create_ecommerce_newsletter_subscribers_table',1),
(444,'2026_04_27_000001_fix_roles_table_columns',1),
(445,'2026_04_27_000002_create_ecommerce_product_restock_alerts_table',1),
(446,'2026_04_27_000003_add_social_provider_fields_to_ecommerce_customers',1),
(447,'2026_04_27_000010_create_ecommerce_legal_pages_table',1),
(448,'2026_04_27_000020_create_ecommerce_webhook_logs_table',1),
(449,'2026_04_27_000030_add_share_token_to_ecommerce_customers',1),
(450,'2026_04_27_000040_create_ecommerce_search_logs_table',1),
(451,'2026_04_27_000050_add_verified_buyer_to_ecommerce_reviews',1),
(452,'2026_04_27_000051_create_ecommerce_product_questions_table',1),
(453,'2026_04_27_000060_create_ecommerce_email_campaigns_table',1),
(454,'2026_04_27_000061_create_ecommerce_email_campaign_recipients_table',1),
(455,'2026_04_27_000070_create_ecommerce_translations_table',1),
(456,'2026_04_27_000080_create_ecommerce_bundles_table',1),
(457,'2026_04_27_000081_create_ecommerce_bundle_products_table',1),
(458,'2026_04_27_000082_create_ecommerce_gift_cards_table',1),
(459,'2026_04_27_000090_create_ecommerce_saved_searches_table',1),
(460,'2026_04_27_000101_create_ecommerce_page_views_table',1),
(461,'2026_04_27_000110_add_subscription_fields_to_products_table',1),
(462,'2026_04_27_000111_create_ecommerce_subscriptions_table',1),
(463,'2026_04_27_000200_create_ecommerce_affiliates_table',1),
(464,'2026_04_27_000201_create_ecommerce_affiliate_referrals_table',1),
(465,'2026_04_27_002000_create_idempotency_keys_table',1),
(466,'2026_04_27_023548_create_telescope_entries_table',1),
(467,'2026_04_27_120451_create_features_table',1),
(468,'2026_04_28_010000_create_campaign_sending_servers_table',1),
(469,'2026_04_28_010001_create_campaign_sending_server_bounce_handlers_table',1),
(470,'2026_04_28_010002_create_campaign_sending_server_feedback_handlers_table',1),
(471,'2026_04_28_010003_create_campaign_sending_server_domains_table',1),
(472,'2026_04_28_010004_create_campaign_sending_server_senders_table',1),
(473,'2026_04_28_010005_create_campaign_sending_server_tracking_domains_table',1),
(474,'2026_04_28_010006_create_campaign_sending_server_bounce_logs_table',1),
(475,'2026_04_28_010007_create_campaign_sending_server_feedback_logs_table',1),
(476,'2026_04_28_010008_create_campaign_sending_server_blacklists_table',1),
(477,'2026_04_28_010100_add_soft_deletes',1),
(478,'2026_04_28_020000_create_campaigns_table',1),
(479,'2026_04_28_020001_create_campaign_maillists_table',1),
(480,'2026_04_28_020002_create_campaign_subscribers_table',1),
(481,'2026_04_28_020003_create_campaign_maillists_subscribers_table',1),
(482,'2026_04_28_020004_create_campaign_segments_table',1),
(483,'2026_04_28_020005_create_campaign_fields_table',1),
(484,'2026_04_28_020006_create_campaign_pivot_tables',1),
(485,'2026_04_28_020007_create_campaign_links_and_webhooks',1),
(486,'2026_04_28_020008_create_campaign_tracking_logs',1),
(487,'2026_04_28_020009_create_campaign_emails_and_links',1),
(488,'2026_04_28_020010_create_campaign_automations',1),
(489,'2026_04_28_020011_create_campaign_layouts_and_templates',1),
(490,'2026_04_28_020012_create_campaign_job_monitors',1),
(491,'2026_04_28_030000_add_legacy_columns',1),
(492,'2026_04_28_040000_add_soft_deletes',1),
(493,'2026_04_28_050000_add_blocks_to_templates',1),
(494,'2026_04_28_051003_create_personal_access_tokens_table',1),
(495,'2026_04_28_100000_create_ecommerce_customer_push_tokens_table',1);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*M!100616 SET NOTE_VERBOSITY=@OLD_NOTE_VERBOSITY */;

