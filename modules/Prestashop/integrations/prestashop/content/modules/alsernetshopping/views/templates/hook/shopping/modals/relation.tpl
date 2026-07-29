<div id="shopping-modal" class="shopping-modal modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-md" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title h6 text-sm-left" id="myModalLabel">
                    {$translations.title}
                </h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="{l s='Close' mod='alsernetshopping'}">
                    <span aria-hidden="true"><i class="material-icons">close</i></span>
                </button>
            </div>
            <div class="modal-body">
                <div class="relation-container">
                    <div class="row">
                        <div class="col-md-12 col-sm-13 col-xs-12 col-sp-12 relation-img">
                            <i class="fa-duotone fa-solid fa-clipboard-check"></i>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12 col-sm-12 col-xs-12 col-sp-12 relation-type">
                            <div class="title">{$category_relation.title}</div>
                            <div class="text">{$category_relation.description}</div>
                            <div class="actions">{$category_relation.action}</div>
                        </div>

                        <div class="col-md-12 col-sm-12 col-xs-12 col-sp-12 relation-buttons">
                            <a href="{$url_category_target}" class="btn btn-primary">{$category_relation.button_yes}</a>
                            <a href="{$url_checkout}" class="btn btn-primary btn-cancel" >{$category_relation.button_no}</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>



