/*M!999999\- enable the sandbox mode */ 
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
  `batch_uuid` varchar(191) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `subject` (`subject_type`,`subject_id`),
  KEY `causer` (`causer_type`,`causer_id`),
  KEY `activity_log_created_at_index` (`created_at`),
  KEY `activity_log_log_name_index` (`log_name`),
  KEY `activity_log_batch_uuid_index` (`batch_uuid`),
  KEY `activity_log_event_index` (`event`)
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
  PRIMARY KEY (`id`),
  KEY `attention_categories_is_active_index` (`is_active`),
  KEY `attention_categories_order_index` (`order`)
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
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `attention_departments_is_active_index` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `attention_mails`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `attention_mails` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `attention_id` bigint(20) unsigned NOT NULL,
  `email_type` varchar(100) NOT NULL COMMENT 'Tipo de email',
  `recipient` varchar(150) NOT NULL COMMENT 'Destinatario',
  `subject` varchar(255) NOT NULL COMMENT 'Asunto del email',
  `body` longtext DEFAULT NULL COMMENT 'Cuerpo del email',
  `sent_at` timestamp NULL DEFAULT NULL COMMENT 'Fecha de envío exitoso',
  `failed_at` timestamp NULL DEFAULT NULL COMMENT 'Fecha de fallo',
  `error_message` text DEFAULT NULL COMMENT 'Mensaje de error si falló',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `attention_mails_attention_id_index` (`attention_id`),
  KEY `attention_mails_email_type_index` (`email_type`),
  KEY `attention_mails_sent_at_index` (`sent_at`),
  KEY `attention_mails_failed_at_index` (`failed_at`),
  KEY `attention_mails_created_at_index` (`created_at`),
  CONSTRAINT `attention_mails_attention_id_foreign` FOREIGN KEY (`attention_id`) REFERENCES `attentions` (`id`) ON DELETE CASCADE
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
  CONSTRAINT `attention_notes_attention_id_foreign` FOREIGN KEY (`attention_id`) REFERENCES `attentions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `attention_notes_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
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
  PRIMARY KEY (`id`),
  KEY `attention_sedes_is_active_index` (`is_active`)
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
  PRIMARY KEY (`id`),
  UNIQUE KEY `attention_types_code_unique` (`code`),
  KEY `attention_types_is_active_index` (`is_active`),
  KEY `attention_types_order_index` (`order`)
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
  `response_type` varchar(50) DEFAULT NULL COMMENT 'email, presencial, correo_fisico, telefono, no_requiere',
  `resolution` longtext DEFAULT NULL COMMENT 'Respuesta oficial',
  `resolved_at` timestamp NULL DEFAULT NULL COMMENT 'Fecha de resolución',
  `closed_at` timestamp NULL DEFAULT NULL COMMENT 'Fecha de cierre',
  `satisfaction_rating` tinyint(3) unsigned DEFAULT NULL COMMENT 'Rating 1-5 estrellas',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
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
  CONSTRAINT `attentions_assigned_user_id_foreign` FOREIGN KEY (`assigned_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `attentions_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `attention_categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `attentions_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `attention_departments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `attentions_sede_id_foreign` FOREIGN KEY (`sede_id`) REFERENCES `attention_sedes` (`id`) ON DELETE SET NULL,
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
  PRIMARY KEY (`id`)
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
DROP TABLE IF EXISTS `core_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `core_settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(191) NOT NULL,
  `value` longtext DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `core_settings_key_unique` (`key`)
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
DROP TABLE IF EXISTS `faq_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `faq_categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uid` uuid NOT NULL,
  `title` varchar(191) NOT NULL,
  `slug` varchar(191) DEFAULT NULL,
  `available` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `faq_categories_uid_unique` (`uid`),
  KEY `faq_categories_slug_index` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `faqs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `faqs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uid` uuid NOT NULL,
  `title` varchar(191) NOT NULL,
  `slug` varchar(191) NOT NULL,
  `description` text DEFAULT NULL,
  `available` tinyint(1) NOT NULL DEFAULT 1,
  `category_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `faqs_uid_unique` (`uid`),
  KEY `faqs_slug_index` (`slug`),
  KEY `faqs_available_index` (`available`),
  KEY `faqs_category_id_index` (`category_id`),
  CONSTRAINT `faqs_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `faq_categories` (`id`) ON DELETE CASCADE
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
DROP TABLE IF EXISTS `helpdesk_ai_agents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `helpdesk_ai_agents` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `description` text DEFAULT NULL,
  `type` varchar(191) NOT NULL,
  `configuration` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`configuration`)),
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `helpdesk_ai_agents_type_index` (`type`),
  KEY `helpdesk_ai_agents_active_index` (`active`)
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
  `description` text DEFAULT NULL,
  `template_id` bigint(20) unsigned DEFAULT NULL,
  `status` varchar(191) NOT NULL DEFAULT 'draft',
  `started_at` timestamp NULL DEFAULT NULL,
  `ended_at` timestamp NULL DEFAULT NULL,
  `targeting_rules` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`targeting_rules`)),
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `helpdesk_campaigns_status_index` (`status`),
  KEY `helpdesk_campaigns_started_at_index` (`started_at`)
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
  KEY `helpdesk_conversation_items_author_id_foreign` (`author_id`),
  KEY `helpdesk_conversation_items_conversation_id_index` (`conversation_id`),
  KEY `helpdesk_conversation_items_type_index` (`type`),
  KEY `helpdesk_conversation_items_created_at_index` (`created_at`),
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
  `name` varchar(191) NOT NULL,
  `slug` varchar(191) NOT NULL,
  `color` varchar(191) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `order` int(11) NOT NULL DEFAULT 0,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `is_system` tinyint(1) NOT NULL DEFAULT 0,
  `is_open` tinyint(1) NOT NULL DEFAULT 1,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `helpdesk_conversation_statuses_slug_unique` (`slug`),
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
  PRIMARY KEY (`id`),
  UNIQUE KEY `helpdesk_conversation_tags_slug_unique` (`slug`),
  KEY `helpdesk_conversation_tags_slug_index` (`slug`)
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
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_74129` (`conversation_id`,`user_id`),
  KEY `helpdesk_conversation_views_conversation_id_index` (`conversation_id`),
  CONSTRAINT `helpdesk_conversation_views_conversation_id_foreign` FOREIGN KEY (`conversation_id`) REFERENCES `helpdesk_conversations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `helpdesk_conversations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `helpdesk_conversations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint(20) unsigned NOT NULL,
  `subject` varchar(191) NOT NULL,
  `status_id` bigint(20) unsigned DEFAULT NULL,
  `assignee_id` bigint(20) unsigned DEFAULT NULL,
  `priority` varchar(191) NOT NULL DEFAULT 'normal',
  `is_archived` tinyint(1) NOT NULL DEFAULT 0,
  `assigned_at` timestamp NULL DEFAULT NULL,
  `closed_at` timestamp NULL DEFAULT NULL,
  `first_response_at` timestamp NULL DEFAULT NULL,
  `last_message_at` timestamp NULL DEFAULT NULL,
  `tags` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`tags`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `helpdesk_conversations_customer_id_index` (`customer_id`),
  KEY `helpdesk_conversations_status_id_index` (`status_id`),
  KEY `helpdesk_conversations_assignee_id_index` (`assignee_id`),
  KEY `helpdesk_conversations_is_archived_index` (`is_archived`),
  KEY `helpdesk_conversations_created_at_index` (`created_at`),
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
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `helpdesk_customers_email_unique` (`email`),
  KEY `helpdesk_customers_email_index` (`email`),
  KEY `helpdesk_customers_banned_at_index` (`banned_at`)
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
  `name` varchar(191) NOT NULL,
  `assignment_mode` varchar(191) NOT NULL DEFAULT 'round_robin',
  `default` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
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
  `order` int(11) NOT NULL DEFAULT 0,
  `views_count` int(11) NOT NULL DEFAULT 0,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
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
  PRIMARY KEY (`id`),
  UNIQUE KEY `helpdesk_helpcenter_categories_slug_unique` (`slug`),
  KEY `helpdesk_helpcenter_categories_order_index` (`order`),
  KEY `helpdesk_helpcenter_categories_active_index` (`active`)
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
  PRIMARY KEY (`id`),
  UNIQUE KEY `helpdesk_ticket_canned_replies_short_code_unique` (`short_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `helpdesk_ticket_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `helpdesk_ticket_categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `slug` varchar(191) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(191) DEFAULT NULL,
  `color` varchar(191) DEFAULT NULL,
  `order` int(11) NOT NULL DEFAULT 0,
  `default_sla_policy_id` bigint(20) unsigned DEFAULT NULL,
  `custom_form_fields` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`custom_form_fields`)),
  `required_fields` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`required_fields`)),
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `helpdesk_ticket_categories_slug_unique` (`slug`),
  KEY `helpdesk_ticket_categories_order_index` (`order`),
  KEY `helpdesk_ticket_categories_active_index` (`active`)
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
  CONSTRAINT `helpdesk_ticket_comments_author_id_foreign` FOREIGN KEY (`author_id`) REFERENCES `helpdesk_customers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `helpdesk_ticket_comments_ticket_id_foreign` FOREIGN KEY (`ticket_id`) REFERENCES `helpdesk_tickets` (`id`) ON DELETE CASCADE
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
  PRIMARY KEY (`id`)
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
  PRIMARY KEY (`id`),
  KEY `helpdesk_ticket_items_author_id_foreign` (`author_id`),
  KEY `helpdesk_ticket_items_ticket_id_index` (`ticket_id`),
  KEY `helpdesk_ticket_items_type_index` (`type`),
  KEY `helpdesk_ticket_items_created_at_index` (`created_at`),
  CONSTRAINT `helpdesk_ticket_items_author_id_foreign` FOREIGN KEY (`author_id`) REFERENCES `helpdesk_customers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `helpdesk_ticket_items_ticket_id_foreign` FOREIGN KEY (`ticket_id`) REFERENCES `helpdesk_tickets` (`id`) ON DELETE CASCADE
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
  `name` varchar(191) NOT NULL,
  `description` text DEFAULT NULL,
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
  PRIMARY KEY (`id`),
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
  `slug` varchar(191) NOT NULL,
  `color` varchar(191) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `order` int(11) NOT NULL DEFAULT 0,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `is_system` tinyint(1) NOT NULL DEFAULT 0,
  `is_open` tinyint(1) NOT NULL DEFAULT 1,
  `stops_sla_timer` tinyint(1) NOT NULL DEFAULT 0,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `helpdesk_ticket_statuses_slug_unique` (`slug`),
  KEY `helpdesk_ticket_statuses_is_default_index` (`is_default`),
  KEY `helpdesk_ticket_statuses_is_open_index` (`is_open`),
  KEY `helpdesk_ticket_statuses_order_index` (`order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `helpdesk_ticket_views`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `helpdesk_ticket_views` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ticket_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `viewed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_6530` (`ticket_id`,`user_id`),
  KEY `helpdesk_ticket_views_ticket_id_index` (`ticket_id`),
  CONSTRAINT `helpdesk_ticket_views_ticket_id_foreign` FOREIGN KEY (`ticket_id`) REFERENCES `helpdesk_tickets` (`id`) ON DELETE CASCADE
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
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `helpdesk_tickets_ticket_number_unique` (`ticket_number`),
  KEY `helpdesk_tickets_category_id_foreign` (`category_id`),
  KEY `helpdesk_tickets_sla_policy_id_foreign` (`sla_policy_id`),
  KEY `helpdesk_tickets_group_id_foreign` (`group_id`),
  KEY `helpdesk_tickets_ticket_number_index` (`ticket_number`),
  KEY `helpdesk_tickets_customer_id_index` (`customer_id`),
  KEY `helpdesk_tickets_status_id_index` (`status_id`),
  KEY `helpdesk_tickets_assignee_id_index` (`assignee_id`),
  KEY `helpdesk_tickets_is_archived_index` (`is_archived`),
  KEY `helpdesk_tickets_created_at_index` (`created_at`),
  CONSTRAINT `helpdesk_tickets_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `helpdesk_ticket_categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `helpdesk_tickets_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `helpdesk_customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `helpdesk_tickets_group_id_foreign` FOREIGN KEY (`group_id`) REFERENCES `helpdesk_groups` (`id`) ON DELETE SET NULL,
  CONSTRAINT `helpdesk_tickets_sla_policy_id_foreign` FOREIGN KEY (`sla_policy_id`) REFERENCES `helpdesk_ticket_sla_policies` (`id`) ON DELETE SET NULL,
  CONSTRAINT `helpdesk_tickets_status_id_foreign` FOREIGN KEY (`status_id`) REFERENCES `helpdesk_ticket_statuses` (`id`) ON DELETE SET NULL
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
  KEY `mailer_endpoints_mailer_template_id_foreign` (`mailer_template_id`),
  KEY `mailer_endpoints_lang_id_foreign` (`lang_id`),
  KEY `mailer_endpoints_slug_index` (`slug`),
  KEY `mailer_endpoints_is_active_index` (`is_active`),
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
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `visibility` varchar(191) NOT NULL DEFAULT 'private',
  `is_favorite` tinyint(1) NOT NULL DEFAULT 0,
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
  FULLTEXT KEY `media_files_name_fulltext` (`name`),
  CONSTRAINT `media_files_folder_id_foreign` FOREIGN KEY (`folder_id`) REFERENCES `media_folders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `media_files_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
  CONSTRAINT `page_preview_tokens_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `page_preview_tokens_page_id_foreign` FOREIGN KEY (`page_id`) REFERENCES `pages` (`id`) ON DELETE CASCADE
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
DROP TABLE IF EXISTS `pages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `pages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(191) NOT NULL,
  `slug` varchar(191) NOT NULL,
  `content` longtext DEFAULT NULL,
  `template` varchar(60) DEFAULT 'default',
  `description` text DEFAULT NULL,
  `status` enum('draft','published','pending') NOT NULL DEFAULT 'draft',
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `seo_title` varchar(191) DEFAULT NULL,
  `seo_description` text DEFAULT NULL,
  `seo_keywords` text DEFAULT NULL,
  `published_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pages_slug_unique` (`slug`),
  KEY `pages_status_published_at_index` (`status`,`published_at`),
  KEY `pages_slug_index` (`slug`),
  KEY `pages_user_id_index` (`user_id`),
  KEY `pages_created_at_index` (`created_at`),
  FULLTEXT KEY `fulltext_search` (`title`,`content`,`description`),
  CONSTRAINT `pages_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
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
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
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
  `description` text DEFAULT NULL COMMENT 'Meta description for search results',
  `keywords` text DEFAULT NULL COMMENT 'Meta keywords (comma separated)',
  `og_title` varchar(255) DEFAULT NULL COMMENT 'Open Graph title (Facebook, LinkedIn)',
  `og_description` text DEFAULT NULL COMMENT 'Open Graph description',
  `og_image` varchar(500) DEFAULT NULL COMMENT 'Open Graph image URL',
  `og_type` varchar(50) NOT NULL DEFAULT 'website' COMMENT 'Open Graph type (website, article, etc.)',
  `twitter_card` varchar(50) NOT NULL DEFAULT 'summary' COMMENT 'Twitter card type (summary, summary_large_image)',
  `twitter_title` varchar(255) DEFAULT NULL COMMENT 'Twitter card title',
  `twitter_description` text DEFAULT NULL COMMENT 'Twitter card description',
  `twitter_image` varchar(500) DEFAULT NULL COMMENT 'Twitter card image URL',
  `canonical_url` varchar(500) DEFAULT NULL COMMENT 'Canonical URL for duplicate content',
  `robots` varchar(100) NOT NULL DEFAULT 'index,follow' COMMENT 'Robots meta tag directive',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `seo_metas_seoable_type_seoable_id_index` (`seoable_type`,`seoable_id`),
  KEY `seo_metas_robots_index` (`robots`),
  KEY `seo_metas_created_at_index` (`created_at`)
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
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `seo_redirects_source_path_index` (`source_path`),
  KEY `seo_redirects_is_active_index` (`is_active`),
  KEY `seo_redirects_source_path_is_active_index` (`source_path`,`is_active`)
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
  `voilated` tinyint(1) NOT NULL DEFAULT 0,
  `last_login_at` timestamp NULL DEFAULT NULL,
  `last_login_ip` varchar(191) DEFAULT NULL,
  `last_logins_at` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  UNIQUE KEY `users_uid_unique` (`uid`),
  KEY `users_email_index` (`email`),
  KEY `users_available_index` (`available`),
  KEY `users_uid_index` (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*M!100616 SET NOTE_VERBOSITY=@OLD_NOTE_VERBOSITY */;

/*M!999999\- enable the sandbox mode */ 
SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1,'2024_01_01_000001_create_menus_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (2,'2024_01_01_000002_create_menu_items_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (3,'2025_11_28_230312_create_backup_schedules_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (4,'2025_12_29_000001_create_products_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (5,'2025_12_29_000003_create_media_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (6,'2025_12_29_000100_create_activity_log_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (7,'2025_12_29_010725_create_sessions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (8,'2025_12_29_014762_create_categories_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (9,'2025_12_29_014764_create_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (10,'2025_12_29_014765_create_core_langs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (11,'2025_12_29_014765_create_langs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (12,'2025_12_29_014766_create_settings_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (13,'2025_12_29_014768_create_notifications_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (14,'2025_12_29_014770_create_application_logs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (15,'2025_12_29_014771_create_media_folders_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (16,'2025_12_29_014772_create_media_files_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (17,'2025_12_29_020405_create_notification_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (18,'2025_12_29_020501_create_mailer_layouts_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (19,'2025_12_29_020502_create_mailer_templates_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (20,'2025_12_29_020503_create_mailer_variables_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (21,'2025_12_29_020504_create_mailer_endpoints_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (22,'2025_12_29_020505_create_mailer_template_langs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (23,'2025_12_29_020506_create_mailer_layout_langs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (24,'2025_12_29_020507_create_mailer_variable_langs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (25,'2025_12_29_020508_create_mailer_endpoint_logs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (26,'2025_12_29_020509_create_faq_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (27,'2025_12_29_020626_create_ip_locations_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (28,'2025_12_29_020627_create_countries_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (29,'2025_12_29_020901_create_helpdesk_customers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (30,'2025_12_29_020902_create_helpdesk_ticket_statuses_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (31,'2025_12_29_020903_create_helpdesk_ticket_categories_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (32,'2025_12_29_020904_create_helpdesk_groups_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (33,'2025_12_29_020905_create_helpdesk_ticket_sla_policies_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (34,'2025_12_29_020906_create_helpdesk_tickets_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (35,'2025_12_29_020907_create_helpdesk_ticket_items_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (36,'2025_12_29_020908_create_helpdesk_ticket_comments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (37,'2025_12_29_020909_create_helpdesk_ticket_notes_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (38,'2025_12_29_020910_create_helpdesk_ticket_mails_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (39,'2025_12_29_020911_create_helpdesk_ticket_histories_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (40,'2025_12_29_020912_create_helpdesk_ticket_watchers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (41,'2025_12_29_020913_create_helpdesk_ticket_reads_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (42,'2025_12_29_020914_create_helpdesk_conversation_statuses_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (43,'2025_12_29_020915_create_helpdesk_conversations_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (44,'2025_12_29_020916_create_helpdesk_conversation_items_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (45,'2025_12_29_020917_create_helpdesk_conversation_reads_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (46,'2025_12_29_020918_create_helpdesk_conversation_tags_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (47,'2025_12_29_020919_create_helpdesk_conversation_tag_pivot_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (48,'2025_12_29_020920_create_helpdesk_conversation_views_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (49,'2025_12_29_020921_create_helpdesk_canned_replies_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (50,'2025_12_29_020922_create_helpdesk_ticket_canned_replies_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (51,'2025_12_29_020923_create_helpdesk_attributes_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (52,'2025_12_29_020924_create_helpdesk_customer_sessions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (53,'2025_12_29_020925_create_helpdesk_page_visits_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (54,'2025_12_29_020926_create_helpdesk_ticket_groups_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (55,'2025_12_29_020927_create_helpdesk_ticket_group_user_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (56,'2025_12_29_020928_create_helpdesk_ticket_sla_breaches_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (57,'2025_12_29_020929_create_helpdesk_ticket_views_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (58,'2025_12_29_020930_create_helpdesk_helpcenter_categories_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (59,'2025_12_29_020931_create_helpdesk_helpcenter_articles_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (60,'2025_12_29_020932_create_helpdesk_helpcenter_tags_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (61,'2025_12_29_020933_create_helpdesk_campaigns_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (62,'2025_12_29_020934_create_helpdesk_campaign_templates_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (63,'2025_12_29_020935_create_helpdesk_campaign_impressions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (64,'2025_12_29_020936_create_helpdesk_agent_settings_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (65,'2025_12_29_020937_create_helpdesk_ai_agents_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (66,'2025_12_29_052122_create_health_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (67,'2025_12_29_054242_create_notification_settings_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (68,'2025_12_29_054249_create_push_notification_tokens_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (69,'2025_12_29_054257_create_helpdesk_group_user_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (70,'2025_12_29_054258_create_roles_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (71,'2025_12_29_054303_create_model_has_roles_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (72,'2025_12_29_054303_create_role_has_permissions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (73,'2025_12_29_100001_create_helpdesk_categories_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (74,'2025_12_29_100002_create_helpdesk_priorities_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (75,'2025_12_29_100003_create_helpdesk_statuses_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (76,'2025_12_29_100004_create_helpdesk_sla_policies_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (77,'2025_12_29_100006_create_helpdesk_ticket_messages_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (78,'2025_12_29_100007_create_helpdesk_ticket_attachments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (79,'2025_12_29_100009_create_helpdesk_ticket_assignments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (80,'2025_12_29_100010_create_helpdesk_ticket_history_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (81,'2026_01_01_203319_add_missing_permission_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (82,'2026_01_02_054123_add_event_column_to_activity_log_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (83,'2026_02_08_000001_add_analytics_dashboard_widgets_setting',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (84,'2026_02_08_000001_create_attention_types_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (85,'2026_02_08_000001_create_pages_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (86,'2026_02_08_000001_create_seo_metas_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (87,'2026_02_08_000002_create_attention_categories_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (88,'2026_02_08_000002_create_page_versions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (89,'2026_02_08_000002_create_seo_redirects_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (90,'2026_02_08_000003_add_fulltext_index_to_pages_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (91,'2026_02_08_000003_create_attention_departments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (92,'2026_02_08_000003_create_page_preview_tokens_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (93,'2026_02_08_000004_create_attention_sedes_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (94,'2026_02_08_000005_create_attentions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (95,'2026_02_08_000006_create_attention_notes_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (96,'2026_02_08_000007_create_attention_actions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (97,'2026_02_08_000008_create_attention_mails_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (98,'2026_02_08_000009_create_attention_satisfaction_table',1);
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
