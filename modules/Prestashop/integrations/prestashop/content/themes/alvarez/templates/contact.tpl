{extends file='layouts/layout-full-width.tpl'}

{block name='page_header'}
  {include file='_partials/page-header.tpl' title={l s='Contact Álvarez' d='Shop.Theme.Global'} }
{/block}

{block name='breadcrumb'}
{/block}

{block name='content'}

  <section id="content" class="page-content page-cms ">

    {assign var='contact_mail' value='web@a-alvarez.com'}
    {if $language.iso_code == 'pt'}
      {assign var='contact_mail' value='webportugal@a-alvarez.com'}
    {/if}

    <p class="contact-text">{l s='You can contact us by any of these means' d='Shop.Theme.Global'}</p>
    <ul class="contact_way">
      {if $language.iso_code == "es"}
      <li><i class="fa fa-phone"></i> {l s='Phone:' d='Shop.Theme.Global'} <a target="_blank" href="tel:+34981179100">981 17 91 00</a></li>
      <li><i class="fa fa-fax"></i> {l s='Fax:' d='Shop.Theme.Global'} <a>981 17 91 01</a></li>
      {/if}
      <li><i class="fa fa-envelope"></i> {l s='E-mail:' d='Shop.Theme.Global'} <a target="_blank" href="mailto:{$contact_mail}">{$contact_mail}</a></li>
    </ul>
    <p><b>{l s='* Telephone service hours: Monday to Friday from 9:00 a.m. to 8:00 p.m.; Saturdays from 10:00 a.m. to 2:00 p.m.' d='Shop.Theme.Global'}</b></p>

    {hook h="displayContactForm7" id="18"}

    <h3>{l s='In our stores' d='Shop.Theme.Global'}</h3>
    <div class="contact-stores">
      <div class="contact-store-item">
        <p>{l s='Poeta Joan Maragall (before Capitán Haya), 60. Madrid[br](to 100 m. of Hotel Meliá Castilla)[br]28020 [b]Madrid[/b]' sprintf=['[br]' => '<br>', '[b]' => '<strong>', '[/b]' => '</strong>'] d='Shop.Theme.Global'}<br>
          <a href="{url entity='cms' id='18'}">{l s='See store' d='Shop.Theme.Global'}</a></p>
      </div>

      <div class="contact-store-item">
        <p>{l s='C/ Diego de León, 56[br](corner St. General Pardiñas)[br]28006 [b]Madrid[/b]' sprintf=['[br]' => '<br>', '[b]' => '<strong>', '[/b]' => '</strong>'] d='Shop.Theme.Global'}<br>
          <a href="{url entity='cms' id='18'}">{l s='See store' d='Shop.Theme.Global'}</a></p>
      </div>

      <div class="contact-store-item">
        <p>{l s='Polígono de Pocomaco[br]Primera Avenida, 81. Parcela C-13[br]15190 [b]A Coruña[/b]' sprintf=['[br]' => '<br>', '[b]' => '<strong>', '[/b]' => '</strong>'] d='Shop.Theme.Global'}<br>
          <a href="{url entity='cms' id='18'}">{l s='See store' d='Shop.Theme.Global'}</a></p>
      </div>
    </div>
  </section>
{/block}
