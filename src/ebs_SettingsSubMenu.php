<?php
namespace Farn\EasyBackendStyle;

$plugin = $GLOBALS['ebsPlugin'];
$definitions = $GLOBALS['ebsColorMapping'];
$defaults = array_map(static fn($definition) => $definition[2], $definitions);
$plugin->ebs_root_variables_css();
$main = ['ebsPrimary', 'ebsSecondary', 'ebsTertiary'];
$renderColor = static function ($key, $definition, $isMain) use ($plugin) {
    $value = sanitize_hex_color($plugin->getColor($key)) ?: $definition[2];
    ?>
    <div class="<?php echo esc_attr('wrapper_' . $key); ?> ebs-color-field">
        <input type="text" name="<?php echo esc_attr($key); ?>" id="<?php echo esc_attr($key); ?>"
            value="<?php echo esc_attr($value); ?>" class="<?php echo $isMain ? 'ebs_mainColorPicker' : 'ebs_colorPicker'; ?>"
            data-default-color="<?php echo esc_attr($definition[2]); ?>" aria-describedby="<?php echo esc_attr('description_' . $key); ?>" />
        <div class="<?php echo $isMain ? 'main_label_group' : 'label_group'; ?>">
            <label class="main_color_label" for="<?php echo esc_attr($key); ?>"><?php echo esc_html($definition[0]); ?></label>
            <small class="color_description" id="<?php echo esc_attr('description_' . $key); ?>"><?php echo esc_html($definition[3]); ?></small>
            <span class="hex_code"><?php echo esc_html($value); ?></span>
        </div>
    </div>
    <?php
};
?>
<div class="wrap ebs-admin">
    <?php AdminSettings::header(); ?>
    <form method="post" id="colorPickForm">
        <?php wp_nonce_field('ebs_save_colors', 'ebs_nonce'); ?>
        <section class="ebs-panel" aria-labelledby="ebs-main-heading">
            <h2 id="ebs-main-heading"><?php esc_html_e('Main colors', 'easybackendstyle'); ?></h2>
            <p class="description"><?php esc_html_e('Select three colors in the main color selectors. All other colors are selected automatically.', 'easybackendstyle'); ?></p>
            <div class="ebs_main_colors">
                <?php foreach ($main as $key) $renderColor($key, $definitions[$key], true); ?>
            </div>
        </section>
        <section class="ebs-panel">
            <button type="button" class="ebs_advanced_settings_toggle" aria-expanded="false" aria-controls="ebs-advanced-colors"><?php esc_html_e('Advanced colors', 'easybackendstyle'); ?><span class="dashicons dashicons-arrow-down-alt2" aria-hidden="true"></span></button>
            <div class="ebs_advanced_settings" id="ebs-advanced-colors">
                <p class="description"><?php esc_html_e('Fine-tune individual colors. Changing a main color recalculates these values.', 'easybackendstyle'); ?></p>
                <div class="ebs_advanced_settings_column">
                    <?php foreach ($definitions as $key => $definition) if (!in_array($key, $main, true)) $renderColor($key, $definition, false); ?>
                </div>
            </div>
        </section>
        <div class="ebs-actions">
            <button type="submit" class="button button-primary" name="submit" id="submit"><?php esc_html_e('Save changes', 'easybackendstyle'); ?></button>
            <button type="submit" class="button button-secondary" name="resetDefaults" id="resetDefaults"><?php esc_html_e('Restore defaults', 'easybackendstyle'); ?></button>
        </div>
    </form>
</div>
