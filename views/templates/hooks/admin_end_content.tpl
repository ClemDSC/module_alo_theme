<form name="manufacturer-size-table" method="post" class="form-horizontal" data-alerts-success="0" data-alerts-info="0"
      data-alerts-warning="0" data-alerts-error="0" data-form-submitted="0" data-form-valid="1">
    <div class="card">
        <h3 class="card-header">
            <i class="material-icons">star</i>
            Guide des tailles
        </h3>
        <div class="card-body">
            <div class="form-wrapper">
                <div id="manufacturer-size-table">
                    <div class="form-group row">
                        <label class="form-control-label">
                            Tableau :
                        </label>
                        <div class="col-sm input-container">
                            <div class="input-group locale-input-group js-locale-input-group d-flex"
                                 id="manufacturer-size-table" tabindex="1">
                                <div data-lang-id="1" class=" js-locale-input js-locale-fr" style="flex-grow: 1;">
                                    <textarea id="manufacturer-size-table_1"
                                              name="manufacturer-size-table[size-guide][1]"
                                              class="form-control form-control"
                                              value="">{if $size_guide_fr}{$size_guide_fr}{/if}</textarea>
                                </div>
                                <div data-lang-id="2" class=" js-locale-input js-locale-gb d-none"
                                     style="flex-grow: 1;">
                                    <textarea id="manufacturer-size-table_2"
                                              name="manufacturer-size-table[size-guide][2]"
                                              class="form-control form-control"
                                              value="">{if $size_guide_gb}{$size_guide_gb}{/if}</textarea>
                                </div>
                                <div class="dropdown">
                                    <button class="btn btn-outline-secondary dropdown-toggle js-locale-btn"
                                            type="button" data-toggle="dropdown" aria-haspopup="true"
                                            aria-expanded="false" id="manufacturer_meta_description_dropdown">
                                        fr
                                    </button>
                                    <div class="dropdown-menu locale-dropdown-menu"
                                         aria-labelledby="manufacturer_size-table_dropdown"><span
                                                class="dropdown-item js-locale-item"
                                                data-locale="fr">Français (French)</span><span
                                                class="dropdown-item js-locale-item" data-locale="gb">English (English)</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-footer" style="height: 3.5rem">
            <button class="btn btn-primary float-right">
                Enregistrer
            </button>
        </div>
    </div>
</form>
