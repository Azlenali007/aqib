-- Migration 001: Initial Schema marker
-- Safe idempotent migration tracking
INSERT IGNORE INTO `migrations` (`migration`) VALUES ('001_initial_schema');
