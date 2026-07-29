<?php
function smarty_modifier_nofilter($string)
{
    return html_entity_decode($string, ENT_QUOTES, 'UTF-8');
}
