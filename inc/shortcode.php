<?php
// T&Cs shortcode
function sb_comp_terms() {
    // Get the T&Cs from your options page settings
    $terms = get_option('sb_comp_terms_option_option') ?? '';
    return $terms;
}
add_shortcode('sb-comp-terms', 'sb_comp_terms');