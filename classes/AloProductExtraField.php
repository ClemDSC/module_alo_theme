<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 */

class AloProductExtraField extends ObjectModel
{
    /** @var string Nom de la table sans préfixe */
    public const TABLE_NAME = 'alo_product_extra_field';
    /** @var string Nom de la clé primaire */
    public const PRIMARY_KEY = 'id_product_extra_field';

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table' => self::TABLE_NAME,
        'primary' => self::PRIMARY_KEY,
        'multilang' => true,
        'fields' => [
            'id_product' => [
                'type' => self::TYPE_INT,
                'validate' => 'isUnsignedId',
            ],
            'iconic_title' => [
                'type' => self::TYPE_STRING,
                'validate' => 'isString',
                'lang' => true,
            ],
            'iconic_text' => [
                'type' => self::TYPE_HTML,
                'lang' => true,
                'validate' => 'isCleanHtml',
            ],
            'iconic_img_url' => [
                'type' => self::TYPE_STRING,
                'validate' => 'isString',
                'lang' => true,
            ],
            'iconic_active' => [
                'type' => self::TYPE_INT,
                'validate' => 'isInt',
            ],
            'date_add' => [
                'type' => self::TYPE_DATE,
                'validate' => 'isDate',
                'copy_post' => false
            ],
            'date_upd' => [
                'type' => self::TYPE_DATE,
                'validate' => 'isDate',
                'copy_post' => false
            ],
        ],
    ];

    public $id_product;
    public $iconic_title;
    public $iconic_text;
    public $iconic_img_url;
    public $iconic_active;
    public $date_add;
    public $date_upd;

    /**
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function createDatabase(): bool
    {
        $sql = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . self::TABLE_NAME . '` '
            . '('
            . '`' . self::PRIMARY_KEY . '` int(11) unsigned NOT NULL AUTO_INCREMENT, '
            . '`id_product` int(11) NULL, '
            . '`iconic_active` tinyint(1) NOT NULL, '
            . '`date_add` datetime NOT NULL, '
            . '`date_upd` datetime NULL, '
            . 'PRIMARY KEY (`' . self::PRIMARY_KEY . '`),'
            . 'KEY `id_product` (`id_product`)'
            . ') '
            . 'ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=UTF8;';

        if (!Db::getInstance()->execute($sql)) {
            return false;
        }

        $sql = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . self::TABLE_NAME . '_lang` '
            . '('
            . '`' . self::PRIMARY_KEY . '` int(11) unsigned NOT NULL, '
            . '`id_lang` int(10) unsigned NOT NULL, '
            . '`iconic_title` varchar(255) NOT NULL, '
            . '`iconic_text` text NOT NULL, '
            . '`iconic_img_url` varchar(255) NOT NULL, '
            . 'PRIMARY KEY (`' . self::PRIMARY_KEY . '`, `id_lang`)'
            . ') '
            . 'ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=UTF8;';

        return Db::getInstance()->execute($sql);
    }

    /**
     * @param int $id_product
     * @param int $id_product_attribute
     * @return array|bool|object|null
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getByPsId(int $id_product, $id_lang = null)
    {
        $id_alo_hiboutik_product = (int)Db::getInstance()->getValue(
            (new DbQuery())
                ->select(self::PRIMARY_KEY)
                ->from(self::TABLE_NAME)
                ->where('id_product = ' . $id_product)
        );

        return new self($id_alo_hiboutik_product, $id_lang);
    }
}
