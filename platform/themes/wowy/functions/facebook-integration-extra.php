<?php

app()->booted(function (): void {
    // En inoqualabs theme_option() es un helper de solo lectura.
    // Las opciones de tema se gestionan desde el panel de admin.
    // theme_option()
    //     ->setField([
    //         'id' => 'facebook_comment_enabled_in_product',
    //         ...
    //     ]);
});
