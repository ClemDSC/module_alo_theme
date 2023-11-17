{**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/OSL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://devdocs.prestashop.com/ for more information.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 *}
{foreach $list as $product}
    <tr>
        <td>
            <table class="table">
                <tr>
                    <td width="5">&nbsp;</td>
                    <td>
                        <div style="width: 109px;height: 154px;display: flex;place-items: center;">
                            <img style="width: 100%"
                                 src="{$product['cover_url']}"
                                 alt="{$product['name']}">
                        </div>
                    </td>
                    <td width="5">&nbsp;</td>
                </tr>
            </table>
        </td>
        <td>
            <table class="table">
                <tr>
                    <td width="5">&nbsp;</td>
                    <td>
                        <p style="font-family:Work Sans, Open sans, arial, sans-serif;font-weight: 700;font-size: 16px;line-height: 19px;text-transform: uppercase;color: #313648;margin: 0;">{if array_key_exists('manufacturer_name', $product)}{$product['manufacturer_name']}{else}{literal}{manufacturer_{/literal}{$product['id_product']}{literal}}{/literal}{/if}</p>
                        <p style="font-family:Work Sans, Open sans, arial, sans-serif;font-weight: 400;font-size: 16px;line-height: 19px;text-transform: lowercase;color: #313648;margin: 0;">
                            {$product['name']}
                            {if count($product['customization']) == 1}
                                <br>
                                {foreach $product['customization'] as $customization}
                                    {$customization['customization_text']}
                                {/foreach}
                            {/if}

                            {hook h='displayProductPriceBlock' product=$product type="unit_price"}
                            <span>x {$product['quantity']}</span>
                        </p>
                    </td>
                    <td width="5">&nbsp;</td>
                </tr>
            </table>
        </td>
        {*<td>
            <table class="table">
                <tr>
                    <td width="5">&nbsp;</td>
                    <td align="right">
                        <font size="2" face="Open-sans, sans-serif" color="#555454">
                            {$product['unit_price']}
                        </font>
                    </td>
                    <td width="5">&nbsp;</td>
                </tr>
            </table>
        </td>*}
        {*<td>
            <table class="table">
                <tr>
                    <td width="5">&nbsp;</td>
                    <td align="right">
                        <font size="2" face="Open-sans, sans-serif" color="#555454">
                            {$product['quantity']}
                        </font>
                    </td>
                    <td width="5">&nbsp;</td>
                </tr>
            </table>
        </td>*}
        <td>
            <table class="table" align="right">
                <tr>
                    <td width="5">&nbsp;</td>
                    <td align="right">
					<span style="font-family:Work Sans, Open sans, arial, sans-serif;font-weight: 400;font-size: 16px;line-height: 19px;text-transform: uppercase;">
						{$product['price']}
					</span>
                    </td>
                    <td width="5">&nbsp;</td>
                </tr>
            </table>
        </td>
    </tr>
    {if count($product['customization']) > 1}
        {foreach $product['customization'] as $customization}
            <tr>
                <td colspan="3">
                    <table class="table">
                        <tr>
                            <td width="5">&nbsp;</td>
                            <td>
                                <font size="2" face="Open-sans, sans-serif" color="#555454">
                                    {$customization['customization_text']}
                                </font>
                            </td>
                            <td width="5">&nbsp;</td>
                        </tr>
                    </table>
                </td>
                <td>
                    <table class="table">
                        <tr>
                            <td width="5">&nbsp;</td>
                            <td align="right">
                                <font size="2" face="Open-sans, sans-serif" color="#555454">
                                    {if count($product['customization']) > 1}
                                        {$customization['customization_quantity']}
                                    {/if}
                                </font>
                            </td>
                            <td width="5">&nbsp;</td>
                        </tr>
                    </table>
                </td>
                <td></td>
            </tr>
        {/foreach}
    {/if}
{/foreach}
