<?php
// Clases de color de los dominios y su etiqueta legible.
// Los estilos de las bolitas viven en style.css (.color-dot.*).

$colorOptions = [
    'pinkshows' => 'Pink',
    'pinkshowst3' => 'Pink T3',
    'redshows' => 'Red',
    'orangeshows' => 'Orange',
    'maturepinkshows' => 'Mature Pink',
    'matureorangeshows' => 'Mature Orange',
];

// Para brandless, los colores son especiales
$brandlessColors = [
    'fiirtingdashows' => 'Brandless Danish',
    'fiirtingnlshows' => 'Brandless Dutch',
    'fiirtingenshows' => 'Brandless English',
    'fiirtingfishows' => 'Brandless Finnish',
    'fiirtingfrshows' => 'Brandless French',
    'fiirtingdeshows' => 'Brandless German',
    'fiirtingitshows' => 'Brandless Italian',
    'fiirtingnoshows' => 'Brandless Norwegian',
    'fiirtingposhows' => 'Brandless Polish',
    'fiirtingesshows' => 'Brandless Spanish',
    'fiirtingseshows' => 'Brandless Swedish',
];

$allColors = array_merge($colorOptions, $brandlessColors);
