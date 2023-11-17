<?php
/**
 * 2007-2021 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2021 PrestaShop SA
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'AloProductExtraField.php';

if (file_exists(_PS_MODULE_DIR_ . 'alo_preco' . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'AloPrecoConfig.php')
    && file_exists(_PS_MODULE_DIR_ . 'alo_preco' . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'AloPrecoConfigProductAttribute.php')
    && file_exists(_PS_MODULE_DIR_ . 'alo_preco' . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'AloPrecoPreorder.php')
) {
    require_once _PS_MODULE_DIR_ . 'alo_preco' . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'AloPrecoConfig.php';
    require_once _PS_MODULE_DIR_ . 'alo_preco' . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'AloPrecoConfigProductAttribute.php';
    require_once _PS_MODULE_DIR_ . 'alo_preco' . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'AloPrecoPreorder.php';
}

use PrestaShop\PrestaShop\Core\MailTemplate\Layout\Layout;
use PrestaShop\PrestaShop\Core\MailTemplate\ThemeCatalogInterface;
use PrestaShop\PrestaShop\Core\MailTemplate\ThemeCollectionInterface;
use PrestaShop\PrestaShop\Core\MailTemplate\ThemeInterface;
use PrestaShop\PrestaShop\Core\Product\ProductPresentationSettings;

class Alo_Theme extends Module
{
    public const HOOKS = [
        'actionDispatcher',
        'actionFrontControllerSetVariables',
        'displayAdminProductsMainStepLeftColumnBottom',
        'actionProductUpdate',
        'actionGetExtraMailTemplateVars',
        'actionEmailSendBefore',
        'displayProductColors',
        'displayAvailabilityClass',
    ];
    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'alo_theme';
        $this->tab = 'administration';
        $this->version = '1.0.1';
        $this->author = 'Klorel';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('A L\'O - Thème');
        $this->description = $this->l('Module de gestion du thème sur-mesure pour A L\'O.');
        $this->ps_versions_compliancy = ['min' => '1.6', 'max' => _PS_VERSION_];
    }

    public function getIdManufacturer()
    {
        if (Tools::getValue("controller") !== "AdminManufacturers") {
            throw new PrestaShopException("Méthode disponible sur les controllers Symfony uniquement");
        }

        global $kernel;
        $requestStack = $kernel->getContainer()->get('request_stack');
        $request = $requestStack->getCurrentRequest();
        $id_manufacturer = $request->get('manufacturerId');

        return $id_manufacturer;
    }

    public function hookDisplayAdminProductsMainStepLeftColumnBottom($params)
    {
        $product_extra_field = AloProductExtraField::getByPsId($params["id_product"]);
        $this->context->smarty->assign([
            'product_extra_field' => $product_extra_field,
        ]);

        return $this->context->smarty->fetch($this->local_path . 'views/templates/hooks/admin_products_main_step_left_column_bottom.tpl');
    }

    public function hookActionProductUpdate($params)
    {
        if (!Tools::isSubmit('iconic') || !Tools::isSubmit('id_product')) {
            return;
        }

        $iconic_data = Tools::getValue('iconic');
        $id_product = (int)Tools::getValue('id_product');
        try {
            $alo_product_extra_field = AloProductExtraField::getByPsId($id_product);
        } catch (PrestaShopDatabaseException|PrestaShopException $e) {
            PrestaShopLogger::addLog(
                'Alo_Theme::hookActionProductUpdate: ' . $e->getMessage(),
                PrestaShopLogger::LOG_SEVERITY_LEVEL_ERROR,
                null,
                null,
                null,
                true
            );
            return;
        }

        if (empty($alo_product_extra_field->id_product)) {
            $alo_product_extra_field->id_product = $id_product;
        }

        $alo_product_extra_field->iconic_title = $iconic_data['title'];
        $alo_product_extra_field->iconic_text = $iconic_data['text'];
        $alo_product_extra_field->iconic_img_url = $iconic_data['img_url'];

        try {
            $alo_product_extra_field->save();
        } catch (PrestaShopDatabaseException|PrestaShopException $e) {
            PrestaShopLogger::addLog(
                'Alo_Theme::hookActionProductUpdate: ' . $e->getMessage(),
                PrestaShopLogger::LOG_SEVERITY_LEVEL_ERROR,
                null,
                null,
                null,
                true
            );
        }
    }

    public function hookActionDispatcher($params)
    {
        $contactform = Module::getInstanceByName('contactform');
        $this->context->smarty->assign([
            'contactform_id' => $contactform->id,
        ]);

        try {
            $this->context->smarty->registerPlugin('function', 'klcm', [self::class, 'getI18nString']);
        } catch (SmartyException $e) {
            PrestaShopLogger::addLog(
                $e->getMessage(),
                3,
                null,
                null,
                null,
                true
            );
        }

        if (Tools::isSubmit('concept-form')) {

            //Captcha validation
            if ($eicaptcha = Module::getInstanceByName('eicaptcha')) {
                if (!$eicaptcha->hookActionValidateCaptcha()) {
                    return;
                }
            }

            if (Tools::getValue('concept-form')['content'] === '') {
                $this->context->controller->errors[] = $this->l('Le message ne doit pas être vide');
                return;
            }

            if (!Validate::isEmail(Tools::getValue('concept-form')['email'])) {
                $this->context->controller->errors[] = $this->l('Adresse e-mail invalide.');
                return;
            }

            if (!Validate::isName(Tools::getValue('concept-form')['lastname'])) {
                $this->context->controller->errors[] = $this->l('Nom invalide');
                return;
            }

            if (!Validate::isName(Tools::getValue('concept-form')['firstname'])) {
                $this->context->controller->errors[] = $this->l('Prénom invalide');
                return;
            }

            PrestaShopLogger::addLog(
                'Concept form submitted',
                PrestaShopLogger::LOG_SEVERITY_LEVEL_INFORMATIVE,
                null,
                null,
                null,
                true
            );
            try {
                $result = $this->processConceptForm(Tools::getValue('concept-form'));
            } catch (PrestaShopDatabaseException|PrestaShopException $e) {
                PrestaShopLogger::addLog(
                    $e->getMessage(),
                    3,
                    null,
                    null,
                    null,
                    true
                );
                $this->context->controller->errors[] = $this->l(
                    'Une erreur est survenue lors de l\'envoi de l\'email'
                );
            }

            if (isset($result) && $result) {
                $this->context->controller->success[] = $this->l('Un email a bien été envoyé');
            }
        }
    }

    /**
     * @param array $formData
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function processConceptForm(array $formData): bool
    {
        $contact = new Contact(Configuration::get('ALO_CONCEPT_PAGE_CONTACT'), $this->context->language->id);

        return (bool)Mail::send(
            $this->context->language->id,
            'alo_contact',
            'Nouveau message de ' . $formData['firstname'] . ' ' . $formData['lastname'] . ' - Formulaire proposition produit en précommande',
            [
                '{email}' => $formData['email'],
                '{firstname}' => $formData['firstname'],
                '{lastname}' => $formData['lastname'],
                '{message}' => $formData['content'],
            ],
            $contact->email,
            $contact->name,
            $formData['email'],
            $formData['firstname'] . ' ' . $formData['lastname'],
            null,
            null,
            _PS_THEME_DIR_ . 'modules'
            . DIRECTORY_SEPARATOR . 'alo_theme'
            . DIRECTORY_SEPARATOR . 'mails'
            . DIRECTORY_SEPARATOR
        );
    }

    /**
     * @return array
     */
    public function hookActionFrontControllerSetVariables()
    {
        return [
            'get_faq_count' => $this->getFAQCount(),
            'module' => $this,
        ];
    }

    protected function getFAQCount()
    {
        include _PS_THEME_DIR_ . 'translations/lang.php';

        if (!isset($klcm)) {
            return '';
        }

        $counter = 0;
        foreach ($klcm['p_faq'] as $key => $content) {
            if (str_contains($key, 'h4_')) {
                $counter++;
            }
        }

        return $counter;
    }

    public function getAloProductExtraField(int $id_product = null)
    {
        return AloProductExtraField::getByPsId($id_product, $this->context->language->id);
    }

    public function getFeaturedProduct(int $featured_product_number, $brand_id)
    {
        $product_id = Configuration::get('ALO_BRAND_' . $brand_id . '_FEATURED_PRODUCT_' . $featured_product_number);

        if (!$product_id) {
            $product_id = 1;
        }

        // Inspiré de https://www.h-hennes.fr/blog/2022/02/21/prestashop-afficher-les-commentaires-produits-dans-les-onglets-de-la-fiche-produit/

        //Gestion des paramètres de présentation
        $settings = new ProductPresentationSettings();
        $settings->catalog_mode = Configuration::isCatalogMode();
        $settings->catalog_mode_with_prices = (int)Configuration::get('PS_CATALOG_MODE_WITH_PRICES');
        $settings->include_taxes = true;
        $settings->allow_add_variant_to_cart_from_listing = (int)Configuration::get('PS_ATTRIBUTE_CATEGORY_DISPLAY');
        $settings->stock_management_enabled = Configuration::get('PS_STOCK_MANAGEMENT');
        $settings->showPrices = Configuration::showPrices();
        $settings->lastRemainingItems = Configuration::get('PS_LAST_QTIES');
        $settings->showLabelOOSListingPages = (bool)Configuration::get('PS_SHOW_LABEL_OOS_LISTING_PAGES');

        //Récupération de l'instance du presenter
        $productPresenter = new ProductPresenterFactory($this->context, null);
        $presenter = $productPresenter->getPresenter();

        //Récupération d'une instance de l'assembler
        $assembler = new ProductAssembler($this->context);

        //Conversion de l'objet "Product" en tableau et ajout de son identifiant
        $product = [];
        $product['id_product'] = (int)$product_id;

        return $presenter->present(
            $settings,
            $assembler->assembleProduct($product),
            $this->context->language
        );
    }

    /**
     * @param array $hookParams
     */
    public function hookActionListMailThemes(array $hookParams)
    {
        if (!isset($hookParams['mailThemes'])) {
            return;
        }

        /** @var ThemeCollectionInterface $themes */
        $themes = $hookParams['mailThemes'];

        /** @var ThemeInterface $theme */
        foreach ($themes as $theme) {
            if (!in_array($theme->getName(), ['classic', 'modern', 'alo_mails'])) {
                continue;
            }

            $path = '@Modules/' . $this->name . '/mails/layouts/' . $theme->getName() . '_alo_contact.html.twig';
            $path2 = '@Modules/' . $this->name . '/mails/layouts/' . $theme->getName() . '_alo_delayed_shipping.html.twig';

            $theme->getLayouts()->add(new Layout(
                'alo_contact',
                $path,
                '',
                $this->name
            ));

            $theme->getLayouts()->add(new Layout(
                'alo_delayed_shipping',
                $path2,
                '',
                $this->name
            ));
        }

        // Copie des templates de mail générés dans le module dans le dossier /mails du thème
        $result = $this->copyMailTemplatesToTheme();

        if ($result) {
            $this->context->controller->confirmations[] = $this->l('Les templates de mail customs ont été copiés dans le thème.');
            PrestaShopLogger::addLog('Les templates de mail ont été copiés dans le thème.', 1, null, 'Module', $this->name, true);
        } else {
            $this->context->controller->errors[] = $this->l('Une erreur est survenue lors de la copie des templates de mail dans le thème.');
            PrestaShopLogger::addLog('Une erreur est survenue lors de la copie des templates de mail customs dans le thème.', 3, null, 'Module', $this->name, true);
        }
    }

    private function copyMailTemplatesToTheme()
    {
        $mail_templates = [
            'alo_delayed_shipping',
        ];

        $languages = Language::getLanguages();
        $result = true;

        foreach ($languages as $language) {
            foreach ($mail_templates as $mail_template) {
                $source = _PS_MODULE_DIR_ . $this->name . '/mails/' . $language['iso_code'] . '/' . $mail_template . '.html';
                $destination = _PS_MAIL_DIR_ . $language['iso_code'] . '/' . $mail_template . '.html';

                if (!file_exists($destination) && file_exists($source)) {
                    $result = $result && copy($source, $destination);
                }

                $source = _PS_MODULE_DIR_ . $this->name . '/mails/' . $language['iso_code'] . '/' . $mail_template . '.txt';
                $destination = _PS_MAIL_DIR_ . $language['iso_code'] . '/' . $mail_template . '.txt';

                if (!file_exists($destination) && file_exists($source)) {
                    $result = $result && copy($source, $destination);
                }
            }
        }

        return $result;
    }

    public function getI18nString($params)
    {
        if (!array_key_exists('c', $params) || !array_key_exists('s', $params)) {
            return '';
        }

        $lang = Context::getContext()->language->iso_code;

        include _PS_THEME_DIR_ . 'translations/lang.php';

        if (
            !isset($klcm)
            || !array_key_exists($params['c'], $klcm)
            || !array_key_exists($params['s'], $klcm[$params['c']])
            || !array_key_exists($lang, $klcm[$params['c']][$params['s']])
        ) {
            return 'A REMPLACER';
        }

        if (($klcm[$params['c']][$params['s']][$lang] === '')) {
            return 'A REMPLACER [clé : ' . $params['c'] . ' - sous-clé : ' . $params['s'] . ']';
        }

        return $klcm[$params['c']][$params['s']][$lang];
    }

    /**
     * @return bool
     */
    public function install(): bool
    {
        return parent::install()
            && $this->registerHooks()
            && $this->registerHook(ThemeCatalogInterface::LIST_MAIL_THEMES_HOOK)
            && AloProductExtraField::createDatabase();
    }

    /**
     * @return bool
     */
    public function registerHooks(): bool
    {
        foreach (self::HOOKS as $hook) {
            if (!$this->registerHook($hook)) {
                return false;
            }
        }

        return true;
    }

    public function uninstall()
    {
        return parent::uninstall();
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        /*
         * If values have been submitted in the form, process.
         */
        if ((Tools::isSubmit('submit_' . $this->name))) {
            $this->postProcess();
        }

        return $this->renderForm();
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {
        $inputs = $this->getConfigForm()['form']['input'];

        foreach ($inputs as $input) {
            if (
                $input['type'] === 'file'
                && array_key_exists($input['name'], $_FILES)
            ) {
                if ($_FILES[$input['name']]['size'] > 0) {
                    $values = self::imgProcess($input['name']);
                    Configuration::updateValue($input['name'], $values);
                }
            } else if (array_key_exists('autoload_rte', $input) && $input['autoload_rte']) {
                Configuration::updateValue($input['name'], Tools::getValue($input['name']), true);
            } else {
                Configuration::updateValue($input['name'], Tools::getValue($input['name']));
            }
        }
    }

    /**
     * Create the structure of your form.
     *
     * @see AdminPatternsController
     */
    protected function getConfigForm()
    {
        $cms_pages = CMS::getCMSPages($this->context->language->id, null, 1);
        $products = Product::getProducts($this->context->language->id, 0, 0, 'reference', 'ASC', false, true);
        $brands = Manufacturer::getManufacturers(false, $this->context->language->id, false);

        $config_form = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Settings'),
                    'icon' => 'icon-cogs',
                ],
                'tabs' => [
                    'concept_page' => 'Page Notre concept',
                    'faq' => 'FAQ',
                    'cgv' => 'CGV',
                    'brands' => 'Marques',
                    'size_guide' => 'Guides des tailles',
                ],
                'input' => [
                    [
                        'type' => 'select',
                        'label' => 'Page "Notre concept"',
                        'name' => 'ALO_CONCEPT_PAGE_ID',
                        'tab' => 'concept_page',
                        'desc' => 'Merci de créer une page "Notre concept" si elle n\'apparait pas dans la liste.',
                        'options' => [
                            'query' => $cms_pages,
                            'id' => 'id_cms',
                            'name' => 'meta_title',
                        ],
                    ],
                    [
                        'type' => 'select',
                        'label' => 'Destinataire du formulaire de contact',
                        'name' => 'ALO_CONCEPT_PAGE_CONTACT',
                        'tab' => 'concept_page',
                        'options' => [
                            'query' => Contact::getContacts($this->context->language->id),
                            'id' => 'id_contact',
                            'name' => 'name',
                        ],
                    ],
                    [
                        'type' => 'select',
                        'label' => 'Page "FAQ"',
                        'name' => 'ALO_FAQ_PAGE_ID',
                        'tab' => 'faq',
                        'desc' => 'Merci de créer une page "FAQ" si elle n\'apparait pas dans la liste.',
                        'options' => [
                            'query' => $cms_pages,
                            'id' => 'id_cms',
                            'name' => 'meta_title',
                        ],
                    ],
                    [
                        'type' => 'select',
                        'label' => 'Page "CGV"',
                        'name' => 'ALO_CGV_PAGE_ID',
                        'tab' => 'cgv',
                        'desc' => 'Merci de créer une page "CGV" si elle n\'apparait pas dans la liste.',
                        'options' => [
                            'query' => $cms_pages,
                            'id' => 'id_cms',
                            'name' => 'meta_title',
                        ],
                    ],
                    [
                        'type' => 'html',
                        'label' => '',
                        'name' => 'HTML',
                        'tab' => 'brands',
                        'html_content' => '<hr/><h3>' . $this->l('Produits incontournables Danton') . '</h3>',
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                ],
            ],
        ];

        $config_form['form']['input'][] =
            [
                'type' => 'select',
                'label' => 'Marque Danton',
                'name' => 'ALO_BRAND_1_ID',
                'col' => 3,
                'tab' => 'brands',
                'options' => [
                    'query' => $brands,
                    'id' => 'id_manufacturer',
                    'name' => 'name',
                ],
            ];

        if (Configuration::get('ALO_BRAND_1_ID') !== false) {
            $brand_id = Configuration::get('ALO_BRAND_1_ID');

            $config_form['form']['input'][] =
                [
                    'type' => 'select',
                    'label' => 'Marque Danton produit 1',
                    'name' => 'ALO_BRAND_' . $brand_id . '_FEATURED_PRODUCT_1',
                    'class' => 'chosen',
                    'col' => 3,
                    'tab' => 'brands',
                    'options' => [
                        'query' => $products,
                        'id' => 'id_product',
                        'name' => 'name',
                    ],
                ];

            $config_form['form']['input'][] =
                [
                    'type' => 'select',
                    'label' => 'Marque Danton produit 2',
                    'name' => 'ALO_BRAND_' . $brand_id . '_FEATURED_PRODUCT_2',
                    'class' => 'chosen',
                    'col' => 3,
                    'tab' => 'brands',
                    'options' => [
                        'query' => $products,
                        'id' => 'id_product',
                        'name' => 'name',
                    ],
                ];
        }

        $config_form['form']['input'][] =
            [
                'type' => 'html',
                'label' => '',
                'name' => 'HTML',
                'tab' => 'brands',
                'html_content' => '<hr/><h3>' . $this->l('Produits incontournables Le Glazik') . '</h3>',
            ];

        $config_form['form']['input'][] =
            [
                'type' => 'select',
                'label' => 'Marque Le Glazik',
                'name' => 'ALO_BRAND_2_ID',
                'col' => 3,
                'tab' => 'brands',
                'options' => [
                    'query' => $brands,
                    'id' => 'id_manufacturer',
                    'name' => 'name',
                ],
            ];

        if (Configuration::get('ALO_BRAND_2_ID') !== false) {
            $brand_id = Configuration::get('ALO_BRAND_2_ID');


            $config_form['form']['input'][] =
                [
                    'type' => 'select',
                    'label' => 'Marque 2 produit 1',
                    'name' => 'ALO_BRAND_' . $brand_id . '_FEATURED_PRODUCT_1',
                    'class' => 'chosen',
                    'col' => 3,
                    'tab' => 'brands',
                    'options' => [
                        'query' => $products,
                        'id' => 'id_product',
                        'name' => 'name',
                    ],
                ];

            $config_form['form']['input'][] =
                [
                    'type' => 'select',
                    'label' => 'Marque 2 produit 2',
                    'name' => 'ALO_BRAND_' . $brand_id . '_FEATURED_PRODUCT_2',
                    'class' => 'chosen',
                    'col' => 3,
                    'tab' => 'brands',
                    'options' => [
                        'query' => $products,
                        'id' => 'id_product',
                        'name' => 'name',
                    ],
                ];
        }

        $config_form['form']['input'][] =
            [
                'type' => 'html',
                'label' => '',
                'name' => 'HTML',
                'tab' => 'brands',
                'html_content' => '<hr/><h3>' . $this->l('Produits incontournables Orcival') . '</h3>',
            ];

        $config_form['form']['input'][] =
            [
                'type' => 'select',
                'label' => 'Marque Orcival',
                'name' => 'ALO_BRAND_3_ID',
                'col' => 3,
                'tab' => 'brands',
                'options' => [
                    'query' => $brands,
                    'id' => 'id_manufacturer',
                    'name' => 'name',
                ],
            ];

        if (Configuration::get('ALO_BRAND_3_ID') !== false) {
            $brand_id = Configuration::get('ALO_BRAND_3_ID');

            $config_form['form']['input'][] =
                [
                    'type' => 'select',
                    'label' => 'Marque 3 produit 1',
                    'name' => 'ALO_BRAND_' . $brand_id . '_FEATURED_PRODUCT_1',
                    'class' => 'chosen',
                    'col' => 3,
                    'tab' => 'brands',
                    'options' => [
                        'query' => $products,
                        'id' => 'id_product',
                        'name' => 'name',
                    ],
                ];

            $config_form['form']['input'][] =
                [
                    'type' => 'select',
                    'label' => 'Marque 3 produit 2',
                    'name' => 'ALO_BRAND_' . $brand_id . '_FEATURED_PRODUCT_2',
                    'class' => 'chosen',
                    'col' => 3,
                    'tab' => 'brands',
                    'options' => [
                        'query' => $products,
                        'id' => 'id_product',
                        'name' => 'name',
                    ],
                ];
        }

        $config_form['form']['input'][] =
            [
                'type' => 'html',
                'label' => '',
                'name' => 'HTML',
                'tab' => 'brands',
                'html_content' => '<hr/><h3>' . $this->l('Produits incontournables Vetra') . '</h3>',
            ];

        $config_form['form']['input'][] =
            [
                'type' => 'select',
                'label' => 'Marque Vetra',
                'name' => 'ALO_BRAND_4_ID',
                'col' => 3,
                'tab' => 'brands',
                'options' => [
                    'query' => $brands,
                    'id' => 'id_manufacturer',
                    'name' => 'name',
                ],
            ];

        if (Configuration::get('ALO_BRAND_4_ID') !== false) {
            $brand_id = Configuration::get('ALO_BRAND_4_ID');

            $config_form['form']['input'][] =
                [
                    'type' => 'select',
                    'label' => 'Marque 4 produit 1',
                    'name' => 'ALO_BRAND_' . $brand_id . '_FEATURED_PRODUCT_1',
                    'class' => 'chosen',
                    'col' => 3,
                    'tab' => 'brands',
                    'options' => [
                        'query' => $products,
                        'id' => 'id_product',
                        'name' => 'name',
                    ],
                ];

            $config_form['form']['input'][] =
                [
                    'type' => 'select',
                    'label' => 'Marque 4 produit 2',
                    'name' => 'ALO_BRAND_' . $brand_id . '_FEATURED_PRODUCT_2',
                    'class' => 'chosen',
                    'col' => 3,
                    'tab' => 'brands',
                    'options' => [
                        'query' => $products,
                        'id' => 'id_product',
                        'name' => 'name',
                    ],
                ];
        }

        $config_form['form']['input'][] =
            [
                'type' => 'html',
                'label' => '',
                'name' => 'HTML',
                'tab' => 'brands',
                'html_content' => '<hr/><h3>' . $this->l('Produits incontournables A l Ouvrier') . '</h3>',
            ];

        $config_form['form']['input'][] =
            [
                'type' => 'select',
                'label' => 'Marque A l Ouvrier',
                'name' => 'ALO_BRAND_5_ID',
                'col' => 3,
                'tab' => 'brands',
                'options' => [
                    'query' => $brands,
                    'id' => 'id_manufacturer',
                    'name' => 'name',
                ],
            ];

        if (Configuration::get('ALO_BRAND_5_ID') !== false) {
            $brand_id = Configuration::get('ALO_BRAND_5_ID');

            $config_form['form']['input'][] =
                [
                    'type' => 'select',
                    'label' => 'Marque 5 produit 1',
                    'name' => 'ALO_BRAND_' . $brand_id . '_FEATURED_PRODUCT_1',
                    'class' => 'chosen',
                    'col' => 3,
                    'tab' => 'brands',
                    'options' => [
                        'query' => $products,
                        'id' => 'id_product',
                        'name' => 'name',
                    ],
                ];

            $config_form['form']['input'][] =
                [
                    'type' => 'select',
                    'label' => 'Marque 5 produit 2',
                    'name' => 'ALO_BRAND_' . $brand_id . '_FEATURED_PRODUCT_2',
                    'class' => 'chosen',
                    'col' => 3,
                    'tab' => 'brands',
                    'options' => [
                        'query' => $products,
                        'id' => 'id_product',
                        'name' => 'name',
                    ],
                ];
        }

        $config_form['form']['input'][] =
            [
                'type' => 'html',
                'label' => '',
                'name' => 'HTML',
                'tab' => 'brands',
                'html_content' => '<hr/><h3>' . $this->l('Produits incontournables Sigrist') . '</h3>',
            ];

        $config_form['form']['input'][] =
            [
                'type' => 'select',
                'label' => 'Marque Sigrist',
                'name' => 'ALO_BRAND_6_ID',
                'col' => 3,
                'tab' => 'brands',
                'options' => [
                    'query' => $brands,
                    'id' => 'id_manufacturer',
                    'name' => 'name',
                ],
            ];

        if (Configuration::get('ALO_BRAND_6_ID') !== false) {
            $brand_id = Configuration::get('ALO_BRAND_6_ID');

            $config_form['form']['input'][] =
                [
                    'type' => 'select',
                    'label' => 'Marque 6 produit 1',
                    'name' => 'ALO_BRAND_' . $brand_id . '_FEATURED_PRODUCT_1',
                    'class' => 'chosen',
                    'col' => 3,
                    'tab' => 'brands',
                    'options' => [
                        'query' => $products,
                        'id' => 'id_product',
                        'name' => 'name',
                    ],
                ];

            $config_form['form']['input'][] =
                [
                    'type' => 'select',
                    'label' => 'Marque 6 produit 2',
                    'name' => 'ALO_BRAND_' . $brand_id . '_FEATURED_PRODUCT_2',
                    'class' => 'chosen',
                    'col' => 3,
                    'tab' => 'brands',
                    'options' => [
                        'query' => $products,
                        'id' => 'id_product',
                        'name' => 'name',
                    ],
                ];
        }

        $manufacturer_data_list = Manufacturer::getManufacturers();
        $language_data_list = Language::getLanguages();


        foreach ($manufacturer_data_list as $manufacturer_data) {
            foreach ($language_data_list as $language_data) {
                $config_form['form']['input'][] =
                    [
                        'type' => 'textarea',
                        'lang' => false,
                        'autoload_rte' => true,
                        'label' => $manufacturer_data['name'] . ' (' . $language_data['iso_code'] . ')',
                        'name' => 'ALO_SIZE_TABLE_' . $manufacturer_data['id_manufacturer'] . '_' . $language_data['id_lang'],
                        'tab' => 'size_guide'
                    ];
            }
        }

        return $config_form;
    }

    /**
     * Upload l'image et retourne le nom pour enregistrement ailleurs qu'en table configuration
     * @param string $name
     * @param int|null $width
     * @param int|null $height
     * @return bool|mixed|string
     */
    public static function imgProcess(string $name, int $width = null, int $height = null)
    {
        if ($width === null) {
            $filename = $_FILES[$name]['tmp_name'];
            $size = getimagesize($filename);
            $width = $size[0];
            $height = $size[1];
        }

        $values = [];
        $update_images_values = false;
        $key = $name;

        if (isset($_FILES[$key]['tmp_name']) && !empty($_FILES[$key]['tmp_name'])) {
            if ($error = ImageManager::validateUpload($_FILES[$key], 4000000)) {
                return $error;
            }

            $ext = substr($_FILES[$key]['name'], strrpos($_FILES[$key]['name'], '.') + 1);
            $file_name = md5($_FILES[$key]['name']) . '.' . $ext;

            if (!ImageManager::resize(
                $_FILES[$key]['tmp_name'],
                __DIR__ . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . $file_name,
                $width,
                $height,
                $ext,
                false,
                $error
            )) {
                return $error;
            }

            $values[$name] = $file_name;

            $update_images_values = true;
        }

        if ($update_images_values) {
            return $values[$name];
        }

        return false;
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submit_' . $this->name;
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = [
            'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        ];

        return $helper->generateForm([$this->getConfigForm()]);
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues()
    {
        $inputs = $this->getConfigForm()['form']['input'];
        $output = [];

        foreach ($inputs as $input) {
            $output[$input['name']] = Configuration::get($input['name']);
        }

        return $output;
    }

    /* Envoyer les couleurs disponibles pour le produit */
    public function hookDisplayProductColors($params)
    {
        if (!isset($params['product_id'])) {
            return '';
        }

        $product_id = (int)$params['product_id'];

        $output = $this->getColorAttributeOptions($product_id);

        return $output;
    }


    public function getColorAttributeOptions(int $product_id)
    {
        $attribute_data_list = Product::getAttributesColorList([$product_id], false);

        $output = '<ul class="product-color-texture-list">';

        if (isset($attribute_data_list[$product_id]) && is_array($attribute_data_list[$product_id])) {
            $attribute_list = $attribute_data_list[$product_id];

            if (empty($attribute_list)) {
                $output .= '<li class="product-color-texture-thumbnail"></li>';
            } else {
                foreach ($attribute_list as $attribute) {
                    $background_style = '';

                    if (!empty($attribute['texture'])) {
                        $background_style = 'background-image: url(' . $attribute['texture'] . ');';
                    } elseif (!empty($attribute['color'])) {
                        $background_style = 'background-color:' . $attribute['color'] . ';';
                    }

                    $output .= '<li class="product-color-texture-thumbnail" style="' . $background_style . '"></li>';
                }
            }
        } else {
            $output .= '<li class="product-color-texture-thumbnail"></li>';
        }

        $output .= '</ul>';

        return $output;
    }


    /* Envoyer une class si un produit n'est pas en stock en fonction de la couleur et/ou taille sélectionnée */
    public function hookDisplayAvailabilityClass($params)
    {
        $id_product = (int)$params['id_product'];
        $id_attribute = (int)$params['id_attribute'];

        $list = $params['groups'];

        $selectedColorAttributeId = $this->getSelectedColorAttributeId($list);

        $sql = new DbQuery();
        $sql->select('pa.`id_product_attribute`');
        $sql->from('product_attribute', 'pa');
        $sql->leftJoin('product_attribute_combination', 'pac', 'pa.`id_product_attribute` = pac.`id_product_attribute`');
        $sql->leftJoin('attribute', 'a', 'pac.`id_attribute` = a.`id_attribute`');
        $sql->where('pa.`id_product` = ' . (int)$id_product . ' AND a.`id_attribute` = ' . (int)$id_attribute);

        $product_attribute_id = Db::getInstance()->executeS($sql);

        if ($selectedColorAttributeId === null) {

            if (!is_array($product_attribute_id) || !array_key_exists(0, $product_attribute_id) || !array_key_exists('id_product_attribute', $product_attribute_id[0])) {
                return '';
            }

            $stock_available = StockAvailable::getQuantityAvailableByProduct($id_product, $product_attribute_id[0]['id_product_attribute']);

            $availability_message = ($stock_available === 0) ? 'unavailable' : ' ';

            return $availability_message;
        }

        if (!$product_attribute_id) {
            return '';
        }

        $matchingProductAttributeId = $this->getMatchingProductAttributeId($product_attribute_id, $selectedColorAttributeId);

        $stock_available = StockAvailable::getQuantityAvailableByProduct($id_product, $matchingProductAttributeId);

        $availability_message = ($stock_available === 0) ? 'unavailable' : ' ';

        return $availability_message;
    }

    public function getSelectedColorAttributeId($list)
    {
        foreach ($list as $group) {
            if ($group["group_type"] === "color") {
                foreach ($group["attributes"] as $attributeId => $attribute) {
                    if ($attribute["selected"]) {
                        return $attributeId;
                    }
                }
            }
        }
        return null;
    }

    public function getMatchingProductAttributeId($product_attribute_id, $selectedColorAttributeId)
    {

        foreach ($product_attribute_id as $attribute) {
            $idProductAttribute = (int)$attribute["id_product_attribute"];

            $sql = new DbQuery();
            $sql->select('id_product_attribute');
            $sql->from('product_attribute_combination');
            $sql->where('id_product_attribute = ' . (int)$idProductAttribute . ' AND id_attribute = ' . (int)$selectedColorAttributeId);

            $result = Db::getInstance()->getValue($sql);

            if ($result) {
                return $idProductAttribute;
            }
        }

        return null;
    }


    public function hookActionGetExtraMailTemplateVars($params)
    {
        if ($params['template'] === 'order_conf') {
            $id_order = $params['template_vars']['{id_order}'];
            $order = new Order($id_order);

            if (!Validate::isLoadedObject($order)) {
                PrestaShopLogger::addLog('Order object not loaded', 3, null, 'Order', $id_order, true);
                return;
            }

            $product_data_list = $order->getProducts();

            foreach ($product_data_list as $product_data) {
                $key = '{cover_' . $product_data['product_id'] . '_' . $product_data['product_attribute_id'] . '}';
                $params['extra_template_vars'][$key] = $this->getMailExtraProductCover($product_data);
                $key = '{manufacturer_' . $product_data['product_id'] . '}';
                $params['extra_template_vars'][$key] = $this->getMailExtraProductManufacturer($product_data);
            }
        }

        if ($params['template'] === 'shipped') {
            $id_order = $params['template_vars']['{id_order}'];
            $order = new Order($id_order);
            if (!Validate::isLoadedObject($order)) {
                PrestaShopLogger::addLog('Order object not loaded', 3, null, 'Order', $id_order, true);
                return;
            }

            $carrier = new Carrier($order->id_carrier, $this->context->language->id);
            if (!Validate::isLoadedObject($carrier)) {
                PrestaShopLogger::addLog('Carrier object not loaded', 3, null, 'Carrier', $order->id_carrier, true);
                return;
            }

            $params['extra_template_vars']['{carrier_name}'] = $carrier->name;


            PrestaShopLogger::addLog(json_encode($params), null, null, null, null, true);
        }

        if ($params['template'] === 'alo_delayed_shipping') {
            // création de la variable {products}
            $id_order = $params['template_vars']['{id_order}'];
            $order = new Order($id_order);

            if (!Validate::isLoadedObject($order)) {
                PrestaShopLogger::addLog('Order object not loaded', 3, null, 'Order', $id_order, true);
                return;
            }

            $product_data_list = $order->getProducts();

            foreach ($product_data_list as $key_product => $product_data) {
                $product_data_list[$key_product]['id_product_attribute'] = $product_data['product_attribute_id'];
                $product_data_list[$key_product]['name'] = $product_data['product_name'];
                $product_data_list[$key_product]['quantity'] = $product_data['product_quantity'];
                $product_data_list[$key_product]['customization'] = [];
                $product_data_list[$key_product]['price'] = Tools::displayPrice($product_data['price']);
                $product_data_list[$key_product]['manufacturer_name'] = $this->getMailExtraProductManufacturer($product_data);
                $product_data_list[$key_product]['cover_url'] = $this->getMailExtraProductCover($product_data);
            }

            $this->context->smarty->assign([
                'list' => $product_data_list,
            ]);

            $params['extra_template_vars']['{products}'] = $this->context->smarty->fetch(
                _PS_MODULE_DIR_ . $this->name
                . DIRECTORY_SEPARATOR . 'mails'
                . DIRECTORY_SEPARATOR . '_partials'
                . DIRECTORY_SEPARATOR . 'alo_delayed_shipping_product_list.tpl'
            );

        }

        if (($params['template'] === 'alo_preco_reminder') && Module::getInstanceByName('alo_preorder')) {

            $id_order = $params['template_vars']['{id_order}'];
            $order = new Order($id_order);

            $product_data_list = $order->getProducts();

            foreach ($product_data_list as $key_product => $product_data) {
                $product_data_list[$key_product]['id_product_attribute'] = $product_data['product_attribute_id'];
                $product_data_list[$key_product]['name'] = $product_data['product_name'];
                $product_data_list[$key_product]['quantity'] = $product_data['product_quantity'];
                $product_data_list[$key_product]['customization'] = [];
                $product_data_list[$key_product]['price'] = Tools::displayPrice($product_data['price']);
                $product_data_list[$key_product]['manufacturer_name'] = $this->getMailExtraProductManufacturer($product_data);
                $product_data_list[$key_product]['cover_url'] = $this->getMailExtraProductCover($product_data);
            }

            $this->context->smarty->assign([
                'list' => $product_data_list,
            ]);

            $params['extra_template_vars']['{products}'] = $this->context->smarty->fetch(
                _PS_MODULE_DIR_ . $this->name
                . DIRECTORY_SEPARATOR . 'mails'
                . DIRECTORY_SEPARATOR . '_partials'
                . DIRECTORY_SEPARATOR . 'alo_preco_reminder_product_list.tpl'
            );
        }

        if (($params['template'] === 'alo_preco_success') && Module::getInstanceByName('alo_preorder')) {

            $id_order = $params['template_vars']['{id_order}'];
            $order = new Order($id_order);

            $product_data_list = $order->getProducts();

            foreach ($product_data_list as $key_product => $product_data) {
                $product_data_list[$key_product]['id_product_attribute'] = $product_data['product_attribute_id'];
                $product_data_list[$key_product]['name'] = $product_data['product_name'];
                $product_data_list[$key_product]['quantity'] = $product_data['product_quantity'];
                $product_data_list[$key_product]['customization'] = [];
                $product_data_list[$key_product]['price'] = Tools::displayPrice($product_data['price']);
                $product_data_list[$key_product]['manufacturer_name'] = $this->getMailExtraProductManufacturer($product_data);
                $product_data_list[$key_product]['cover_url'] = $this->getMailExtraProductCover($product_data);
            }

            $sql = 'SELECT acpl.production_time
                    FROM ' . _DB_PREFIX_ . AloPrecoPreorder::TABLE_NAME . ' app
                    LEFT JOIN ' . _DB_PREFIX_ . AloPrecoConfig::TABLE_NAME . '_lang acpl ON app.' . AloPrecoConfig::PRIMARY_KEY . ' = acpl.' . AloPrecoConfig::PRIMARY_KEY . '
                    WHERE app.id_order = ' . (int)$id_order;

            $production_time = Db::getInstance()->getValue($sql);

            $this->context->smarty->assign([
                'list' => $product_data_list,
                'production_time' => $production_time,
            ]);

            $params['extra_template_vars']['{products}'] = $this->context->smarty->fetch(
                _PS_MODULE_DIR_ . $this->name
                . DIRECTORY_SEPARATOR . 'mails'
                . DIRECTORY_SEPARATOR . '_partials'
                . DIRECTORY_SEPARATOR . 'alo_preco_success_product_list.tpl'
            );

            $params['extra_template_vars']['{production_time}'] = $this->context->smarty->fetch(
                _PS_MODULE_DIR_ . $this->name
                . DIRECTORY_SEPARATOR . 'mails'
                . DIRECTORY_SEPARATOR . '_partials'
                . DIRECTORY_SEPARATOR . 'alo_preco_success_shipping.tpl'
            );


        }

        if (($params['template'] === 'alo_preco_failed') && Module::getInstanceByName('alo_preorder')) {
            $product_data_list = [];

            $current_product_attributes = Db::getInstance()->executeS("
                        SELECT cppa.id_product_attribute
                        FROM " . _DB_PREFIX_ . AloPrecoConfig::TABLE_NAME . " cp
                        LEFT JOIN " . _DB_PREFIX_ . AloPrecoConfigProductAttribute::TABLE_NAME . " cppa ON cp.".AloPrecoConfig::PRIMARY_KEY." = cppa.".AloPrecoConfig::PRIMARY_KEY."
                        WHERE cp.date_begin <= NOW() AND cp.date_end >= NOW()
                    ");

            if (!empty($current_product_attributes)) {
                $random_key = array_rand($current_product_attributes);
                $id_product_attribute1 = $current_product_attributes[$random_key]['id_product_attribute'];
                unset($current_product_attributes[$random_key]);

                $random_key = array_rand($current_product_attributes);
                $id_product_attribute2 = $current_product_attributes[$random_key]['id_product_attribute'];

                foreach ([$id_product_attribute1, $id_product_attribute2] as $id_product_attribute) {
                    $combination = new Combination($id_product_attribute);
                    $id_product = $combination->id_product;

                    $product_data_list[] = $this->getProductMailPreco($id_product);
                }
            }

            $this->context->smarty->assign([
                'list' => $product_data_list
            ]);

            try {
                $params['extra_template_vars']['{preco_list}'] = $this->context->smarty->fetch(
                    _PS_MODULE_DIR_ . $this->name
                    . DIRECTORY_SEPARATOR . 'mails'
                    . DIRECTORY_SEPARATOR . '_partials'
                    . DIRECTORY_SEPARATOR . 'alo_preco_failed_preco_product_list.tpl'
                );
            } catch (SmartyException $e) {
                PrestaShopLogger::addLog(
                    'Template mail alo_preco_failed : ' . $e->getMessage(),
                    PrestaShopLogger::LOG_SEVERITY_LEVEL_ERROR,
                    null,
                    null,
                    null,
                    true
                );
            }
        }

        if (($params['template'] === 'alo_preco_produced') && Module::getInstanceByName('alo_preorder')) {
            $id_order = $params['template_vars']['{id_order}'];
            $order = new Order($id_order);

            $product_data_list = $order->getProducts();

            foreach ($product_data_list as $key_product => $product_data) {
                $product_data_list[$key_product]['id_product_attribute'] = $product_data['product_attribute_id'];
                $product_data_list[$key_product]['name'] = $product_data['product_name'];
                $product_data_list[$key_product]['quantity'] = $product_data['product_quantity'];
                $product_data_list[$key_product]['customization'] = [];
                $product_data_list[$key_product]['price'] = Tools::displayPrice($product_data['price']);
                $product_data_list[$key_product]['manufacturer_name'] = $this->getMailExtraProductManufacturer($product_data);
                $product_data_list[$key_product]['cover_url'] = $this->getMailExtraProductCover($product_data);
            }

            $sql = 'SELECT acp.date_shipping
                    FROM ' . _DB_PREFIX_ . 'alo_preco_preorder app
                    LEFT JOIN ' . _DB_PREFIX_ . AloPrecoConfig::TABLE_NAME . ' acp ON app.'.AloPrecoConfig::PRIMARY_KEY.' = acp.'.AloPrecoConfig::PRIMARY_KEY.'
                    WHERE app.id_order = ' . (int)$id_order;

            $date_shipping = Db::getInstance()->getValue($sql);

            if ($date_shipping) {
                $formatted_date_shipping = date('d / m / Y', strtotime($date_shipping));

                $this->context->smarty->assign([
                    'list' => $product_data_list,
                    'shipping_date' => $formatted_date_shipping,
                ]);

                $params['extra_template_vars']['{products}'] = $this->context->smarty->fetch(
                    _PS_MODULE_DIR_ . $this->name
                    . DIRECTORY_SEPARATOR . 'mails'
                    . DIRECTORY_SEPARATOR . '_partials'
                    . DIRECTORY_SEPARATOR . 'alo_preco_produced_product_list.tpl'
                );

                $params['extra_template_vars']['{$shipping_date}'] = $this->context->smarty->fetch(
                    _PS_MODULE_DIR_ . $this->name
                    . DIRECTORY_SEPARATOR . 'mails'
                    . DIRECTORY_SEPARATOR . '_partials'
                    . DIRECTORY_SEPARATOR . 'alo_preco_produced_shipping.tpl'
                );
            }
        }
    }

    public function getMailExtraProductCover($product_data): string
    {
        return $this->context->link->getImageLink(
            $product_data['product_id'] . '-' . $product_data['image']->id,
            $product_data['product_id'] . '-' . $product_data['image']->id
        );
    }

    public function getMailExtraProductManufacturer($product_data): string
    {
        if (!isset($product_data['id_manufacturer'])) {
            PrestaShopLogger::addLog(
                'Manufacturer id not set',
                PrestaShopLogger::LOG_SEVERITY_LEVEL_ERROR,
                null,
                'Manufacturer',
                $product_data['id_manufacturer'],
                true
            );
            return '';
        }

        $manufacturer = new Manufacturer($product_data['id_manufacturer'], $this->context->language->id);

        if (!Validate::isLoadedObject($manufacturer)) {
            PrestaShopLogger::addLog(
                'Manufacturer object not loaded',
                PrestaShopLogger::LOG_SEVERITY_LEVEL_ERROR,
                null,
                'Manufacturer',
                $product_data['id_manufacturer'],
                true
            );
            return '';
        }

        return $manufacturer->name;
    }

    public function getProductMailPreco(int $product_id)
    {
        if (!$product_id) {
            $product_id = 1;
        }

        // Inspiré de https://www.h-hennes.fr/blog/2022/02/21/prestashop-afficher-les-commentaires-produits-dans-les-onglets-de-la-fiche-produit/

        //Gestion des paramètres de présentation
        $settings = new ProductPresentationSettings();
        $settings->catalog_mode = Configuration::isCatalogMode();
        $settings->catalog_mode_with_prices = (int)Configuration::get('PS_CATALOG_MODE_WITH_PRICES');
        $settings->include_taxes = true;
        $settings->allow_add_variant_to_cart_from_listing = (int)Configuration::get('PS_ATTRIBUTE_CATEGORY_DISPLAY');
        $settings->stock_management_enabled = Configuration::get('PS_STOCK_MANAGEMENT');
        $settings->showPrices = Configuration::showPrices();
        $settings->lastRemainingItems = Configuration::get('PS_LAST_QTIES');
        $settings->showLabelOOSListingPages = (bool)Configuration::get('PS_SHOW_LABEL_OOS_LISTING_PAGES');

        //Récupération de l'instance du presenter
        $productPresenter = new ProductPresenterFactory($this->context, null);
        $presenter = $productPresenter->getPresenter();

        //Récupération d'une instance de l'assembler
        $assembler = new ProductAssembler($this->context);

        //Conversion de l'objet "Product" en tableau et ajout de son identifiant
        $product = [];
        $product['id_product'] = (int)$product_id;

        return $presenter->present(
            $settings,
            $assembler->assembleProduct($product),
            $this->context->language
        );
    }

    /**
     * Ajout du nom et prénom dans le message du formulaire de contact
     * @param $params
     * @return void
     */
    public function hookActionEmailSendBefore($params)
    {
        if ($params['template'] !== 'contact') {
            return;
        }

        $params['templateVars']['{message}'] =
            'Nom : ' . Tools::getValue('firstname') . '<br/>'
            . 'Prénom : ' . Tools::getValue('lastname') . '<br/><br/>'
            . $params['templateVars']['{message}'];
    }
}
