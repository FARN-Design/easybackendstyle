<?php

require_once('ebs_DatabaseConnector.php');

//ToDo: Update --wp-admin-theme-color-darker-10 and --wp-admin-theme-color-darker-20
echo '<style>
    :root{
      --wp-admin-theme-color: '.getValueFromDB("highlight")[0][0].';
      --wp-admin-theme-color-darker-10: '.getValueFromDB("highlight")[0][0].';
      --wp-admin-theme-color-darker-20: '.getValueFromDB("highlight")[0][0].';
    }

    .wp-core-ui .button-link{
      color: '.getValueFromDB("links")[0][0].'
    }

    .wrap .page-title-action, .components-button.is-primary{
      color: '.getValueFromDB("menuText")[0][0].';
      background-color: '.getValueFromDB("buttons")[0][0].';
    }

    .components-button.is-primary:disabled, .components-button.is-primary:disabled:active:enabled, 
    .components-button.is-primary[aria-disabled=true], .components-button.is-primary[aria-disabled=true]:active:enabled,
    .components-button.is-primary[aria-disabled=true]:enabled,
    .edit-post-header-toolbar.edit-post-header-toolbar__left>.edit-post-header-toolbar__inserter-toggle.has-icon{
      color: '.getValueFromDB("menuText")[0][0].';
      background-color: '.getValueFromDB("buttons")[0][0].';
    }

    </style>';


    //Template CSS
    echo '<style>
    body {
      background: '.getValueFromDB("background")[0][0].';
    }

    /* Links */
    a {
      color: '.getValueFromDB("links")[0][0].';
    }

    a:hover, a:active, a:focus {
      color: #'.substr("000000".dechex(hexdec(substr(getValueFromDB("links")[0][0],1)) + 13061),-6).';
    }

    #post-body .misc-pub-post-status:before,
    #post-body #visibility:before,
    .curtime #timestamp:before,
    #post-body .misc-pub-revisions:before,
    span.wp-media-buttons-icon:before {
      color: currentColor;
    }

    /* Forms */
    input[type=checkbox]:checked::before {
      content: url("data:image/svg+xml;utf8,%3Csvg%20xmlns%3D%27http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%27%20viewBox%3D%270%200%2020%2020%27%3E%3Cpath%20d%3D%27M14.83%204.89l1.34.94-5.81%208.38H9.02L5.78%209.67l1.34-1.25%202.57%202.4z%27%20fill%3D%27%23ffaf00%27%2F%3E%3C%2Fsvg%3E");
    }

    input[type=radio]:checked::before {
      background: '.getValueFromDB("formInputs")[0][0].';
    }

    .wp-core-ui input[type="reset"]:hover,
    .wp-core-ui input[type="reset"]:active {
      color: #'.substr("000000".dechex(hexdec(substr(getValueFromDB("links")[0][0],1)) + 13061),-6).';
    }

    input[type="text"]:focus,
    input[type="password"]:focus,
    input[type="color"]:focus,
    input[type="date"]:focus,
    input[type="datetime"]:focus,
    input[type="datetime-local"]:focus,
    input[type="email"]:focus,
    input[type="month"]:focus,
    input[type="number"]:focus,
    input[type="search"]:focus,
    input[type="tel"]:focus,
    input[type="text"]:focus,
    input[type="time"]:focus,
    input[type="url"]:focus,
    input[type="week"]:focus,
    input[type="checkbox"]:focus,
    input[type="radio"]:focus,
    select:focus,
    textarea:focus {
      border-color: '.getValueFromDB("highlight")[0][0].';
      box-shadow: 0 0 0 1px '.getValueFromDB("highlight")[0][0].';
    }

    /* Core UI */
    .wp-core-ui .button,
    .wp-core-ui .button-secondary {
      color: '.getValueFromDB("buttons")[0][0].';
      border-color: '.getValueFromDB("buttons")[0][0].';
    }

    .wp-core-ui .button.hover,
    .wp-core-ui .button:hover,
    .wp-core-ui .button-secondary:hover,
    .wp-core-ui .button.focus,
    .wp-core-ui .button:focus,
    .wp-core-ui .button-secondary:focus {
      border-color: #;
      color: #'.substr("000000".dechex(hexdec(substr(getValueFromDB("buttons")[0][0],1)) - 6400),-6).';
    }

    .wp-core-ui .button.focus,
    .wp-core-ui .button:focus,
    .wp-core-ui .button-secondary:focus {
      border-color: '.getValueFromDB("buttons")[0][0].';
      color: #'.substr("000000".dechex(hexdec(substr(getValueFromDB("buttons")[0][0],1)) - 6400),-6).';
      box-shadow: 0 0 0 1px '.getValueFromDB("buttons")[0][0].';
    }

    .wp-core-ui .button:active {
      background: #'.substr("000000".dechex(hexdec(substr(getValueFromDB("buttons")[0][0],1)) - 6400),-6).';
      border-color: #'.substr("000000".dechex(hexdec(substr(getValueFromDB("buttons")[0][0],1)) - 6400),-6).';
    }

    .wp-core-ui .button.active,
    .wp-core-ui .button.active:focus,
    .wp-core-ui .button.active:hover {
      border-color: #'.substr("000000".dechex(hexdec(substr(getValueFromDB("buttons")[0][0],1)) - 6400),-6).';
      color: #'.substr("000000".dechex(hexdec(substr(getValueFromDB("buttons")[0][0],1)) - 6400),-6).';
      box-shadow: inset 0 2px 5px -3px #'.substr("000000".dechex(hexdec(substr(getValueFromDB("buttons")[0][0],1)) - 6400),-6).';
    }

    .wp-core-ui .button-primary {
      background: '.getValueFromDB("buttons")[0][0].';
      border-color: '.getValueFromDB("buttons")[0][0].';
      color: '.getValueFromDB("menuText")[0][0].';
    }

    .wp-core-ui .button-primary:hover, .wp-core-ui .button-primary:focus {
      background: '.getValueFromDB("highlight")[0][0].'; 
      border-color: '.getValueFromDB("highlight")[0][0].';
      color: '.getValueFromDB("menuText")[0][0].';
    }

    .wp-core-ui .button-primary:focus {
      box-shadow: 0 0 0 1px '.getValueFromDB("menuText")[0][0].', 0 0 0 3px '.getValueFromDB("buttons")[0][0].';
    }

    .wp-core-ui .button-primary:active {
      background: #'.substr("000000".dechex(hexdec(substr(getValueFromDB("buttons")[0][0],1)) - 6400),-6).';
      border-color: #'.substr("000000".dechex(hexdec(substr(getValueFromDB("buttons")[0][0],1)) - 6400),-6).';
      color: '.getValueFromDB("menuText")[0][0].';
    }

    .wp-core-ui .button-primary.active, .wp-core-ui .button-primary.active:focus, .wp-core-ui .button-primary.active:hover {
      background: '.getValueFromDB("buttons")[0][0].';
      color: '.getValueFromDB("menuText")[0][0].';
      border-color: #'.substr("000000".dechex(hexdec(substr(getValueFromDB("buttons")[0][0],1)) - 19457),-6).'; 
      box-shadow: inset 0 2px 5px -3px black;
    }

    .wp-core-ui .button-primary[disabled], .wp-core-ui .button-primary:disabled, .wp-core-ui .button-primary.button-primary-disabled, .wp-core-ui .button-primary.disabled {
      color: #'.substr("000000".dechex(hexdec(substr(getValueFromDB("menuText")[0][0],1)) - 3681848),-6).' !important;
      background: #'.substr("000000".dechex(hexdec(substr(getValueFromDB("buttons")[0][0],1)) - 10497),-6).' !important; 
      border-color: #'.substr("000000".dechex(hexdec(substr(getValueFromDB("buttons")[0][0],1)) - 10497),-6).' !important;
      text-shadow: none !important;
    }

    .wp-core-ui .button-group > .button.active {
      border-color: '.getValueFromDB("buttons")[0][0].';
    }

    .wp-core-ui .wp-ui-primary {
      color: '.getValueFromDB("menuText")[0][0].';
      background-color: '.getValueFromDB("baseMenu")[0][0].';
    }

    .wp-core-ui .wp-ui-text-primary {
      color: '.getValueFromDB("baseMenu")[0][0].';
    }

    .wp-core-ui .wp-ui-highlight {
      color: '.getValueFromDB("menuText")[0][0].';
      background-color: '.getValueFromDB("highlight")[0][0].';
    }

    .wp-core-ui .wp-ui-text-highlight {
      color: '.getValueFromDB("highlight")[0][0].';
    }

    .wp-core-ui .wp-ui-notification {
      color: '.getValueFromDB("menuText")[0][0].';
      background-color: '.getValueFromDB("notification")[0][0].';
    }

    .wp-core-ui .wp-ui-text-notification {
      color: '.getValueFromDB("notification")[0][0].';
    }

    .wp-core-ui .wp-ui-text-icon {
      color: #'.substr("000000".dechex(hexdec(substr(getValueFromDB("menuText")[0][0],1)) - 920588),-6).';
    }

    /* List tables */
    .wrap .add-new-h2:hover,
    .wrap .page-title-action:hover {
      color: '.getValueFromDB("menuText")[0][0].';
      background-color: '.getValueFromDB("baseMenu")[0][0].';
    }

    .view-switch a.current:before {
      color: '.getValueFromDB("baseMenu")[0][0].';
    }

    .view-switch a:hover:before {
      color: '.getValueFromDB("notification")[0][0].';
    }

    /* Admin Menu */
    #adminmenuback,
    #adminmenuwrap,
    #adminmenu {
      background: '.getValueFromDB("baseMenu")[0][0].';
    }

    #adminmenu a {
      color: '.getValueFromDB("menuText")[0][0].';
    }

    #adminmenu div.wp-menu-image:before {
      color: #'.substr("000000".dechex(hexdec(substr(getValueFromDB("menuText")[0][0],1)) - 920588),-6).';
    }

    #adminmenu a:hover,
    #adminmenu li.menu-top:hover,
    #adminmenu li.opensub > a.menu-top,
    #adminmenu li > a.menu-top:focus {
      color: '.getValueFromDB("menuText")[0][0].';
      background-color: '.getValueFromDB("highlight")[0][0].';
    }

    #adminmenu li.menu-top:hover div.wp-menu-image:before,
    #adminmenu li.opensub > a.menu-top div.wp-menu-image:before {
      color: '.getValueFromDB("menuText")[0][0].';
    }

    /* Active tabs use a bottom border color that matches the page background color. */
    .about-wrap .nav-tab-active,
    .nav-tab-active,
    .nav-tab-active:hover {
      background-color: '.getValueFromDB("background")[0][0].';
      border-bottom-color: '.getValueFromDB("background")[0][0].';
    }

    /* Admin Menu: submenu */
    #adminmenu .wp-submenu,
    #adminmenu .wp-has-current-submenu .wp-submenu,
    #adminmenu .wp-has-current-submenu.opensub .wp-submenu,
    .folded #adminmenu .wp-has-current-submenu .wp-submenu,
    #adminmenu a.wp-has-current-submenu:focus + .wp-submenu {
      background: '.getValueFromDB("subMenu")[0][0].'; 
    }

    #adminmenu li.wp-has-submenu.wp-not-current-submenu.opensub:hover:after {
      border-right-color: '.getValueFromDB("subMenu")[0][0].';
    }

    #adminmenu .wp-submenu .wp-submenu-head {
      color: '.getValueFromDB("menuText")[0][0].';
    }

    #adminmenu .wp-submenu a,
    #adminmenu .wp-has-current-submenu .wp-submenu a,
    .folded #adminmenu .wp-has-current-submenu .wp-submenu a,
    #adminmenu a.wp-has-current-submenu:focus + .wp-submenu a,
    #adminmenu .wp-has-current-submenu.opensub .wp-submenu a {
      color: '.getValueFromDB("menuText")[0][0].';
    }

    #adminmenu .wp-submenu a:focus, #adminmenu .wp-submenu a:hover,
    #adminmenu .wp-has-current-submenu .wp-submenu a:focus,
    #adminmenu .wp-has-current-submenu .wp-submenu a:hover,
    .folded #adminmenu .wp-has-current-submenu .wp-submenu a:focus,
    .folded #adminmenu .wp-has-current-submenu .wp-submenu a:hover,
    #adminmenu a.wp-has-current-submenu:focus + .wp-submenu a:focus,
    #adminmenu a.wp-has-current-submenu:focus + .wp-submenu a:hover,
    #adminmenu .wp-has-current-submenu.opensub .wp-submenu a:focus,
    #adminmenu .wp-has-current-submenu.opensub .wp-submenu a:hover {
      color: '.getValueFromDB("highlight")[0][0].';
    }

    /* Admin Menu: current */
    #adminmenu .wp-submenu li.current a,
    #adminmenu a.wp-has-current-submenu:focus + .wp-submenu li.current a,
    #adminmenu .wp-has-current-submenu.opensub .wp-submenu li.current a {
      color: '.getValueFromDB("menuText")[0][0].';
    }

    #adminmenu .wp-submenu li.current a:hover, #adminmenu .wp-submenu li.current a:focus,
    #adminmenu a.wp-has-current-submenu:focus + .wp-submenu li.current a:hover,
    #adminmenu a.wp-has-current-submenu:focus + .wp-submenu li.current a:focus,
    #adminmenu .wp-has-current-submenu.opensub .wp-submenu li.current a:hover,
    #adminmenu .wp-has-current-submenu.opensub .wp-submenu li.current a:focus {
      color: '.getValueFromDB("highlight")[0][0].';
    }

    ul#adminmenu a.wp-has-current-submenu:after,
    ul#adminmenu > li.current > a.current:after {
      border-right-color: '.getValueFromDB("background")[0][0].';
    }

    #adminmenu li.current a.menu-top,
    #adminmenu li.wp-has-current-submenu a.wp-has-current-submenu,
    #adminmenu li.wp-has-current-submenu .wp-submenu .wp-submenu-head,
    .folded #adminmenu li.current.menu-top {
      color: '.getValueFromDB("menuText")[0][0].';
      background: '.getValueFromDB("highlight")[0][0].';
    }

    #adminmenu li.wp-has-current-submenu div.wp-menu-image:before,
    #adminmenu a.current:hover div.wp-menu-image:before,
    #adminmenu li.wp-has-current-submenu a:focus div.wp-menu-image:before,
    #adminmenu li.wp-has-current-submenu.opensub div.wp-menu-image:before,
    #adminmenu li:hover div.wp-menu-image:before,
    #adminmenu li a:focus div.wp-menu-image:before,
    #adminmenu li.opensub div.wp-menu-image:before,
    .ie8 #adminmenu li.opensub div.wp-menu-image:before {
      color: '.getValueFromDB("menuText")[0][0].';
    }

    /* Admin Menu: bubble */
    #adminmenu .awaiting-mod,
    #adminmenu .update-plugins {
      color: '.getValueFromDB("menuText")[0][0].';
      background: '.getValueFromDB("notification")[0][0].';
    }

    #adminmenu li.current a .awaiting-mod,
    #adminmenu li a.wp-has-current-submenu .update-plugins,
    #adminmenu li:hover a .awaiting-mod,
    #adminmenu li.menu-top:hover > a .update-plugins {
      color: '.getValueFromDB("menuText")[0][0].';
      background: '.getValueFromDB("notification")[0][0].';
    }

    /* Admin Menu: collapse button */
    #collapse-button {
      color: #'.substr("000000".dechex(hexdec(substr(getValueFromDB("menuText")[0][0],1)) - 920588),-6).';
    }

    #collapse-button:hover,
    #collapse-button:focus {
      color: '.getValueFromDB("highlight")[0][0].';
    }

    /* Admin Bar */
    #wpadminbar {
      color: '.getValueFromDB("menuText")[0][0].';
      background: '.getValueFromDB("baseMenu")[0][0]
    .';
    }

    #wpadminbar .ab-item,
    #wpadminbar a.ab-item,
    #wpadminbar > #wp-toolbar span.ab-label,
    #wpadminbar > #wp-toolbar span.noticon {
      color: '.getValueFromDB("menuText")[0][0].';
    }

    #wpadminbar .ab-icon,
    #wpadminbar .ab-icon:before,
    #wpadminbar .ab-item:before,
    #wpadminbar .ab-item:after {
      color: #'.substr("000000".dechex(hexdec(substr(getValueFromDB("menuText")[0][0],1)) - 920588),-6).';
    }

    #wpadminbar:not(.mobile) .ab-top-menu > li:hover > .ab-item,
    #wpadminbar:not(.mobile) .ab-top-menu > li > .ab-item:focus,
    #wpadminbar.nojq .quicklinks .ab-top-menu > li > .ab-item:focus,
    #wpadminbar.nojs .ab-top-menu > li.menupop:hover > .ab-item,
    #wpadminbar .ab-top-menu > li.menupop.hover > .ab-item {
      color: '.getValueFromDB("highlight")[0][0].';
      background: '.getValueFromDB("subMenu")[0][0].';
    }

    #wpadminbar:not(.mobile) > #wp-toolbar li:hover span.ab-label,
    #wpadminbar:not(.mobile) > #wp-toolbar li.hover span.ab-label,
    #wpadminbar:not(.mobile) > #wp-toolbar a:focus span.ab-label {
      color: '.getValueFromDB("highlight")[0][0].';
    }

    #wpadminbar:not(.mobile) li:hover .ab-icon:before,
    #wpadminbar:not(.mobile) li:hover .ab-item:before,
    #wpadminbar:not(.mobile) li:hover .ab-item:after,
    #wpadminbar:not(.mobile) li:hover #adminbarsearch:before {
      color: '.getValueFromDB("menuText")[0][0].';
    }

    /* Admin Bar: submenu */
    #wpadminbar .menupop .ab-sub-wrapper {
      background: '.getValueFromDB("subMenu")[0][0].';
    }

    #wpadminbar .quicklinks .menupop ul.ab-sub-secondary,
    #wpadminbar .quicklinks .menupop ul.ab-sub-secondary .ab-submenu {
      background: '.getValueFromDB("subMenu")[0][0].';
    }

    #wpadminbar .ab-submenu .ab-item,
    #wpadminbar .quicklinks .menupop ul li a,
    #wpadminbar .quicklinks .menupop.hover ul li a,
    #wpadminbar.nojs .quicklinks .menupop:hover ul li a {
      color: '.getValueFromDB("menuText")[0][0].';
    }

    #wpadminbar .quicklinks li .blavatar,
    #wpadminbar .menupop .menupop > .ab-item:before {
      color: #'.substr("000000".dechex(hexdec(substr(getValueFromDB("menuText")[0][0],1)) - 920588),-6).';
    }

    #wpadminbar .quicklinks .menupop ul li a:hover,
    #wpadminbar .quicklinks .menupop ul li a:focus,
    #wpadminbar .quicklinks .menupop ul li a:hover strong,
    #wpadminbar .quicklinks .menupop ul li a:focus strong,
    #wpadminbar .quicklinks .ab-sub-wrapper .menupop.hover > a,
    #wpadminbar .quicklinks .menupop.hover ul li a:hover,
    #wpadminbar .quicklinks .menupop.hover ul li a:focus,
    #wpadminbar.nojs .quicklinks .menupop:hover ul li a:hover,
    #wpadminbar.nojs .quicklinks .menupop:hover ul li a:focus,
    #wpadminbar li:hover .ab-icon:before,
    #wpadminbar li:hover .ab-item:before,
    #wpadminbar li a:focus .ab-icon:before,
    #wpadminbar li .ab-item:focus:before,
    #wpadminbar li .ab-item:focus .ab-icon:before,
    #wpadminbar li.hover .ab-icon:before,
    #wpadminbar li.hover .ab-item:before,
    #wpadminbar li:hover #adminbarsearch:before,
    #wpadminbar li #adminbarsearch.adminbar-focused:before {
      color: '.getValueFromDB("highlight")[0][0].';
    }

    #wpadminbar .quicklinks li a:hover .blavatar,
    #wpadminbar .quicklinks li a:focus .blavatar,
    #wpadminbar .quicklinks .ab-sub-wrapper .menupop.hover > a .blavatar,
    #wpadminbar .menupop .menupop > .ab-item:hover:before,
    #wpadminbar.mobile .quicklinks .ab-icon:before,
    #wpadminbar.mobile .quicklinks .ab-item:before {
      color: '.getValueFromDB("highlight")[0][0].';
    }

    #wpadminbar.mobile .quicklinks .hover .ab-icon:before,
    #wpadminbar.mobile .quicklinks .hover .ab-item:before {
      color: #'.substr("000000".dechex(hexdec(substr(getValueFromDB("menuText")[0][0],1)) - 920588),-6).';
    }

    /* Admin Bar: search */
    #wpadminbar #adminbarsearch:before {
      color: #'.substr("000000".dechex(hexdec(substr(getValueFromDB("menuText")[0][0],1)) - 920588),-6).';
    }

    #wpadminbar > #wp-toolbar > #wp-admin-bar-top-secondary > #wp-admin-bar-search #adminbarsearch input.adminbar-input:focus {
      color: '.getValueFromDB("menuText")[0][0].';
      background: #'.substr("000000".dechex(2458366 - hexdec(substr(getValueFromDB("baseMenu")[0][0],1))),-6).';
    }

    /* Admin Bar: recovery mode */
    #wpadminbar #wp-admin-bar-recovery-mode {
      color: '.getValueFromDB("menuText")[0][0].';
      background-color: '.getValueFromDB("notification")[0][0].';
    }

    #wpadminbar #wp-admin-bar-recovery-mode .ab-item,
    #wpadminbar #wp-admin-bar-recovery-mode a.ab-item {
      color: '.getValueFromDB("menuText")[0][0].';
    }

    #wpadminbar .ab-top-menu > #wp-admin-bar-recovery-mode.hover > .ab-item,
    #wpadminbar.nojq .quicklinks .ab-top-menu > #wp-admin-bar-recovery-mode > .ab-item:focus,
    #wpadminbar:not(.mobile) .ab-top-menu > #wp-admin-bar-recovery-mode:hover > .ab-item,
    #wpadminbar:not(.mobile) .ab-top-menu > #wp-admin-bar-recovery-mode > .ab-item:focus {
      color: '.getValueFromDB("menuText")[0][0].';
      background-color: #'.substr("000000".dechex(hexdec(substr(getValueFromDB("notification")[0][0],1)) - 1638400),-6).';
    }

    /* Admin Bar: my account */
    #wpadminbar .quicklinks li#wp-admin-bar-my-account.with-avatar > a img {
      border-color: #'.substr("000000".dechex(2458366 - hexdec(substr(getValueFromDB("baseMenu")[0][0],1))),-6).';
      background-color: #'.substr("000000".dechex(2458366 - hexdec(substr(getValueFromDB("baseMenu")[0][0],1))),-6).';
    }

    #wpadminbar #wp-admin-bar-user-info .display-name {
      color: '.getValueFromDB("menuText")[0][0].';
    }

    #wpadminbar #wp-admin-bar-user-info a:hover .display-name {
      color: '.getValueFromDB("highlight")[0][0].';
    }

    #wpadminbar #wp-admin-bar-user-info .username {
      color: '.getValueFromDB("menuText")[0][0].';
    }

    /* Pointers */
    .wp-pointer .wp-pointer-content h3 {
      background-color: '.getValueFromDB("highlight")[0][0].';
      border-color: #'.substr("000000".dechex(hexdec(substr(getValueFromDB("highlight")[0][0],1)) - 327705),-6).'; 
    }

    .wp-pointer .wp-pointer-content h3:before {
      color: '.getValueFromDB("highlight")[0][0].';
    }

    .wp-pointer.wp-pointer-top .wp-pointer-arrow,
    .wp-pointer.wp-pointer-top .wp-pointer-arrow-inner,
    .wp-pointer.wp-pointer-undefined .wp-pointer-arrow,
    .wp-pointer.wp-pointer-undefined .wp-pointer-arrow-inner {
      border-bottom-color: '.getValueFromDB("highlight")[0][0].';
    }

    /* Media */
    .media-item .bar,
    .media-progress-bar div {
      background-color: '.getValueFromDB("highlight")[0][0].';
    }

    .details.attachment {
      box-shadow: inset 0 0 0 3px '.getValueFromDB("menuText")[0][0].', inset 0 0 0 7px '.getValueFromDB("highlight")[0][0].';
    }

    .attachment.details .check {
      background-color: '.getValueFromDB("highlight")[0][0].';
      box-shadow: 0 0 0 1px '.getValueFromDB("menuText")[0][0].', 0 0 0 2px '.getValueFromDB("highlight")[0][0].';
    }

    .media-selection .attachment.selection.details .thumbnail {
      box-shadow: 0 0 0 1px '.getValueFromDB("menuText")[0][0].', 0 0 0 3px '.getValueFromDB("highlight")[0][0].';
    }

    /* Themes */
    .theme-browser .theme.active .theme-name,
    .theme-browser .theme.add-new-theme a:hover:after,
    .theme-browser .theme.add-new-theme a:focus:after {
      background: '.getValueFromDB("highlight")[0][0].';
    }

    .theme-browser .theme.add-new-theme a:hover span:after,
    .theme-browser .theme.add-new-theme a:focus span:after {
      color: '.getValueFromDB("highlight")[0][0].';
    }

    .theme-section.current,
    .theme-filter.current {
      border-bottom-color: '.getValueFromDB("baseMenu")[0][0]
    .';
    }

    body.more-filters-opened .more-filters {
      color: '.getValueFromDB("menuText")[0][0].';
      background-color: '.getValueFromDB("baseMenu")[0][0]
    .';
    }

    body.more-filters-opened .more-filters:before {
      color: '.getValueFromDB("menuText")[0][0].';
    }

    body.more-filters-opened .more-filters:hover,
    body.more-filters-opened .more-filters:focus {
      background-color: '.getValueFromDB("highlight")[0][0].';
      color: '.getValueFromDB("menuText")[0][0].';
    }

    body.more-filters-opened .more-filters:hover:before,
    body.more-filters-opened .more-filters:focus:before {
      color: '.getValueFromDB("menuText")[0][0].';
    }

    /* Widgets */
    .widgets-chooser li.widgets-chooser-selected {
      background-color: '.getValueFromDB("highlight")[0][0].';
      color: '.getValueFromDB("menuText")[0][0].';
    }

    .widgets-chooser li.widgets-chooser-selected:before,
    .widgets-chooser li.widgets-chooser-selected:focus:before {
      color: '.getValueFromDB("menuText")[0][0].';
    }

    /* Responsive Component */
    div#wp-responsive-toggle a:before {
      color: #'.substr("000000".dechex(hexdec(substr(getValueFromDB("menuText")[0][0],1)) - 920588),-6).';
    }

    .wp-responsive-open div#wp-responsive-toggle a {
      border-color: transparent;
      background: '.getValueFromDB("highlight")[0][0].';
    }

    .wp-responsive-open #wpadminbar #wp-admin-bar-menu-toggle a {
      background: '.getValueFromDB("subMenu")[0][0].';
    }

    .wp-responsive-open #wpadminbar #wp-admin-bar-menu-toggle .ab-icon:before {
      color: #'.substr("000000".dechex(hexdec(substr(getValueFromDB("menuText")[0][0],1)) - 920588),-6).';
    }

    /* TinyMCE */
    .mce-container.mce-menu .mce-menu-item:hover,
    .mce-container.mce-menu .mce-menu-item.mce-selected,
    .mce-container.mce-menu .mce-menu-item:focus,
    .mce-container.mce-menu .mce-menu-item-normal.mce-active,
    .mce-container.mce-menu .mce-menu-item-preview.mce-active {
      background: '.getValueFromDB("highlight")[0][0].';
    }</style>';