# Changelog

## 3.1.0 / 2026-06-26

### Breaking changes

* None

### Features / changes

* KUSTOM-93: Loosened up PHP version constraint to allow installing the package on Magento 2.4.9
  and PHP 8.5. Adjusted integration and unit tests in the package to run on currently supported
  Magento, PHP and PHPUnit versions.

### Fixes

* None

## 3.0.9 / 2025-06-03

  * PPP-2085 Change lengths of index columns for Klarna Logger database table

## 3.0.8 / 2025-05-21

  * PPP-2055 Compatibility with AC 2.4.8 and PHP 8.4

## 3.0.7 / 2025-04-03

  * PPP-1759 Create integration tests for \Klarna\Logger\Cron\UpdateApiLog
  * PPP-1860 Simplified repository classes for database abstractions

## 3.0.6 / 2025-03-26

  * PPP-1903 Fetch available Klarna features from Klarna API

## 3.0.6 / 2025-01-22

  * PPP-1859 Simplified unit tests by using a helper which includes the mocking logic.
  * PPP-1954 Fix database connection pooling issue

## 3.0.5 / 2024-12-03

  * PPP-1729 Reduced the filter list at \Klarna\Logger\Model\Cleanser

## 3.0.4 / 2024-10-18

  * PPP-1714 Simplify composer.json files
  * PPP-1730 Change logging file name

## 3.0.3 / 2024-09-26

  * PPP-588 Fixed shown exception on the landing page
  * PPP-1637 Readded the ability to enable and disable the file logging in the settings.

## 3.0.2 / 2024-08-21

  * PPP-338 Added unit tests for the Cleanser class.
  * PPP-1606 Refactor the Logger/Model/Logger class
  * PPP-1625 Updated phpunit.xml and fixed warnings in the unit tests
  * PPP-1632 Added timestamps to the database table.

## 3.0.1 / 2024-07-26

  * PPP-1553 Make the extension compatible with Adobe Commerce app assurance program requirements

## 3.0.0 / 2024-06-20

  * PPP-1437 Updated the admin UX and changed internally the API credentials handling

## 2.0.10 / 2024-05-30

  * PPP-923 Add Klarna logger resource to the Klarna role

## 2.0.9 / 2024-04-24

  * PPP-1391 Added support for Adobe Commerce 2.4.7 and PHP 8.3

## 2.0.8 / 2024-03-30

  * PPP-1013 Using instead of \Klarna\Base\Helper\ConfigHelper logic from other classes to get back Klarna specific configuration values.
  * PPP-1330 Make Logger module routes.xml file valid

## 2.0.7 / 2024-03-04

  * PPP-596 Logging entries can now be filtered by the KP authorization callback and the status codes 400, 403 and 503
  * PPP-916 Retrieve and add more debugging related data to the admin support request form.

## 2.0.5 / 2024-01-19

  * PPP-917 Added integration tests for the repository

## 2.0.4 / 2023-11-15

  * PPP-802 Fix setRequest exception

## 2.0.3 / 2023-07-14

  * MAGE-4228 Removed the composer caret version range for Klarna dependencies

## 2.0.2 / 2023-05-22

  * MAGE-3857 Added a default value for the response_code

## 2.0.1 / 2023-03-28

  * MAGE-4162 Added support for PHP 8.2

## 2.0.0 / 2023-03-09

  * MAGE-4062 Removed deprecated methods
  * MAGE-4063 Removd deprecated classes
  * MAGE-4066 Removed the Objectmanager workaround for public API class contructors
  * MAGE-4068 Do not using anymore in all controllers the parent Magento\Framework\App\Action\Action class
  * MAGE-4077 Added "declare(strict_types=1);" to all production class files

## 1.0.8 / 2022-09-27

  * MAGE-4006 Using the PHP method array_walk_recursive for cleaning the logging entries in a production environment

## 1.0.7 / 2022-09-01

  * MAGE-3018 Added dropdown values for filter on the admin logger page
  * MAGE-3572 Added better descriptions of the admin payment fields.
  * MAGE-3712 Using constancts instead of magic numbers

## 1.0.6 / 2022-08-18

  * MAGE-3950 Added missing translations

## 1.0.5 / 2022-08-12

  * MAGE-3575 Add log link to order
  * MAGE-3876 Reordered translations and set of missing translations
  * MAGE-3910 Updated the copyright text

## 1.0.4 / 2022-07-11

  * MAGE-3888 Removed object creations via "new ..."

## 1.0.3 / 2022-06-23

  * MAGE-3726 Add logging entries to the order history table when logging something to a file

## 1.0.2 / 2022-06-13

  * MAGE-3019 Fix missing Increment ID value on the logs page 
  * MAGE-3785 Fix PHP requirements so that it matches the PHP requirement from Magento 2.4.4

## 1.0.1 / 2022-05-09

  * MAGE-3459 Improved DB footprint

## 1.0.0 / 2022-03-01

  * Initial Commit
