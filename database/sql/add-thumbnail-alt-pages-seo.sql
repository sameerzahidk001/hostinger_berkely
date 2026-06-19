-- Fix: Unknown column 'thumbnail_alt' on pages_s_e_o_s
-- Run on live DB (phpMyAdmin SQL tab), then retry saving the page.

ALTER TABLE `pages_s_e_o_s` ADD COLUMN `thumbnail_alt` varchar(255) DEFAULT NULL;
