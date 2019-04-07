<?php


use Phinx\Migration\AbstractMigration;

class MyNewMigration extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-abstractmigration-class
     *
     * The following commands can be used in this method and Phinx will
     * automatically reverse them when rolling back:
     *
     *    createTable
     *    renameTable
     *    addColumn
     *    addCustomColumn
     *    renameColumn
     *    addIndex
     *    addForeignKey
     *
     * Any other destructive changes will result in an error when trying to
     * rollback the migration.
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function change()
    {
        
        // create, klice, autoincrementy
        $count = $this->execute("

SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";
SET time_zone = \"+00:00\";

-- --------------------------------------------------------

--
-- Struktura tabulky `platby_mzdy`
--

CREATE TABLE `platby_mzdy` (
  `id` int(11) NOT NULL,
  `id_creator` int(11) NOT NULL,
  `dt_created` int(11) NOT NULL,
  `sender_account` varchar(30) NOT NULL,
  `sender_bank` varchar(10) NOT NULL,
  `recipient_account` varchar(30) NOT NULL,
  `recipient_bank` varchar(10) NOT NULL,
  `amount` int(11) NOT NULL,
  `symbol_variable` varchar(20) DEFAULT NULL,
  `symbol_constant` varchar(20) DEFAULT NULL,
  `symbol_specific` varchar(20) DEFAULT NULL,
  `message` varchar(255) DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL,
  `files` varchar(255) DEFAULT NULL COMMENT 'link na soubor faktury, todo',
  `date_due` int(11) NOT NULL,
  `date_send` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------


--
-- Struktura tabulky `t_accounting_account_groups`
--

CREATE TABLE `t_accounting_account_groups` (
  `c_uid` int(11) NOT NULL,
  `c_owner` int(11) NOT NULL,
  `c_group` int(11) NOT NULL,
  `c_unixperms` int(11) NOT NULL DEFAULT '500',
  `c_definition_id` int(11) NOT NULL,
  `c_definition_name` varchar(100) NOT NULL,
  `c_group_number` varchar(20) NOT NULL,
  `c_group_description` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Struktura tabulky `t_accounting_received`
--

CREATE TABLE `t_accounting_received` (
  `c_uid` int(11) NOT NULL,
  `c_owner` int(11) NOT NULL,
  `c_group` int(11) NOT NULL,
  `c_unixperms` int(11) NOT NULL DEFAULT '500',
  `c_data` json NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Struktura tabulky `t_action`
--

CREATE TABLE `t_action` (
  `c_uid` int(11) NOT NULL,
  `c_title` varchar(100) NOT NULL,
  `c_apply_object` tinyint(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Struktura tabulky `t_assets_items`
--

CREATE TABLE `t_assets_items` (
  `c_uid` int(11) NOT NULL,
  `c_data` json NOT NULL,
  `stor_name` varchar(255) COLLATE utf8mb4_unicode_ci GENERATED ALWAYS AS (json_unquote(json_extract(`c_data`,'$.data.item'))) VIRTUAL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `t_authentication`
--

CREATE TABLE `t_authentication` (
  `c_uid` int(10) UNSIGNED NOT NULL,
  `c_user_id` int(10) UNSIGNED NOT NULL,
  `c_type` tinyint(4) NOT NULL DEFAULT '1',
  `c_username` varchar(100) NOT NULL,
  `c_pasword` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `c_domain` varchar(255) DEFAULT NULL,
  `c_limits` json DEFAULT NULL,
  `c_status` json DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Struktura tabulky `t_consumables_items`
--

CREATE TABLE `t_consumables_items` (
  `c_uid` int(11) NOT NULL,
  `c_data` json NOT NULL,
  `stor_name` varchar(255) COLLATE utf8mb4_unicode_ci GENERATED ALWAYS AS (json_unquote(json_extract(`c_data`,'$.data.item_name'))) VIRTUAL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `t_contacts`
--

CREATE TABLE `t_contacts` (
  `c_uid` int(11) NOT NULL,
  `c_owner` int(11) NOT NULL,
  `c_group` int(11) NOT NULL,
  `c_unixperms` int(11) NOT NULL DEFAULT '500',
  `c_data` json NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Struktura tabulky `t_facebook_events`
--

CREATE TABLE `t_facebook_events` (
  `c_uid` int(11) NOT NULL,
  `c_event_id` bigint(11) NOT NULL,
  `c_data` json NOT NULL,
  `c_new` tinyint(4) NOT NULL DEFAULT '1',
  `c_downloaded` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `t_facebook_pages`
--

CREATE TABLE `t_facebook_pages` (
  `c_id` int(11) NOT NULL,
  `c_token_id` int(11) NOT NULL,
  `c_fb_id` bigint(11) NOT NULL,
  `c_fb_name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `c_max_months` int(11) NOT NULL DEFAULT '0',
  `c_data` json DEFAULT NULL,
  `c_added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `t_facebook_page_events`
--

CREATE TABLE `t_facebook_page_events` (
  `c_page_id` int(11) NOT NULL,
  `c_event_uid` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Struktura tabulky `t_facebook_tokens`
--

CREATE TABLE `t_facebook_tokens` (
  `c_id` int(11) NOT NULL,
  `c_title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `c_data` json DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `t_helper`
--

CREATE TABLE `t_helper` (
  `c_uid` int(11) NOT NULL,
  `stor_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `t_implemented_action`
--

CREATE TABLE `t_implemented_action` (
  `c_id` int(11) NOT NULL,
  `c_table` varchar(100) NOT NULL,
  `c_action` varchar(100) NOT NULL,
  `c_status` int(11) NOT NULL DEFAULT '0',
  `c_abac` json DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Struktura tabulky `t_implemented_roles`
--

CREATE TABLE `t_implemented_roles` (
  `c_uid` int(11) NOT NULL,
  `c_role` varchar(50) NOT NULL,
  `c_tables` json DEFAULT NULL,
  `c_description` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Struktura tabulky `t_imported_data`
--

CREATE TABLE `t_imported_data` (
  `c_uid` int(11) NOT NULL,
  `c_data` json NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Struktura tabulky `t_imported_settings`
--

CREATE TABLE `t_imported_settings` (
  `c_uid` int(11) NOT NULL,
  `c_schema` varchar(15) NOT NULL,
  `c_layout` tinyint(4) NOT NULL,
  `c_name` varchar(100) NOT NULL,
  `c_setting` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Struktura tabulky `t_parts_items`
--

CREATE TABLE `t_parts_items` (
  `c_uid` int(11) NOT NULL,
  `c_data` json NOT NULL,
  `stor_name` varchar(255) COLLATE utf8mb4_unicode_ci GENERATED ALWAYS AS (json_unquote(json_extract(`c_data`,'$.data.item_name'))) VIRTUAL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `t_privileges`
--

CREATE TABLE `t_privileges` (
  `c_id` int(10) UNSIGNED NOT NULL,
  `c_role` varchar(30) NOT NULL,
  `c_who` int(11) NOT NULL DEFAULT '0',
  `c_action` varchar(100) NOT NULL,
  `c_type` varchar(30) NOT NULL,
  `c_neg` int(11) DEFAULT NULL,
  `c_related_table` varchar(100) NOT NULL,
  `c_related_uid` int(11) NOT NULL DEFAULT '0',
  `c_protected` tinyint(4) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Struktura tabulky `t_stor_links`
--

CREATE TABLE `t_stor_links` (
  `c_uid` int(10) UNSIGNED NOT NULL,
  `c_owner` int(10) UNSIGNED NOT NULL DEFAULT '1',
  `c_unixperms` int(10) UNSIGNED NOT NULL DEFAULT '500',
  `c_status` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `c_sha512` char(128) NOT NULL,
  `c_filename` varchar(255) NOT NULL,
  `c_inherit_table` varchar(50) DEFAULT NULL,
  `c_inherit_object` int(11) NOT NULL DEFAULT '0',
  `c_ts_created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Struktura tabulky `t_stor_objects`
--

CREATE TABLE `t_stor_objects` (
  `doc` json NOT NULL,
  `sha512` char(128) GENERATED ALWAYS AS (json_unquote(json_extract(`doc`,'$.data.sha512'))) VIRTUAL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Struktura tabulky `t_tag_assignments`
--

CREATE TABLE `t_tag_assignments` (
  `c_table` varchar(100) NOT NULL,
  `c_uid` int(11) NOT NULL,
  `c_tagname` varchar(100) NOT NULL,
  `c_tagvalue` varchar(50) DEFAULT NULL,
  `c_system` tinyint(4) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Struktura tabulky `t_users`
--

CREATE TABLE `t_users` (
  `c_uid` int(10) UNSIGNED NOT NULL,
  `c_owner` int(10) UNSIGNED NOT NULL DEFAULT '1',
  `c_group` int(10) UNSIGNED NOT NULL DEFAULT '1',
  `c_unixperms` int(10) UNSIGNED NOT NULL DEFAULT '500',
  `c_status` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `c_screenname` varchar(50) NOT NULL,
  `c_group_mememberships` int(10) UNSIGNED NOT NULL DEFAULT '2',
  `stor_name` varchar(50) GENERATED ALWAYS AS (`c_screenname`) VIRTUAL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Struktura tabulky `t_vectors`
--

CREATE TABLE `t_vectors` (
  `c_uid` int(11) NOT NULL,
  `c_source_table` varchar(50) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `c_source_object` int(11) NOT NULL DEFAULT '0',
  `c_data` json DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `t_wiki`
--

CREATE TABLE `t_wiki` (
  `c_uid` int(11) NOT NULL,
  `c_name` varchar(100) NOT NULL,
  `c_url` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Struktura tabulky `t_wiki_articles`
--

CREATE TABLE `t_wiki_articles` (
  `c_uid` int(11) NOT NULL,
  `c_wiki_uid` int(11) NOT NULL,
  `c_title` varchar(100) NOT NULL,
  `c_url` varchar(100) NOT NULL,
  `c_text` text,
  `c_html` text,
  `c_created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `c_creator` int(11) NOT NULL,
  `c_variant` int(11) NOT NULL DEFAULT '1',
  `c_data` json DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


--
-- Klíèe pro exportované tabulky
--

--
-- Klíèe pro tabulku `platby_mzdy`
--
ALTER TABLE `platby_mzdy`
  ADD PRIMARY KEY (`id`);

--
-- Klíèe pro tabulku `t_accounting_account_groups`
--
ALTER TABLE `t_accounting_account_groups`
  ADD PRIMARY KEY (`c_uid`);

--
-- Klíèe pro tabulku `t_accounting_received`
--
ALTER TABLE `t_accounting_received`
  ADD PRIMARY KEY (`c_uid`);

--
-- Klíèe pro tabulku `t_action`
--
ALTER TABLE `t_action`
  ADD PRIMARY KEY (`c_uid`),
  ADD UNIQUE KEY `k_joined` (`c_title`,`c_apply_object`) USING BTREE;

--
-- Klíèe pro tabulku `t_assets_items`
--
ALTER TABLE `t_assets_items`
  ADD PRIMARY KEY (`c_uid`);

--
-- Klíèe pro tabulku `t_authentication`
--
ALTER TABLE `t_authentication`
  ADD PRIMARY KEY (`c_uid`),
  ADD KEY `c_user_id` (`c_user_id`),
  ADD KEY `c_username` (`c_username`),
  ADD KEY `c_type` (`c_type`),
  ADD KEY `c_pasword` (`c_pasword`);

--
-- Klíèe pro tabulku `t_consumables_items`
--
ALTER TABLE `t_consumables_items`
  ADD PRIMARY KEY (`c_uid`);

--
-- Klíèe pro tabulku `t_contacts`
--
ALTER TABLE `t_contacts`
  ADD PRIMARY KEY (`c_uid`);

--
-- Klíèe pro tabulku `t_facebook_events`
--
ALTER TABLE `t_facebook_events`
  ADD PRIMARY KEY (`c_uid`),
  ADD UNIQUE KEY `c_event_id` (`c_event_id`);

--
-- Klíèe pro tabulku `t_facebook_pages`
--
ALTER TABLE `t_facebook_pages`
  ADD PRIMARY KEY (`c_id`);

--
-- Klíèe pro tabulku `t_facebook_page_events`
--
ALTER TABLE `t_facebook_page_events`
  ADD PRIMARY KEY (`c_page_id`,`c_event_uid`);

--
-- Klíèe pro tabulku `t_facebook_tokens`
--
ALTER TABLE `t_facebook_tokens`
  ADD PRIMARY KEY (`c_id`);

--
-- Klíèe pro tabulku `t_helper`
--
ALTER TABLE `t_helper`
  ADD PRIMARY KEY (`c_uid`);

--
-- Klíèe pro tabulku `t_implemented_action`
--
ALTER TABLE `t_implemented_action`
  ADD PRIMARY KEY (`c_id`),
  ADD UNIQUE KEY `c_table` (`c_table`,`c_action`);

--
-- Klíèe pro tabulku `t_implemented_roles`
--
ALTER TABLE `t_implemented_roles`
  ADD PRIMARY KEY (`c_uid`);

--
-- Klíèe pro tabulku `t_imported_data`
--
ALTER TABLE `t_imported_data`
  ADD PRIMARY KEY (`c_uid`);

--
-- Klíèe pro tabulku `t_imported_settings`
--
ALTER TABLE `t_imported_settings`
  ADD PRIMARY KEY (`c_uid`);

--
-- Klíèe pro tabulku `t_parts_items`
--
ALTER TABLE `t_parts_items`
  ADD PRIMARY KEY (`c_uid`);

--
-- Klíèe pro tabulku `t_privileges`
--
ALTER TABLE `t_privileges`
  ADD PRIMARY KEY (`c_id`);

--
-- Klíèe pro tabulku `t_stor_links`
--
ALTER TABLE `t_stor_links`
  ADD PRIMARY KEY (`c_uid`);

--
-- Klíèe pro tabulku `t_stor_objects`
--
ALTER TABLE `t_stor_objects`
  ADD UNIQUE KEY `sha512` (`sha512`);

--
-- Klíèe pro tabulku `t_tag_assignments`
--
ALTER TABLE `t_tag_assignments`
  ADD PRIMARY KEY (`c_table`,`c_uid`,`c_tagname`);

--
-- Klíèe pro tabulku `t_users`
--
ALTER TABLE `t_users`
  ADD PRIMARY KEY (`c_uid`);

--
-- Klíèe pro tabulku `t_vectors`
--
ALTER TABLE `t_vectors`
  ADD PRIMARY KEY (`c_uid`);

--
-- Klíèe pro tabulku `t_wiki`
--
ALTER TABLE `t_wiki`
  ADD PRIMARY KEY (`c_uid`),
  ADD KEY `url` (`c_url`);

--
-- Klíèe pro tabulku `t_wiki_articles`
--
ALTER TABLE `t_wiki_articles`
  ADD PRIMARY KEY (`c_uid`);


--
-- AUTO_INCREMENT pro tabulky
--

--
-- AUTO_INCREMENT pro tabulku `platby_mzdy`
--
ALTER TABLE `platby_mzdy`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;


-- AUTO_INCREMENT pro tabulku `t_accounting_account_groups`
--
ALTER TABLE `t_accounting_account_groups`
  MODIFY `c_uid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;
--
-- AUTO_INCREMENT pro tabulku `t_accounting_received`
--
ALTER TABLE `t_accounting_received`
  MODIFY `c_uid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
--
-- AUTO_INCREMENT pro tabulku `t_action`
--
ALTER TABLE `t_action`
  MODIFY `c_uid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;
--
-- AUTO_INCREMENT pro tabulku `t_assets_items`
--
ALTER TABLE `t_assets_items`
  MODIFY `c_uid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
--
-- AUTO_INCREMENT pro tabulku `t_authentication`
--
ALTER TABLE `t_authentication`
  MODIFY `c_uid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
--
-- AUTO_INCREMENT pro tabulku `t_consumables_items`
--
ALTER TABLE `t_consumables_items`
  MODIFY `c_uid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
--
-- AUTO_INCREMENT pro tabulku `t_contacts`
--
ALTER TABLE `t_contacts`
  MODIFY `c_uid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
--
-- AUTO_INCREMENT pro tabulku `t_facebook_events`
--
ALTER TABLE `t_facebook_events`
  MODIFY `c_uid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
--
-- AUTO_INCREMENT pro tabulku `t_facebook_pages`
--
ALTER TABLE `t_facebook_pages`
  MODIFY `c_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
--
-- AUTO_INCREMENT pro tabulku `t_facebook_tokens`
--
ALTER TABLE `t_facebook_tokens`
  MODIFY `c_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
--
-- AUTO_INCREMENT pro tabulku `t_helper`
--
ALTER TABLE `t_helper`
  MODIFY `c_uid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT pro tabulku `t_implemented_action`
--
ALTER TABLE `t_implemented_action`
  MODIFY `c_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
--
-- AUTO_INCREMENT pro tabulku `t_implemented_roles`
--
ALTER TABLE `t_implemented_roles`
  MODIFY `c_uid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
--
-- AUTO_INCREMENT pro tabulku `t_imported_data`
--
ALTER TABLE `t_imported_data`
  MODIFY `c_uid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
--
-- AUTO_INCREMENT pro tabulku `t_imported_settings`
--
ALTER TABLE `t_imported_settings`
  MODIFY `c_uid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
--
-- AUTO_INCREMENT pro tabulku `t_parts_items`
--
ALTER TABLE `t_parts_items`
  MODIFY `c_uid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
--
-- AUTO_INCREMENT pro tabulku `t_privileges`
--
ALTER TABLE `t_privileges`
  MODIFY `c_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
--
-- AUTO_INCREMENT pro tabulku `t_stor_links`
--
ALTER TABLE `t_stor_links`
  MODIFY `c_uid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
--
-- AUTO_INCREMENT pro tabulku `t_users`
--
ALTER TABLE `t_users`
  MODIFY `c_uid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
--
-- AUTO_INCREMENT pro tabulku `t_vectors`
--
ALTER TABLE `t_vectors`
  MODIFY `c_uid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
--
-- AUTO_INCREMENT pro tabulku `t_wiki`
--
ALTER TABLE `t_wiki`
  MODIFY `c_uid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
--
-- AUTO_INCREMENT pro tabulku `t_wiki_articles`
--
ALTER TABLE `t_wiki_articles`
  MODIFY `c_uid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

        
        ");
        
        
        // defaultni data, insert
        $count = $this->execute("
--
-- Vypisuji data pro tabulku `t_accounting_account_groups`
--

INSERT INTO `t_accounting_account_groups` (`c_uid`, `c_owner`, `c_group`, `c_unixperms`, `c_definition_id`, `c_definition_name`, `c_group_number`, `c_group_description`) VALUES
(1, 1, 2, 500, 1, 'czech_default', '520', 'osobní náklady'),
(2, 1, 2, 500, 1, 'czech_default', '520', 'osobní náklady - prac. smlouvy'),
(3, 1, 2, 500, 1, 'czech_default', '520', 'osobní náklady - dpp'),
(4, 1, 2, 500, 1, 'czech_default', '520', 'osobní náklady - autorské honoráøe'),
(5, 1, 2, 500, 1, 'czech_default', '520', 'osobní náklady - oon'),
(6, 1, 2, 500, 1, 'czech_default', '550', 'dhm'),
(7, 1, 2, 500, 1, 'czech_default', '550', 'dhm - software do 60tkè'),
(8, 1, 2, 500, 1, 'czech_default', '550', 'dhm - hardware do 40tkè'),
(9, 1, 2, 500, 1, 'czech_default', '502', 'energie'),
(10, 1, 2, 500, 1, 'czech_default', '510', 'služby'),
(11, 1, 2, 500, 1, 'czech_default', '518', 'služby - telefony, internet, poštovné, spoje'),
(12, 1, 2, 500, 1, 'czech_default', '518', 'služby - nájemné'),
(13, 1, 2, 500, 1, 'czech_default', '511', 'služby - opravy a údržba'),
(14, 1, 2, 500, 1, 'czech_default', '512', 'služby - cestovní náklady'),
(15, 1, 2, 500, 1, 'czech_default', '518', 'služby - inzerce, reklama, propagace'),
(16, 1, 2, 500, 1, 'czech_default', '518', 'služby - úklid'),
(17, 1, 2, 500, 1, 'czech_default', '518', 'služby - ostatní');

--
-- Vypisuji data pro tabulku `t_action`
--

INSERT INTO `t_action` (`c_uid`, `c_title`, `c_apply_object`) VALUES
(9, 'delete', 1),
(7, 'list', 1),
(5, 'read', 1),
(8, 'write', 1);

--
-- Vypisuji data pro tabulku `t_helper`
--

INSERT INTO `t_helper` (`c_uid`, `stor_name`) VALUES
(1, 'import souboru');

--
-- Vypisuji data pro tabulku `t_implemented_roles`
--

INSERT INTO `t_implemented_roles` (`c_uid`, `c_role`, `c_tables`, `c_description`) VALUES
(1, 'friend', NULL, NULL),
(2, 'owner', NULL, NULL),
(3, 'self', NULL, NULL);
        ");
        
    }
}
