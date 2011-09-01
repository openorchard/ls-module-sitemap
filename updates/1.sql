ALTER TABLE `pages` add column `sitemap_visible` tinyint(4) NULL default 1;

UPDATE `pages` set `sitemap_visible` = 1 where `sitemap_visible` is null;