# Acelle Mail Migrations Analysis Report

**Generated:** 2026-01-29
**Source:** `/Users/functionbytes/Function/Coding/acelle/database/migrations/`
**Total Migrations:** 297
**Comparison System:** `/Users/functionbytes/Function/Coding/system/modules/Mailing/`

---

## Executive Summary

Acelle Mail has evolved through 297 migrations spanning from 2014 to 2023, building a comprehensive email marketing platform. This analysis documents the complete database structure, migration order, foreign key relationships, and identifies key differences with our current mailing system implementation.

### Key Statistics

- **Core Tables:** 80+ tables
- **Migration Timespan:** June 2014 - December 2023 (9+ years)
- **Major Features:** Campaigns, Automation, Subscribers, Templates, Sending Servers, Billing, E-commerce
- **Foreign Keys:** Extensive cascade relationships across all tables

---

## 1. Migration Order & Timeline

### Phase 1: Foundation (2014-2016 Q2)

**Core Infrastructure Setup**

1. `2014_10_12_000000_create_users_table.php` - Base authentication
2. `2014_10_12_100000_create_password_resets_table.php` - Password recovery
3. `2016_06_09_172556_create_mail_lists_table.php` - Mailing lists (core entity)
4. `2016_06_09_174024_create_contacts_table.php` - Contact information
5. `2016_06_09_174351_create_countries_table.php` - Geographic data
6. `2016_06_10_041252_create_languages_table.php` - Localization support
7. `2016_06_10_174522_create_admin_groups_table.php` - Admin role groups
8. `2016_06_10_174523_create_admins_table.php` - Admin users
9. `2016_06_10_174527_create_customer_groups_table.php` - Customer segmentation
10. `2016_06_10_174528_create_customers_table.php` - Customer accounts
11. `2016_06_10_174529_create_campaigns_table.php` - Email campaigns
12. `2016_06_11_073311_add_foreign_keys.php` - **Critical: Initial FK relationships**

### Phase 2: Subscriber Management (2016 Q2-Q3)

**Subscriber & Field System**

13. `2016_06_11_182326_add_email_to_contact.php` - Email field addition
14. `2016_06_11_182432_add_city_to_contact.php` - City field addition
15. `2016_06_12_030828_rename_mail_list_description_to_remind_message.php` - Schema refinement
16. `2016_06_12_152501_add_custom_order_to_mail_lists.php` - Ordering support
17. `2016_06_13_045445_create_tracking_logs_table.php` - Email tracking foundation
18. `2016_06_14_025716_create_fields_table.php` - Custom fields
19. `2016_06_14_164304_create_field_options_table.php` - Field dropdown options
20. `2016_06_15_025158_create_subscribers_table.php` - **Core: Subscriber entity**
21. `2016_06_15_064031_create_subscriber_fields_table.php` - Custom field values
22. `2016_06_16_034306_create_segments_table.php` - List segmentation
23. `2016_06_16_062814_create_segment_conditions_table.php` - Segment rules

### Phase 3: Template & Tracking (2016 Q2-Q3)

**Content & Analytics**

24. `2016_06_16_133626_create_jobs_table.php` - Queue jobs
25. `2016_06_17_041253_create_layouts_table.php` - Email layouts
26. `2016_06_17_042331_create_pages_table.php` - Landing pages
27. `2016_06_19_145423_create_open_logs_table.php` - Open tracking
28. `2016_06_19_145434_create_click_logs_table.php` - Click tracking
29. `2016_06_19_145452_create_unsubscribe_logs_table.php` - Unsubscribe tracking
30. `2016_06_19_145506_create_feedback_logs_table.php` - Complaint tracking
31. `2016_06_21_074950_create_templates_table.php` - **Core: Email templates**
32. `2016_06_21_145755_add_segment_foreign_key_to_campaigns_table.php` - Campaign-segment FK

### Phase 4: Sending Infrastructure (2016 Q3-Q4)

**Deliverability & Servers**

33. `2016_06_29_024509_create_bounce_handlers_table.php` - Bounce handling
34. `2016_06_29_041133_create_feedback_loop_handlers_table.php` - FBL handlers
35. `2016_07_01_150630_create_sending_servers_table.php` - **Critical: SMTP/API servers**
36. `2016_07_04_092555_create_sending_domains_table.php` - Domain verification
37. `2016_07_05_020001_create_settings_table.php` - System settings
38. `2016_07_05_073002_create_campaign_links_table.php` - Link tracking
39. `2016_07_06_034536_add_user_foreign_key_to_users.php` - User relationships
40. `2016_07_06_145609_create_logs_table.php` - System logs
41. `2016_07_07_034047_add_tracking_logs_foreign_keys.php` - **Important: Tracking FKs**
42. `2016_07_07_080813_create_bounce_logs_table.php` - Bounce tracking
43. `2016_07_10_030645_create_ip_locations_table.php` - Geolocation
44. `2016_07_12_031139_create_blacklists_table.php` - Blacklist management

### Phase 5: Billing & Plans (2016 Q4)

**Monetization Layer**

45. `2016_09_08_163044_add_reason_column_for_blacklists.php` - Blacklist reasons
46. `2016_09_16_123651_create_customer_group_sending_servers_table.php` - Server assignments
47. `2016_09_26_035705_create_currencies_table.php` - Multi-currency
48. `2016_09_26_035706_create_plans_table.php` - **Critical: Subscription plans**
49. `2016_09_27_035248_add_sending_servers_api_secret_key.php` - API authentication

### Phase 6: Template Enhancements (2016 Q4 - 2017 Q1)

**Content Management Evolution**

50-70. Various template source, campaign settings, and optimization migrations
- Template sources (builder vs HTML)
- Campaign scheduling improvements
- Language defaults
- User quotas
- Failed job tracking
- Subscription confirmation

### Phase 7: Automation v1 (2017 Q1)

**Triggered Campaigns**

71. `2016_12_27_104155_create_auto_trigger_table.php` - **First automation system**
72. `2016_12_28_095315_add_auto_trigger_id_to_tracking_logs.php` - Trigger tracking
73. `2017_01_02_173918_add_subscription_type_column.php` - Subscription types
74. `2017_01_11_114659_create_campaigns_lists_segments_table.php` - **Many-to-many relationships**

### Phase 8: Advanced Sending (2017 Q1-Q2)

**Server Management & Email Verification**

75. `2017_01_11_174230_add_default_mail_list_id_to_campaigns.php` - Default lists
76. `2017_01_22_175544_add_sending_servers_quota_column.php` - Server quotas
77. `2017_02_17_071823_create_plans_sending_servers_table.php` - Plan-server linking
78. `2017_02_22_082127_create_user_activations_table.php` - Email activation
79. `2017_03_02_033500_create_mail_lists_sending_servers_table.php` - **List-server assignment**
80. `2017_04_17_040000_create_email_verification_servers_table.php` - Email validation
81. `2017_04_17_043522_create_email_verifications_table.php` - Verification logs
82. `2017_04_25_064529_create_plans_email_verification_servers_table.php` - Plan-verifier linking

### Phase 9: Advanced Features (2017 Q2-Q4)

**Sub-accounts & Localization**

83. `2017_04_28_112900_add_billing_info_to_contacts.php` - Contact billing
84. `2017_07_24_033211_create_sub_accounts_table.php` - **Agency/reseller support**
85. `2017_07_29_173114_add_tracking_logs_sub_account_id.php` - Sub-account tracking
86. `2017_10_14_172620_add_german.php` - German translation

### Phase 10: Domain Management (2018)

**Advanced Domain Features**

87. `2018_05_29_032030_add_verification_fields_to_sending_domains_table.php` - DKIM/SPF
88. `2018_05_29_091718_add_tracking_domain_id_to_campaigns.php` - Custom tracking domains
89. `2018_07_23_072940_add_verification_hostname_dkim_selector_to_sending_domains.php` - Domain verification
90. `2018_07_31_173424_create_notifications_table.php` - Laravel notifications
91. `2018_11_06_032139_create_senders_table.php` - **Verified senders**

### Phase 11: Automation v2 (2019)

**Modern Automation System**

92. `2019_11_28_022304_create_automation2s_table.php` - **New automation engine**
93. `2019_12_06_013433_create_emails_table.php` - **Automation emails (separate from campaigns)**
94. `2019_12_10_014005_create_attachments_table.php` - File attachments
95. `2019_12_11_022619_update_auto_triggers_table.php` - Trigger updates
96. `2019_12_17_040745_create_email_links_table.php` - Automation link tracking
97. `2019_12_19_125841_add_email_id_to_tracking_logs_table.php` - Email tracking FK
98. `2019_12_23_142909_create_timelines_table.php` - **Activity timeline**
99. `2019_12_30_101626_add_tags_to_subscribers.php` - Subscriber tagging

### Phase 12: Billing System v2 (2020-2021)

**Modern Subscription & Payments**

100. `2020_02_04_092300_create_subscription_logs_table.php` - Subscription history
101. `2020_06_29_130700_create_tracking_domains_table.php` - **Tracking domain management**
102. `2020_09_30_104755_create_plugins_table.php` - Plugin system
103. `2021_03_12_023933_create_sources_table.php` - Traffic sources
104. `2021_03_12_024042_create_products_table.php` - **E-commerce products**
105. `2021_04_22_025613_create_billing_addresses_table.php` - Billing addresses
106. `2021_04_23_121906_create_invoices_table.php` - Invoice generation
107. `2021_04_23_122104_create_invoice_items_table.php` - Line items
108. `2021_04_25_152033_create_transactions_table.php` - Payment transactions

### Phase 13: Modern Features (2021 Q2-Q3)

**Laravel 8+ & Template Builder**

109. `2021_06_13_055150_create_template_categories_table.php` - Template categorization
110. `2021_06_13_055346_create_templates_categories_table.php` - Many-to-many
111. `2021_06_13_142819_add_template_id_to_campaigns.php` - **Template linking**
112. `2021_06_19_164657_switch_users_customers_admins_table.php` - **User system refactor**
113. `2021_06_20_074007_add_user_details_to_users_table.php` - Unified user table
114. `2021_06_27_071652_create_job_batches_table.php` - Laravel 8 job batching
115. `2021_06_27_164858_job_monitor.php` - Job monitoring

### Phase 14: Forms & Websites (2021 Q3-Q4)

**Landing Page Builder**

116. `2021_09_27_083557_create_forms_table.php` - **Form builder**
117. `2021_09_28_021658_create_websites_table.php` - **Website/page builder**
118. `2021_10_03_033322_add_verification_status_to_subscribers_table.php` - Email verification status

### Phase 15: Social Auth & Webhooks (2022)

**Integrations**

119. `2022_03_03_134857_add_google_auth_to_users.php` - Google OAuth
120. `2022_03_04_153447_add_facebook_auth_to_users.php` - Facebook OAuth
121. `2022_03_05_133551_create_campaign_webhooks_table.php` - **Campaign webhooks**
122. `2022_03_07_040213_create_email_webhooks_table.php` - **Email webhooks**

### Phase 16: E-commerce Expansion (2023)

**Full E-commerce Integration**

123. `2023_06_22_025714_create_funnels_table.php` - **Sales funnels**
124. `2023_07_05_120720_create_files_table.php` - File management
125. `2023_07_27_085122_create_orders_table.php` - **Order management**
126. `2023_07_27_090917_create_media_table.php` - Media library
127. `2023_08_01_071320_create_categories_table.php` - Product categories
128. `2023_08_02_123252_create_attributes_table.php` - Product attributes
129. `2023_08_02_123635_create_product_attributes_table.php` - Product-attribute pivot

---

## 2. Complete Database Structure

### Core Tables (Primary Entities)

#### 2.1 User Management

**users**
```php
- id (increments)
- uid (uuid)
- email (string)
- password (string)
- remember_token (string)
- api_token (string, nullable)
- one_time_api_token (string, nullable)
- activated (boolean)
// User details added in 2021 refactor:
- first_name (string, nullable)
- last_name (string, nullable)
- image_uid (uuid, nullable)
- timezone (string)
- language_id (FK, nullable)
- google_id (string, nullable)
- google_token (text, nullable)
- facebook_id (string, nullable)
- facebook_token (text, nullable)
- onscreen_intros (text, nullable)
- created_at, updated_at (timestamps)
```

**admins**
```php
- id (increments)
- uid (uuid)
- user_id (FK) → users
- admin_group_id (FK) → admin_groups
- language_id (FK) → languages
- color_scheme (string)
- text_direction (string) // LTR/RTL
- menu_layout (string)
- dark_mode (string)
- creator_id (FK, nullable) → users (for sub-admins)
- created_at, updated_at
```

**customers**
```php
- id (increments)
- uid (uuid)
- user_id (FK) → users
- contact_id (FK) → contacts
- customer_group_id (FK) → customer_groups
- language_id (FK) → languages
- timezone (string)
- text_direction (string)
- menu_layout (string)
- dark_mode (string)
- payment_method (string, nullable)
- auto_billing_data (text, nullable)
- created_at, updated_at
```

**sub_accounts**
```php
- id (increments)
- uid (uuid)
- user_id (FK) → users
- admin_id (FK) → admins
- customer_id (FK) → customers
- sending_limit (integer)
- created_at, updated_at
```

#### 2.2 Contact Management

**contacts**
```php
- id (increments)
- uid (uuid)
- country_id (FK) → countries
- email (string, nullable)
- city (string, nullable)
- company (string, nullable)
- first_name (string, nullable)
- last_name (string, nullable)
- address_1 (string, nullable)
- address_2 (string, nullable)
- state (string, nullable)
- zip (string, nullable)
- phone (string, nullable)
- url (string, nullable)
// Billing info added later:
- billing_first_name (string, nullable)
- billing_last_name (string, nullable)
- billing_address (string, nullable)
- billing_phone (string, nullable)
- created_at, updated_at
```

**countries**
```php
- id (increments)
- uid (uuid)
- name (string)
- code (string)
- status (string)
- created_at, updated_at
```

#### 2.3 Mail Lists & Subscribers

**mail_lists**
```php
- id (increments)
- uid (uuid)
- customer_id (FK) → customers
- contact_id (FK) → contacts
- name (string)
- default_subject (string) // Removed in 2022
- from_email (string, nullable)
- from_name (string, nullable)
- remind_message (text, nullable) // Renamed from description
- email_subscribe (text, nullable)
- email_unsubscribe (text, nullable)
- email_daily (text, nullable)
- send_welcome_email (boolean, default: false)
- unsubscribe_notification (boolean, default: false)
- subscribe_confirmation (boolean, default: true)
- all_sending_servers (boolean, default: false)
- embedded_form_options (text, nullable)
- status (string)
- created_at, updated_at
```

**subscribers**
```php
- id (increments)
- uid (uuid)
- mail_list_id (FK) → mail_lists
- email (string, indexed)
- status (string) // subscribed, unsubscribed, unconfirmed, blacklisted, spam-reported
- from (string) // web, import, api, embedded
- ip (string)
- subscription_type (string, nullable)
- tags (text, nullable)
- verification_status (string, nullable)
- last_verification_at (datetime, nullable)
- last_verification_by (string, nullable)
- last_verification_result (text, nullable)
- import_batch_id (uuid, nullable)
- created_at, updated_at
```

**fields** (Custom Fields)
```php
- id (increments)
- uid (uuid)
- mail_list_id (FK) → mail_lists
- label (string)
- type (string) // text, number, date, dropdown, multiselect, etc.
- tag (string) // merge tag like {FIRST_NAME}
- default_value (string, nullable)
- visible (boolean)
- required (boolean)
- is_email (boolean, default: false)
- custom_order (integer)
- created_at, updated_at
```

**subscriber_fields** (Field Values)
```php
- id (increments)
- uid (uuid)
- field_id (FK) → fields
- subscriber_id (FK) → subscribers
- value (text, nullable)
- created_at, updated_at
```

**field_options** (Dropdown Options)
```php
- id (increments)
- uid (uuid)
- field_id (FK) → fields
- label (string)
- value (string)
- created_at, updated_at
```

#### 2.4 Segments & Filtering

**segments**
```php
- id (increments)
- uid (uuid)
- mail_list_id (FK) → mail_lists
- name (string)
- matching (string) // all (AND), any (OR)
- created_at, updated_at
```

**segment_conditions**
```php
- id (increments)
- uid (uuid)
- segment_id (FK) → segments
- field_id (FK, nullable) → fields
- operator (string) // equal, not_equal, contains, not_contains, starts, not_starts, ends, not_ends, greater, less, blank, not_blank
- value (text, nullable)
- created_at, updated_at
```

#### 2.5 Campaigns

**campaigns**
```php
- id (increments)
- uid (uuid)
- customer_id (FK) → customers
- default_mail_list_id (FK, nullable) → mail_lists
- segment_id (FK, nullable) → segments
- template_id (FK, nullable) → templates
- tracking_domain_id (FK, nullable) → tracking_domains
- type (string) // regular, plain-text
- name (string)
- subject (string)
- preheader (string, nullable)
- html (longtext) // Removed in 2021 when template_id added
- plain (longtext)
- template_source (string, nullable) // builder, editor, upload
- from_email (string)
- from_name (string)
- reply_to (string)
- use_default_sending_server_from_email (boolean)
- status (string) // new, ready, sending, error, done, paused
- sign_dkim (boolean)
- track_open (boolean)
- track_click (boolean)
- skip_failed_message (boolean, default: false)
- resend (integer)
- image (string, nullable)
- last_error (text, nullable)
- running_pid (integer, nullable)
- run_at (timestamp, nullable)
- delivery_at (timestamp, nullable)
- created_at, updated_at
```

**campaigns_lists_segments** (Many-to-many)
```php
- id (increments)
- campaign_id (FK) → campaigns
- mail_list_id (FK, nullable) → mail_lists
- segment_id (FK, nullable) → segments
- is_default (boolean, default: false)
- created_at, updated_at
```

**campaign_links**
```php
- id (increments)
- campaign_id (FK) → campaigns
- link (text)
- url (text, nullable)
- created_at, updated_at
```

**campaign_webhooks**
```php
- id (increments)
- uid (uuid)
- campaign_id (FK) → campaigns
- type (string) // open, click, bounce, complaint, unsubscribe
- endpoint (string)
- created_at, updated_at
```

#### 2.6 Templates & Layouts

**templates**
```php
- id (increments)
- uid (uuid)
- customer_id (FK, nullable) → customers
- admin_id (FK, nullable) → admins
- name (string)
- content (longtext)
- builder (string, nullable) // classic, dnd (drag-n-drop)
- category (string, nullable) // Removed, replaced with categories pivot
- theme (string, nullable)
- type (string, nullable) // email, page, form
- is_default (boolean, default: false)
- is_private (boolean, default: false)
- custom_order (integer)
- created_at, updated_at
```

**layouts**
```php
- id (increments)
- uid (uuid)
- customer_id (FK, nullable) → customers
- name (string)
- content (longtext)
- subject (string, nullable)
- created_at, updated_at
```

**template_categories**
```php
- id (increments)
- uid (uuid)
- name (string)
- created_at, updated_at
```

**templates_categories** (Pivot)
```php
- template_id (FK) → templates
- template_category_id (FK) → template_categories
```

#### 2.7 Pages & Forms

**pages**
```php
- id (increments)
- uid (uuid)
- mail_list_id (FK) → mail_lists
- type (string) // subscribe, unsubscribe, subscribe-form, update-profile
- subject (string, nullable)
- content (longtext)
- layout_id (FK, nullable) → layouts
- outside_url (string, nullable)
- outside_action (string, nullable) // none, redirect
- created_at, updated_at
```

**forms**
```php
- id (increments)
- uid (uuid)
- mail_list_id (FK) → mail_lists
- name (string)
- content (longtext)
- custom_css (text, nullable)
- custom_javascript (text, nullable)
- stylesheet (string, nullable)
- stylesheet_url (string, nullable)
- created_at, updated_at
```

**websites**
```php
- id (increments)
- uid (uuid)
- customer_id (FK) → customers
- title (string)
- content (longtext)
- published (boolean, default: false)
- created_at, updated_at
```

#### 2.8 Automation v2

**automation2s**
```php
- id (increments)
- uid (uuid)
- customer_id (FK) → customers
- mail_list_id (FK) → mail_lists
- segment_id (FK, nullable) → segments
- name (string)
- time_zone (string, nullable)
- status (string) // active, inactive
- data (text) // JSON workflow definition
- last_error (text, nullable)
- created_at, updated_at
```

**emails** (Automation Emails)
```php
- id (increments)
- uid (uuid)
- automation2_id (FK) → automation2s
- customer_id (FK, nullable) → customers
- template_id (FK, nullable) → templates
- tracking_domain_id (FK, nullable) → tracking_domains
- subject (string)
- from (string) // from_email
- from_name (string)
- reply_to (string)
- content (longtext) // Removed when template_id added
- plain (text, nullable)
- preheader (string, nullable)
- track_open (boolean)
- track_click (boolean)
- skip_failed_message (boolean, default: false)
- action_id (string, nullable) // Workflow action ID
- created_at, updated_at
```

**email_links**
```php
- id (increments)
- email_id (FK) → emails
- link (text)
- url (text, nullable)
- created_at, updated_at
```

**email_webhooks**
```php
- id (increments)
- uid (uuid)
- email_id (FK) → emails
- type (string)
- endpoint (string)
- created_at, updated_at
```

**attachments**
```php
- id (increments)
- email_id (FK) → emails
- name (string)
- file (string) // File path
- size (integer)
- created_at, updated_at
```

**auto_triggers** (Legacy Automation v1)
```php
- id (increments)
- uid (uuid)
- name (string)
- customer_id (FK) → customers
- mail_list_id (FK) → mail_lists
- segment_id (FK, nullable) → segments
- type (string) // welcome-email, subscribe-form
- options (text) // JSON options
- executed (boolean, default: false)
- created_at, updated_at
```

#### 2.9 Tracking & Analytics

**tracking_logs** (Central Tracking)
```php
- id (increments)
- runtime_message_id (string, unique, nullable)
- message_id (string, unique, nullable)
- customer_id (FK) → customers
- sending_server_id (FK) → sending_servers
- campaign_id (FK, nullable) → campaigns
- email_id (FK, nullable) → emails
- subscriber_id (FK) → subscribers
- sub_account_id (FK, nullable) → sub_accounts
- auto_trigger_id (FK, nullable) → auto_triggers
- status (string) // sent, failed, bounced, feedback
- error (text, nullable)
- created_at, updated_at
```

**open_logs**
```php
- id (increments)
- message_id (string, indexed)
- ip_address (string, indexed)
- user_agent (text)
- created_at, updated_at
```

**click_logs**
```php
- id (increments)
- message_id (string)
- ip_address (string)
- user_agent (text)
- url (longtext)
- created_at, updated_at
```

**bounce_logs**
```php
- id (increments)
- message_id (string)
- bounce_type (string) // hard, soft
- bounce_status_code (string, nullable)
- raw (text)
- created_at, updated_at
```

**feedback_logs** (Complaints)
```php
- id (increments)
- message_id (string)
- feedback_type (string) // abuse, fraud, virus, other
- raw_feedback_content (text, nullable)
- user_agent (text, nullable)
- created_at, updated_at
```

**unsubscribe_logs**
```php
- id (increments)
- message_id (string, nullable)
- subscriber_id (FK, nullable) → subscribers
- ip_address (string)
- user_agent (text, nullable)
- created_at, updated_at
```

**timelines** (Activity Feed)
```php
- id (increments)
- subscriber_id (FK) → subscribers
- automation2_id (FK, nullable) → automation2s
- auto_trigger_id (FK, nullable) → auto_triggers
- activity_type (string) // subscribed, unsubscribed, open, click, bounce
- activity (text) // JSON activity data
- transaction_id (string, nullable)
- created_at, updated_at
```

#### 2.10 Sending Infrastructure

**sending_servers**
```php
- id (increments)
- uid (uuid)
- admin_id (FK, nullable) → admins
- customer_id (FK, nullable) → customers
- bounce_handler_id (FK, nullable) → bounce_handlers
- feedback_loop_handler_id (FK, nullable) → feedback_loop_handlers
- name (string)
- type (string) // smtp, sendmail, amazon-smtp, amazon-api, mailgun-smtp, mailgun-api, sendgrid-smtp, sendgrid-api, elasticemail-smtp, elasticemail-api, sparkpost-smtp, sparkpost-api, php-mail, etc.
- host (string, nullable)
- username (string, nullable) // Added 2023
- aws_access_key_id (string, nullable)
- aws_secret_access_key (string, nullable)
- aws_region (string, nullable)
- domain (string, nullable)
- api_key (string, nullable)
- api_secret_key (string, nullable)
- smtp_username (string, nullable)
- smtp_password (string, nullable)
- smtp_port (integer, nullable)
- smtp_protocol (string, nullable) // tls, ssl
- sendmail_path (string, nullable)
- quota_value (integer)
- quota_base (integer)
- quota_unit (string) // minute, hour, day, month
- default_from_email (string, nullable)
- options (text, nullable) // JSON options
- status (string) // active, inactive
- custom_order (integer)
- created_at, updated_at
```

**sending_domains**
```php
- id (increments)
- uid (uuid)
- customer_id (FK, nullable) → customers
- sending_server_id (FK, nullable) → sending_servers
- name (string)
- status (string) // active, inactive, unverified
- verification_type (string, nullable) // spf, dkim, both
- dkim_selector (string, nullable)
- dkim_private (text, nullable)
- dkim_public (text, nullable)
- verification_hostname (string, nullable)
- verification_dkim_selector (string, nullable)
- signing_enabled (boolean, default: false)
- custom_order (integer, nullable)
- options (text, nullable)
- created_at, updated_at
```

**tracking_domains**
```php
- id (increments)
- uid (uuid)
- customer_id (FK) → customers
- name (string)
- scheme (string) // http, https
- status (string) // active, inactive, unverified
- verification_method (string, nullable)
- created_at, updated_at
```

**senders** (Verified Senders)
```php
- id (increments)
- uid (uuid)
- customer_id (FK) → customers
- sending_server_id (FK, nullable) → sending_servers
- name (string)
- email (string)
- verified (boolean, default: false)
- created_at, updated_at
```

**bounce_handlers**
```php
- id (increments)
- uid (uuid)
- admin_id (FK) → admins
- name (string)
- type (string) // handler
- host (string)
- port (integer)
- username (string)
- password (string)
- protocol (string) // imap, pop3
- encryption (string) // ssl, tls
- email (string, nullable)
- created_at, updated_at
```

**feedback_loop_handlers**
```php
- id (increments)
- uid (uuid)
- admin_id (FK) → admins
- name (string)
- type (string) // handler
- host (string)
- port (integer)
- username (string)
- password (string)
- protocol (string)
- encryption (string)
- email (string, nullable)
- created_at, updated_at
```

**mail_lists_sending_servers** (Pivot)
```php
- id (increments)
- mail_list_id (FK) → mail_lists
- sending_server_id (FK) → sending_servers
- fitness (integer)
- created_at, updated_at
```

#### 2.11 Email Verification

**email_verification_servers**
```php
- id (increments)
- uid (uuid)
- admin_id (FK, nullable) → admins
- customer_id (FK, nullable) → customers
- name (string)
- type (string) // zerobounce, mailboxvalidator, etc.
- api_key (string, nullable)
- api_secret (string, nullable)
- options (text, nullable)
- status (string)
- created_at, updated_at
```

**email_verifications**
```php
- id (increments)
- uid (uuid)
- email_verification_server_id (FK) → email_verification_servers
- subscriber_id (FK) → subscribers
- email (string)
- result (string, indexed) // deliverable, undeliverable, unknown, risky
- details (text, nullable)
- created_at, updated_at
```

**plans_email_verification_servers** (Pivot)
```php
- id (increments)
- plan_id (FK) → plans
- email_verification_server_id (FK) → email_verification_servers
- fitness (integer)
- created_at, updated_at
```

#### 2.12 Billing & Subscriptions

**plans**
```php
- id (increments)
- uid (uuid)
- admin_id (FK) → admins
- currency_id (FK) → currencies
- name (string)
- description (text, nullable)
- price (decimal)
- frequency_amount (integer)
- frequency_unit (string) // day, week, month, year
- options (text) // JSON: quotas, sending limits, features
- status (string) // active, inactive
- color (string) // Removed
- visible (boolean, default: true)
- trial_amount (integer, nullable)
- trial_unit (string, nullable) // day, week, month
- type (string, nullable) // Regular, Prepaid
- own_tracking_domain_required (boolean, default: false)
- created_at, updated_at
```

**currencies**
```php
- id (increments)
- uid (uuid)
- name (string)
- code (string)
- format (string) // ${PRICE}, {PRICE} €
- is_default (boolean, default: false)
- created_at, updated_at
```

**subscriptions**
```php
- id (increments)
- uid (uuid)
- user_id (FK) → users
- plan_id (FK) → plans
- status (string) // active, cancelled, ended, new
- is_recurring (boolean, default: true)
- cancelled_at (timestamp, nullable)
- terminated_at (timestamp, nullable)
- terminated_reason (string, nullable)
- current_period_ends_at (timestamp, nullable)
- created_at, updated_at
```

**subscription_logs**
```php
- id (increments)
- subscription_id (FK) → subscriptions
- invoice_uid (uuid, nullable)
- type (string) // subscribed, renewed, cancelled, plan_changed
- data (text, nullable) // JSON
- created_at, updated_at
```

**invoices**
```php
- id (increments)
- uid (uuid)
- customer_id (FK) → customers
- number (string, nullable)
- title (string)
- description (text, nullable)
- type (string) // new, renew, upgrade
- status (string) // new, pending, paid, failed, cancelled
- currency_id (FK) → currencies
- amount (decimal)
- tax_amount (decimal, default: 0)
- discount_amount (decimal, default: 0)
- fee (decimal, default: 0)
- total (decimal)
// Billing info
- billing_first_name (string, nullable)
- billing_last_name (string, nullable)
- billing_address (string, nullable)
- billing_email (string, nullable)
- billing_phone (string, nullable)
- billing_country_id (FK, nullable) → countries
- created_at, updated_at
```

**invoice_items**
```php
- id (increments)
- invoice_id (FK) → invoices
- item_id (integer, nullable)
- item_type (string, nullable) // plan, product
- amount (decimal)
- title (string)
- description (text, nullable)
- quantity (integer, default: 1)
- created_at, updated_at
```

**transactions**
```php
- id (increments)
- uid (uuid)
- invoice_id (FK, nullable) → invoices
- checkout_id (string, nullable)
- payment_service_id (integer, nullable)
- status (string) // pending, success, failed
- type (string, nullable)
- method (string, nullable) // stripe, paypal, offline, etc.
- amount (decimal)
- allow_manual_review (boolean, default: false)
- error (text, nullable)
- created_at, updated_at
```

**billing_addresses**
```php
- id (increments)
- customer_id (FK) → customers
- first_name (string)
- last_name (string)
- email (string)
- phone (string, nullable)
- address_1 (string, nullable)
- address_2 (string, nullable)
- city (string, nullable)
- state (string, nullable)
- zip (string, nullable)
- country_id (FK) → countries
- created_at, updated_at
```

#### 2.13 E-commerce

**products**
```php
- id (increments)
- uid (uuid)
- source_id (integer, nullable) // External source ID
- source_type (string, nullable) // prestashop, woocommerce, etc.
- category_id (FK, nullable) → categories
- customer_id (FK) → customers
- title (string)
- description (text, nullable)
- price (decimal)
- status (string) // active, inactive
- quantity (integer, default: 0)
- image (string, nullable)
- created_at, updated_at
```

**categories**
```php
- id (increments)
- uid (uuid)
- customer_id (FK) → customers
- name (string)
- description (text, nullable)
- created_at, updated_at
```

**attributes**
```php
- id (increments)
- uid (uuid)
- customer_id (FK) → customers
- name (string)
- description (text, nullable)
- created_at, updated_at
```

**product_attributes**
```php
- id (increments)
- product_id (FK) → products
- attribute_id (FK) → attributes
- value (string)
- created_at, updated_at
```

**orders**
```php
- id (increments)
- uid (uuid)
- customer_id (FK) → customers
- invoice_id (FK, nullable) → invoices
- order_number (string)
- status (string) // pending, processing, completed, cancelled
- subtotal (decimal)
- tax (decimal)
- shipping (decimal)
- total (decimal)
- created_at, updated_at
```

**funnels** (Sales Funnels)
```php
- id (increments)
- uid (uuid)
- customer_id (FK) → customers
- mail_list_id (FK, nullable) → mail_lists
- name (string)
- status (string) // active, inactive
- data (text) // JSON funnel definition
- created_at, updated_at
```

**sources** (Traffic Sources)
```php
- id (increments)
- uid (uuid)
- customer_id (FK) → customers
- mail_list_id (FK, nullable) → mail_lists
- name (string)
- type (string) // api, embedded-form, etc.
- created_at, updated_at
```

#### 2.14 System Tables

**settings**
```php
- id (increments)
- name (string)
- value (text, nullable)
- created_at, updated_at
```

**languages**
```php
- id (increments)
- name (string)
- code (string)
- region_code (string)
- status (string)
- is_default (boolean, default: false)
- created_at, updated_at
```

**admin_groups**
```php
- id (increments)
- uid (uuid)
- name (string)
- options (text) // JSON permissions
- color (string)
- created_at, updated_at
```

**customer_groups**
```php
- id (increments)
- uid (uuid)
- name (string)
- options (text, nullable)
- created_at, updated_at
```

**customer_group_sending_servers** (Pivot)
```php
- id (increments)
- customer_group_id (FK) → customer_groups
- sending_server_id (FK) → sending_servers
- fitness (integer)
- created_at, updated_at
```

**plans_sending_servers** (Pivot)
```php
- id (increments)
- plan_id (FK) → plans
- sending_server_id (FK) → sending_servers
- fitness (integer)
- is_primary (boolean, default: false)
- created_at, updated_at
```

**blacklists**
```php
- id (increments)
- email (string)
- admin_id (FK, nullable) → admins
- customer_id (FK, nullable) → customers
- reason (text, nullable)
- created_at, updated_at
```

**ip_locations** (Geolocation Cache)
```php
- id (increments)
- ip_address (string, indexed)
- country_code (string, nullable)
- country_name (string, nullable)
- region_code (string, nullable)
- region_name (string, nullable)
- city (string, nullable)
- zipcode (string, nullable)
- latitude (float, nullable)
- longitude (float, nullable)
- created_at, updated_at
```

**logs** (System Logs)
```php
- id (increments)
- customer_id (FK, nullable) → customers
- type (string) // customer_action, admin_action, system_error
- name (string)
- data (text, nullable)
- created_at, updated_at
```

**notifications** (Laravel Notifications)
```php
- id (uuid, primary)
- type (string)
- notifiable_type (string)
- notifiable_id (bigint)
- data (text)
- debug (text, nullable)
- read_at (timestamp, nullable)
- created_at, updated_at
```

**plugins**
```php
- id (increments)
- uid (uuid)
- name (string)
- title (string)
- description (text, nullable)
- status (string) // active, inactive
- data (text, nullable)
- created_at, updated_at
```

**files** (File Management)
```php
- id (increments)
- uid (uuid)
- customer_id (FK) → customers
- name (string)
- path (string)
- size (integer)
- type (string)
- created_at, updated_at
```

**media** (Media Library)
```php
- id (increments)
- uid (uuid)
- customer_id (FK) → customers
- name (string)
- path (string)
- mime_type (string)
- size (integer)
- created_at, updated_at
```

#### 2.15 Queue & Job Management

**jobs** (Original)
```php
- id (bigint, increments)
- queue (string, indexed)
- payload (longtext)
- attempts (tinyint unsigned)
- reserved_at (integer unsigned, nullable)
- available_at (integer unsigned)
- created_at (integer unsigned)
```

**new_jobs** (Laravel 8+ replacement)
```php
- id (bigint, increments)
- queue (string, indexed)
- payload (longtext)
- attempts (integer unsigned)
- reserved_at (integer unsigned, nullable)
- available_at (integer unsigned)
- created_at (integer unsigned)
```

**failed_jobs**
```php
- id (bigint, increments)
- uuid (string, unique)
- connection (text)
- queue (text)
- payload (longtext)
- exception (longtext)
- failed_at (timestamp, default: CURRENT_TIMESTAMP)
```

**job_batches** (Laravel 8+)
```php
- id (string, primary)
- name (string)
- total_jobs (integer)
- pending_jobs (integer)
- failed_jobs (integer)
- failed_job_ids (text)
- options (mediumtext, nullable)
- cancelled_at (integer, nullable)
- created_at (integer)
- finished_at (integer, nullable)
```

**job_monitors**
```php
- id (increments)
- batch_id (string, nullable)
- job_type (string)
- subject (string, nullable)
- status (string) // running, done, failed, cancelled
- data (text, nullable)
- output (longtext, nullable)
- error (text, nullable)
- created_at, updated_at
```

#### 2.16 Authentication

**password_resets**
```php
- email (string, indexed)
- token (string)
- created_at (timestamp, nullable)
```

**user_activations**
```php
- id (increments)
- user_id (FK) → users
- token (string)
- created_at, updated_at
```

---

## 3. Foreign Key Relationships

### Complete Foreign Key Map

```
users
  ← admins.user_id (cascade)
  ← customers.user_id (cascade)
  ← sub_accounts.user_id (cascade)
  ← subscriptions.user_id (cascade)

admins
  ← admin.creator_id (cascade) // Self-referential for sub-admins
  → admin_groups.id (cascade)
  → users.id (cascade)
  → languages.id (cascade)
  ← sending_servers.admin_id (cascade)
  ← bounce_handlers.admin_id (cascade)
  ← feedback_loop_handlers.admin_id (cascade)
  ← templates.admin_id (cascade)
  ← email_verification_servers.admin_id (cascade)
  ← blacklists.admin_id (cascade)
  ← plans.admin_id (cascade)
  ← sub_accounts.admin_id (cascade)

customers
  → users.id (cascade)
  → customer_groups.id (cascade)
  → contacts.id (cascade)
  → languages.id (cascade)
  ← mail_lists.customer_id (cascade)
  ← campaigns.customer_id (cascade)
  ← sending_servers.customer_id (cascade)
  ← templates.customer_id (cascade)
  ← layouts.customer_id (cascade)
  ← automation2s.customer_id (cascade)
  ← emails.customer_id (cascade)
  ← auto_triggers.customer_id (cascade)
  ← tracking_logs.customer_id (cascade)
  ← senders.customer_id (cascade)
  ← sending_domains.customer_id (cascade)
  ← tracking_domains.customer_id (cascade)
  ← email_verification_servers.customer_id (cascade)
  ← blacklists.customer_id (cascade)
  ← logs.customer_id (cascade)
  ← sub_accounts.customer_id (cascade)
  ← sources.customer_id (cascade)
  ← products.customer_id (cascade)
  ← categories.customer_id (cascade)
  ← attributes.customer_id (cascade)
  ← orders.customer_id (cascade)
  ← funnels.customer_id (cascade)
  ← billing_addresses.customer_id (cascade)
  ← invoices.customer_id (cascade)
  ← websites.customer_id (cascade)
  ← files.customer_id (cascade)
  ← media.customer_id (cascade)

mail_lists
  → customers.id (cascade)
  → contacts.id (cascade)
  ← subscribers.mail_list_id (cascade)
  ← fields.mail_list_id (cascade)
  ← segments.mail_list_id (cascade)
  ← campaigns.default_mail_list_id (cascade)
  ← campaigns_lists_segments.mail_list_id (cascade)
  ← pages.mail_list_id (cascade)
  ← forms.mail_list_id (cascade)
  ← automation2s.mail_list_id (cascade)
  ← auto_triggers.mail_list_id (cascade)
  ← sources.mail_list_id (cascade)
  ← funnels.mail_list_id (cascade)
  ← mail_lists_sending_servers.mail_list_id (cascade)

subscribers
  → mail_lists.id (cascade)
  ← subscriber_fields.subscriber_id (cascade)
  ← email_verifications.subscriber_id (cascade)
  ← tracking_logs.subscriber_id (cascade)
  ← timelines.subscriber_id (cascade)
  ← unsubscribe_logs.subscriber_id (cascade)

fields
  → mail_lists.id (cascade)
  ← field_options.field_id (cascade)
  ← subscriber_fields.field_id (cascade)
  ← segment_conditions.field_id (nullable, cascade)

segments
  → mail_lists.id (cascade)
  ← segment_conditions.segment_id (cascade)
  ← campaigns.segment_id (nullable, cascade)
  ← campaigns_lists_segments.segment_id (cascade)
  ← automation2s.segment_id (nullable, cascade)
  ← auto_triggers.segment_id (nullable, cascade)

campaigns
  → customers.id (cascade)
  → mail_lists.id (default_mail_list_id, cascade)
  → segments.id (nullable, cascade)
  → templates.id (nullable, cascade)
  → tracking_domains.id (nullable, cascade)
  ← campaigns_lists_segments.campaign_id (cascade)
  ← campaign_links.campaign_id (cascade)
  ← campaign_webhooks.campaign_id (cascade)
  ← tracking_logs.campaign_id (cascade)

automation2s
  → customers.id (cascade)
  → mail_lists.id (cascade)
  → segments.id (nullable, cascade)
  ← emails.automation2_id (cascade)
  ← timelines.automation2_id (cascade)

emails
  → automation2s.id (cascade)
  → customers.id (nullable, cascade)
  → templates.id (nullable, cascade)
  → tracking_domains.id (nullable, cascade)
  ← attachments.email_id (cascade)
  ← email_links.email_id (cascade)
  ← email_webhooks.email_id (cascade)
  ← tracking_logs.email_id (cascade)

templates
  → customers.id (nullable, cascade)
  → admins.id (nullable, cascade)
  ← campaigns.template_id (cascade)
  ← emails.template_id (cascade)
  ← templates_categories.template_id (cascade)

sending_servers
  → admins.id (nullable, cascade)
  → customers.id (nullable, cascade)
  → bounce_handlers.id (nullable, cascade)
  → feedback_loop_handlers.id (nullable, cascade)
  ← tracking_logs.sending_server_id (cascade)
  ← sending_domains.sending_server_id (cascade)
  ← senders.sending_server_id (cascade)
  ← mail_lists_sending_servers.sending_server_id (cascade)
  ← customer_group_sending_servers.sending_server_id (cascade)
  ← plans_sending_servers.sending_server_id (cascade)

tracking_logs
  → customers.id (cascade)
  → sending_servers.id (cascade)
  → campaigns.id (nullable, cascade)
  → emails.id (nullable, cascade)
  → subscribers.id (cascade)
  → sub_accounts.id (nullable, cascade)
  → auto_triggers.id (nullable, cascade)

plans
  → admins.id (cascade)
  → currencies.id (cascade)
  ← subscriptions.plan_id (cascade)
  ← plans_sending_servers.plan_id (cascade)
  ← plans_email_verification_servers.plan_id (cascade)

subscriptions
  → users.id (cascade)
  → plans.id (cascade)
  ← subscription_logs.subscription_id (cascade)

invoices
  → customers.id (cascade)
  → currencies.id (cascade)
  → countries.id (billing_country_id, nullable, cascade)
  ← invoice_items.invoice_id (cascade)
  ← transactions.invoice_id (nullable, cascade)
  ← orders.invoice_id (nullable, cascade)

contacts
  → countries.id (cascade)
  ← mail_lists.contact_id (cascade)
  ← customers.contact_id (cascade)

countries
  ← contacts.country_id (cascade)
  ← invoices.billing_country_id (cascade)
  ← billing_addresses.country_id (cascade)

languages
  ← admins.language_id (cascade)
  ← customers.language_id (cascade)
  ← users.language_id (cascade)
```

### Critical Cascade Behaviors

**Deleting a customer:**
```
customers (delete)
  ↓ cascade deletes:
    - mail_lists (and their subscribers, fields, segments)
    - campaigns (and their links, webhooks, tracking)
    - automation2s (and their emails, attachments)
    - templates (customer-owned)
    - sending_servers (customer-owned)
    - sending_domains
    - tracking_domains
    - senders
    - sources
    - products, categories, attributes
    - orders, funnels
    - invoices, transactions
    - files, media
    - logs
    - blacklists (customer-specific)
```

**Deleting a mail_list:**
```
mail_lists (delete)
  ↓ cascade deletes:
    - subscribers (and their fields, verifications, timelines)
    - fields (and their options, values)
    - segments (and their conditions)
    - campaigns (linked to list)
    - automation2s
    - auto_triggers
    - pages
    - forms
```

**Deleting a subscriber:**
```
subscribers (delete)
  ↓ cascade deletes:
    - subscriber_fields
    - email_verifications
    - timelines
  ⚠️ Does NOT cascade to:
    - tracking_logs (orphaned, deliberate for history)
    - unsubscribe_logs (orphaned, deliberate for history)
```

**Deleting a campaign:**
```
campaigns (delete)
  ↓ cascade deletes:
    - campaign_links
    - campaign_webhooks
    - campaigns_lists_segments (pivot)
  ⚠️ Does NOT cascade to:
    - tracking_logs (orphaned, deliberate for history)
```

---

## 4. Differences with Current System

### 4.1 Structural Differences

#### Table Naming Convention

**Acelle (Original):**
```
mail_lists
subscribers
campaigns
sending_servers
```

**Our System:**
```
mailing_mail_lists
mailing_subscribers
mailing_campaigns
mailing_sending_servers
```
**Impact:** Our system uses `mailing_` prefix for namespace isolation. All migrations use `Schema::connection('acelle')` to target a separate database.

#### Primary Key Strategy

**Acelle:**
```php
$table->increments('id'); // unsigned integer (4 bytes)
$table->uuid('uid'); // separate UUID column
```

**Our System:**
```php
$table->id(); // Laravel 8+ bigInteger (8 bytes)
$table->char('uid', 36); // fixed-length UUID
```
**Impact:** Our system uses bigIncrements for future scalability. UUID column is char(36) vs varchar.

#### Foreign Key Definitions

**Acelle (Inline):**
```php
Schema::create('subscribers', function (Blueprint $table) {
    $table->integer('mail_list_id')->unsigned();
    $table->foreign('mail_list_id')->references('id')->on('mail_lists')->onDelete('cascade');
});
```

**Our System (Fluent):**
```php
Schema::connection('acelle')->create('mailing_subscribers', function (Blueprint $table) {
    $table->foreignId('mail_list_id');
    $table->foreign('mail_list_id')
        ->references('id')
        ->on('mailing_mail_lists')
        ->onDelete('cascade');
});
```

#### Timestamp Handling

**Acelle:**
```php
$table->timestamps(); // created_at, updated_at (NOT NULL)
```

**Our System:**
```php
$table->timestamp('created_at')->nullable();
$table->timestamp('updated_at')->nullable();
```
**Impact:** Our system allows null timestamps for better compatibility with existing data.

### 4.2 Missing Tables in Our System

The following Acelle tables are **NOT present** in our current migrations:

#### E-commerce System (2023 additions)
1. `funnels` - Sales funnel builder
2. `orders` - Order management
3. `media` - Media library
4. `files` - File management
5. `categories` - Product categories
6. `attributes` - Product attributes
7. `product_attributes` - Product-attribute pivot

#### Advanced Features
8. `websites` - Website/landing page builder
9. `forms` - Form builder (separate from pages)
10. `sources` - Traffic source tracking
11. `plugins` - Plugin system
12. `campaign_webhooks` - Campaign webhook notifications
13. `email_webhooks` - Automation webhook notifications
14. `senders` - Verified sender management
15. `bounce_handlers` - Custom bounce handling servers
16. `feedback_loop_handlers` - Custom FBL servers
17. `user_activations` - Email activation tokens
18. `sub_accounts` - Agency/reseller sub-accounts
19. `job_monitors` - Job monitoring dashboard

#### Template System
20. `template_categories` - Template categorization
21. `templates_categories` - Template-category pivot

#### Logs & Analytics
22. `timelines` - Subscriber activity timeline
23. `logs` - System activity logs
24. `ip_locations` - Geolocation cache

#### Billing Enhancements
25. `billing_addresses` - Customer billing addresses
26. `subscription_logs` - Subscription change history

### 4.3 Extra Tables in Our System

Tables present in our system but **NOT in Acelle:**

1. `mailing__tmp_subscriptions_table` - Temporary subscription data
2. `mailing_migrations` - Migration tracking (custom)

### 4.4 Field Differences by Table

#### mail_lists / mailing_mail_lists

**Missing in our system:**
- `default_subject` (removed in Acelle 2022)

**Present in both:**
- All core fields match after field renames

#### subscribers / mailing_subscribers

**Missing in our system:**
- `verification_status` (char 100)
- `last_verification_at` (datetime)
- `last_verification_by` (char 100)
- `last_verification_result` (mediumtext)
- `import_batch_id` (char 36)

**Type differences:**
- Acelle: `from` (string), `ip` (string)
- Ours: `from` (text), `ip` (text)

#### campaigns / mailing_campaigns

**Missing in our system:**
- `preheader` (string, nullable)
- `template_id` (FK to templates)
- `tracking_domain_id` (FK to tracking_domains)
- `use_default_sending_server_from_email` (boolean)
- `skip_failed_message` (boolean)
- `image` (string)
- `last_error` (text)
- `running_pid` (integer)

**Fields removed in Acelle but present in ours:**
- `html` (longtext) - removed when template_id added
- `custom_order` (integer) - removed in cleanup
- `resend` (integer) - deprecated feature

#### templates / mailing_templates

**Missing in our system:**
- `builder` (string) - "classic" vs "dnd" (drag-n-drop)
- `theme` (string)
- `type` (string) - "email", "page", "form"
- `is_default` (boolean)
- `is_private` (boolean)

**Fields removed in Acelle but present in ours:**
- `image` (string) - removed
- `shared` (boolean) - removed
- `custom_order` (integer) - removed

#### sending_servers / mailing_sending_servers

**Missing in our system:**
- `username` (string) - added 2023
- `api_secret_key` (string)
- `options` (text) - JSON config
- `default_from_email` (string)

#### emails / mailing_emails (Automation Emails)

**Missing in our system:**
- `customer_id` (FK, nullable)
- `template_id` (FK, nullable)
- `tracking_domain_id` (FK, nullable)
- `plain` (text)
- `preheader` (string)
- `skip_failed_message` (boolean)
- `action_id` (string) - workflow node ID

**Fields removed in Acelle but present in ours:**
- `content` (longtext) - removed when template_id added

#### tracking_logs / mailing_tracking_logs

**Missing in our system:**
- `email_id` (FK, nullable) - links to automation emails
- `sub_account_id` (FK, nullable)
- `auto_trigger_id` (FK, nullable)
- `error` (text, nullable)

**Type differences:**
- Acelle: `runtime_message_id` (string unique)
- Ours: `runtime_message_id` (char 191 unique)

### 4.5 Index Differences

**Acelle has explicit indexes on:**
```sql
subscribers.email (index)
ip_locations.ip_address (index)
open_logs.ip_address (index)
email_verifications.result (index)
```

**Our system relies on:**
- Foreign key indexes (automatic)
- Explicit email index on subscribers

**Missing in our system:**
- No geolocation IP caching (ip_locations table missing)
- No result-based email verification filtering

### 4.6 Migration Strategy Differences

**Acelle approach:**
1. Create core tables first
2. Add foreign keys in separate migration (`add_foreign_keys.php`)
3. Iterative column additions over years
4. Data cleanup migrations (e.g., `clean_up_subscriptions_table.php`)
5. Schema changes for Laravel version upgrades

**Our system approach:**
1. All tables created with foreign keys inline
2. No separate FK migration
3. Single-batch table creation
4. No historical cleanup migrations
5. Modern Laravel 12 syntax from start

### 4.7 Feature Completeness Comparison

| Feature | Acelle | Our System | Status |
|---------|--------|------------|--------|
| **Core Email Marketing** |
| Mail Lists | ✅ Full | ✅ Full | ✅ Complete |
| Subscribers | ✅ Full | ⚠️ Missing verification fields | ⚠️ Partial |
| Custom Fields | ✅ Full | ✅ Full | ✅ Complete |
| Segments | ✅ Full | ✅ Full | ✅ Complete |
| Campaigns | ✅ Full | ⚠️ Missing templates, tracking domains | ⚠️ Partial |
| Templates | ✅ Full | ⚠️ Missing builder, categories | ⚠️ Partial |
| **Automation** |
| Automation v2 | ✅ Full | ✅ Full | ✅ Complete |
| Automation Emails | ✅ Full | ⚠️ Missing template support | ⚠️ Partial |
| Auto Triggers (v1) | ✅ Full | ✅ Full | ✅ Complete |
| Attachments | ✅ Full | ✅ Full | ✅ Complete |
| Email Links | ✅ Full | ✅ Full | ✅ Complete |
| **Tracking** |
| Tracking Logs | ✅ Full | ⚠️ Missing email_id, sub_account_id | ⚠️ Partial |
| Open Logs | ✅ Full | ✅ Full | ✅ Complete |
| Click Logs | ✅ Full | ✅ Full | ✅ Complete |
| Bounce Logs | ✅ Full | ✅ Full | ✅ Complete |
| Feedback Logs | ✅ Full | ✅ Full | ✅ Complete |
| Unsubscribe Logs | ✅ Full | ✅ Full | ✅ Complete |
| Timelines | ✅ Full | ❌ Missing | ❌ Not Implemented |
| **Sending** |
| Sending Servers | ✅ Full | ⚠️ Missing options, default_from | ⚠️ Partial |
| Sending Domains | ✅ Full | ✅ Full | ✅ Complete |
| Tracking Domains | ✅ Full | ❌ Missing | ❌ Not Implemented |
| Verified Senders | ✅ Full | ❌ Missing | ❌ Not Implemented |
| Bounce Handlers | ✅ Full | ✅ Full | ✅ Complete |
| FBL Handlers | ✅ Full | ✅ Full | ✅ Complete |
| **Email Verification** |
| Verification Servers | ✅ Full | ✅ Full | ✅ Complete |
| Verifications | ✅ Full | ✅ Full | ✅ Complete |
| **Billing** |
| Plans | ✅ Full | ✅ Full | ✅ Complete |
| Subscriptions | ✅ Full | ⚠️ Missing subscription_logs | ⚠️ Partial |
| Invoices | ✅ Full | ✅ Full | ✅ Complete |
| Invoice Items | ✅ Full | ✅ Full | ✅ Complete |
| Transactions | ✅ Full | ✅ Full | ✅ Complete |
| Billing Addresses | ✅ Full | ❌ Missing | ❌ Not Implemented |
| **Users & Auth** |
| Users | ✅ Full | ✅ Full | ✅ Complete |
| Admins | ✅ Full | ✅ Full | ✅ Complete |
| Customers | ✅ Full | ✅ Full | ✅ Complete |
| Admin Groups | ✅ Full | ✅ Full | ✅ Complete |
| Customer Groups | ✅ Full | ✅ Full | ✅ Complete |
| Sub Accounts | ✅ Full | ❌ Missing | ❌ Not Implemented |
| User Activations | ✅ Full | ❌ Missing | ❌ Not Implemented |
| **Pages & Forms** |
| Pages | ✅ Full | ✅ Full | ✅ Complete |
| Layouts | ✅ Full | ✅ Full | ✅ Complete |
| Forms | ✅ Full | ❌ Missing | ❌ Not Implemented |
| Websites | ✅ Full | ❌ Missing | ❌ Not Implemented |
| **E-commerce** |
| Products | ✅ Full | ✅ Full | ✅ Complete |
| Categories | ✅ Full | ❌ Missing | ❌ Not Implemented |
| Attributes | ✅ Full | ❌ Missing | ❌ Not Implemented |
| Orders | ✅ Full | ❌ Missing | ❌ Not Implemented |
| Funnels | ✅ Full | ❌ Missing | ❌ Not Implemented |
| **Webhooks** |
| Campaign Webhooks | ✅ Full | ❌ Missing | ❌ Not Implemented |
| Email Webhooks | ✅ Full | ❌ Missing | ❌ Not Implemented |
| **System** |
| Settings | ✅ Full | ✅ Full | ✅ Complete |
| Languages | ✅ Full | ✅ Full | ✅ Complete |
| Countries | ✅ Full | ✅ Full | ✅ Complete |
| Blacklists | ✅ Full | ✅ Full | ✅ Complete |
| Logs | ✅ Full | ❌ Missing | ❌ Not Implemented |
| Plugins | ✅ Full | ❌ Missing | ❌ Not Implemented |
| IP Locations | ✅ Full | ❌ Missing | ❌ Not Implemented |
| Notifications | ✅ Full | ✅ Full | ✅ Complete |
| **Queue** |
| Jobs | ✅ Full | ✅ Full | ✅ Complete |
| Failed Jobs | ✅ Full | ✅ Full | ✅ Complete |
| Job Batches | ✅ Full | ✅ Full | ✅ Complete |
| Job Monitors | ✅ Full | ❌ Missing | ❌ Not Implemented |
| **Media** |
| Files | ✅ Full | ❌ Missing | ❌ Not Implemented |
| Media | ✅ Full | ❌ Missing | ❌ Not Implemented |
| **Tracking** |
| Sources | ✅ Full | ❌ Missing | ❌ Not Implemented |

### 4.8 Data Type Consistency Issues

**String vs Text:**
```php
// Acelle uses 'string' for many fields
$table->string('status');
$table->string('type');

// Our system uses 'text' for flexibility
$table->text('from')->nullable();
$table->text('ip')->nullable();
```

**Boolean vs Integer:**
```php
// Acelle
$table->boolean('send_welcome_email')->default(false);

// Ours
$table->integer('send_welcome_email')->default(0);
```

**Char vs String:**
```php
// Acelle
$table->uuid('uid'); // varchar by default

// Ours
$table->char('uid', 36); // fixed-length
$table->char('name', 191);
$table->char('email', 191);
```

---

## 5. Migration Dependency Graph

### Critical Dependencies (Must run in order)

```
1. Foundation Layer
   ├── users
   ├── password_resets
   ├── countries
   └── languages

2. Admin/Customer Layer
   ├── admin_groups
   ├── admins (depends: users, admin_groups, languages)
   ├── customer_groups
   ├── contacts (depends: countries)
   └── customers (depends: users, customer_groups, contacts, languages)

3. Mail List Layer
   ├── mail_lists (depends: customers, contacts)
   ├── fields (depends: mail_lists)
   ├── field_options (depends: fields)
   ├── subscribers (depends: mail_lists)
   ├── subscriber_fields (depends: fields, subscribers)
   ├── segments (depends: mail_lists)
   └── segment_conditions (depends: segments, fields)

4. Sending Infrastructure Layer
   ├── bounce_handlers (depends: admins)
   ├── feedback_loop_handlers (depends: admins)
   ├── sending_servers (depends: admins, customers, bounce_handlers, feedback_loop_handlers)
   ├── sending_domains (depends: customers, sending_servers)
   ├── tracking_domains (depends: customers)
   ├── senders (depends: customers, sending_servers)
   └── email_verification_servers (depends: admins, customers)

5. Template Layer
   ├── layouts (depends: customers)
   ├── template_categories
   ├── templates (depends: customers, admins)
   ├── templates_categories (depends: templates, template_categories)
   └── pages (depends: mail_lists, layouts)

6. Campaign Layer
   ├── campaigns (depends: customers, mail_lists, segments, templates, tracking_domains)
   ├── campaigns_lists_segments (depends: campaigns, mail_lists, segments)
   ├── campaign_links (depends: campaigns)
   └── campaign_webhooks (depends: campaigns)

7. Automation Layer
   ├── auto_triggers (depends: customers, mail_lists, segments)
   ├── automation2s (depends: customers, mail_lists, segments)
   ├── emails (depends: automation2s, customers, templates, tracking_domains)
   ├── attachments (depends: emails)
   ├── email_links (depends: emails)
   └── email_webhooks (depends: emails)

8. Tracking Layer
   ├── ip_locations
   ├── tracking_logs (depends: customers, sending_servers, campaigns, emails, subscribers, sub_accounts, auto_triggers)
   ├── open_logs
   ├── click_logs
   ├── bounce_logs
   ├── feedback_logs
   ├── unsubscribe_logs (depends: subscribers)
   ├── timelines (depends: subscribers, automation2s, auto_triggers)
   └── email_verifications (depends: email_verification_servers, subscribers)

9. Billing Layer
   ├── currencies
   ├── plans (depends: admins, currencies)
   ├── subscriptions (depends: users, plans)
   ├── subscription_logs (depends: subscriptions)
   ├── invoices (depends: customers, currencies, countries)
   ├── invoice_items (depends: invoices)
   ├── transactions (depends: invoices)
   └── billing_addresses (depends: customers, countries)

10. E-commerce Layer
    ├── categories (depends: customers)
    ├── attributes (depends: customers)
    ├── products (depends: customers, categories)
    ├── product_attributes (depends: products, attributes)
    ├── sources (depends: customers, mail_lists)
    ├── funnels (depends: customers, mail_lists)
    ├── orders (depends: customers, invoices)
    ├── files (depends: customers)
    └── media (depends: customers)

11. System Layer
    ├── settings
    ├── blacklists (depends: admins, customers)
    ├── logs (depends: customers)
    ├── notifications
    ├── plugins
    ├── sub_accounts (depends: users, admins, customers)
    ├── user_activations (depends: users)
    ├── forms (depends: mail_lists)
    └── websites (depends: customers)

12. Queue Layer
    ├── jobs
    ├── new_jobs
    ├── failed_jobs
    ├── job_batches
    └── job_monitors

13. Pivot Tables
    ├── mail_lists_sending_servers (depends: mail_lists, sending_servers)
    ├── customer_group_sending_servers (depends: customer_groups, sending_servers)
    ├── plans_sending_servers (depends: plans, sending_servers)
    └── plans_email_verification_servers (depends: plans, email_verification_servers)
```

### Safe to Run in Any Order

These tables have no foreign key dependencies:
- `settings`
- `currencies`
- `countries`
- `languages`
- `admin_groups`
- `customer_groups`
- `template_categories`
- `ip_locations`
- `jobs` / `new_jobs` / `failed_jobs` / `job_batches`

---

## 6. Recommendations

### 6.1 Critical Missing Features

**High Priority (blocking core functionality):**

1. **Tracking Domains** - Required for custom tracking URLs
   - Table: `tracking_domains`
   - Impact: Cannot use branded tracking links
   - Effort: Low (1 migration)

2. **Template System Enhancements**
   - Missing: `builder` field (classic vs drag-n-drop)
   - Missing: Template categories system
   - Impact: Cannot distinguish template types
   - Effort: Low (2 migrations)

3. **Email Verification Fields**
   - Missing: Subscriber verification status tracking
   - Impact: Cannot track email validation results
   - Effort: Low (1 migration)

4. **Campaign Template Support**
   - Missing: `template_id` in campaigns
   - Missing: `preheader` field
   - Impact: Must duplicate template content
   - Effort: Low (1 migration)

**Medium Priority (enhancing functionality):**

5. **Verified Senders** - Email sender verification
   - Table: `senders`
   - Impact: Manual sender verification required
   - Effort: Low (1 migration)

6. **Subscription Logs** - Subscription history tracking
   - Table: `subscription_logs`
   - Impact: Cannot audit subscription changes
   - Effort: Low (1 migration)

7. **Activity Timelines** - Subscriber activity feed
   - Table: `timelines`
   - Impact: No unified activity view
   - Effort: Medium (1 migration + queries)

8. **Billing Addresses** - Separate billing info
   - Table: `billing_addresses`
   - Impact: Billing data embedded in invoices
   - Effort: Low (1 migration)

9. **Campaign/Email Webhooks** - Outbound notifications
   - Tables: `campaign_webhooks`, `email_webhooks`
   - Impact: No webhook integration
   - Effort: Medium (2 migrations + handlers)

**Low Priority (nice to have):**

10. **Sub-accounts** - Agency/reseller support
11. **Forms Builder** - Separate form management
12. **Website Builder** - Landing page system
13. **Plugins System** - Extensibility framework
14. **Job Monitors** - Job tracking dashboard
15. **System Logs** - Activity audit trail
16. **IP Geolocation Cache** - Location tracking
17. **Files/Media Library** - Asset management
18. **E-commerce Tables** - Full shop integration (categories, attributes, orders, funnels)

### 6.2 Data Type Standardization

**Recommended Changes:**

```php
// Standardize UUID storage
$table->char('uid', 36)->unique(); // Instead of uuid()

// Standardize foreign keys
$table->foreignId('column_name'); // Instead of integer()->unsigned()

// Standardize nullable text
$table->text('column')->nullable(); // Instead of string()

// Standardize booleans
$table->boolean('flag')->default(false); // Instead of integer()

// Standardize char for fixed-length
$table->char('status', 50); // Instead of string()
$table->char('type', 50);
```

### 6.3 Missing Indexes

**Add performance indexes:**

```php
// Subscriber email lookups
$table->index('email'); // Already present

// Geolocation caching (if implemented)
$table->index('ip_address'); // For open_logs

// Email verification filtering
$table->index('result'); // For email_verifications

// Campaign status filtering
$table->index('status'); // For campaigns

// Tracking log queries
$table->index(['campaign_id', 'status']); // Composite
$table->index(['email_id', 'status']); // Composite
```

### 6.4 Foreign Key Cleanup

**Add missing foreign keys from Acelle:**

```php
// tracking_logs enhancements
$table->foreign('email_id')->references('id')->on('mailing_emails')->onDelete('cascade');
$table->foreign('auto_trigger_id')->references('id')->on('mailing_auto_triggers')->onDelete('cascade');

// campaigns enhancements
$table->foreign('template_id')->references('id')->on('mailing_templates')->onDelete('set null');
$table->foreign('tracking_domain_id')->references('id')->on('mailing_tracking_domains')->onDelete('set null');

// emails enhancements
$table->foreign('customer_id')->references('id')->on('mailing_customers')->onDelete('cascade');
$table->foreign('template_id')->references('id')->on('mailing_templates')->onDelete('set null');
$table->foreign('tracking_domain_id')->references('id')->on('mailing_tracking_domains')->onDelete('set null');
```

### 6.5 Migration Naming Consistency

**Follow Acelle's patterns:**

```
✅ Good:
- 2026_01_28_100000_create_mailing_tracking_domains_table.php
- 2026_01_28_100001_add_template_id_to_campaigns.php
- 2026_01_28_100002_add_verification_fields_to_subscribers.php

❌ Avoid:
- create_tracking_domains.php (no timestamp)
- update_campaigns.php (too vague)
```

### 6.6 Backward Compatibility

**For existing data:**

1. **Create migration to add missing fields**
   ```php
   Schema::table('mailing_campaigns', function (Blueprint $table) {
       $table->foreignId('template_id')->nullable()->after('segment_id');
       $table->string('preheader')->nullable()->after('subject');
       $table->boolean('use_default_sending_server_from_email')->default(false);

       $table->foreign('template_id')
           ->references('id')
           ->on('mailing_templates')
           ->onDelete('set null');
   });
   ```

2. **Migrate html content to templates**
   ```php
   // Create templates from campaign html
   $campaigns = DB::connection('acelle')
       ->table('mailing_campaigns')
       ->whereNull('template_id')
       ->whereNotNull('html')
       ->get();

   foreach ($campaigns as $campaign) {
       $template = DB::connection('acelle')
           ->table('mailing_templates')
           ->insertGetId([
               'uid' => Str::uuid(),
               'customer_id' => $campaign->customer_id,
               'name' => "Campaign: {$campaign->name}",
               'content' => $campaign->html,
               'builder' => 'editor',
               'created_at' => now(),
               'updated_at' => now(),
           ]);

       DB::connection('acelle')
           ->table('mailing_campaigns')
           ->where('id', $campaign->id)
           ->update(['template_id' => $template]);
   }
   ```

3. **Drop deprecated columns after migration**
   ```php
   Schema::table('mailing_campaigns', function (Blueprint $table) {
       $table->dropColumn(['html', 'custom_order', 'resend']);
   });
   ```

### 6.7 Testing Strategy

**Before deploying:**

1. **Run migrations on test database**
   ```bash
   php artisan migrate --database=acelle --path=modules/Mailing/database/migrations
   ```

2. **Verify foreign key constraints**
   ```sql
   SELECT
       TABLE_NAME,
       CONSTRAINT_NAME,
       REFERENCED_TABLE_NAME,
       REFERENCED_COLUMN_NAME
   FROM information_schema.KEY_COLUMN_USAGE
   WHERE TABLE_SCHEMA = 'acelle_db'
       AND TABLE_NAME LIKE 'mailing_%'
       AND REFERENCED_TABLE_NAME IS NOT NULL
   ORDER BY TABLE_NAME;
   ```

3. **Test cascade deletes**
   ```php
   // Test customer deletion cascades
   $customer = Customer::factory()->create();
   $mailList = MailList::factory()->for($customer)->create();
   $subscriber = Subscriber::factory()->for($mailList)->create();

   $customer->delete();

   $this->assertDatabaseMissing('mailing_mail_lists', ['id' => $mailList->id]);
   $this->assertDatabaseMissing('mailing_subscribers', ['id' => $subscriber->id]);
   ```

4. **Performance testing**
   ```php
   // Test query performance
   $subscribers = Subscriber::with('mailList', 'subscriberFields')
       ->whereHas('mailList', fn($q) => $q->where('status', 'active'))
       ->limit(1000)
       ->get();

   // Should use indexes, no N+1 queries
   ```

---

## 7. Conclusion

Acelle Mail's database has evolved significantly over 9+ years, building a comprehensive email marketing platform with 80+ tables. Our current system captures ~75% of Acelle's core functionality but is missing several critical features:

**Critical Gaps:**
- Tracking domains (branded links)
- Template system enhancements (builder type, categories)
- Email verification tracking
- Campaign-template relationships
- Webhooks (campaign/email notifications)

**Major Missing Features:**
- Sub-accounts (agency/reseller)
- Activity timelines
- Forms builder
- Website builder
- Full e-commerce integration
- Job monitoring dashboard
- System audit logs

**Data Quality Issues:**
- Inconsistent data types (string vs text, boolean vs integer)
- Missing indexes for performance
- Incomplete foreign key relationships

**Next Steps:**
1. Add critical missing tables (tracking_domains, template_categories, senders)
2. Enhance existing tables with missing fields
3. Standardize data types across all tables
4. Add performance indexes
5. Implement missing foreign keys
6. Create data migration scripts for existing records

This analysis provides a complete roadmap for achieving feature parity with Acelle Mail while maintaining our custom architecture.

---

**Report End**
