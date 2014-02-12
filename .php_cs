<?php 

$finder = Symfony\CS\Finder\DefaultFinder::create()
    ->exclude(array(
      'app/cache',
      'model/om',
      'model/map',
      'vendor',
      'web/images',
    ))
    ->notName('/.*\.(ico|gif|png|jpeg|jpg|bmp|zip|gz|tar|7z|tiff|log|phar|jar)/')
    ->in(array(
        __DIR__.'/src',
    ))
;

return Symfony\CS\Config\Config::create()
    ->fixers(array(
        'indentation',
        'linefeed',
        'unused_use',
        'trailing_spaces',
        'php_closing_tag',
        'short_tag',
        'return',
        'visibility',
        'braces',
        'phpdoc_params',
        'eof_ending',
        'extra_empty_lines',
        'include',
        'psr0',
        'controls_spaces',
        'elseif',
    ))
    ->finder($finder)
;
