<h2>{l s='Section ICONIC' mod='alo_theme'}</h2>
<div class="row">
    <div class="col-lg-11 col-xl-6">
        <fieldset class="form-group mb-0">
            <label class="form-control-label">{l s='Titre' mod='alo_theme'}</label>
            <div class="translations tabbable" id="form_iconic_title">
                <div class="translationsFields tab-content">
                    {foreach from=Language::getLanguages() item=language_data}
                        {if $language_data.language_code==="en-gb"}
                            {assign var="local_language_code" value="en"}
                        {else}
                            {assign var="local_language_code" value=$language_data.language_code}
                        {/if}
                    <div data-locale="{$local_language_code}"
                         class="translationsFields-form_iconic_title_{$language_data.id_lang} tab-pane translation-field show active translation-label-{$local_language_code}">
                        <input type="text"
                               id="form_iconic_title_{$language_data.id_lang}"
                               name="iconic[title][{$language_data.id_lang}]"
                               aria-label="form_iconic_title_{$language_data.id_lang} saisie"
                               value="{if $product_extra_field->iconic_title}{$product_extra_field->iconic_title[$language_data.id_lang]}{/if}"
                               class="form-control">
                    </div>
                    {/foreach}
                </div>
            </div>
        </fieldset>
    </div>
</div>
<div class="row">
    <div class="col-md-9">
        <fieldset class="form-group">
            <label popover="Cette description apparaîtra dans les moteurs de recherche. Elle doit consister en une seule phrase de 160 caractères maximum (espaces comprises)." popover_placement="right" class="px-0 form-control-label">{l s='Texte' mod='alo_theme'}</label>
            <div class="translations tabbable" id="form_step5_meta_description">
                <div class="translationsFields tab-content">
                    {foreach from=Language::getLanguages() item=language_data}
                        {if $language_data.language_code==="en-gb"}
                            {assign var="local_language_code" value="en"}
                        {else}
                            {assign var="local_language_code" value=$language_data.language_code}
                        {/if}
                    <div data-locale="{$local_language_code}"
                         class="translationsFields-form_iconic_text_{$language_data.id_lang} tab-pane translation-field show active translation-label-{$local_language_code}">
                        <textarea id="form_iconic_text_{$language_data.id_lang}"
                                  name="iconic[text][{$language_data.id_lang}]"
                                  placeholder="Paragraphes de la section" counter="160" counter_type="recommended"
                                  class="serp-watched-description form-control">{if $product_extra_field->iconic_text}{$product_extra_field->iconic_text[$language_data.id_lang]}{/if}</textarea>
                        <small class="form-text text-muted text-right maxLength ">
                            <em><span class="currentLength">0</span> des <span class="currentTotalMax">160</span> caractères utilisés (recommandé)</em>
                        </small>
                    </div>
                    {/foreach}
                </div>
            </div>
        </fieldset>
    </div>
</div>
<div class="row">
    <div class="col-lg-11 col-xl-9">
        <fieldset class="form-group mb-0">
            <label class="form-control-label">{l s='Url de l\'image' mod='alo_theme'}</label>
            <div class="translations tabbable" id="form_iconic_img_url">
                <div class="translationsFields tab-content">
                    {foreach from=Language::getLanguages() item=language_data}
                        {if $language_data.language_code==="en-gb"}
                            {assign var="local_language_code" value="en"}
                        {else}
                            {assign var="local_language_code" value=$language_data.language_code}
                        {/if}
                        <div data-locale="{$local_language_code}"
                             class="translationsFields-form_iconic_img_url_{$language_data.id_lang} tab-pane translation-field show active translation-label-{$local_language_code}">
                            <input type="text"
                                   id="form_iconic_img_url_{$language_data.id_lang}"
                                   name="iconic[img_url][{$language_data.id_lang}]"
                                   aria-label="form_iconic_img_url_{$language_data.id_lang} saisie"
                                   value="{if $product_extra_field->iconic_img_url}{$product_extra_field->iconic_img_url[$language_data.id_lang]}{/if}"
                                   class="form-control">
                        </div>
                    {/foreach}
                </div>
            </div>
        </fieldset>
    </div>
</div>
