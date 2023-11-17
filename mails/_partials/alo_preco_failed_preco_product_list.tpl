{if $list}
    <tr style="display:block; padding-left: 16px">
        <td style="border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt;font-family:Work Sans, Open sans, arial, sans-serif;font-weight: 900;font-size: 16px;line-height: 19px;color: #313648;text-transform: uppercase; padding-top: 32px; padding-bottom: 24px;">
            Produits en précommande actuellement :
        </td>
    </tr>
    <tr>
        <td style="border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt;">
            <table cellspacing="0" cellpadding="0" border="0" width="100%">
                <tr>
                    {foreach $list as $product}
                        <td valign="top" width="50%" style="padding: 0 8px;">
                            <table cellspacing="0" cellpadding="0" border="0" width="100%">
                                <tr>
                                    <td style="text-align: center">
                                        <img style="width: 296px" src="{$product.cover.bySize.large_default.url}"
                                             alt="{$product.manufacturer_name}">
                                    </td>
                                </tr>
                                <tr>
                                    <td style="font-size: 16px; font-family: Work Sans, Open sans, arial, sans-serif;">
                                        <p style="font-weight: 700; text-transform: uppercase; margin-bottom: 0;">{$product.manufacturer_name}</p>
                                        <p style="margin: 0">{$product.name}</p>
                                        <p style="margin-bottom: 38px; margin-top: 0">{$product.regular_price}</p>
                                        <a style="background-color: #313648; text-transform: uppercase; color: white; font-weight: 700; border-radius: 8px; padding: 16px; text-decoration: none;"
                                           href="{$product.link}">{l s='Découvrir' mod='alo_theme'}</a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    {/foreach}
                </tr>
            </table>
        </td>
    </tr>
{/if}
